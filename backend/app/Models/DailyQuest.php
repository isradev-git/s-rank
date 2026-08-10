<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyQuest extends Model
{
    protected $fillable = [
        'user_id', 'date', 'quest_key', 'target', 'progress',
        'xp_reward', 'is_optional', 'completed_at',
    ];

    protected $casts = [
        'date'         => 'date',
        'target'       => 'float',
        'progress'     => 'float',
        'xp_reward'    => 'integer',
        'is_optional'  => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
