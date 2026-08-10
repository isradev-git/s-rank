<?php

namespace App\System;

/**
 * La curva de nivel y los rangos. Sin estado y sin base de datos.
 *
 *   XP para pasar del nivel N al N+1 = 100 + 40(N-1)
 *   XP acumulado para alcanzar el nivel N = 100(N-1) + 20(N-1)(N-2) = 20N² + 40N - 60
 *
 * Lineal a propósito: con curva exponencial, a partir del nivel 30 haría falta un mes
 * por nivel y la app se abandona.
 */
final class Progression
{
    /** Umbral de nivel de cada rango, de mayor a menor. */
    private const RANKS = [55 => 'S', 45 => 'A', 35 => 'B', 25 => 'C', 15 => 'D', 1 => 'E'];

    public static function xpForNextLevel(int $level): int
    {
        return 100 + 40 * (max(1, $level) - 1);
    }

    public static function xpToReachLevel(int $level): int
    {
        $n = max(1, $level);

        return 20 * $n * $n + 40 * $n - 60;
    }

    public static function levelForXp(int $xp): int
    {
        if ($xp <= 0) {
            return 1;
        }

        // Inversa de la cuadrática: N = (sqrt(6400 + 80·xp) - 40) / 40
        $level = max(1, (int) floor((sqrt(6400 + 80 * $xp) - 40) / 40));

        // ponytail: corrección de ±1 por si la raíz flotante se queda al borde.
        // Cuesta dos comparaciones y elimina toda una clase de errores en las fronteras.
        while (self::xpToReachLevel($level + 1) <= $xp) {
            $level++;
        }
        while ($level > 1 && self::xpToReachLevel($level) > $xp) {
            $level--;
        }

        return $level;
    }

    public static function rankForLevel(int $level): string
    {
        foreach (self::RANKS as $minLevel => $rank) {
            if ($level >= $minLevel) {
                return $rank;
            }
        }

        return 'E';
    }

    /**
     * XP conseguido dentro del nivel actual y XP que cuesta el nivel entero.
     * Es lo que dibuja la barra de la cabecera.
     *
     * @return array{into_level:int, for_next:int}
     */
    public static function levelBar(int $xpTotal): array
    {
        $level = self::levelForXp($xpTotal);

        return [
            'into_level' => $xpTotal - self::xpToReachLevel($level),
            'for_next'   => self::xpForNextLevel($level),
        ];
    }
}
