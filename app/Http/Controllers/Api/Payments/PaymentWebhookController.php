<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Services\Contributions\ContributionService;
use App\Services\Payments\PaymentServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly \App\Services\Payments\PaymentServiceInterface $paymentService,
        private readonly \App\Services\Contributions\ContributionService $contributionService,
        private readonly \App\Services\Tontines\TontineService $tontineService
    ) {
    }

    public function handleCinetPay(Request $request)
    {
        Log::info('CinetPay Webhook Received', $request->all());

        $transactionId = $request->input('cpm_trans_id');
        $siteId = $request->input('cpm_site_id');

        if (!$transactionId) {
            return response()->json(['message' => 'Transaction ID missing'], 400);
        }

        // Verification via CinetPay API (using token if available or direct check)
        // Usually CinetPay sends the status in the webhook, but it's safer to re-verify.

        // For CinetPay v2, we might not have the token in the POST notification directly, 
        // we might need to check by transaction_id if the token is not stored.
        // Actually, CinetPay v2 notification sends 'cpm_trans_id'.

        try {
            // Simplified: if we get the notification and it's a success cpm_result == '00'
            if ($request->input('cpm_result') === '00') {
                $this->contributionService->complete($transactionId);
                return response()->json(['message' => 'Paiement validé']);
            }
        } catch (\Exception $e) {
            Log::error('Error processing CinetPay webhook', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error'], 500);
        }

        return response()->json(['message' => 'Notification reçue']);
    }

    public function handleFedaPay(Request $request)
    {
        Log::info('FedaPay Webhook Received', $request->all());

        // FedaPay webhooks send an event object
        $event = $request->input('event');
        $data = $request->input('data');

        if ($event === 'transaction.approved' || $event === 'transaction.transferred') {
            // In FedaPay, we use the custom reference if provided, 
            // or we might need to find the contribution by payment_id

            // For now, let's assume we use the external payment ID or find by metadata
            // FedaPay allows custom metadata. 
            $paymentId = $data['id'];

            // We need a way to link FedaPay ID to our reference. 
            // I will update ContributionService to store payment_id.

            $contribution = \App\Models\Contribution::where('payment_reference', $data['reference'])
                ->orWhere('payment_reference', $paymentId)
                ->first();

            if ($contribution) {
                $this->contributionService->complete($contribution->payment_reference);
            }
        }

        return response()->json(['message' => 'Webhook processed']);
    }

    public function handlePayDunya(Request $request)
    {
        Log::info('PayDunya IPN Received', [
            'method' => $request->method(),
            'body' => $request->all()
        ]);

        $token = $request->input('token');
        // PayDunya sends custom_data as an array if sent as array in initiatePayment
        $customData = $request->input('custom_data', []);
        $internalRef = $customData['transaction_id'] ?? null;

        if (!$token) {
            Log::error('PayDunya IPN: Token missing in request');
            return response()->json(['message' => 'Token missing'], 400);
        }

        try {
            // Re-vérifier l'authenticité auprès de PayDunya
            if ($this->paymentService->verifyPayment($token)) {
                
                // 1. Chercher par la référence interne (KOF-... ou TON-...)
                if ($internalRef) {
                    if (str_starts_with($internalRef, 'TON-')) {
                        $tontineService = app(\App\Services\Tontines\TontineService::class);
                        $tontineService->completePayment($internalRef);
                        return response()->json(['message' => 'Tontine IPN Processed']);
                    }

                    $contribution = \App\Models\Contribution::where('payment_reference', $internalRef)->first();
                    if ($contribution) {
                        $this->contributionService->complete($internalRef);
                        return response()->json(['message' => 'Contribution IPN Processed']);
                    }
                }

                // 2. Fallback: Chercher par le token (si stocké dans payment_reference par erreur ou rétrocompatibilité)
                $contribution = \App\Models\Contribution::where('payment_reference', $token)->first();
                if ($contribution) {
                    $this->contributionService->complete($contribution->payment_reference);
                    return response()->json(['message' => 'IPN Processed (found by token)']);
                }

                Log::warning('PayDunya IPN: Reference not found in DB', [
                    'token' => $token,
                    'internalRef' => $internalRef
                ]);
            } else {
                Log::warning('PayDunya IPN: Verification failed for token ' . $token);
            }
        } catch (\Exception $e) {
            Log::error('PayDunya IPN processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Error'], 500);
        }

        return response()->json(['message' => 'Notification received and acknowledged']);
    }

    public function handleGeniusPay(Request $request)
    {
        Log::info('GeniusPay Webhook Received', [
            'headers' => $request->headers->all(),
            'body' => $request->all()
        ]);

        // 1. Récupérer les headers de sécurité
        $signature = $request->header('X-Webhook-Signature');
        $timestamp = $request->header('X-Webhook-Timestamp');
        $event = $request->header('X-Webhook-Event');

        // 2. Vérification basique des headers
        if (!$signature || !$timestamp || !$event) {
            Log::warning('GeniusPay Webhook: Headers de sécurité manquants');
            return response()->json(['message' => 'Missing security headers'], 400);
        }

        // 3. Vérifier la signature HMAC SHA-256
        $secret = config('services.geniuspay.webhook_secret');
        if (empty($secret)) {
            Log::error('GeniusPay Webhook: Webhook secret non configuré');
            // En dev/test on peut laisser passer si configuré ainsi, mais en prod c'est bloquant
            if (config('app.env') === 'production') {
                return response()->json(['message' => 'Configuration error'], 500);
            }
        } else {
            // signature = HMAC-SHA256(timestamp + "." + json_payload, secret)
            // Utiliser getContent() pour avoir le JSON brut exact
            $payload = $request->getContent();
            $dataToSign = $timestamp . '.' . $payload;
            $expectedSignature = hash_hmac('sha256', $dataToSign, $secret);

            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('GeniusPay Webhook: Signature invalide', [
                    'received' => $signature,
                    'expected' => $expectedSignature
                ]);
                return response()->json(['message' => 'Invalid signature'], 401);
            }
        }

        // 4. Vérifier le timestamp (fenêtre de 5 minutes) para rapport au Replay Attack
        $timeDiff = abs(time() - (int) $timestamp);
        if ($timeDiff > 300) {
            Log::warning('GeniusPay Webhook: Timestamp trop ancien', ['diff' => $timeDiff]);
            return response()->json(['message' => 'Timestamp too old'], 400);
        }

        // 5. Traitement de l'événement
        $payloadData = $request->all();

        // Notre référence interne (KOF-... ou TON-...) est dans metadata
        // La référence GeniusPay (SANDBOX_... ou MTX-...) est dans data.reference - utilisée si payment_reference a été écrasé
        $data = $payloadData['data'] ?? [];
        $metadata = $data['metadata'] ?? [];
        $internalRef = $metadata['transaction_id'] ?? $metadata['order_id'] ?? null;
        $geniuspayRef = $data['reference'] ?? null;

        Log::info('GeniusPay Webhook: Référence extraite', [
            'event' => $event,
            'internal_ref' => $internalRef,
            'geniuspay_ref' => $payloadData['data']['reference'] ?? null,
        ]);

        // webhook.test ne nécessite pas de référence
        if ($event === 'webhook.test') {
            Log::info('GeniusPay Webhook Test Success');
            return response()->json(['success' => true, 'message' => 'Test successful']);
        }

        $refs = array_filter([$internalRef, $geniuspayRef]);
        if (empty($refs)) {
            Log::warning('GeniusPay Webhook: Aucune référence trouvée dans le payload');
            return response()->json(['message' => 'Reference missing'], 400);
        }

        // Gérer les différents types d'événements
        switch ($event) {
            case 'payment.success':
                try {
                    if ($internalRef && str_starts_with($internalRef, 'TON-')) {
                        $tontineService = app(\App\Services\Tontines\TontineService::class);
                        $done = $tontineService->completePayment($refs);
                        return response()->json(['success' => true, 'message' => $done ? 'Tontine payment processed' : 'No matching payment']);
                    }

                    $contribution = \App\Models\Contribution::whereIn('payment_reference', $refs)->first();
                    if ($contribution) {
                        $this->contributionService->complete($contribution->payment_reference);
                    }
                    return response()->json(['success' => true, 'message' => 'Contribution payment processed']);
                } catch (\Exception $e) {
                    Log::error('GeniusPay Webhook Error: ' . $e->getMessage(), [
                        'internal_ref' => $internalRef,
                        'event' => $event,
                    ]);
                    return response()->json([
                        'type' => 'about:blank',
                        'title' => 'Internal Server Error',
                        'status' => 500,
                        'detail' => 'Failed to process webhook',
                        'instance' => $request->path()
                    ], 500);
                }

            case 'payment.failed':
            case 'payment.cancelled':
            case 'payment.expired':
                Log::info('GeniusPay Payment issue', ['event' => $event, 'refs' => $refs]);
                $contribution = \App\Models\Contribution::whereIn('payment_reference', $refs)->first();
                if ($contribution) {
                    $contribution->update(['payment_status' => 'failed']);
                }
                $tontinePayment = \App\Models\TontinePayment::whereIn('payment_reference', $refs)->where('status', 'pending')->first();
                if ($tontinePayment) {
                    $tontinePayment->update(['status' => 'failed']);
                }
                return response()->json(['success' => true, 'message' => 'Event acknowledged']);

            default:
                Log::info('GeniusPay Webhook: Event not handled', ['event' => $event]);
                return response()->json(['success' => true, 'message' => 'Event ignored']);
        }
    }
}
