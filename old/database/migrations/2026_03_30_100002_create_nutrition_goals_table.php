<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: nutrition_goals
 * Objetivo nutricional diario del usuario.
 * Solo hay UNA fila por usuario (unique en user_id).
 * Si el usuario no tiene objetivo, usamos valores por defecto calculados
 * a partir de su peso/altura/edad del perfil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('daily_calories')->default(2000);   // kcal objetivo por día
            $table->integer('target_protein')->default(150);    // gramos de proteína
            $table->integer('target_carbs')->default(250);      // gramos de carbohidratos
            $table->integer('target_fat')->default(65);         // gramos de grasa
            $table->integer('target_fiber')->default(25);       // gramos de fibra
            // goal_type: lose_weight = bajar, maintain = mantener, gain_muscle = subir masa
            $table->enum('goal_type', ['lose_weight', 'maintain', 'gain_muscle'])->default('maintain');
            $table->integer('meals_per_day')->default(3);       // Comidas al día planificadas
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_goals');
    }
};
