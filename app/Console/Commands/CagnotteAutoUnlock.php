<?php

namespace App\Console\Commands;

use App\Models\Cagnotte;
use App\Models\User;
use App\Services\Cagnottes\CagnotteService;
use Illuminate\Console\Command;

class CagnotteAutoUnlock extends Command
{
    protected $signature = 'cagnotte:auto-unlock';
    protected $description = 'Débloquer et verser automatiquement les cagnottes privées après 10 minutes d\'attente admin.';

    public function handle(CagnotteService $cagnotteService)
    {
        $threshold = now()->subMinutes(10);

        $pendingCagnottes = Cagnotte::query()
            ->where('is_private_coffre', true)
            ->where('unlock_status', 'pending')
            ->where('unlock_requested_at', '<=', $threshold)
            ->get();

        if ($pendingCagnottes->isEmpty()) {
            $this->info('Aucune cagnotte en attente de déblocage auto.');
            return;
        }

        foreach ($pendingCagnottes as $cagnotte) {
            $this->info("Déblocage auto pour la cagnotte #{$cagnotte->id}");

            try {
                // 1. Approuver (sans admin, on peut passer un null ou l'admin système)
                $admin = User::where('is_admin', true)->first();
                $cagnotteService->approveUnlock($cagnotte->id, $admin);
                
                // 2. Verser l'argent immédiatement
                $cagnotteService->processPayout($cagnotte->id);

                $this->info("Cagnotte #{$cagnotte->id} débloquée et versée avec succès.");
            } catch (\Exception $e) {
                $this->error("Erreur pour la cagnotte #{$cagnotte->id}: " . $e->getMessage());
            }
        }
    }
}
