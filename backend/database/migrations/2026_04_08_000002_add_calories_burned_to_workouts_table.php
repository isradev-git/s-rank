<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            // Calorías quemadas reportadas directamente por el dispositivo (reloj, app, etc.)
            // Si se proporciona, se usa en lugar del cálculo MET.
            $table->integer('calories_burned')->nullable()->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn('calories_burned');
        });
    }
};
