<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tontine;
use App\Models\TontineEarning;
use Illuminate\Http\Request;

class AdminPlatformEarningController extends Controller
{
    /**
     * Section transactions plateforme (commissions tontine).
     */
    public function index(Request $request)
    {
        $query = TontineEarning::query()
            ->whereNull('user_id')
            ->where('type', TontineEarning::TYPE_PLATFORM_FEE)
            ->with(['tontine:id,title', 'tontinePayout:id,cycle_number', 'tontinePayment:id,payment_reference']);

        if ($request->filled('tontine_id')) {
            $query->where('tontine_id', $request->tontine_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $earnings = $query->orderByDesc('created_at')->paginate(30);

        $totalFees = TontineEarning::query()
            ->whereNull('user_id')
            ->where('type', TontineEarning::TYPE_PLATFORM_FEE)
            ->sum('amount');

        $tontines = Tontine::select('id', 'title')->orderBy('title')->get();

        return view('admin.platform-earnings.index', compact('earnings', 'totalFees', 'tontines'));
    }
}
