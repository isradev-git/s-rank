<?php

namespace App\Events;

use App\Models\Workout;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * El módulo de entrenamiento avisa de que se ha guardado un entreno. No sabe qué
 * hará el Sistema con esto. `$rewards` lo rellena el listener y lo lee el controlador
 * para devolverlo en la misma respuesta, sin una segunda llamada.
 */
class WorkoutStored
{
    use Dispatchable;

    public array $rewards = [];

    public function __construct(
        public Workout $workout,
        public array $newRecords = [],
    ) {}
}
