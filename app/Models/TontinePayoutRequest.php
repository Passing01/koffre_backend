<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TontinePayoutRequest extends Model
{
    protected $fillable = [
        'tontine_id',
        'tontine_member_id',
        'cycle_number',
        'amount',
        'unpaid_member_ids',
        'status',
        'approved_by_user_id',
        'approved_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'unpaid_member_ids' => 'array',
        'approved_at' => 'datetime',
    ];

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(TontineMember::class, 'tontine_member_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }
}
