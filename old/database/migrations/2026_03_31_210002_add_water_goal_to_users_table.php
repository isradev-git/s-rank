<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade el objetivo diario de agua a la tabla users.
 * Por defecto: 2000ml (8 vasos de 250ml) — recomendación OMS.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('water_goal_ml')->default(2000)->after('weight');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('water_goal_ml');
        });
    }
};
