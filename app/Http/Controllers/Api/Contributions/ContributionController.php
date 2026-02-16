<?php

namespace App\Http\Controllers\Api\Contributions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contributions\SimulateContributionRequest;
use App\Services\Contributions\ContributionSimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContributionController extends Controller
{
    public function __construct(
        private readonly ContributionSimulationService $simulationService,
        private readonly \App\Services\Contributions\ContributionService $contributionService
    ) {
    }

    public function initiate(\App\Http\Requests\Contributions\StoreContributionRequest $request): JsonResponse
    {
        $actor = $request->user();

        $result = $this->contributionService->initiate(
            cagnotteId: (int) $request->input('cagnotte_id'),
            amount: (float) $request->input('amount'),
            actor: $actor,
            contributorName: $request->string('contributor_name')->toString() ?: null,
        );

        return response()->json([
            'message' => 'Paiement initialisé.',
            'data' => $result,
        ], 201);
    }

    public function simulate(SimulateContributionRequest $request): JsonResponse
    {
        $actor = $request->user();

        $result = $this->simulationService->simulate(
            cagnotteId: (int) $request->input('cagnotte_id'),
            amount: (float) $request->input('amount'),
            actor: $actor,
            contributorName: $request->string('contributor_name')->toString() ?: null,
            paymentMethod: $request->string('payment_method')->toString() ?: null,
        );

        return response()->json([
            'message' => 'Contribution simulée avec succès.',
            'data' => $result,
        ], 201);
    }

    public function listMine(Request $request): JsonResponse
    {
        $actor = $request->user();

        $items = $actor
            ? $actor->contributions()->with('cagnotte')->orderByDesc('id')->get()
            : collect();

        return response()->json([
            'data' => $items,
        ]);
    }
}
