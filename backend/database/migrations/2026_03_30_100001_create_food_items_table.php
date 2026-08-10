<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: food_items
 * Catálogo de alimentos con sus valores nutricionales por 100g.
 * Contiene alimentos del sistema (is_verified=true) y alimentos
 * creados por el usuario (user_id no nulo, is_verified=false).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('food_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // Nombre del alimento (ej: "Pechuga de pollo cocida")
            $table->string('brand')->nullable();            // Marca comercial (opcional)
            $table->string('category')->nullable();         // Categoría: carnes, lácteos, cereales, etc.
            $table->decimal('calories_per_100g', 7, 2);    // Calorías por 100g
            $table->decimal('protein_per_100g', 7, 2)->default(0);
            $table->decimal('carbs_per_100g', 7, 2)->default(0);
            $table->decimal('fat_per_100g', 7, 2)->default(0);
            $table->decimal('fiber_per_100g', 7, 2)->default(0);
            $table->decimal('sugar_per_100g', 7, 2)->default(0);
            $table->boolean('is_verified')->default(false); // true = alimento del sistema (validado)
            $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete(); // null = sistema
            $table->timestamps();

            // Índice para búsqueda rápida por nombre
            $table->index('name');
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_items');
    }
};
