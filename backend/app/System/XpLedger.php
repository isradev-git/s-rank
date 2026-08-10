<?php

namespace App\System;

use App\Models\User;
use App\Models\UserProgress;
use App\Models\XpEvent;
use Carbon\CarbonImmutable;

/**
 * Todo el XP concedido pasa por aquí. Nadie escribe en `xp_events` ni toca
 * `xp_total` por su cuenta: es lo que hace que los topes diarios se cumplan
 * siempre y que el progreso se pueda recalcular entero desde el libro mayor.
 */
class XpLedger
{
    /**
     * Concede XP aplicando el tope diario. Devuelve lo realmente concedido,
     * que puede ser menos de lo pedido, o cero.
     */
    public function award(User $user, string $source, ?string $sourceId, int $amount, CarbonImmutable $date): int
    {
        if ($amount <= 0) {
            return 0;
        }

        $room = max(0, (int) config('srank.xp.daily_cap') - $this->spentOn($user, $date));
        $granted = min($amount, $room);

        if ($granted <= 0) {
            return 0;
        }

        XpEvent::create([
            'user_id'   => $user->id,
            'date'      => $date->toDateString(),
            'source'    => $source,
            'source_id' => $sourceId,
            'amount'    => $granted,
        ]);

        $progress = $this->progressFor($user);
        $progress->xp_total += $granted;
        $progress->level = Progression::levelForXp($progress->xp_total);
        $progress->save();

        return $granted;
    }

    /**
     * ponytail: whereDate y no where. Eloquent guarda las fechas con hora incluida,
     * así que en SQLite —el motor de los tests— una comparación literal no casa nunca.
     * Con estas tablas, de miles de filas por usuario, el coste es irrelevante.
     */
    public function spentOn(User $user, CarbonImmutable $date): int
    {
        return (int) XpEvent::where('user_id', $user->id)
            ->whereDate('date', $date->toDateString())
            ->sum('amount');
    }

    public function countSource(User $user, string $source, CarbonImmutable $date): int
    {
        return XpEvent::where('user_id', $user->id)
            ->whereDate('date', $date->toDateString())
            ->where('source', $source)
            ->count();
    }

    public function hasSource(User $user, string $source, CarbonImmutable $date): bool
    {
        return $this->countSource($user, $source, $date) > 0;
    }

    public function progressFor(User $user): UserProgress
    {
        return UserProgress::firstOrCreate(['user_id' => $user->id]);
    }
}
