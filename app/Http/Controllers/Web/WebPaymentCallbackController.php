<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cagnotte;
use App\Models\Contribution;
use App\Models\TontinePayment;
use App\Services\Contributions\ContributionService;
use App\Services\Tontines\TontineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebPaymentCallbackController extends Controller
{
    private const SUCCESS_EVENTS = [
        'payment.success',
        'payment.initiated',
    ];

    private const FAILURE_EVENTS = [
        'payment.failed',
        'payment.cancelled',
        'payment.expired',
    ];

    public function __construct(
        private readonly ContributionService $contributionService,
        private readonly TontineService $tontineService
    ) {
    }

    public function handle(Request $request)
    {
        $event = $request->input('event');
        $reference = $request->input('reference');
        $token = $request->input('token');
        $status = $request->input('status');
        $provider = $request->input('provider', 'unknown');

        if (!$event) {
            $event = $this->inferEventFromLegacyParams($token, $status);
        }

        $realRef = $reference ?? $token;

        Log::info('Web Payment Callback', [
            'event' => $event,
            'reference' => $realRef,
            'provider' => $provider,
        ]);

        // ── Case 1: Tontine ──
        if ($realRef && str_starts_with($realRef, 'TON-')) {
            return $this->handleTontine($event, $realRef, $provider);
        }

        // ── Case 2: Contribution ──
        $contribution = Contribution::with('cagnotte')
            ->where('payment_reference', $realRef)
            ->first();

        $cagnotte = $contribution?->cagnotte;

        if (in_array($event, self::SUCCESS_EVENTS)) {
            return $this->handleSuccess($contribution, $cagnotte, $realRef, $provider);
        }

        if (in_array($event, self::FAILURE_EVENTS)) {
            return $this->handleFailure($event, $contribution, $cagnotte);
        }

        return $this->handleSuccess($contribution, $cagnotte, $realRef, $provider);
    }

    private function handleTontine(string $event, string $reference, string $provider)
    {
        $payment = TontinePayment::with('tontine')->where('payment_reference', $reference)->first();

        if (in_array($event, self::SUCCESS_EVENTS)) {
            if ($payment && $payment->status !== 'success') {
                $this->tontineService->completePayment($reference);
                $payment->refresh();
            }

            return view('contributions.success', [
                'contribution' => null, // We could pass a generic object if needed
                'cagnotte' => null,
                'tontine' => $payment?->tontine,
                'reference' => $reference,
                'provider' => $provider,
                'deeplink' => 'koffre://payment/success?reference=' . $reference,
            ]);
        }

        return view('contributions.cancel', [
            'event' => $event,
            'message' => 'Le paiement de votre cotisation a été interrompu.',
            'deeplink' => 'koffre://payment/failed?reference=' . $reference,
        ]);
    }

    private function handleSuccess($contribution, $cagnotte, ?string $reference, string $provider)
    {
        if ($contribution && $contribution->payment_status !== 'success') {
            try {
                $this->contributionService->complete($contribution->payment_reference);
                $contribution->refresh();
            } catch (\Throwable $e) {
                Log::error('Web Callback Error', ['error' => $e->getMessage()]);
            }
        }

        return view('contributions.success', [
            'contribution' => $contribution,
            'cagnotte' => $cagnotte,
            'reference' => $reference,
            'provider' => $provider,
            'deeplink' => 'koffre://payment/success?reference=' . $reference,
        ]);
    }

    private function handleFailure(string $event, $contribution, $cagnotte)
    {
        if ($contribution && $contribution->payment_status === 'pending') {
            $contribution->update(['payment_status' => 'failed']);
        }

        $messages = [
            'payment.failed' => 'Le paiement a échoué.',
            'payment.cancelled' => 'Le paiement a été annulé.',
            'payment.expired' => 'La session a expiré.',
        ];

        return view('contributions.cancel', [
            'event' => $event,
            'message' => $messages[$event] ?? 'Paiement interrompu.',
            'cagnotte' => $cagnotte,
            'deeplink' => 'koffre://payment/failed?event=' . $event,
        ]);
    }

    private function inferEventFromLegacyParams(?string $token, ?string $status): string
    {
        if (!$token && !$status)
            return 'payment.initiated';
        return match (strtolower($status ?? '')) {
            'success', 'completed', 'paid' => 'payment.success',
            'failed', 'error' => 'payment.failed',
            'cancelled', 'canceled' => 'payment.cancelled',
            'expired' => 'payment.expired',
            default => $token ? 'payment.success' : 'payment.initiated',
        };
    }
}
