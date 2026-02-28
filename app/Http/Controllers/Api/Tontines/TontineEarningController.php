<?php

namespace App\Http\Controllers\Api\Tontines;

use App\Http\Controllers\Controller;
use App\Models\TontineEarning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TontineEarningController extends Controller
{
    /**
     * Liste des commissions du créateur (tontines dont il est propriétaire).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = TontineEarning::query()
            ->where('user_id', $user->id)
            ->where('type', TontineEarning::TYPE_CREATOR_COMMISSION)
            ->with(['tontine:id,title', 'tontinePayout:id,cycle_number,paid_at']);

        if ($request->filled('tontine_id')) {
            $query->where('tontine_id', $request->tontine_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $earnings = $query->orderByDesc('created_at')->paginate(20);

        $total = TontineEarning::query()
            ->where('user_id', $user->id)
            ->where('type', TontineEarning::TYPE_CREATOR_COMMISSION)
            ->sum('amount');

        return response()->json([
            'data' => $earnings,
            'meta' => [
                'total_earnings' => (float) $total,
            ],
        ]);
    }
}
