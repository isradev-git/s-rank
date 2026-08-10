<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade la columna `sugar` a meal_logs.
 *
 * La BD de food_items ya tenía sugar_per_100g, pero al guardar el
 * snapshot en meal_logs solo se guardaba fiber, no sugar.
 * Con esta migración lo añadimos para poder mostrarlo en la UI.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_logs', function (Blueprint $table) {
            // Añadimos sugar justo después de fiber
            $table->decimal('sugar', 7, 2)->default(0)->after('fiber');
        });
    }

    public function down(): void
    {
        Schema::table('meal_logs', function (Blueprint $table) {
            $table->dropColumn('sugar');
        });
    }
};
