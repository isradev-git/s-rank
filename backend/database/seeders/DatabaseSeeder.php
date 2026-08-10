<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            TemplatesTableSeeder::class,
            ExercisesTableSeeder::class,
            FoodItemsTableSeeder::class,   // Catálogo de alimentos para nutrición (~152 items)
            AlimentosSeeder::class,        // Catálogo ampliado desde /alimentos/*.json (+1272 items)
            RecipesTableSeeder::class,     // Recetas del sistema
            UsersSeeder::class,            // Admin + Isra + Slavka
        ]);
    }
}
