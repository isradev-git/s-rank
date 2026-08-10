<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // workouts: filtros por usuario y fecha en dashboard/historial
        Schema::table('workouts', function (Blueprint $table) {
            $table->index(['user_id', 'date'], 'idx_workouts_user_date');
        });

        // exercise_sets: JOIN al cargar entrenamientos con sets y búsqueda por nombre
        Schema::table('exercise_sets', function (Blueprint $table) {
            $table->index('workout_id', 'idx_exercise_sets_workout');
            $table->index('name', 'idx_exercise_sets_name');
        });

        // weight_logs: filtros por usuario y fecha + unicidad por día
        Schema::table('weight_logs', function (Blueprint $table) {
            $table->unique(['user_id', 'date'], 'uq_weight_logs_user_date');
        });
    }

    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropIndex('idx_workouts_user_date');
        });

        Schema::table('exercise_sets', function (Blueprint $table) {
            $table->dropIndex('idx_exercise_sets_workout');
            $table->dropIndex('idx_exercise_sets_name');
        });

        Schema::table('weight_logs', function (Blueprint $table) {
            $table->dropUnique('uq_weight_logs_user_date');
        });
    }
};
