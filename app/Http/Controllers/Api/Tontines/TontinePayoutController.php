<?php

namespace App\Http\Controllers\Api\Tontines;

use App\Http\Controllers\Controller;
use App\Services\Tontines\TontineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TontinePayoutController extends Controller
{
    public function __construct(private readonly TontineService $tontineService)
    {
    }

    /**
     * Approuver le transfert malgré des impayés (le créateur accepte de bloquer les membres impayés).
     */
    public function approve(Request $request, int $id, int $cycle): JsonResponse
    {
        $request->validate([
            'accept_blocking' => 'required|accepted',
        ], [
            'accept_blocking.accepted' => 'Vous devez accepter de bloquer les membres impayés pour procéder.',
        ]);

        $this->tontineService->approvePayoutWithBlocking($id, $cycle, $request->user());

        return response()->json([
            'message' => 'Transfert approuvé. Les membres impayés ont été bloqués.',
        ]);
    }

    /**
     * Relancer manuellement le paiement d'un cycle (par un admin).
     */
    public function retryPayout(Request $request, int $id, int $cycle): JsonResponse
    {
        $this->tontineService->processPayoutByAdmin($id, $cycle, $request->user());

        return response()->json([
            'message' => 'Le virement a été relancé avec succès.',
        ]);
    }
}
