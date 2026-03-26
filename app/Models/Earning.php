<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Earning extends Model
{
    protected $fillable = [
        'module',
        'amount',
        'reference',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'float',
    ];
}
