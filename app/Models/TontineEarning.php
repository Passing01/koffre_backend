<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TontineEarning extends Model
{
    protected $fillable = [
        'tontine_id',
        'user_id',
        'type',
        'amount',
        'tontine_payment_id',
        'tontine_payout_id',
        'reference',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public const TYPE_PLATFORM_FEE = 'platform_fee';
    public const TYPE_CREATOR_COMMISSION = 'creator_commission';

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tontinePayment(): BelongsTo
    {
        return $this->belongsTo(TontinePayment::class);
    }

    public function tontinePayout(): BelongsTo
    {
        return $this->belongsTo(TontinePayout::class);
    }
}
