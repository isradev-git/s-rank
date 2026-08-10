<?php

/**
 * Constantes del Sistema. Viven aquí y no en el código para poder reajustar el
 * balanceo sin publicar una versión nueva de la app en Play Store.
 */
return [
    'timezone' => 'Europe/Madrid',

    'xp' => [
        'workout_base'          => 50,   // entreno de 15 minutos o más
        'workout_min_minutes'   => 15,
        'workout_bonus_step'    => 5,    // +1 XP por cada 5 minutos por encima del mínimo
        'workout_bonus_cap'     => 30,
        'record'                => 30,
        'all_quests_bonus'      => 40,
        'streak_per_day'        => 2,
        'streak_cap'            => 30,
        'daily_cap'             => 300,  // tope total diario, todas las fuentes
        'workouts_per_day_cap'  => 2,    // el tercer entreno del día ya no puntúa
    ],

    // valor de la estadística = floor(sqrt(acumulador / k))
    'stats' => [
        'strength'    => 1500,  // kilogramos
        'endurance'   => 25,    // minutos
        'consistency' => 1.5,   // días activos + misiones cumplidas
        'vitality'    => 2.5,   // objetivos de hábito cumplidos
    ],

    'quests' => [
        'train'       => 20,
        'water'       => 20,
        'protein'     => 30,
        'weight'      => 10,
        'meals_3'     => 20,
        'supplements' => 15,
        'optional'    => 15,
    ],

    // Módulos activos que la app muestra en la ficha de perfil. La fase 2 añade aquí.
    'modules' => ['entrenamiento', 'nutrición'],
];
