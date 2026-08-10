<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * El libro mayor. Todo el XP concedido pasa por aquí: es lo que permite aplicar
 * los topes diarios, auditar la progresión y recalcularla entera desde cero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('xp_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('source', 20);            // workout|record|quest|quest_bonus|streak
            $table->string('source_id', 60)->nullable();
            $table->integer('amount');
            $table->timestamps();

            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'source', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('xp_events');
    }
};
