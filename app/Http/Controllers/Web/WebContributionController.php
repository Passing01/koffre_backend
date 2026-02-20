<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cagnotte;
use App\Models\CagnotteComment;
use App\Services\Contributions\ContributionService;
use Illuminate\Http\Request;

class WebContributionController extends Controller
{
    public function __construct(
        private readonly ContributionService $contributionService
    ) {
    }

    public function show(int $id)
    {
        $cagnotte = Cagnotte::with(['user:id,fullname,phone', 'comments.user:id,fullname,phone', 'comments.replies.user:id,fullname,phone'])->findOrFail($id);

        if ($cagnotte->status !== 'active') {
            return view('contributions.closed', compact('cagnotte'));
        }

        $comments = $cagnotte->comments()->orderByDesc('id')->get();
        $stats = $cagnotte->stats;

        return view('contributions.show', compact('cagnotte', 'comments', 'stats'));
    }

    public function contribute(Request $request, int $id)
    {
        $request->validate([
            'contributor_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:100',
        ]);

        try {
            $result = $this->contributionService->initiate(
                cagnotteId: $id,
                amount: (float) $request->input('amount'),
                actor: null, // Web guest contribution
                contributorName: $request->input('contributor_name'),
            );

            return redirect($result['payment_url']);
        } catch (\Exception $e) {
            return back()->with('error', 'Une erreur est survenue lors de l\'initialisation du paiement : ' . $e->getMessage());
        }
    }

    public function storeComment(Request $request, int $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'body' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer|exists:cagnotte_comments,id',
        ]);

        $cagnotte = Cagnotte::findOrFail($id);

        CagnotteComment::create([
            'cagnotte_id' => $cagnotte->id,
            'user_id' => null,
            'contributor_name' => $request->input('name'),
            'parent_id' => $request->input('parent_id'),
            'body' => $request->input('body'),
        ]);

        return back()->with('success', 'Commentaire ajouté avec succès !');
    }
}
