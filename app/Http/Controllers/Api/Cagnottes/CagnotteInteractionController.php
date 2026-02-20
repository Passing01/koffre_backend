<?php

namespace App\Http\Controllers\Api\Cagnottes;

use App\Http\Controllers\Controller;
use App\Models\Cagnotte;
use App\Models\CagnotteComment;
use App\Models\CagnotteLike;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CagnotteInteractionController extends Controller
{
    // ─── Commentaires ────────────────────────────────────────────────────────────

    /**
     * Lister les commentaires d'une cagnotte
     */
    public function listComments(Request $request, int $id): JsonResponse
    {
        $cagnotte = Cagnotte::findOrFail($id);

        $comments = $cagnotte->comments()->orderByDesc('id')->get();

        return response()->json([
            'data' => $comments,
            'meta' => [
                'total' => $cagnotte->allComments()->count(),
            ],
        ]);
    }

    /**
     * Poster un commentaire sur une cagnotte
     */
    public function storeComment(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'body' => 'required|string|max:1000',
            'parent_id' => 'nullable|integer|exists:cagnotte_comments,id',
        ]);

        $cagnotte = Cagnotte::findOrFail($id);

        // Vérifier que le parent_id appartient bien à cette cagnotte
        if ($request->filled('parent_id')) {
            $parent = CagnotteComment::find($request->input('parent_id'));
            if (!$parent || $parent->cagnotte_id !== $cagnotte->id) {
                throw ValidationException::withMessages([
                    'parent_id' => ['Commentaire parent invalide.'],
                ]);
            }
        }

        $user = $request->user();

        $comment = CagnotteComment::create([
            'cagnotte_id' => $cagnotte->id,
            'user_id' => $user->id,
            'parent_id' => $request->input('parent_id'),
            'body' => $request->input('body'),
        ]);

        $comment->load('user:id,fullname,phone');

        return response()->json([
            'message' => 'Commentaire ajouté.',
            'data' => $comment,
        ], 201);
    }

    /**
     * Supprimer un commentaire (auteur uniquement)
     */
    public function deleteComment(Request $request, int $id, int $commentId): JsonResponse
    {
        $comment = CagnotteComment::where('cagnotte_id', $id)
            ->where('id', $commentId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $comment->delete();

        return response()->json(['message' => 'Commentaire supprimé.']);
    }

    // ─── Likes ───────────────────────────────────────────────────────────────────

    /**
     * Liker / Unliker une cagnotte (toggle)
     */
    public function toggleLike(Request $request, int $id): JsonResponse
    {
        $cagnotte = Cagnotte::findOrFail($id);
        $user = $request->user();

        $existing = CagnotteLike::where('cagnotte_id', $cagnotte->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
            $message = 'Like retiré.';
        } else {
            CagnotteLike::create([
                'cagnotte_id' => $cagnotte->id,
                'user_id' => $user->id,
            ]);
            $liked = true;
            $message = 'Cagnotte likée.';
        }

        $likesCount = CagnotteLike::where('cagnotte_id', $cagnotte->id)->count();

        return response()->json([
            'message' => $message,
            'data' => [
                'liked' => $liked,
                'likes_count' => $likesCount,
            ],
        ]);
    }

    /**
     * Vérifier si l'utilisateur a liké une cagnotte
     */
    public function checkLike(Request $request, int $id): JsonResponse
    {
        $cagnotte = Cagnotte::findOrFail($id);
        $user = $request->user();

        $liked = CagnotteLike::where('cagnotte_id', $cagnotte->id)
            ->where('user_id', $user->id)
            ->exists();

        $likesCount = CagnotteLike::where('cagnotte_id', $cagnotte->id)->count();

        return response()->json([
            'data' => [
                'liked' => $liked,
                'likes_count' => $likesCount,
            ],
        ]);
    }
}
