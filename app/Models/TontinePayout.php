<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TontinePayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'tontine_id',
        'tontine_member_id',
        'cycle_number',
        'amount',
        'status',
        'paid_at',
        'payout_reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(TontineMember::class, 'tontine_member_id');
    }
}
