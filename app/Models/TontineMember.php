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

    protected $appends = ['display_name', 'next_payout_date'];

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

    /** Calcule la date de la prochaine prise du membre en fonction de son rang et du cycle actuel */
    public function getNextPayoutDateAttribute(): ?string
    {
        $tontine = $this->tontine;
        if (!$tontine || !$this->payout_rank || $tontine->status !== 'active') return null;

        $acceptedCount = $tontine->members()->where('status', 'accepted')->count();
        if ($acceptedCount === 0) return null;

        $start = $tontine->starts_at;
        if (!$start) return null;

        $freq = $tontine->payout_frequency ?: $tontine->frequency;
        $num = $tontine->payout_frequency_number ?: $tontine->frequency_number;

        // Calculer le cycle de versement actuel
        $now = now();
        $diff = 0;
        switch ($freq) {
            case 'days': $diff = $start->diffInDays($now); break;
            case 'weeks': $diff = $start->diffInWeeks($now); break;
            case 'months': $diff = $start->diffInMonths($now); break;
            case 'years': $diff = $start->diffInYears($now); break;
            default: $diff = $start->diffInDays($now); break;
        }
        $currentPayoutCycle = (int) floor($diff / max(1, $num)) + 1;

        // Trouver quel numéro de cycle correspond au rang de ce membre
        // Dans chaque grande boucle (Full Cycle), le membre a sa place au rang R.
        $fullCyclesFactor = (int) floor(($currentPayoutCycle - 1) / $acceptedCount);
        $plannedPayoutCycle = ($fullCyclesFactor * $acceptedCount) + $this->payout_rank;

        // Si ce cycle est déjà passé (ex: Rang 1 alors qu'on est au cycle 3 du 1er tour), 
        // alors son prochain tour est dans la boucle suivante.
        if ($plannedPayoutCycle < $currentPayoutCycle) {
            $plannedPayoutCycle += $acceptedCount;
        }

        // Convertir le numéro du cycle prévu en date
        $nextDate = \Illuminate\Support\Carbon::parse($start)->copy();
        $intervals = ($plannedPayoutCycle - 1) * $num;

        switch ($freq) {
            case 'days': $nextDate->addDays($intervals); break;
            case 'weeks': $nextDate->addWeeks($intervals); break;
            case 'months': $nextDate->addMonths($intervals); break;
            case 'years': $nextDate->addYears($intervals); break;
            default: $nextDate->addDays($intervals); break;
        }

        return $nextDate->toDateString();
    }
}
