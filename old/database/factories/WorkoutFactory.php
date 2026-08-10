<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workout>
 */
class WorkoutFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'date'             => fake()->dateTimeBetween('-1 year', 'now'),
            'mode'             => fake()->randomElement(['gym', 'home', 'calisthenics', 'swimming']),
            'duration_minutes' => fake()->numberBetween(20, 120),
            'notes'            => null,
        ];
    }
}
