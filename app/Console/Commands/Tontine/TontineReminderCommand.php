<?php

namespace App\Console\Commands\Tontine;

use Illuminate\Console\Command;

class TontineReminderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tontine:remind';
    protected $description = 'Send reminders to tontine members before the payment deadline.';

    public function handle(\App\Models\Tontine $tontineModel, \App\Services\Notifications\FcmService $fcmService)
    {
        $tontines = \App\Models\Tontine::where('status', 'active')->get();

        foreach ($tontines as $tontine) {
            /** @var \App\Models\Tontine $tontine */
            $nextDueDate = $this->calculateNextDueDate($tontine);
            if (!$nextDueDate)
                continue;

            $daysUntil = now()->startOfDay()->diffInDays(\Illuminate\Support\Carbon::parse($nextDueDate)->startOfDay(), false);

            $settings = $tontine->notification_settings ?? []; // Array of days, e.g. [1, 2, 3]

            if (in_array($daysUntil, $settings)) {
                $cycle = $this->calculateCurrentCycle($tontine);
                $members = $tontine->members()->where('status', 'accepted')->get();

                foreach ($members as $member) {
                    $hasPaid = $tontine->payments()
                        ->where('tontine_member_id', $member->id)
                        ->where('cycle_number', $cycle)
                        ->where('status', 'success')
                        ->exists();

                    if (!$hasPaid && $member->user) {
                        $fcmService->sendToUser(
                            $member->user,
                            "Rappel de paiement",
                            "Votre épargne de {$tontine->amount_per_installment} {$tontine->currency} pour '{$tontine->title}' est attendue dans {$daysUntil} jour(s)."
                        );
                    }
                }
            }

            // Notification de retrait pour les tontines individuelles
            if ($tontine->type === 'individual' && $tontine->target_payout_date) {
                // Notifier le jour même si la date est atteinte
                if (now()->startOfDay()->equalTo(\Illuminate\Support\Carbon::parse($tontine->target_payout_date)->startOfDay()) && $tontine->user) {
                    $fcmService->sendToUser(
                        $tontine->user,
                        "C'est le jour J !",
                        "Félicitations, votre tontine individuelle '{$tontine->title}' a atteint sa date de maturité. Vous pouvez retirer vos fonds dès maintenant !"
                    );
                }
            }
        }

        $this->info('Reminders sent.');
    }

    private function calculateNextDueDate(\App\Models\Tontine $tontine)
    {
        $start = $tontine->starts_at;
        $now = now();

        if ($now->lt($start))
            return $start;

        $cycle = $this->calculateCurrentCycle($tontine);
        $next = $start->copy();

        $freq = $tontine->contribution_frequency ?: $tontine->frequency;
        $num = $tontine->contribution_frequency_number ?: $tontine->frequency_number;

        switch ($freq) {
            case 'days':
                $next->addDays($cycle * $num);
                break;
            case 'weeks':
                $next->addWeeks($cycle * $num);
                break;
            case 'months':
                $next->addMonths($cycle * $num);
                break;
        }

        return $next;
    }

    private function calculateCurrentCycle(\App\Models\Tontine $tontine): int
    {
        $now = now();
        $start = $tontine->starts_at;

        if ($now->lt($start))
            return 1;

        $freq = $tontine->contribution_frequency ?: $tontine->frequency;
        $num = $tontine->contribution_frequency_number ?: $tontine->frequency_number;

        $diff = 0;
        switch ($freq) {
            case 'days':
                $diff = $start->diffInDays($now);
                break;
            case 'weeks':
                $diff = $start->diffInWeeks($now);
                break;
            case 'months':
                $diff = $start->diffInMonths($now);
                break;
        }

        return (int) floor($diff / max(1, $num)) + 1;
    }
}
