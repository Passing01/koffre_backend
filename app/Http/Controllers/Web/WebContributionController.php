<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Cagnotte;
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
        $cagnotte = Cagnotte::findOrFail($id);

        if ($cagnotte->status !== 'active') {
            return view('contributions.closed', compact('cagnotte'));
        }

        return view('contributions.show', compact('cagnotte'));
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
}
