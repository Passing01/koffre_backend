<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'cagnotte_id',
        'user_id',
        'contributor_name',
        'amount',
        'platform_fee',
        'total_charged',
        'payment_reference',
        'payment_status',
        'payment_method',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'total_charged' => 'decimal:2',
    ];

    public function cagnotte(): BelongsTo
    {
        return $this->belongsTo(Cagnotte::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
