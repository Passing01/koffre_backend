<?php

namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Obtenir les informations de profil de l'utilisateur connecté.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // On peut ajouter ici des statistiques si nécessaire
        $stats = [
            'total_cagnottes' => $user->cagnottes()->count(),
            'total_contributions' => $user->contributions()->count(),
            'total_amount_contributed' => (float) $user->contributions()->where('payment_status', 'success')->sum('amount'),
        ];

        return response()->json([
            'data' => [
                'user' => $user,
                'stats' => $stats,
                'archived_cagnottes' => $user->cagnottes()->where('is_archived', true)->get(),
            ],
        ]);
    }

    /**
     * Mettre à jour les informations de profil.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'fullname' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'max:5'],
            // Ajoutez d'autres champs si nécessaire
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès.',
            'data' => $user,
        ]);
    }
}
