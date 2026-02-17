<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cagnotte;
use App\Models\Contribution;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Statistiques générales
        $stats = [
            'total_cagnottes' => Cagnotte::count(),
            'active_cagnottes' => Cagnotte::where('status', 'active')->count(),
            'completed_cagnottes' => Cagnotte::where('status', 'completed')->count(),
            'total_users' => User::count(),
            'total_contributions' => Contribution::count(),
            'total_amount_collected' => Contribution::where('payment_status', 'success')->sum('amount'),
            'pending_contributions' => Contribution::where('payment_status', 'pending')->count(),
            'failed_contributions' => Contribution::where('payment_status', 'failed')->count(),
        ];

        // Cagnottes récentes
        $recent_cagnottes = Cagnotte::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Contributions récentes
        $recent_contributions = Contribution::with(['cagnotte', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Transactions récentes
        $recent_transactions = Transaction::with('cagnotte')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Statistiques par jour (7 derniers jours)
        $daily_stats = Contribution::where('payment_status', 'success')
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recent_cagnottes',
            'recent_contributions',
            'recent_transactions',
            'daily_stats'
        ));
    }
}
