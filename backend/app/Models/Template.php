<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Template extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'level',
        'mode',
        'duration_minutes',
        'image_url',
        'video_url',
        'is_custom',
        'user_id'
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    public function exercises()
    {
        return $this->hasMany(TemplateExercise::class);
    }
}
