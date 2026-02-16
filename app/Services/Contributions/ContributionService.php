<?php

namespace App\Services\Contributions;

use App\Models\Cagnotte;
use App\Models\Contribution;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\FcmService;
use App\Services\Payments\PaymentServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContributionService
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService,
        private readonly AuditService $auditService,
        private readonly FcmService $fcmService
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

            // Notify Contributor
            if ($contribution->user) {
                $this->fcmService->sendToUser(
                    $contribution->user,
                    "Merci !",
                    "Votre contribution de {$contribution->amount} XOF à la cagnotte '{$cagnotte->title}' a été confirmée."
                );
            }

            // Notify Owner
            $contributorName = $contribution->user?->fullname ?? $contribution->contributor_name ?? 'Un invité';
            $this->fcmService->sendToUser(
                $cagnotte->user,
                "Nouvelle contribution",
                "{$contributorName} vient de contribuer {$contribution->amount} XOF à votre cagnotte '{$cagnotte->title}'."
            );

            // Check if 100% reached
            if ($cagnotte->target_amount > 0 && $cagnotte->current_amount >= $cagnotte->target_amount) {
                // Notify Owner
                $this->fcmService->sendToUser(
                    $cagnotte->user,
                    "Objectif atteint !",
                    "Félicitations ! Votre cagnotte '{$cagnotte->title}' a atteint son objectif de {$cagnotte->target_amount} XOF."
                );

                // Notify all contributors (optional/simplified)
                $distinctContributors = $cagnotte->contributions()
                    ->where('payment_status', 'success')
                    ->whereNotNull('user_id')
                    ->with('user')
                    ->get()
                    ->pluck('user')
                    ->unique('id');

                foreach ($distinctContributors as $contributor) {
                    if ($contributor->id !== $cagnotte->user_id) {
                        $this->fcmService->sendToUser(
                            $contributor,
                            "Objectif atteint !",
                            "La cagnotte '{$cagnotte->title}' à laquelle vous avez contribué a atteint son objectif !"
                        );
                    }
                }
            }

            return true;
        });
    }
}
