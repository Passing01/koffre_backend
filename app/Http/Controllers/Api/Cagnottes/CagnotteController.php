<?php

namespace App\Http\Controllers\Api\Cagnottes;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cagnottes\AddParticipantRequest;
use App\Http\Requests\Cagnottes\StoreCagnotteRequest;
use App\Models\Cagnotte;
use App\Services\Cagnottes\CagnotteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CagnotteController extends Controller
{
    public function __construct(private readonly CagnotteService $cagnotteService)
    {
    }

    public function publics(): JsonResponse
    {
        $cagnottes = $this->cagnotteService->listPublic();

        return response()->json([
            'data' => $cagnottes,
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $user = $request->user();

        $cagnottes = $this->cagnotteService->listMine($user);

        return response()->json([
            'data' => $cagnottes,
        ]);
    }

    public function store(StoreCagnotteRequest $request): JsonResponse
    {
        $user = $request->user();

        $cagnotte = $this->cagnotteService->create($user, $request->validated());

        return response()->json([
            'message' => 'Cagnotte créée.',
            'data' => $cagnotte,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $details = $this->cagnotteService->getDetails($id, $user);

        return response()->json([
            'data' => $details,
        ]);
    }

    public function addParticipant(AddParticipantRequest $request, int $id): JsonResponse
    {
        $user = $request->user();

        $cagnotte = Cagnotte::query()->findOrFail($id);

        $participant = $this->cagnotteService->addParticipant(
            cagnotte: $cagnotte,
            user: $user,
            phone: $request->string('phone')->toString(),
        );

        return response()->json([
            'message' => 'Participant ajouté.',
            'data' => $participant,
        ]);
    }
}
