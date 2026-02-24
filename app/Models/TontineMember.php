<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TontineMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'tontine_id',
        'phone',
        'user_id',
        'payout_rank',
        'permissions',
        'status',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function tontine(): BelongsTo
    {
        return $this->belongsTo(Tontine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TontinePayment::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(TontinePayout::class);
    }
}
