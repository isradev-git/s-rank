<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProgress extends Model
{
    protected $table = 'user_progress';

    protected $fillable = [
        'user_id', 'level', 'xp_total',
        'strength_acc', 'endurance_acc', 'consistency_acc', 'vitality_acc',
        'current_streak', 'longest_streak', 'last_active_date',
    ];

    /**
     * Los mismos valores por defecto que la migración, pero también en memoria: sin
     * esto, una fila recién creada con firstOrCreate() devuelve null en cada columna
     * hasta que se relee, y el Sistema hace cuentas con ella en el acto.
     */
    protected $attributes = [
        'level'           => 1,
        'xp_total'        => 0,
        'strength_acc'    => 0,
        'endurance_acc'   => 0,
        'consistency_acc' => 0,
        'vitality_acc'    => 0,
        'current_streak'  => 0,
        'longest_streak'  => 0,
    ];

    protected $casts = [
        'level'            => 'integer',
        'xp_total'         => 'integer',
        'strength_acc'     => 'float',
        'endurance_acc'    => 'float',
        'consistency_acc'  => 'float',
        'vitality_acc'     => 'float',
        'current_streak'   => 'integer',
        'longest_streak'   => 'integer',
        'last_active_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
