<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tontine;
use App\Models\Earning;
use App\Services\Payments\PaymentServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AdminPlatformEarningController extends Controller
{
    /**
     * Section transactions plateforme (commissions tontine).
     */
    public function index(Request $request)
    {
        $query = Earning::query();

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $pendingEarningsSum = Earning::query()->whereNull('transferred_at')->sum('amount');
        $earnings = $query->orderByDesc('created_at')->paginate(50);

        $totalFees = Earning::query()->sum('amount');

        $modules = [
            'cagnotte' => 'Cagnottes',
            'tontine' => 'Tontines',
        ];

        return view('admin.platform-earnings.index', compact('earnings', 'totalFees', 'modules', 'pendingEarningsSum'));
    }

    public function transfer(Request $request, PaymentServiceInterface $paymentService)
    {
        $pendingEarnings = Earning::query()->whereNull('transferred_at')->get();
        $totalAmount = $pendingEarnings->sum('amount');

        if ($totalAmount <= 0) {
            return back()->with('error', 'Aucun gain en attente de virement.');
        }

        $payoutAccount = config('services.platform.payout_phone');
        if (!$payoutAccount) {
            return back()->with('error', 'Le numéro de virement plateforme n\'est pas configuré (PLATFORM_PAYOUT_PHONE).');
        }

        try {
            $reference = 'ADMIN-EARN-' . time();
            $success = $paymentService->payout(
                account: $payoutAccount,
                amount: (float) $totalAmount,
                description: "Virement de commissions plateforme Kofre - Global",
                method: null // Utiliser méthode défaut
            );

            if (!$success) {
                return back()->with('error', 'Échec du virement via l\'opérateur de paiement.');
            }

            DB::transaction(function () use ($pendingEarnings, $reference) {
                foreach ($pendingEarnings as $earning) {
                    $earning->update([
                        'transferred_at' => now(),
                        'transfer_reference' => $reference
                    ]);
                }
            });

            return back()->with('success', "Le virement de " . number_format($totalAmount, 0, ',', ' ') . " XOF a été initié avec succès vers {$payoutAccount}.");

        } catch (\Exception $e) {
            Log::error('Admin Transfer Error: ' . $e->getMessage());
            return back()->with('error', 'Erreur système lors du virement : ' . $e->getMessage());
        }
    }
}
