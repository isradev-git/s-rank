<?php

namespace Database\Factories;

use App\Models\Workout;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ExerciseSet>
 */
class ExerciseSetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workout_id' => Workout::factory(),
            'name'       => fake()->randomElement(['Sentadilla', 'Press Banca', 'Peso Muerto', 'Dominadas']),
            'sets'       => fake()->numberBetween(1, 5),
            'reps'       => fake()->numberBetween(5, 15),
            'weight_kg'  => fake()->randomFloat(1, 20, 100),
            'rpe'        => null,
        ];
    }
}
