<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Cagnotte;
use Illuminate\Http\Request;

class AdminTransactionController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with(['cagnotte', 'contribution']);

        // Filtres
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        if ($request->has('cagnotte_id') && $request->cagnotte_id !== '') {
            $query->where('cagnotte_id', $request->cagnotte_id);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('cagnotte', function ($cagnotteQuery) use ($search) {
                        $cagnotteQuery->where('title', 'like', "%{$search}%");
                    });
            });
        }

        // Filtre par date
        if ($request->has('date_from') && $request->date_from !== '') {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to !== '') {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $transactions = $query->paginate(50);

        // Statistiques
        $stats = [
            'total_transactions' => Transaction::count(),
            'total_amount' => Transaction::sum('amount'),
            'credit_total' => Transaction::where('type', 'credit')->sum('amount'),
            'debit_total' => Transaction::where('type', 'debit')->sum('amount'),
        ];

        // Liste des cagnottes pour le filtre
        $cagnottes = Cagnotte::select('id', 'title')->orderBy('title')->get();

        return view('admin.transactions.index', compact('transactions', 'stats', 'cagnottes'));
    }
}
