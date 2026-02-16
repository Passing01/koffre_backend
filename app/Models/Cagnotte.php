<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cagnotte extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'target_amount',
        'current_amount',
        'visibility',
        'payout_mode',
        'payout_method',
        'payout_account',
        'payout_accounts',
        'creator_type',
        'profile_photo_path',
        'identity_document_path',
        'business_name',
        'company_logo_path',
        'rccm_number',
        'ifu_number',
        'rccm_document_path',
        'ifu_document_path',
        'signed_contract_path',
        'starts_at',
        'ends_at',
        'status',
        'notified_admin_24h',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'payout_accounts' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    protected $appends = [
        'profile_photo_url',
        'identity_document_url',
        'company_logo_url',
        'rccm_document_url',
        'ifu_document_url',
        'signed_contract_url',
    ];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo_path ? asset('storage/' . $this->profile_photo_path) : null;
    }

    public function getIdentityDocumentUrlAttribute(): ?string
    {
        return $this->identity_document_path ? asset('storage/' . $this->identity_document_path) : null;
    }

    public function getCompanyLogoUrlAttribute(): ?string
    {
        return $this->company_logo_path ? asset('storage/' . $this->company_logo_path) : null;
    }

    public function getRccmDocumentUrlAttribute(): ?string
    {
        return $this->rccm_document_path ? asset('storage/' . $this->rccm_document_path) : null;
    }

    public function getIfuDocumentUrlAttribute(): ?string
    {
        return $this->ifu_document_path ? asset('storage/' . $this->ifu_document_path) : null;
    }

    public function getSignedContractUrlAttribute(): ?string
    {
        return $this->signed_contract_path ? asset('storage/' . $this->signed_contract_path) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class);
    }

    public function contributions(): HasMany
    {
        return $this->hasMany(Contribution::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
