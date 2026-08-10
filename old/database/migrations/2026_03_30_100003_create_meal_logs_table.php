<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: meal_logs
 * Registro de lo que el usuario ha comido.
 * Cada fila = un alimento en una comida de un día específico.
 * Por ejemplo: "100g de arroz blanco en el almuerzo del 2026-03-30"
 *
 * Permite dos formas de registrar:
 * 1. Seleccionando un alimento del catálogo (food_item_id no nulo)
 * 2. Ingresando manualmente un nombre y calorías (food_item_id nulo)
 *
 * Las calorías/macros se guardan en la fila (no se recalculan desde food_items)
 * para que si el alimento cambia en el catálogo, el historial quede igual.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_logs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();   // UUID para la API (evita exponer IDs secuenciales)
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');               // Fecha del registro (YYYY-MM-DD)
            // meal_type: breakfast=desayuno, lunch=almuerzo, dinner=cena, snack=snack
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack'])->default('lunch');
            $table->foreignId('food_item_id')->nullable()->constrained('food_items')->nullOnDelete();
            $table->string('custom_food_name')->nullable(); // Si se ingresó manualmente
            $table->decimal('quantity_grams', 7, 2)->default(100); // Cantidad consumida en gramos
            // Macros calculados al momento del registro (snapshot)
            $table->decimal('calories', 7, 2)->default(0);
            $table->decimal('protein', 7, 2)->default(0);
            $table->decimal('carbs', 7, 2)->default(0);
            $table->decimal('fat', 7, 2)->default(0);
            $table->decimal('fiber', 7, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            // Índice compuesto para consultas del tipo "dame todas las comidas del usuario X del día Y"
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_logs');
    }
};
