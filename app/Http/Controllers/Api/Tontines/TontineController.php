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
        $tontines = $this->tontineService->listMine($request->user());

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

    public function show(int $id, Request $request): JsonResponse
    {
        $details = $this->tontineService->getDetails($id, $request->user());

        return response()->json([
            'data' => $details,
        ]);
    }

    public function update(int $id, UpdateTontineRequest $request): JsonResponse
    {
        $tontine = $this->tontineService->update($id, $request->user(), $request->validated());

        return response()->json([
            'message' => 'Paramètres de la tontine mis à jour.',
            'data' => $tontine,
        ]);
    }

    public function addMember(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|string',
            'payout_rank' => 'nullable|integer',
            'permissions' => 'nullable|array',
        ]);

        $member = $this->tontineService->addMember($id, $request->user(), $request->only(['phone', 'payout_rank', 'permissions']));

        return response()->json([
            'message' => 'Membre invité.',
            'data' => $member,
        ]);
    }

    public function setRanks(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'ranks' => 'required|array',
            'ranks.*.phone' => 'required|string',
            'ranks.*.rank' => 'required|integer|min:1',
        ]);

        $this->tontineService->setRanks($id, $request->user(), $request->input('ranks'));

        return response()->json([
            'message' => 'L\'ordre de prise a été mis à jour.',
        ]);
    }

    public function updateMemberPermissions(int $id, string $phone, Request $request): JsonResponse
    {
        $request->validate([
            'permissions' => 'required|array',
        ]);

        $member = $this->tontineService->updateMemberPermissions($id, $request->user(), $phone, $request->input('permissions'));

        return response()->json([
            'message' => 'Permissions mises à jour.',
            'data' => $member,
        ]);
    }
}
