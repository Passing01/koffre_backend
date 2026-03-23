<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use App\Models\TontinePayment;
use App\Models\TontinePayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class UserTransactionController extends Controller
{
    /**
     * Récupérer l'historique complet des transactions de l'utilisateur (Cagnottes et Tontines).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->integer('limit', 20);

        // 1. Contributions aux cagnottes (celles qu'il a faites)
        $contributions = Contribution::with('cagnotte:id,title')
            ->where('user_id', $user->id)
            ->where('payment_status', 'success')
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'type' => 'contribution',
                    'category' => 'cagnotte',
                    'amount' => (float) $c->amount,
                    'direction' => 'out', // Il donne de l'argent
                    'status' => $c->payment_status,
                    'date' => $c->paid_at ?? $c->created_at,
                    'label' => "Contribution : " . ($c->cagnotte?->title ?? 'Cagnotte'),
                    'reference' => $c->payment_reference,
                    'metadata' => [
                        'cagnotte_id' => $c->cagnotte_id,
                    ]
                ];
            });

        // 2. Paiements de tontine
        $memberIds = $user->tontineMembers()->pluck('id');
        $tontinePayments = TontinePayment::with('tontine:id,title')
            ->whereIn('tontine_member_id', $memberIds)
            ->where('status', 'success')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'type' => 'tontine_payment',
                    'category' => 'tontine',
                    'amount' => (float) $p->amount,
                    'direction' => 'out',
                    'status' => $p->status,
                    'date' => $p->paid_at ?? $p->created_at,
                    'label' => "Paiement Tontine : " . ($p->tontine?->title ?? 'Tontine'),
                    'reference' => $p->payment_reference,
                    'metadata' => [
                        'tontine_id' => $p->tontine_id,
                        'cycle' => $p->cycle_number,
                    ]
                ];
            });

        // 3. Reversements de tontine (Réceptions)
        $tontinePayouts = TontinePayout::with('tontine:id,title')
            ->whereIn('tontine_member_id', $memberIds)
            ->where('status', 'success')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'type' => 'tontine_payout',
                    'category' => 'tontine',
                    'amount' => (float) $p->amount,
                    'direction' => 'in', // Il reçoit de l'argent
                    'status' => $p->status,
                    'date' => $p->paid_at ?? $p->created_at,
                    'label' => "Réception Tontine : " . ($p->tontine?->title ?? 'Tontine'),
                    'reference' => $p->payout_reference,
                    'metadata' => [
                        'tontine_id' => $p->tontine_id,
                        'cycle' => $p->cycle_number,
                    ]
                ];
            });

        // Consolidation
        $allTransactions = collect()
            ->concat($contributions)
            ->concat($tontinePayments)
            ->concat($tontinePayouts)
            ->sortByDesc('date')
            ->values()
            ->take($limit);

        return response()->json([
            'data' => $allTransactions,
            'meta' => [
                'total' => $allTransactions->count(),
            ]
        ]);
    }
}
