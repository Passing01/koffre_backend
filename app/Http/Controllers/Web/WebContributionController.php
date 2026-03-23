<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cagnotte;
use App\Models\CagnotteComment;
use App\Services\Contributions\ContributionService;
use Illuminate\Http\Request;
use App\Models\CagnotteLike;
use Illuminate\Support\Facades\Cookie;

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

        $hasLiked = Cookie::get('liked_cagnotte_' . $id) ? true : false;
        return view('contributions.show', compact('cagnotte', 'comments', 'stats', 'hasLiked'));
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

    public function toggleLike(int $id)
    {
        $cagnotte = Cagnotte::findOrFail($id);
        $cookieName = 'liked_cagnotte_' . $id;
        
        if (Cookie::get($cookieName)) {
            return response()->json(['message' => 'Déjà aimé'], 422);
        }

        // Si on n'est pas loggé, on crée un like "anonyme" dans les stats
        // Pour ne pas risquer de bloquer le DB migration pour l'instant,
        // on peut aussi utiliser un champ likes_count séparé ou juste simuler
        // Mais mieux: on essaie de créer l'entrée si possible, sinon on incrémente juste en JS/Stats.
        
        // Pour l'instant on va rester simple et simuler l'incrément si la DB n'est pas prête
        // Ou mieux: on gère juste le retour succès pour le front
        
        // On définit le cookie pour 1 an
        $cookie = Cookie::make($cookieName, '1', 60 * 24 * 365);

        // Sauvegarder le like dans la base (user_id est maintenant nullable grâce à la migration)
        CagnotteLike::create([
            'cagnotte_id' => $cagnotte->id,
            'user_id' => null,
        ]);
        
        return response()->json([
            'message' => 'Cagnotte aimée !',
            'likes_count' => $cagnotte->likes()->count()
        ])->withCookie($cookie);
    }
}
