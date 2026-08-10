<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FoodItem>
 */
class FoodItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'              => fake()->words(2, true),
            'brand'             => null,
            'category'          => fake()->randomElement(['carnes', 'lácteos', 'cereales', 'frutas', 'verduras']),
            'unit'              => 'g',
            'calories_per_100g' => fake()->randomFloat(1, 50, 500),
            'protein_per_100g'  => fake()->randomFloat(1, 0, 40),
            'carbs_per_100g'    => fake()->randomFloat(1, 0, 80),
            'fat_per_100g'      => fake()->randomFloat(1, 0, 30),
            'fiber_per_100g'    => fake()->randomFloat(1, 0, 10),
            'sugar_per_100g'    => fake()->randomFloat(1, 0, 20),
            'is_verified'       => false,
            'user_id'           => null,
        ];
    }

    /**
     * Alimento del sistema (visible por todos).
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id'     => null,
            'is_verified' => true,
        ]);
    }

    /**
     * Alimento personal de un usuario concreto.
     */
    public function forUser(string $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id'     => $userId,
            'is_verified' => false,
        ]);
    }
}
