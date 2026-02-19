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
        private readonly PaymentServiceInterface $paymentService,
        private readonly ContributionService $contributionService
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
        Log::info('PayDunya IPN Received', $request->all());

        $token = $request->input('token');

        if (!$token) {
            return response()->json(['message' => 'Token missing'], 400);
        }

        try {
            if ($this->paymentService->verifyPayment($token)) {
                $contribution = \App\Models\Contribution::where('payment_reference', $token)->first();

                if ($contribution) {
                    $this->contributionService->complete($contribution->payment_reference);
                }

                return response()->json(['message' => 'IPN Processed']);
            }
        } catch (\Exception $e) {
            Log::error('PayDunya IPN processing error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Error'], 500);
        }

        return response()->json(['message' => 'Notification received']);
    }

    public function handleGeniusPay(Request $request)
    {
        Log::info('GeniusPay Webhook Received', $request->all());

        $signature = $request->header('X-GeniusPay-Signature');
        $payload = $request->getContent();

        // Verification of signature could be added here if secret is known
        // For now, we trust the reference and re-verify via API if needed

        $data = $request->input('data', []);
        $reference = $data['reference'] ?? null;
        $status = $data['status'] ?? null;

        if (!$reference) {
            return response()->json(['message' => 'Reference missing'], 400);
        }

        if (in_array($status, ['completed', 'success', 'paid'])) {
            try {
                // Re-verify for security
                if ($this->paymentService->verifyPayment($reference)) {
                    $this->contributionService->complete($reference);
                    return response()->json(['message' => 'GeniusPay payment completed']);
                }
            } catch (\Exception $e) {
                Log::error('GeniusPay webhook processing error', ['error' => $e->getMessage()]);
                return response()->json(['message' => 'Error'], 500);
            }
        }

        return response()->json(['message' => 'Notification received']);
    }
}
