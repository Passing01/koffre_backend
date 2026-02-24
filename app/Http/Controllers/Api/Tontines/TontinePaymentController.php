<?php

namespace App\Http\Controllers\Api\Tontines;

use App\Http\Controllers\Controller;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Services\Payments\PaymentServiceInterface;
use App\Services\Tontines\TontineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TontinePaymentController extends Controller
{
    public function __construct(
        private readonly TontineService $tontineService
    ) {
    }

    public function pay(int $tontineId, Request $request): JsonResponse
    {
        $result = $this->tontineService->initiatePayment($tontineId, $request->user());

        return response()->json([
            'message' => 'Lien de paiement généré.',
            'payment_url' => $result['payment_url'],
            'payment_token' => $result['payment_token'],
        ]);
    }
}
