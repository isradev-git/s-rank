<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Recipe>
 */
class RecipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'                  => fake()->words(3, true),
            'description'           => fake()->sentence(),
            'category'              => fake()->randomElement(['desayuno', 'almuerzo', 'cena', 'snack']),
            'image_url'             => null,
            'calories_per_serving'  => fake()->numberBetween(100, 800),
            'protein_per_serving'   => fake()->randomFloat(1, 5, 50),
            'carbs_per_serving'     => fake()->randomFloat(1, 10, 100),
            'fat_per_serving'       => fake()->randomFloat(1, 2, 40),
            'fiber_per_serving'     => fake()->randomFloat(1, 0, 10),
            'servings'              => 1,
            'prep_time_min'         => fake()->numberBetween(5, 30),
            'cook_time_min'         => fake()->numberBetween(5, 60),
            'ingredients'           => [['name' => 'Ingrediente', 'quantity' => '100g']],
            'instructions'          => fake()->paragraph(),
            'difficulty'            => fake()->randomElement(['fácil', 'media', 'difícil']),
            'is_system'             => true,
            'user_id'               => null,
        ];
    }

    /**
     * Receta del sistema (sin user_id, no se puede borrar).
     */
    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'user_id'   => null,
        ]);
    }

    /**
     * Receta creada por un usuario.
     */
    public function byUser(string $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system' => true,
            'user_id'   => $userId,
        ]);
    }
}
