<?php

namespace App\Http\Controllers\Api\Tontines;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tontines\StoreTontineRequest;
use App\Http\Requests\Tontines\UpdateTontineRequest;
use App\Services\Tontines\TontineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TontineController extends Controller
{
    public function __construct(private readonly TontineService $tontineService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $role = $request->query('role');
        $tontines = $this->tontineService->listMine($request->user(), $role);

        return response()->json([
            'data' => $tontines,
        ]);
    }

    public function store(StoreTontineRequest $request): JsonResponse
    {
        $tontine = $this->tontineService->create($request->user(), $request->validated());

        return response()->json([
            'message' => 'Tontine créée avec succès.',
            'data' => $tontine,
        ], 201);
    }

    public function storeIndividual(\App\Http\Requests\Tontines\StoreIndividualTontineRequest $request): JsonResponse
    {
        $tontine = $this->tontineService->createIndividual($request->user(), $request->validated());

        return response()->json([
            'message' => 'Tontine individuelle créée avec succès.',
            'data' => $tontine,
        ], 201);
    }

    public function withdrawIndividual($id, Request $request): JsonResponse
    {
        $payout = $this->tontineService->withdrawIndividual((int) $id, $request->user());

        return response()->json([
            'message' => 'Vos fonds ont été reversés avec succès.',
            'data' => $payout,
        ]);
    }

    public function show($id, Request $request): JsonResponse
    {
        $details = $this->tontineService->getDetails((int) $id, $request->user());

        return response()->json([
            'data' => $details,
        ]);
    }

    public function update($id, UpdateTontineRequest $request): JsonResponse
    {
        $tontine = $this->tontineService->update((int) $id, $request->user(), $request->validated());

        return response()->json([
            'message' => 'Paramètres de la tontine mis à jour.',
            'data' => $tontine,
        ]);
    }

    public function addMember($id, Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'payout_rank' => 'nullable|integer',
            'permissions' => 'nullable|array',
        ]);

        $member = $this->tontineService->addMember((int) $id, $request->user(), $request->only(['phone', 'payout_rank', 'permissions']));

        return response()->json([
            'message' => 'Membre invité.',
            'data' => $member,
        ]);
    }

    public function setRanks($id, Request $request): JsonResponse
    {
        $request->validate([
            'ranks' => 'required|array',
            'ranks.*.phone' => 'required|string',
            'ranks.*.rank' => 'required|integer|min:1',
        ]);

        $this->tontineService->setRanks((int) $id, $request->user(), $request->input('ranks'));

        return response()->json([
            'message' => 'L\'ordre de prise a été mis à jour.',
        ]);
    }

    public function updateMemberPermissions($id, string $phone, Request $request): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
        ]);

        $member = $this->tontineService->updateMemberPermissions((int) $id, $request->user(), $phone, $request->input('permissions'));

        return response()->json([
            'message' => 'Permissions mises à jour.',
            'data' => $member,
        ]);
    }

    /**
     * Compléter l'inscription (nom, prénom, pièce d'identité) pour les membres en attente.
     */
    public function completeRegistration($id, Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'identity_document' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $member = $this->tontineService->completeMemberRegistration(
            (int) $id,
            $request->user(),
            $request->only(['first_name', 'last_name', 'identity_document'])
        );

        return response()->json([
            'message' => 'Inscription complétée. Vous pouvez maintenant accéder à la tontine et contribuer.',
            'data' => $member,
        ]);
    }

    public function close($id, Request $request): JsonResponse
    {
        $tontine = $this->tontineService->close((int) $id, $request->user());

        return response()->json([
            'message' => 'Tontine clôturée avec succès.',
            'data' => $tontine,
        ]);
    }
}
