<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo NutritionGoal — Objetivo nutricional del usuario
 *
 * Cada usuario tiene UN solo objetivo (relación 1:1 con User).
 * Si el usuario no tiene uno creado aún, se puede calcular
 * automáticamente con la fórmula de Harris-Benedict usando
 * los datos del perfil (peso, altura, edad).
 *
 * goal_type determina si el usuario quiere:
 * - 'lose_weight': déficit calórico (bajar de peso)
 * - 'maintain': mantenimiento
 * - 'gain_muscle': superávit calórico (ganar músculo)
 */
class NutritionGoal extends Model
{
    protected $fillable = [
        'user_id',
        'daily_calories',
        'target_protein',
        'target_carbs',
        'target_fat',
        'target_fiber',
        'goal_type',
        'meals_per_day',
    ];

    protected $casts = [
        'daily_calories' => 'integer',
        'target_protein' => 'integer',
        'target_carbs'   => 'integer',
        'target_fat'     => 'integer',
        'target_fiber'   => 'integer',
        'meals_per_day'  => 'integer',
    ];

    /**
     * Relación con el usuario dueño del objetivo.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calcula las calorías recomendadas basándose en el perfil del usuario.
     * Usa la fórmula de Mifflin-St Jeor, que distingue por sexo:
     *   - Hombre: (10 × peso_kg) + (6.25 × altura_cm) - (5 × edad) + 5
     *   - Mujer:  (10 × peso_kg) + (6.25 × altura_cm) - (5 × edad) - 161
     *
     * @param User   $user     El usuario con sus datos de perfil
     * @param string $activity Nivel de actividad física
     * @return int Calorías diarias recomendadas
     */
    public static function calculateRecommended(User $user, string $activity = 'moderate'): int
    {
        // Si el usuario no tiene datos completos, retornamos un valor por defecto
        if (!$user->weight || !$user->height || !$user->age) {
            return 2000;
        }

        // Constante de género: +5 para hombre, -161 para mujer (Mifflin-St Jeor)
        $genderConstant = ($user->gender === 'female') ? -161 : 5;

        // BMR = (10 × peso_kg) + (6.25 × altura_cm) - (5 × edad) ± constante_género
        $bmr = (10 * $user->weight) + (6.25 * $user->height) - (5 * $user->age) + $genderConstant;

        // Factor de actividad (TDEE = Total Daily Energy Expenditure)
        $activityFactors = [
            'sedentary'    => 1.2,   // Poco o ningún ejercicio
            'light'        => 1.375, // Ejercicio ligero 1-3 días/semana
            'moderate'     => 1.55,  // Ejercicio moderado 3-5 días/semana
            'active'       => 1.725, // Ejercicio intenso 6-7 días/semana
            'very_active'  => 1.9,   // Ejercicio muy intenso + trabajo físico
        ];

        $tdee = $bmr * ($activityFactors[$activity] ?? 1.55);

        return (int) round($tdee);
    }
}
