<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Services\Contributions\ContributionService;
use App\Services\Tontines\TontineService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    // ─── Événements valides ──────────────────────────────────────────────────────
    private const ALLOWED_EVENTS = [
        'payment.initiated',
        'payment.success',
        'payment.failed',
        'payment.cancelled',
        'payment.refunded',
        'payment.expired',
        'cashout.requested',
        'cashout.approved',
        'cashout.completed',
        'cashout.failed',
        'webhook.test',
    ];

    public function __construct(
        private readonly ContributionService $contributionService,
        private readonly TontineService $tontineService
    ) {
    }

    /**
     * URL de callback unifiée  :  GET|POST /api/payments/callback
     */
    public function handle(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $reference = $request->input('reference');
        $status = $request->input('status');
        $provider = $request->input('provider', 'unknown');
        $redirect = $request->input('redirect');

        // Si référence absente, extraire depuis le payload GeniusPay (data.metadata)
        $data = $request->input('data', []);
        if (!$reference && !empty($data['metadata'])) {
            $metadata = $data['metadata'] ?? [];
            $reference = $metadata['transaction_id'] ?? $metadata['order_id'] ?? null;
            if ($reference) {
                $provider = 'geniuspay';
            }
        }
        $geniuspayRef = is_array($data) ? ($data['reference'] ?? null) : null;

        Log::info('Payment Callback Received', [
            'event' => $event,
            'reference' => $reference,
            'status' => $status,
            'provider' => $provider,
            'all' => $request->all(),
        ]);

        if (!$event || !in_array($event, self::ALLOWED_EVENTS)) {
            return response()->json([
                'success' => false,
                'message' => 'Événement inconnu ou non autorisé : ' . $event,
            ], 422);
        }

        if (!$reference && $event !== 'webhook.test') {
            return response()->json([
                'success' => false,
                'message' => 'Référence de transaction manquante.',
            ], 400);
        }

        try {
            $result = match ($event) {
                'payment.success' => $this->onPaymentSuccess($reference, $geniuspayRef),
                'payment.failed' => $this->onPaymentFailed($reference),
                'payment.cancelled' => $this->onPaymentCancelled($reference),
                'payment.refunded' => $this->onPaymentRefunded($reference),
                'payment.expired' => $this->onPaymentExpired($reference),
                'payment.initiated' => ['message' => 'Paiement initié, en attente de confirmation.'],
                'cashout.requested' => ['message' => 'Demande de cashout reçue.'],
                'cashout.approved' => ['message' => 'Cashout approuvé.'],
                'cashout.completed' => $this->onCashoutCompleted($reference),
                'cashout.failed' => ['message' => 'Cashout échoué.'],
                'webhook.test' => ['message' => 'Webhook de test validé avec succès.'],
                default => ['message' => 'Événement reçu.'],
            };
        } catch (\Throwable $e) {
            Log::error('Payment Callback Processing Error', [
                'event' => $event,
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement de l\'événement.',
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'success' => true,
            'event' => $event,
            'reference' => $reference,
            'redirect' => $redirect,
            'data' => $result,
        ]);
    }

    private function onPaymentSuccess(string $reference, ?string $geniuspayRef = null): array
    {
        $refs = array_filter([$reference, $geniuspayRef]);

        if (str_starts_with($reference, 'TON-')) {
            $done = $this->tontineService->completePayment($refs);
            if ($done) {
                return ['message' => 'Cotisation tontine confirmée avec succès.'];
            }
        }

        $contribution = Contribution::whereIn('payment_reference', $refs)->first();

        if ($contribution) {
            $this->contributionService->complete($contribution->payment_reference);
            return ['message' => 'Paiement confirmé avec succès.', 'contribution_id' => $contribution->id];
        }

        return ['message' => 'Transaction confirmée (aucune entité liée trouvée).'];
    }

    private function onPaymentFailed(string $reference): array
    {
        $contribution = Contribution::where('payment_reference', $reference)->first();

        if ($contribution) {
            $contribution->update(['payment_status' => 'failed']);
            return ['message' => 'Paiement marqué comme échoué.', 'contribution_id' => $contribution->id];
        }

        return ['message' => 'Transaction échouée enregistrée.'];
    }

    private function onPaymentCancelled(string $reference): array
    {
        $contribution = Contribution::where('payment_reference', $reference)->first();

        if ($contribution) {
            $contribution->update(['payment_status' => 'failed']);
            return ['message' => 'Paiement annulé.', 'contribution_id' => $contribution->id];
        }

        return ['message' => 'Annulation enregistrée.'];
    }

    private function onPaymentRefunded(string $reference): array
    {
        Log::info('Payment Refunded', ['reference' => $reference]);
        return ['message' => 'Remboursement enregistré. Traitement manuel requis.'];
    }

    private function onPaymentExpired(string $reference): array
    {
        $contribution = Contribution::where('payment_reference', $reference)->first();

        if ($contribution) {
            $contribution->update(['payment_status' => 'failed']);
            return ['message' => 'Session de paiement expirée.', 'contribution_id' => $contribution->id];
        }

        return ['message' => 'Expiration enregistrée.'];
    }

    private function onCashoutCompleted(string $reference): array
    {
        Log::info('Cashout Completed', ['reference' => $reference]);
        return ['message' => 'Cashout complété avec succès.'];
    }
}
