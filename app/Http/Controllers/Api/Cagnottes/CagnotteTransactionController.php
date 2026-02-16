<?php

namespace App\Http\Controllers\Api\Cagnottes;

use App\Http\Controllers\Controller;
use App\Services\Cagnottes\CagnotteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CagnotteTransactionController extends Controller
{
    public function __construct(private readonly CagnotteService $cagnotteService)
    {
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $cagnotte = $this->cagnotteService->getAccessibleOrFail($id, $user);

        $items = $cagnotte->transactions()->orderByDesc('id')->get();

        return response()->json([
            'data' => $items,
        ]);
    }
}
