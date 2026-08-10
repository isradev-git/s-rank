<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('template_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('template_id')->constrained('templates')->onDelete('cascade');
            $table->string('name');
            $table->float('weight_kg')->nullable();
            $table->integer('reps')->nullable();
            $table->integer('sets')->nullable();
            $table->integer('time_seconds')->nullable();
            $table->float('distance_m')->nullable();
            $table->integer('laps')->nullable();
            $table->integer('rest_seconds')->nullable();
            $table->string('style')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_exercises');
    }
};
