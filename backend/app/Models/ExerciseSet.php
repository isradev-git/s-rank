<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExerciseSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'workout_id',
        'name',
        'weight_kg',
        'reps',
        'sets',
        'time_seconds',
        'distance_m',
        'laps',
        'rpe',
        'rest_seconds',
        'style'
    ];

    public function workout()
    {
        return $this->belongsTo(Workout::class);
    }
}
