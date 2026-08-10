<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplementLog extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'supplement_key',
        'taken',
    ];

    protected $casts = [
        'date' => 'date',
        'taken' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
