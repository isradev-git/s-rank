<?php

namespace App\Listeners;

use App\Events\MealLogged;
use App\Events\SupplementToggled;
use App\Events\WaterLogged;
use App\Events\WeightLogged;
use App\Events\WorkoutStored;
use App\System\SystemService;

/**
 * El único puente entre los módulos y el Sistema. Si mañana aparece un módulo nuevo,
 * publica su evento y se añade aquí una línea: el núcleo no se toca.
 */
class UpdateSystemProgress
{
    public function __construct(private SystemService $system) {}

    public function handleWorkout(WorkoutStored $event): void
    {
        $event->rewards = $this->system->afterWorkout($event->workout, $event->newRecords);
    }

    public function handleHabit(MealLogged|WaterLogged|SupplementToggled|WeightLogged $event): void
    {
        $event->rewards = $this->system->afterHabit($event->user, $event->date);
    }
}
