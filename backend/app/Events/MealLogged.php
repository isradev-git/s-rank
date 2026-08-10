<?php

namespace App\Events;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Events\Dispatchable;

class MealLogged
{
    use Dispatchable;

    public array $rewards = [];

    public function __construct(
        public User $user,
        public CarbonImmutable $date,
    ) {}
}
