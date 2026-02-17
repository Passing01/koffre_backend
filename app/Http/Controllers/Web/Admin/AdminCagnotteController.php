<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cagnotte;
use Illuminate\Http\Request;

class AdminCagnotteController extends Controller
{
    public function index(Request $request)
    {
        $query = Cagnotte::with(['user', 'contributions', 'participants']);

        // Filtres
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('visibility') && $request->visibility !== '') {
            $query->where('visibility', $request->visibility);
        }

        if ($request->has('search') && $request->search !== '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('fullname', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $cagnottes = $query->paginate(20);

        return view('admin.cagnottes.index', compact('cagnottes'));
    }

    public function show($id)
    {
        $cagnotte = Cagnotte::with([
            'user',
            'participants',
            'contributions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'contributions.user',
            'transactions' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        // Statistiques de la cagnotte
        $stats = [
            'total_contributions' => $cagnotte->contributions->count(),
            'successful_contributions' => $cagnotte->contributions->where('payment_status', 'success')->count(),
            'pending_contributions' => $cagnotte->contributions->where('payment_status', 'pending')->count(),
            'failed_contributions' => $cagnotte->contributions->where('payment_status', 'failed')->count(),
            'total_participants' => $cagnotte->participants->count(),
            'progress_percentage' => $cagnotte->target_amount > 0
                ? round(($cagnotte->current_amount / $cagnotte->target_amount) * 100, 2)
                : 0,
        ];

        return view('admin.cagnottes.show', compact('cagnotte', 'stats'));
    }
}
