<?php

namespace App\System;

use App\Models\DailyQuest;
use App\Models\MealLog;
use App\Models\SupplementLog;
use App\Models\User;
use App\Models\WaterLog;
use App\Models\WeightLog;
use App\Models\Workout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Las misiones del día salen de los objetivos reales del usuario, nunca inventadas:
 * su meta semanal, su objetivo de hidratación, su objetivo de proteína. Máximo cuatro
 * obligatorias y una opcional; una lista larga desmotiva más de lo que empuja.
 *
 * No hay castigo por fallar: fallar rompe la racha, y eso ya duele bastante.
 */
class QuestService
{
    public const LABELS = [
        'train'       => 'Entrenar',
        'water'       => 'Beber :litros litros de agua',
        'protein'     => 'Llegar a :gramos g de proteína',
        'weight'      => 'Apuntar el peso',
        'meals_3'     => 'Registrar las 3 comidas',
        'supplements' => 'Tomar los suplementos',
        'pushups_50'  => '50 flexiones',
        'steps_8000'  => '8.000 pasos',
    ];

    private const ROTATING = ['weight', 'meals_3', 'supplements'];
    private const OPTIONAL = ['pushups_50', 'steps_8000'];

    /** Misiones cuyo cumplimiento alimenta la Vitalidad. */
    public const HABIT_KEYS = ['water', 'protein', 'weight', 'meals_3', 'supplements'];

    public function __construct(private XpLedger $ledger) {}

    /**
     * Crea las misiones del día si aún no existen. Idempotente: la primera petición
     * de cada día las crea, las siguientes no hacen nada. Por eso no hace falta cron,
     * que en un hosting compartido es poco fiable.
     */
    public function generate(User $user, CarbonImmutable $date): void
    {
        if ($this->questsOf($user, $date)->exists()) {
            return;
        }

        $rewards = config('srank.quests');
        $rows = [];

        // Entrenar: solo mientras no haya cumplido la cuota de la semana en curso.
        // FitLoop nunca ha guardado qué días tocan, así que la misión empuja a la cuota.
        $weeklyGoal = max(1, (int) $user->weekly_goal);

        if ($this->workoutsThisWeek($user, $date) < $weeklyGoal) {
            $rows[] = ['quest_key' => 'train', 'target' => $weeklyGoal, 'xp_reward' => $rewards['train']];
        }

        // Agua: siempre.
        $rows[] = [
            'quest_key' => 'water',
            'target'    => (int) ($user->water_goal_ml ?? 2000),
            'xp_reward' => $rewards['water'],
        ];

        // Proteína: solo si tiene objetivo nutricional.
        $goal = DB::table('nutrition_goals')->where('user_id', $user->id)->first();

        if ($goal) {
            $rows[] = [
                'quest_key' => 'protein',
                'target'    => (float) $goal->target_protein,
                'xp_reward' => $rewards['protein'],
            ];
        }

        // Rotativa: una de las tres, elegida de forma determinista a partir de
        // (usuario, fecha), para que el resultado sea estable si se repite la petición.
        $rotating = self::ROTATING[$this->pick($user, $date, count(self::ROTATING))];
        $rows[] = [
            'quest_key' => $rotating,
            'target'    => $rotating === 'meals_3' ? 3 : 1,
            'xp_reward' => $rewards[$rotating],
        ];

        // Opcional: bajo el epígrafe «si te sobra tiempo».
        $optional = self::OPTIONAL[$this->pick($user, $date, count(self::OPTIONAL))];
        $rows[] = [
            'quest_key'   => $optional,
            'target'      => 1,
            'xp_reward'   => $rewards['optional'],
            'is_optional' => true,
        ];

        foreach ($rows as $row) {
            DailyQuest::create($row + [
                'user_id'     => $user->id,
                'date'        => $date->toDateString(),
                'progress'    => 0,
                'is_optional' => false,
            ]);
        }
    }

    /**
     * Recalcula el progreso de las misiones del día, completa las que llegan al
     * objetivo y concede su XP. Es el único sitio donde una misión se da por cumplida:
     * lo llaman tanto GET /api/system/today como el listener tras cada registro.
     *
     * @return array{completed: string[], xp: int}
     */
    public function sync(User $user, CarbonImmutable $date): array
    {
        $quests = $this->questsOf($user, $date)->get();

        if ($quests->isEmpty()) {
            return ['completed' => [], 'xp' => 0];
        }

        $measured = $this->measure($user, $date);
        $completed = [];
        $xp = 0;

        foreach ($quests as $quest) {
            if (array_key_exists($quest->quest_key, $measured)) {
                $quest->progress = $measured[$quest->quest_key];
            }

            if (! $quest->isCompleted() && $quest->target > 0 && $quest->progress >= $quest->target) {
                $quest->completed_at = now();
                $xp += $this->ledger->award($user, 'quest', $quest->quest_key, $quest->xp_reward, $date);
                $completed[] = $quest->quest_key;
            }

            $quest->save();
        }

        $xp += $this->awardBonusIfAllDone($user, $date, $quests->fresh());

        return ['completed' => $completed, 'xp' => $xp];
    }

