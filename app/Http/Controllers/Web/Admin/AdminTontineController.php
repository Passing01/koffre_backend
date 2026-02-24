<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\TontinePayment;
use App\Services\Audit\AuditService;
use Illuminate\Http\Request;

class AdminTontineController extends Controller
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    public function index(Request $request)
    {
        $query = Tontine::with(['user', 'members']);

        // Filtre par statut
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filtre par fréquence
        if ($request->filled('frequency')) {
            $query->where('frequency', $request->frequency);
        }

        // Recherche
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('fullname', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Tri
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $tontines = $query->paginate(20);

        $stats = [
            'total' => Tontine::count(),
            'active' => Tontine::where('status', 'active')->count(),
            'disabled' => Tontine::where('status', 'disabled')->count(),
        ];

        return view('admin.tontines.index', compact('tontines', 'stats'));
    }

    public function show(int $id)
    {
        $tontine = Tontine::with([
            'user',
            'members.user',
            'payments' => fn($q) => $q->orderByDesc('created_at'),
            'payouts' => fn($q) => $q->orderByDesc('created_at'),
        ])->findOrFail($id);

        $stats = [
            'total_members' => $tontine->members->count(),
            'active_members' => $tontine->members->where('status', 'accepted')->count(),
            'pending_members' => $tontine->members->where('status', 'pending')->count(),
            'total_collected' => $tontine->payments->where('status', 'success')->sum('amount'),
            'total_payouts' => $tontine->payouts->where('status', 'success')->sum('amount'),
        ];

        return view('admin.tontines.show', compact('tontine', 'stats'));
    }

    /**
     * Désactiver une tontine (l'admin ne peut PAS activer, seulement désactiver).
     */
    public function disable(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $tontine = Tontine::findOrFail($id);

        if ($tontine->status === 'disabled') {
            return back()->withErrors(['error' => 'Cette tontine est déjà désactivée.']);
        }

        $tontine->update([
            'status' => 'disabled',
            'moderation_reason' => $request->reason,
        ]);

        $this->auditService->log(
            action: 'tontine.disabled_by_admin',
            actorUserId: $request->user()->id,
            auditableType: 'tontine',
            auditableId: $tontine->id,
            metadata: ['reason' => $request->reason],
        );

        return back()->with('success', 'Tontine désactivée avec succès.');
    }

    /**
     * Réactiver une tontine préalablement désactivée par l'admin.
     */
    public function enable(Request $request, int $id)
    {
        $tontine = Tontine::findOrFail($id);

        if ($tontine->status !== 'disabled') {
            return back()->withErrors(['error' => 'Cette tontine n\'est pas désactivée.']);
        }

        $tontine->update([
            'status' => 'active',
            'moderation_reason' => null,
        ]);

        $this->auditService->log(
            action: 'tontine.enabled_by_admin',
            actorUserId: $request->user()->id,
            auditableType: 'tontine',
            auditableId: $tontine->id,
        );

        return back()->with('success', 'Tontine réactivée avec succès.');
    }
}
