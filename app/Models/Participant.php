<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Participant extends Model
{
    use HasFactory;

    protected $fillable = [
        'cagnotte_id',
        'phone',
    ];

    public function cagnotte(): BelongsTo
    {
        return $this->belongsTo(Cagnotte::class);
    }
}
