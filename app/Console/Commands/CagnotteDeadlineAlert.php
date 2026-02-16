<?php

namespace App\Console\Commands;

use App\Models\Cagnotte;
use App\Services\Notifications\FcmService;
use Illuminate\Console\Command;

class CagnotteDeadlineAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cagnotte-deadline-alert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for cagnottes ending in 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(FcmService $fcmService)
    {
        $cagnottes = Cagnotte::query()
            ->where('status', 'active')
            ->where('notified_admin_24h', false)
            ->where('ends_at', '<=', now()->addDay())
            ->where('ends_at', '>', now())
            ->with('user')
            ->get();

        /** @var Cagnotte $cagnotte */
        foreach ($cagnottes as $cagnotte) {
            $fcmService->sendToUser(
                $cagnotte->user,
                "Bientôt la fin !",
                "Votre cagnotte '{$cagnotte->title}' se termine dans moins de 24 heures."
            );

            $cagnotte->update(['notified_admin_24h' => true]);
        }

        $this->info(count($cagnottes) . " cagnottes notified.");
    }
}
