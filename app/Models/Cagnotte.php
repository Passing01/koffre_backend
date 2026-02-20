<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

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
        'background_image_path',
        'rccm_number',
        'ifu_number',
        'rccm_document_path',
        'ifu_document_path',
        'signed_contract_path',
        'starts_at',
        'ends_at',
        'status',
        'notified_admin_24h',
        'unlock_requested_at',
        'unlock_document_path',
        'unlock_status',
        'unlocked_at',
        'payout_processed_at',
        'is_archived',
        'moderation_reason',
        'blocked_at',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'payout_accounts' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'unlock_requested_at' => 'datetime',
        'unlocked_at' => 'datetime',
        'payout_processed_at' => 'datetime',
        'is_archived' => 'boolean',
        'blocked_at' => 'datetime',
    ];


    protected $appends = [
        'profile_photo_url',
        'identity_document_url',
        'company_logo_url',
        'background_image_url',
        'rccm_document_url',
        'ifu_document_url',
        'signed_contract_url',
        'unlock_document_url',
        'stats',
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

    public function getUnlockDocumentUrlAttribute(): ?string
    {
        return $this->unlock_document_path ? asset('storage/' . $this->unlock_document_path) : null;
    }

    public function getBackgroundImageUrlAttribute(): ?string
    {
        return $this->background_image_path ? asset('storage/' . $this->background_image_path) : null;
    }

    public function getStatsAttribute(): array
    {
        $start = $this->starts_at ?? $this->created_at;
        $end = $this->ends_at;
        $now = Carbon::now();

        // Durée de la cagnotte
        if ($end) {
            $totalSeconds = $start->diffInSeconds($end);
            $elapsedSeconds = $start->diffInSeconds($now->lessThan($end) ? $now : $end);
            $remainingSeconds = max(0, $now->diffInSeconds($end, false));
        } else {
            $totalSeconds = $start->diffInSeconds($now);
            $elapsedSeconds = $totalSeconds;
            $remainingSeconds = 0;
        }

        // Format durée restante
        $remainingDays = intdiv($remainingSeconds, 86400);
        $remainingHours = intdiv($remainingSeconds % 86400, 3600);

        // Format durée totale
        $totalDays = intdiv($totalSeconds, 86400);
        $totalHours = intdiv($totalSeconds % 86400, 3600);

        // Compteurs
        $likesCount = $this->likes()->count();
        $commentsCount = $this->comments()->count();
        $contributorsCount = $this->contributions()->where('payment_status', 'success')->distinct('user_id')->count('user_id');

        return [
            'duration_days' => $totalDays,
            'duration_hours' => $totalHours,
            'remaining_days' => $remainingDays,
            'remaining_hours' => $remainingHours,
            'is_expired' => $end ? $now->greaterThan($end) : false,
            'likes_count' => $likesCount,
            'comments_count' => $commentsCount,
            'contributors_count' => $contributorsCount,
            'started_at_human' => $start->diffForHumans(),
        ];
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

    public function comments(): HasMany
    {
        return $this->hasMany(CagnotteComment::class)
            ->whereNull('parent_id')
            ->where('is_blocked', false)
            ->with([
                'user:id,fullname,phone',
                'replies' => function ($q) {
                    $q->where('is_blocked', false);
                }
            ]);
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(CagnotteComment::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(CagnotteLike::class);
    }
}
