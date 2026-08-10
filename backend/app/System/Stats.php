<?php

namespace App\System;

use App\Models\UserProgress;

/**
 * Las cuatro estadísticas suben solas: valor = floor(sqrt(acumulador / K)).
 * La raíz hace que suba rápido al principio y cueste más después, sin llegar a
 * estancarse. Las K viven en config/srank.php.
 */
final class Stats
{
    public static function value(float $accumulator, float $k): int
    {
        if ($accumulator <= 0 || $k <= 0) {
            return 0;
        }

        return (int) floor(sqrt($accumulator / $k));
    }

    /**
     * @return array{strength:int, endurance:int, consistency:int, vitality:int}
     */
    public static function all(UserProgress $progress): array
    {
        $k = config('srank.stats');

        return [
            'strength'    => self::value($progress->strength_acc, $k['strength']),
            'endurance'   => self::value($progress->endurance_acc, $k['endurance']),
            'consistency' => self::value($progress->consistency_acc, $k['consistency']),
            'vitality'    => self::value($progress->vitality_acc, $k['vitality']),
        ];
    }
}