    /**
     * Marca a mano una misión opcional. Las opcionales («50 flexiones», «8.000 pasos»)
     * no se pueden medir solas, así que las confirma el usuario pulsando.
     */
    public function completeOptional(User $user, string $questKey, CarbonImmutable $date): bool
    {
        $quest = $this->questsOf($user, $date)
            ->where('quest_key', $questKey)
            ->where('is_optional', true)
            ->whereNull('completed_at')
            ->first();

        if (! $quest) {
            return false;
        }

        $quest->update(['progress' => $quest->target, 'completed_at' => now()]);
        $this->ledger->award($user, 'quest', $quest->quest_key, $quest->xp_reward, $date);

        return true;
    }

    /**
     * Las misiones del día tal y como las pinta la app.
     */
    public function forDate(User $user, CarbonImmutable $date): array
    {
        return $this->questsOf($user, $date)
            ->orderBy('is_optional')
            ->orderBy('id')
            ->get()
            ->map(fn (DailyQuest $q) => [
                'key'         => $q->quest_key,
                'label'       => $this->label($q),
                'target'      => (float) $q->target,
                'progress'    => (float) $q->progress,
                'xp_reward'   => $q->xp_reward,
                'is_optional' => $q->is_optional,
                'completed'   => $q->isCompleted(),
            ])
            ->all();
    }

    private function questsOf(User $user, CarbonImmutable $date)
    {
        return DailyQuest::where('user_id', $user->id)
            ->whereDate('date', $date->toDateString());
    }

    private function label(DailyQuest $quest): string
    {
        $template = self::LABELS[$quest->quest_key] ?? $quest->quest_key;

        return strtr($template, [
            ':litros' => rtrim(rtrim(number_format($quest->target / 1000, 1, ',', ''), '0'), ','),
            ':gramos' => (int) $quest->target,
        ]);
    }

    /**
     * Progreso real de cada tipo de misión medible, leído de los datos del día.
     *
     * @return array<string, float>
     */
    private function measure(User $user, CarbonImmutable $date): array
    {
        $day = $date->toDateString();

        return [
            'train'   => $this->workoutsThisWeek($user, $date),
            'water'   => (float) WaterLog::where('user_id', $user->id)->whereDate('date', $day)->sum('amount_ml'),
            'protein' => (float) MealLog::where('user_id', $user->id)->whereDate('date', $day)->sum('protein'),
            'weight'  => WeightLog::where('user_id', $user->id)->whereDate('date', $day)->exists() ? 1 : 0,
            'meals_3' => (float) MealLog::where('user_id', $user->id)->whereDate('date', $day)
                            ->distinct()->count('meal_type'),
            // Los cuatro suplementos del día marcados
            'supplements' => SupplementLog::where('user_id', $user->id)->whereDate('date', $day)
                            ->where('taken', true)->count() >= 4 ? 1 : 0,
        ];
    }

    private function workoutsThisWeek(User $user, CarbonImmutable $date): int
    {
        return Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')
            ->whereBetween('date', [
                $date->startOfWeek()->startOfDay()->toDateTimeString(),
                $date->endOfWeek()->endOfDay()->toDateTimeString(),
            ])
            ->count();
    }

    /**
     * Bonus por completar todas las obligatorias del día. Una sola vez: el libro
     * mayor es el que lleva la cuenta.
     */
    private function awardBonusIfAllDone(User $user, CarbonImmutable $date, $quests): int
    {
        $mandatory = $quests->where('is_optional', false);

        if ($mandatory->isEmpty() || $mandatory->contains(fn ($q) => ! $q->isCompleted())) {
            return 0;
        }

        if ($this->ledger->hasSource($user, 'quest_bonus', $date)) {
            return 0;
        }

        return $this->ledger->award($user, 'quest_bonus', null, config('srank.xp.all_quests_bonus'), $date);
    }

    /**
     * Elección determinista a partir de (usuario, fecha).
     */
    private function pick(User $user, CarbonImmutable $date, int $options): int
    {
        return crc32($user->id.$date->toDateString()) % $options;
    }
}
