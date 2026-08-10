<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla: water_logs
 * Registra cada vez que el usuario bebe agua.
 * Cada fila = un vaso/botella añadida en un día concreto.
 *
 * El objetivo diario por defecto es 2000ml (8 vasos de 250ml).
 * El usuario puede cambiar el objetivo en su perfil.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');                           // Día del registro
            $table->integer('amount_ml')->default(250);    // Cantidad en ml (1 vaso = 250ml por defecto)
            $table->timestamps();

            // Índice compuesto para consultas rápidas por usuario y día
            $table->index(['user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_logs');
    }
};
