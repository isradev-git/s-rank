<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplement_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('supplement_key', 50);
            $table->boolean('taken')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'date', 'supplement_key']);
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplement_logs');
    }
};
