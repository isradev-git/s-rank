<?php

namespace App\System;

use App\Models\User;
use App\Models\UserProgress;
use App\Models\Workout;
use Carbon\CarbonImmutable;

/**
 * El Sistema. Recibe lo que ha pasado en un módulo y devuelve lo que ha ganado el
 * usuario. Es el único que conoce a la vez el libro mayor, las misiones y los logros;
 * los módulos no conocen a ninguno.
 */
class SystemService
{
    public function __construct(
        private XpLedger $ledger,
        private QuestService $quests,
        private AchievementService $achievements,
    ) {}

    public function afterWorkout(Workout $workout, array $newRecords = []): array
    {
        $user = $workout->user()->firstOrFail();
        $date = CarbonImmutable::parse($workout->date);
        $before = $this->snapshotOf($this->ledger->progressFor($user));

        // Los pasos son actividad diaria, no entrenamiento: no puntúan.
        if ($workout->mode !== 'pasos') {
            $cap = (int) config('srank.xp.workouts_per_day_cap');

            if ($this->ledger->countSource($user, 'workout', $date) < $cap) {
                $this->ledger->award($user, 'workout', $workout->id, $this->workoutXp($workout), $date);

                foreach ($newRecords as $record) {
                    $this->ledger->award($user, 'record', $workout->id, config('srank.xp.record'), $date);
                }

                $progress = $this->ledger->progressFor($user);
                $progress->strength_acc += $this->volumeOf($workout);
                $progress->endurance_acc += $workout->duration_minutes;
                $progress->save();
            }
        }

        return $this->close($user, $date, $before, $this->formatRecords($newRecords));
    }

    public function afterHabit(User $user, CarbonImmutable $date): array
    {
        $before = $this->snapshotOf($this->ledger->progressFor($user));

        return $this->close($user, $date, $before, []);
    }

    /**
     * Estado actual sin conceder nada. Lo usan los GET.
     */
    public function snapshot(User $user): array
    {
        return $this->snapshotOf($this->ledger->progressFor($user));
    }

    /**
     * Cierra el ciclo: racha, misiones, logros y comparación con el estado anterior.
     */
    private function close(User $user, CarbonImmutable $date, array $before, array $records): array
    {
        $this->touchStreak($user, $date);

        $quests = $this->quests->sync($user, $date);

        // Constancia: un punto por misión cumplida. Vitalidad: solo las de hábito.
        $progress = $this->ledger->progressFor($user);
        $progress->consistency_acc += count($quests['completed']);
        $progress->vitality_acc += count(array_intersect($quests['completed'], QuestService::HABIT_KEYS));
        $progress->save();

        $unlocked = $this->achievements->evaluate($user);
        $after = $this->snapshotOf($this->ledger->progressFor($user));

        return [
            'xp_gained'             => $after['xp_total'] - $before['xp_total'],
            'level_up'              => $after['level'] !== $before['level']
                                        ? ['from' => $before['level'], 'to' => $after['level']]
                                        : null,
            'rank_up'               => $after['rank'] !== $before['rank']
                                        ? ['from' => $before['rank'], 'to' => $after['rank']]
                                        : null,
            'achievements_unlocked' => $unlocked,
            'records'               => $records,
            'quests_completed'      => $quests['completed'],
            'progress'              => $after,
        ];
    }

    /**
     * Marca el día como activo y actualiza la racha. Un día activo suma un punto de
     * Constancia; volver tras un hueco reinicia la racha a uno.
     */
    private function touchStreak(User $user, CarbonImmutable $date): void
    {
        $progress = $this->ledger->progressFor($user);
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

        // Bonus de racha: +2 por día consecutivo, hasta 30. Una vez al día.
        $bonus = min(
            config('srank.xp.streak_per_day') * $progress->current_streak,
            config('srank.xp.streak_cap')
        );

        $this->ledger->award($user, 'streak', null, (int) $bonus, $date);
    }

    private function workoutXp(Workout $workout): int
    {
        $xp = config('srank.xp');
        $minutes = (int) $workout->duration_minutes;

        if ($minutes < $xp['workout_min_minutes']) {
            return 0;
        }

        $bonus = intdiv($minutes - $xp['workout_min_minutes'], $xp['workout_bonus_step']);

        return $xp['workout_base'] + min($bonus, $xp['workout_bonus_cap']);
    }

    private function volumeOf(Workout $workout): float
    {
        return (float) $workout->sets()->get()->sum(
            fn ($set) => (float) ($set->weight_kg ?? 0) * (int) ($set->reps ?? 0) * max(1, (int) ($set->sets ?? 1))
        );
    }

    private function formatRecords(array $newRecords): array
    {
        return array_map(fn (array $r) => [
            'exercise' => $r['name'],
            'kind'     => 'weight',
            'value'    => $r['weight_kg'],
            'previous' => $r['previous_pr'] ?? null,
        ], $newRecords);
    }

    private function snapshotOf(UserProgress $progress): array
    {
        $level = Progression::levelForXp($progress->xp_total);
        $bar = Progression::levelBar($progress->xp_total);

        return [
            'level'          => $level,
            'rank'           => Progression::rankForLevel($level),
            'xp_total'       => $progress->xp_total,
            'xp_into_level'  => $bar['into_level'],
            'xp_for_next'    => $bar['for_next'],
            'current_streak' => $progress->current_streak,
            'longest_streak' => $progress->longest_streak,
            'stats'          => Stats::all($progress),
        ];
    }
}
