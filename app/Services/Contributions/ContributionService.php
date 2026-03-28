<?php

namespace App\Services\Contributions;

use App\Models\Cagnotte;
use App\Models\Contribution;
use App\Models\Earning;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\FcmService;
use App\Services\Payments\PaymentServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        
        $commissionRate = (float) config('services.platform.commission_rate', 0.01);
        $platformFee = round($amount * $commissionRate, 2);
        $totalCharged = $amount + $platformFee;

        return DB::transaction(function () use ($cagnotte, $amount, $platformFee, $totalCharged, $actor, $contributorName, $paymentMethod, $reference) {
            $contribution = Contribution::query()->create([
                'cagnotte_id' => $cagnotte->id,
                'user_id' => $actor?->id,
                'contributor_name' => $contributorName ?? $actor?->fullname,
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'total_charged' => $totalCharged,
                'payment_reference' => $reference,
                'payment_status' => 'pending',
                'payment_method' => $paymentMethod ?? config('services.default_gateway') ?? 'geniuspay',
            ]);

            $paymentData = $this->paymentService->initiatePayment(
                transactionId: $reference,
                amount: (float) $totalCharged,
                currency: 'XOF',
                description: "Contribution à la cagnotte: {$cagnotte->title} (Montant: {$amount} + Frais: {$platformFee})",
                customer: [
                    'name' => $actor?->fullname ?? $contributorName ?? 'Invité',
                    'email' => $actor?->email ?? 'guest@kofre.com',
                    'phone' => $actor?->phone ?? '',
                ]
            );

            // Mettre à jour l'ID externe si disponible (token PayDunya)
            if (!empty($paymentData['payment_token'])) {
                $contribution->update(['payment_reference_external' => $paymentData['payment_token']]);
            }

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

            // Enregistrer le gain plateforme (earnings)
            if ($contribution->platform_fee > 0) {
                Earning::query()->create([
                    'module' => 'cagnotte',
                    'amount' => (float) $contribution->platform_fee,
                    'reference' => 'EARN-CAG-' . $reference,
                    'metadata' => [
                        'cagnotte_id' => $cagnotte->id,
                        'contribution_id' => $contribution->id,
                        'total_charged' => $contribution->total_charged,
                    ],
                ]);
            }

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

            // Trigger automatic payout if in direct mode
            if ($cagnotte->payout_mode === 'direct') {
                $commissionRate = config('services.platform.commission_rate', 0.01);
                $commission = $contribution->amount * $commissionRate;
                $netAmount = $contribution->amount - $commission;

                $payoutAccount = $cagnotte->payout_account ?? $cagnotte->user->phone;

                try {
                    $payoutSuccess = $this->paymentService->payout(
                        account: $payoutAccount,
                        amount: (float) $netAmount,
                        description: "Versement automatique Koffre - Contrib #{$contribution->id}",
                        method: $cagnotte->payout_method
                    );

                    if ($payoutSuccess) {
                        Log::info("Automatic payout successful for contribution #{$contribution->id}");
                        // We could mark the contribution or a separate payout record as "disbursed"
                    } else {
                        Log::error("Automatic payout failed for contribution #{$contribution->id}");
                    }
                } catch (\Exception $e) {
                    Log::error("Error during automatic payout for contribution #{$contribution->id}: " . $e->getMessage());
                }
            }

            return true;
        });
    }

    /**
     * Relancer un paiement en attente pour un utilisateur.
     * Génère un nouveau lien de paiement sans créer une nouvelle contribution.
     */
    public function retry(string $reference, ?User $actor): array
    {
        $contribution = Contribution::where('payment_reference', $reference)
            ->where('payment_status', 'pending')
            ->first();

        if (!$contribution) {
            throw ValidationException::withMessages([
                'reference' => ['Aucun paiement en attente trouvé pour cette référence. Il a peut-être déjà été traité.'],
            ]);
        }

        // Vérifier que l'utilisateur connecté est bien le propriétaire
        if ($actor && $contribution->user_id && (int) $contribution->user_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'reference' => ['Vous ne pouvez pas relancer un paiement qui ne vous appartient pas.'],
            ]);
        }

        $cagnotte = $contribution->cagnotte;
        if (!$cagnotte || $cagnotte->status !== 'active') {
            throw ValidationException::withMessages([
                'cagnotte_id' => ['La cagnotte associée n\'est plus active.'],
            ]);
        }

        // Générer un nouveau lien de paiement avec la même référence et le montant total
        $paymentData = $this->paymentService->initiatePayment(
            transactionId: $contribution->payment_reference,
            amount: (float) $contribution->total_charged,
            currency: 'XOF',
            description: "Contribution à la cagnotte: {$cagnotte->title} (Montant: {$contribution->amount} + Frais: {$contribution->platform_fee})",
            customer: [
                'name'  => $actor?->fullname ?? $contribution->contributor_name ?? 'Invité',
                'email' => $actor?->email ?? 'guest@kofre.com',
                'phone' => $actor?->phone ?? '',
            ]
        );

        Log::info('Contribution payment retried', [
            'reference'   => $contribution->payment_reference,
            'user_id'     => $actor?->id,
            'cagnotte_id' => $cagnotte->id,
        ]);

        return [
            'contribution'  => $contribution,
            'payment_url'   => $paymentData['payment_url'],
            'payment_token' => $paymentData['payment_token'],
        ];
    }
}
