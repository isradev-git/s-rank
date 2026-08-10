<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade la columna `unit` a la tabla food_items.
 *
 * `unit` indica si los valores nutricionales son por 100g o por 100ml.
 * Los alimentos sólidos usan 'g', los líquidos usan 'ml'.
 *
 * Valores posibles: 'g' | 'ml'
 * Por defecto: 'g' (la mayoría de alimentos son sólidos)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('food_items', 'unit')) {
            Schema::table('food_items', function (Blueprint $table) {
                $table->string('unit', 10)->default('g')->after('category');
            });
        }
    }

    public function down(): void
    {
        Schema::table('food_items', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }
};
