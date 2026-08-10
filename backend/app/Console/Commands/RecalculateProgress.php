<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserAchievement;
use App\Models\UserProgress;
use App\Models\Workout;
use App\Models\XpEvent;
use App\System\AchievementService;
use App\System\Progression;
use App\System\Stats;
use App\System\XpLedger;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconstruye el progreso desde los datos que ya existen, reproduciendo el libro
 * mayor día a día. Idempotente: borra lo que él mismo escribió y lo vuelve a hacer.
 *
 * Lo que NO reconstruye, y por qué:
 *   - Las misiones diarias, porque nunca existieron: no hay de dónde sacarlas.
 *   - Vitalidad, que se alimenta de misiones de hábito cumplidas.
 *   - Fuerza queda casi a cero: el almacenamiento por serie se añadió muy tarde a
 *     FitLoop y los entrenos antiguos no guardaron ni peso ni repeticiones.
 */
class RecalculateProgress extends Command
{
    protected $signature = 'srank:recalculate {--user= : Solo este usuario (uuid)}';

    protected $description = 'Reconstruye XP, nivel, estadísticas y logros desde el historial';

    public function __construct(private XpLedger $ledger, private AchievementService $achievements)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $users = User::query()
            ->when($this->option('user'), fn ($q) => $q->where('id', $this->option('user')))
            ->get();

        foreach ($users as $user) {
            $this->recalculateFor($user);
        }

        return self::SUCCESS;
    }

    private function recalculateFor(User $user): void
    {
        DB::transaction(function () use ($user) {
            XpEvent::where('user_id', $user->id)->delete();
            UserAchievement::where('user_id', $user->id)->delete();

            $progress = UserProgress::firstOrCreate(['user_id' => $user->id]);
            $progress->fill([
                'level' => 1, 'xp_total' => 0,
                'strength_acc' => 0, 'endurance_acc' => 0,
                'consistency_acc' => 0, 'vitality_acc' => 0,
                'current_streak' => 0, 'longest_streak' => 0, 'last_active_date' => null,
            ])->save();

            $workouts = Workout::where('user_id', $user->id)
                ->where('mode', '!=', 'pasos')
                ->with('sets')
                ->orderBy('date')
                ->get();

            $byDay = $workouts->groupBy(fn (Workout $w) => CarbonImmutable::parse($w->date)->toDateString());

            $bestByExercise = [];      // récords, recalculados en orden cronológico
            $withoutKg = 0;
            $cap = (int) config('srank.xp.workouts_per_day_cap');

            // Se recorren los días activos —cualquier registro, no solo entrenos—, que es
            // la misma definición que usa el Sistema en vivo. Si aquí se recorriesen solo
            // los entrenos, quien lleva la app a base de agua y comidas saldría del
            // recálculo con la racha a cero.
            foreach ($this->achievements->activeDates($user) as $day) {
                $date = CarbonImmutable::parse($day)->startOfDay();
                $awardedToday = 0;

                foreach ($byDay->get($day, []) as $workout) {
                    $volume = (float) $workout->sets->sum(
                        fn ($s) => (float) ($s->weight_kg ?? 0) * (int) ($s->reps ?? 0) * max(1, (int) ($s->sets ?? 1))
                    );

                    if ($volume <= 0) {
                        $withoutKg++;
                    }

                    // Récords: se detectan siempre, aunque el entreno ya no puntúe
                    $records = 0;

                    foreach ($workout->sets->groupBy('name') as $name => $sets) {
                        $best = (float) $sets->max('weight_kg');

                        if ($best > 0 && $best > ($bestByExercise[$name] ?? 0)) {
                            $bestByExercise[$name] = $best;
                            $records++;
                        }
                    }

                    if ($awardedToday >= $cap) {
                        continue;
                    }

                    $awardedToday++;

                    $this->ledger->award($user, 'workout', $workout->id, $this->workoutXp($workout), $date);

                    for ($i = 0; $i < $records; $i++) {
                        $this->ledger->award($user, 'record', $workout->id, config('srank.xp.record'), $date);
                    }

                    $progress->refresh();
                    $progress->strength_acc += $volume;
                    $progress->endurance_acc += $workout->duration_minutes;
                    $progress->save();
                }

                // La racha se paga al final del día, igual que en vivo: el bonus es lo
                // primero que se recorta si ese día ya se ha llegado al tope de 300.
                $this->touchStreak($user, $progress, $date);
            }

            $progress->refresh();
            $progress->level = Progression::levelForXp($progress->xp_total);
            $progress->save();

            $this->achievements->evaluate($user);

            $stats = Stats::all($progress);

            $this->info("Usuario {$user->email}");
            $this->line("  XP {$progress->xp_total} · nivel {$progress->level} · rango "
                .Progression::rankForLevel($progress->level));
            $this->line("  Fuerza {$stats['strength']} · Resistencia {$stats['endurance']}"
                ." · Constancia {$stats['consistency']} · Vitalidad {$stats['vitality']}");

            if ($withoutKg > 0) {
                $this->warn("  {$withoutKg} de {$workouts->count()} entrenos sin detalle de series: "
                    .'no aportan kilos, así que Fuerza arranca baja. No es un fallo, '
                    .'es que esos datos nunca se guardaron.');
            }
        });
    }

    private function touchStreak(User $user, UserProgress $progress, CarbonImmutable $date): void
    {
        $today = $date->toDateString();
        $last = $progress->last_active_date?->toDateString();

        if ($last === $today) {
            return;
        }

        $progress->current_streak = ($last !== null && CarbonImmutable::parse($last)->addDay()->toDateString() === $today)
            ? $progress->current_streak + 1
            : 1;

        $progress->longest_streak = max($progress->longest_streak, $progress->current_streak);
        $progress->last_active_date = $today;
        $progress->consistency_acc += 1;
        $progress->save();

        $bonus = min(
            config('srank.xp.streak_per_day') * $progress->current_streak,
            config('srank.xp.streak_cap')
        );

        $this->ledger->award($user, 'streak', null, (int) $bonus, $date);
        $progress->refresh();
    }

    private function workoutXp(Workout $workout): int
    {
        $xp = config('srank.xp');
        $minutes = (int) $workout->duration_minutes;

        if ($minutes < $xp['workout_min_minutes']) {
            return 0;
        }

        return $xp['workout_base'] + min(
            intdiv($minutes - $xp['workout_min_minutes'], $xp['workout_bonus_step']),
            $xp['workout_bonus_cap']
        );
    }
}
