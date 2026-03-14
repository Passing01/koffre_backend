<?php

namespace App\Http\Controllers\Api\Tontines;

use App\Http\Controllers\Controller;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\TontinePayment;
use App\Services\Payments\PaymentServiceInterface;
use App\Services\Tontines\TontineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TontinePaymentController extends Controller
{
    public function __construct(
        private readonly TontineService $tontineService,
        private readonly PaymentServiceInterface $paymentService
    ) {
    }

    public function pay(int $tontineId, Request $request): JsonResponse
    {
        $result = $this->tontineService->initiatePayment($tontineId, $request->user());

        return response()->json([
            'message'       => 'Lien de paiement généré.',
            'payment_url'   => $result['payment_url'],
            'payment_token' => $result['payment_token'],
        ]);
    }

    /**
     * Relancer un paiement tontine en attente.
     * Retourne un nouveau lien de paiement sans dupliquer l'enregistrement.
     */
    public function retry(int $tontineId, string $reference, Request $request): JsonResponse
    {
        $user    = $request->user();
        $tontine = Tontine::findOrFail($tontineId);
        $member  = TontineMember::where('tontine_id', $tontineId)
            ->where('phone', $user->phone)
            ->firstOrFail();

        $payment = TontinePayment::where('tontine_id', $tontineId)
            ->where('tontine_member_id', $member->id)
            ->where('payment_reference', $reference)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            throw ValidationException::withMessages([
                'reference' => ['Aucun paiement en attente trouvé pour cette référence. Il a peut-être déjà été traité.'],
            ]);
        }

        $paymentData = $this->paymentService->initiatePayment(
            transactionId: $payment->payment_reference,
            amount: (float) $payment->total_charged,
            currency: $tontine->currency ?? 'XOF',
            description: "Cotisation Tontine: {$tontine->title} - Cycle #{$payment->cycle_number}",
            customer: [
                'name'  => $user->fullname,
                'email' => "user-{$user->id}@kofre.com",
                'phone' => $user->phone,
            ]
        );

        return response()->json([
            'message'       => 'Nouveau lien de paiement généré.',
            'payment_url'   => $paymentData['payment_url'],
            'payment_token' => $paymentData['payment_token'],
            'reference'     => $payment->payment_reference,
        ]);
    }
}
