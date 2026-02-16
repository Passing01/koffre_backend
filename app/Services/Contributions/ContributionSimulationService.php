<?php

namespace App\Services\Contributions;

use App\Models\Cagnotte;
use App\Models\Contribution;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContributionSimulationService
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function simulate(
        int $cagnotteId,
        float $amount,
        ?User $actor,
        ?string $contributorName = null,
        ?string $paymentMethod = null,
    ): array {
        $paymentMethod = $paymentMethod ?: 'simulation';
        $reference = 'SIM-' . Str::upper(Str::random(12));

        return DB::transaction(function () use ($cagnotteId, $amount, $actor, $contributorName, $paymentMethod, $reference) {
            $cagnotte = Cagnotte::query()->whereKey($cagnotteId)->lockForUpdate()->first();

            if (!$cagnotte) {
                throw ValidationException::withMessages([
                    'cagnotte_id' => ['Cagnotte introuvable.'],
                ]);
            }

            if ($cagnotte->status !== 'active') {
                throw ValidationException::withMessages([
                    'cagnotte_id' => ['La cagnotte n\'est pas active.'],
                ]);
            }

            if ($cagnotte->visibility === 'private') {
                if (!$actor) {
                    throw ValidationException::withMessages([
                        'cagnotte_id' => ['Accès refusé.'],
                    ]);
                }

                $isOwner = (int) $cagnotte->user_id === (int) $actor->id;
                $isParticipant = $cagnotte->participants()->where('phone', $actor->phone)->exists();

                if (!$isOwner && !$isParticipant) {
                    throw ValidationException::withMessages([
                        'cagnotte_id' => ['Accès refusé.'],
                    ]);
                }
            }

            if ($cagnotte->ends_at && $cagnotte->ends_at->isPast()) {
                throw ValidationException::withMessages([
                    'cagnotte_id' => ['La cagnotte est expirée.'],
                ]);
            }

            $contribution = Contribution::query()->create([
                'cagnotte_id' => $cagnotte->id,
                'user_id' => $actor?->id,
                'contributor_name' => $contributorName,
                'amount' => $amount,
                'payment_reference' => $reference,
                'payment_status' => 'success',
                'payment_method' => $paymentMethod,
            ]);

            $newBalance = (float) $cagnotte->current_amount + (float) $amount;
            $cagnotte->current_amount = $newBalance;
            $cagnotte->save();

            $transaction = Transaction::query()->create([
                'cagnotte_id' => $cagnotte->id,
                'contribution_id' => $contribution->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $reference,
                'meta' => [
                    'simulation' => true,
                    'payment_method' => $paymentMethod,
                ],
            ]);

            $this->auditService->log(
                action: 'contribution.simulated',
                actorUserId: $actor?->id,
                auditableType: 'cagnotte',
                auditableId: $cagnotte->id,
                metadata: [
                    'contribution_id' => $contribution->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $amount,
                    'reference' => $reference,
                ],
            );

            return [
                'contribution' => $contribution,
                'transaction' => $transaction,
                'cagnotte' => $cagnotte->fresh(),
            ];
        });
    }
}
