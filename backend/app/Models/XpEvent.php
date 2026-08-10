<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XpEvent extends Model
{
    protected $fillable = ['user_id', 'date', 'source', 'source_id', 'amount'];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'integer',
    ];
}
