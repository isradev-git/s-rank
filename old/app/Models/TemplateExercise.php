<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TemplateExercise extends Model
{
    protected $fillable = [
        'template_id',
        'name',
        'sets',
        'reps',
        'weight_kg',
        'time_seconds',
        'distance_m',
        'laps',
        'rest_seconds',
        'style'
    ];

    public function template()
    {
        return $this->belongsTo(Template::class);
    }
}
