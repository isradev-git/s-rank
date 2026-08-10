<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo WaterLog — Registro de ingesta de agua
 *
 * Cada fila = un "vaso" (o cantidad personalizada) de agua
 * que el usuario añadió en un día concreto.
 *
 * Ejemplo: el usuario añadió 3 vasos de 250ml → 3 filas con amount_ml=250.
 * Así podemos eliminar entradas individuales fácilmente.
 */
class WaterLog extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'amount_ml',
    ];

    protected $casts = [
        'date'      => 'date',
        'amount_ml' => 'integer',
    ];

    /**
     * Relación con el usuario dueño del registro.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
