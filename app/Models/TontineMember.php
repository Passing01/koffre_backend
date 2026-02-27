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
        'first_name',
        'last_name',
        'identity_document_path',
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

    /** Nom affiché aux membres (visible par tous) */
    public function getDisplayNameAttribute(): string
    {
        if ($this->first_name || $this->last_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }
        return $this->user?->fullname ?? 'Invité';
    }

    /** URL de la pièce d'identité (visible créateur + admin uniquement) */
    public function getIdentityDocumentUrlAttribute(): ?string
    {
        return $this->identity_document_path ? asset('storage/' . $this->identity_document_path) : null;
    }

    public function isFullyRegistered(): bool
    {
        return !empty($this->first_name) && !empty($this->last_name) && !empty($this->identity_document_path);
    }
}
