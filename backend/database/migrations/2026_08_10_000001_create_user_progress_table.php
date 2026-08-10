<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Progreso del usuario. `level` es caché: la verdad es `xp_total`, de donde se
 * derivan nivel y rango. Los acumuladores alimentan las cuatro estadísticas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('xp_total')->default(0);
            $table->decimal('strength_acc', 14, 2)->default(0);
            $table->decimal('endurance_acc', 14, 2)->default(0);
            $table->decimal('consistency_acc', 14, 2)->default(0);
            $table->decimal('vitality_acc', 14, 2)->default(0);
            $table->unsignedInteger('current_streak')->default(0);
            $table->unsignedInteger('longest_streak')->default(0);
            $table->date('last_active_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
