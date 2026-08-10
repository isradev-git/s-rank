<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: recipes
 * Recetas con sus macros totales por porción.
 * Hay recetas del sistema (is_system=true, user_id=null) cargadas
 * por el seeder, y puede haber recetas personalizadas del usuario.
 *
 * Los macros son por porción (no por 100g), porque las recetas
 * se consumen como unidad completa o en fracciones de porciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            // category: desayuno, almuerzo, cena, snack, postre, bebida
            $table->string('category')->default('almuerzo');
            $table->string('image_url')->nullable();
            // Macros POR PORCIÓN
            $table->integer('calories_per_serving');
            $table->decimal('protein_per_serving', 7, 2)->default(0);
            $table->decimal('carbs_per_serving', 7, 2)->default(0);
            $table->decimal('fat_per_serving', 7, 2)->default(0);
            $table->decimal('fiber_per_serving', 7, 2)->default(0);
            $table->integer('servings')->default(1);        // Número de porciones que rinde
            $table->integer('prep_time_min')->default(15);  // Tiempo de preparación en minutos
            $table->integer('cook_time_min')->default(0);   // Tiempo de cocción en minutos
            $table->text('ingredients');                    // JSON array de ingredientes con cantidades
            $table->text('instructions');                   // Pasos de preparación
            $table->string('difficulty')->default('fácil'); // fácil / media / difícil
            $table->boolean('is_system')->default(true);    // true = receta del sistema (seeder)
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('category');
            $table->index('calories_per_serving');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
