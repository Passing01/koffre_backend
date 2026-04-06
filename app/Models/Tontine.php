<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tontine extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'amount_per_installment',
        'currency',
        'frequency',
        'frequency_number',
        'starts_at',
        'payout_mode',
        'payout_method',
        'payout_account',

        'creator_percentage',
        'identity_document_path',
        'notification_settings',
        'late_fee_amount',
        'max_participants',
        'requires_member_registration',
        'status',
        'moderation_reason',
        'is_random_payout',
        'type',
        'target_payout_date',
        'contribution_frequency',
        'contribution_frequency_number',
        'payout_frequency',
        'payout_frequency_number',
    ];

    protected $casts = [
        'amount_per_installment' => 'decimal:2',
        'starts_at' => 'datetime',
        'target_payout_date' => 'date',
        'creator_percentage' => 'decimal:2',
        'notification_settings' => 'array',
        'late_fee_amount' => 'decimal:2',
        'is_random_payout' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(TontineMember::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(TontinePayment::class);
    }

    public function payouts(): HasMany
    {
        return $this->hasMany(TontinePayout::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(TontinePayoutRequest::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(TontineEarning::class);
    }

    public function getIdentityDocumentUrlAttribute(): ?string
    {
        return $this->identity_document_path ? asset('storage/' . $this->identity_document_path) : null;
    }
}
