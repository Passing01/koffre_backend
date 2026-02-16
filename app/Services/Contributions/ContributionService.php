<?php

namespace App\Services\Contributions;

use App\Models\Cagnotte;
use App\Models\Contribution;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Payments\PaymentServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContributionService
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService,
        private readonly AuditService $auditService
    ) {
    }

    public function initiate(
        int $cagnotteId,
        float $amount,
        ?User $actor,
        ?string $contributorName = null,
        ?string $paymentMethod = null,
    ): array {
        $cagnotte = Cagnotte::query()->findOrFail($cagnotteId);

        if ($cagnotte->status !== 'active') {
            throw ValidationException::withMessages(['cagnotte_id' => ['La cagnotte n\'est pas active.']]);
        }

        $reference = 'KOF-' . Str::upper(Str::random(12));

        return DB::transaction(function () use ($cagnotte, $amount, $actor, $contributorName, $paymentMethod, $reference) {
            $contribution = Contribution::query()->create([
                'cagnotte_id' => $cagnotte->id,
                'user_id' => $actor?->id,
                'contributor_name' => $contributorName ?? $actor?->fullname,
                'amount' => $amount,
                'payment_reference' => $reference,
                'payment_status' => 'pending',
                'payment_method' => $paymentMethod ?? 'CinetPay',
            ]);

            $paymentData = $this->paymentService->initiatePayment(
                transactionId: $reference,
                amount: $amount,
                currency: 'XOF',
                description: "Contribution à la cagnotte: {$cagnotte->title}",
                customer: [
                    'name' => $actor?->fullname ?? $contributorName ?? 'Invité',
                    'phone' => $actor?->phone ?? '',
                ]
            );

            // Update contribution with the external token if needed for IPN
            $contribution->update([
                'payment_reference' => $paymentData['payment_token'] ?? $reference
            ]);

            return [
                'contribution' => $contribution,
                'payment_url' => $paymentData['payment_url'],
                'payment_token' => $paymentData['payment_token'],
            ];
        });
    }

    public function complete(string $reference): bool
    {
        return DB::transaction(function () use ($reference) {
            $contribution = Contribution::query()
                ->where('payment_reference', $reference)
                ->where('payment_status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$contribution) {
                return false;
            }

            /** @var Cagnotte $cagnotte */
            $cagnotte = $contribution->cagnotte()->lockForUpdate()->first();

            $contribution->update(['payment_status' => 'success']);

            $newBalance = (float) $cagnotte->current_amount + (float) $contribution->amount;
            $cagnotte->update(['current_amount' => $newBalance]);

            Transaction::query()->create([
                'cagnotte_id' => $cagnotte->id,
                'contribution_id' => $contribution->id,
                'type' => 'credit',
                'amount' => $contribution->amount,
                'balance_after' => $newBalance,
                'reference' => $reference,
            ]);

            $this->auditService->log(
                action: 'contribution.completed',
                actorUserId: $contribution->user_id,
                auditableType: 'cagnotte',
                auditableId: $cagnotte->id,
                metadata: [
                    'contribution_id' => $contribution->id,
                    'amount' => $contribution->amount,
                ],
            );

            return true;
        });
    }
}
