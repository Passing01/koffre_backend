<?php

namespace App\Services\Tontines;

use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\FcmService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TontineService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly FcmService $fcmService,
        private readonly \App\Services\Payments\PaymentServiceInterface $paymentService
    ) {
    }

    public function listMine(User $user, ?string $role = null): Collection
    {
        $query = Tontine::query();

        if ($role === 'creator') {
            $query->where('user_id', $user->id);
        } elseif ($role === 'member') {
            $query->whereHas('members', function ($q) use ($user) {
                $q->where('phone', $user->phone);
            })->where('user_id', '!=', $user->id);
        } else {
            // Unified list
            $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhereHas('members', function ($m) use ($user) {
                        $m->where('phone', $user->phone);
                    });
            });
        }

        return $query->with(['members', 'user:id,fullname,phone'])
            ->orderByDesc('id')
            ->get();
    }

    public function create(User $user, array $data): Tontine
    {
        return DB::transaction(function () use ($user, $data) {
            $data['user_id'] = $user->id;

            $members = $data['members'] ?? [];
            unset($data['members']);

            if (isset($data['identity_document'])) {
                $data['identity_document_path'] = $data['identity_document']->store('tontines/documents', 'public');
                unset($data['identity_document']);
            }

            $tontine = Tontine::query()->create($data);

            // Add the creator as the first member with high permissions usually
            TontineMember::query()->create([
                'tontine_id' => $tontine->id,
                'phone' => $user->phone,
                'user_id' => $user->id,
                'status' => 'accepted',
                'payout_rank' => 1,
                'permissions' => ['is_admin' => true, 'can_invite' => true, 'can_view_stats' => true],
            ]);

            foreach ($members as $memberData) {
                // Skip if it's the creator's phone
                if ($memberData['phone'] === $user->phone)
                    continue;

                $invitedUser = User::where('phone', $memberData['phone'])->first();

                $requiresReg = (bool) ($tontine->requires_member_registration ?? false);
                TontineMember::query()->create([
                    'tontine_id' => $tontine->id,
                    'phone' => $memberData['phone'],
                    'user_id' => $invitedUser?->id,
                    'payout_rank' => $memberData['payout_rank'] ?? null,
                    'permissions' => $memberData['permissions'] ?? ['can_view_stats' => true],
                    'status' => $requiresReg ? 'pending' : 'accepted',
                ]);

                if ($invitedUser) {
                    $this->fcmService->sendToUser(
                        $invitedUser,
                        "Invitation Tontine",
                        "{$user->fullname} vous a invité à rejoindre la tontine '{$tontine->title}'."
                    );
                }
            }

            $this->auditService->log(
                action: 'tontine.created',
                actorUserId: $user->id,
                auditableType: 'tontine',
                auditableId: $tontine->id,
                metadata: [
                    'amount' => $tontine->amount_per_installment,
                    'frequency' => $tontine->frequency,
                    'members_count' => count($members) + 1,
                ],
            );

            return $tontine->load('members');
        });
    }

    public function update(int $tontineId, User $user, array $data): Tontine
    {
        $tontine = Tontine::query()->findOrFail($tontineId);

        if ((int) $tontine->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'tontine_id' => ['Seul le créateur peut modifier cette tontine.'],
            ]);
        }

        $tontine->update($data);

        $this->auditService->log(
            action: 'tontine.updated',
            actorUserId: $user->id,
            auditableType: 'tontine',
            auditableId: $tontine->id,
            metadata: array_keys($data),
        );

        return $tontine->fresh(['members']);
    }

    public function getDetails(int $tontineId, User $user): array
    {
        $tontine = Tontine::query()
            ->with(['members.user:id,fullname,phone', 'user:id,fullname,phone'])
            ->findOrFail($tontineId);

        $membership = $tontine->members()->where('phone', $user->phone)->first();
        $isMember = (bool) $membership;
        $isCreator = (int) $tontine->user_id === (int) $user->id;

        if (!$isMember && !$isCreator) {
            throw ValidationException::withMessages([
                'tontine_id' => ['Accès refusé.'],
            ]);
        }

        // Membre en attente (requires_registration) : pas accès aux détails complets
        if ($membership && $membership->status === 'pending' && $tontine->requires_member_registration) {
            return [
                'tontine' => $tontine->only(['id', 'title', 'amount_per_installment', 'currency']),
                'registration_required' => true,
                'message' => 'Complétez votre inscription (nom, prénom, pièce d\'identité) pour accéder aux détails.',
                'stats' => null,
                'members_stats' => [],
                'my_membership' => $membership,
            ];
        }

        $currentCycle = $this->calculateCurrentCycle($tontine);

        // Stats calculation
        $totalPaid = $tontine->payments()->where('status', 'success')->sum('amount');

        $stats = [
            'total_members' => $tontine->members()->count(),
            'active_members' => $tontine->members()->where('status', 'accepted')->count(),
            'total_paid' => $totalPaid,
            'current_cycle' => $currentCycle,
        ];

        // Member specific stats - fullname visible par tous, identity_document uniquement créateur/admin
        $isCreatorOrAdmin = $isCreator || $user->is_admin;
        $membersStats = $tontine->members->map(function ($member) use ($tontine, $currentCycle, $isCreatorOrAdmin) {
            $paidCycles = $tontine->payments()
                ->where('tontine_member_id', $member->id)
                ->where('status', 'success')
                ->count();

            $totalAmountPaid = $tontine->payments()
                ->where('tontine_member_id', $member->id)
                ->where('status', 'success')
                ->sum('amount');

            $row = [
                'member_id' => $member->id,
                'display_name' => $member->display_name,
                'phone' => $member->phone,
                'status' => $member->status,
                'payout_rank' => $member->payout_rank,
                'paid_cycles_count' => $paidCycles,
                'attendance_rate' => $currentCycle > 1 ? round(($paidCycles / ($currentCycle - 1)) * 100, 2) : 100,
                'total_contributed' => $totalAmountPaid,
                'payout_received' => $tontine->payouts()->where('tontine_member_id', $member->id)->where('status', 'success')->exists(),
            ];
            if ($isCreatorOrAdmin && $member->identity_document_path) {
                $row['identity_document_url'] = $member->identity_document_url;
            }
            return $row;
        });

        return [
            'tontine' => $tontine,
            'stats' => $stats,
            'members_stats' => $membersStats,
            'my_membership' => $tontine->members()->where('phone', $user->phone)->first(),
        ];
    }

    public function updateMemberPermissions(int $tontineId, User $user, string $memberPhone, array $permissions): TontineMember
    {
        $tontine = Tontine::query()->findOrFail($tontineId);

        if ((int) $tontine->user_id !== (int) $user->id) {
            throw ValidationException::withMessages(['tontine_id' => ['Seul le créateur peut modifier les permissions.']]);
        }

        $member = TontineMember::where('tontine_id', $tontineId)->where('phone', $memberPhone)->firstOrFail();
        $member->update(['permissions' => $permissions]);

        return $member;
    }

    public function addMember(int $tontineId, User $user, array $memberData): TontineMember
    {
        $tontine = Tontine::query()->findOrFail($tontineId);

        if ((int) $tontine->user_id !== (int) $user->id) {
            // Check if user has permission to invite
            $membership = TontineMember::where('tontine_id', $tontineId)->where('phone', $user->phone)->first();
            if (!$membership || !($membership->permissions['can_invite'] ?? false)) {
                throw ValidationException::withMessages(['tontine_id' => ['Vous n\'avez pas la permission d\'inviter des membres.']]);
            }
        }

        $invitedUser = User::where('phone', $memberData['phone'])->first();

        $requiresReg = (bool) ($tontine->requires_member_registration ?? false);
        $member = TontineMember::query()->updateOrCreate(
            ['tontine_id' => $tontineId, 'phone' => $memberData['phone']],
            [
                'user_id' => $invitedUser?->id,
                'payout_rank' => $memberData['payout_rank'] ?? null,
                'permissions' => $memberData['permissions'] ?? ['can_view_stats' => true],
                'status' => $requiresReg ? 'pending' : 'accepted',
            ]
        );

        if ($invitedUser) {
            $this->fcmService->sendToUser(
                $invitedUser,
                "Invitation Tontine",
                "Vous avez été invité à rejoindre la tontine '{$tontine->title}'."
            );
        }

        return $member;
    }

    /**
     * Compléter l'inscription d'un membre en attente (nom, prénom, pièce d'identité).
     * Le téléphone est déjà connu (récupéré à l'ajout).
     */
    public function completeMemberRegistration(int $tontineId, User $user, array $data): TontineMember
    {
        $tontine = Tontine::query()->findOrFail($tontineId);
        $member = TontineMember::where('tontine_id', $tontineId)->where('phone', $user->phone)->firstOrFail();

        if ($member->status !== 'pending' || !$tontine->requires_member_registration) {
            throw ValidationException::withMessages([
                'tontine_id' => ['Cette inscription n\'est pas requise ou déjà complétée.'],
            ]);
        }

        $identityPath = null;
        if (!empty($data['identity_document']) && $data['identity_document'] instanceof \Illuminate\Http\UploadedFile) {
            $identityPath = $data['identity_document']->store('tontines/members/documents', 'public');
        }

        $member->update([
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'identity_document_path' => $identityPath,
            'status' => 'accepted',
        ]);

        $this->auditService->log(
            action: 'tontine.member_registered',
            actorUserId: $user->id,
            auditableType: 'tontine',
            auditableId: $tontineId,
            metadata: ['member_id' => $member->id],
        );

        return $member->fresh();
    }

    private function calculateCurrentCycle(Tontine $tontine): int
    {
        if ($tontine->status !== 'active')
            return 0;

        $now = now();
        $start = $tontine->starts_at;

        if ($now->lt($start))
            return 0;

        $diff = 0;
        switch ($tontine->frequency) {
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

        return (int) floor($diff / $tontine->frequency_number) + 1;
    }

    public function setRanks(int $tontineId, User $user, array $ranks): void
    {
        $tontine = Tontine::query()->findOrFail($tontineId);

        if ((int) $tontine->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'tontine_id' => ['Seul le créateur peut définir le rang des prises.'],
            ]);
        }

        DB::transaction(function () use ($ranks, $tontineId) {
            foreach ($ranks as $rankData) {
                TontineMember::query()
                    ->where('tontine_id', $tontineId)
                    ->where('phone', $rankData['phone'])
                    ->update(['payout_rank' => $rankData['rank']]);
            }
        });

        $this->auditService->log(
            action: 'tontine.ranks_updated',
            actorUserId: $user->id,
            auditableType: 'tontine',
            auditableId: $tontineId,
            metadata: $ranks,
        );
    }

    public function initiatePayment(int $tontineId, User $user): array
    {
        $tontine = Tontine::query()->findOrFail($tontineId);
        $member = TontineMember::where('tontine_id', $tontineId)->where('phone', $user->phone)->firstOrFail();

        if ($member->status !== 'accepted') {
            throw ValidationException::withMessages([
                'tontine_id' => ['Vous devez compléter votre inscription avant de contribuer.'],
            ]);
        }

        if ($tontine->status !== 'active') {
            throw ValidationException::withMessages(['tontine_id' => ['La tontine n\'est pas active.']]);
        }

        $cycle = $this->calculateCurrentCycle($tontine);
        $reference = 'TON-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12));

        return DB::transaction(function () use ($tontine, $member, $cycle, $reference, $user) {
            $payment = \App\Models\TontinePayment::query()->create([
                'tontine_id' => $tontine->id,
                'tontine_member_id' => $member->id,
                'amount' => $tontine->amount_per_installment,
                'cycle_number' => $cycle,
                'payment_reference' => $reference,
                'status' => 'pending',
            ]);

            $paymentData = $this->paymentService->initiatePayment(
                transactionId: $reference,
                amount: (float) $tontine->amount_per_installment,
                currency: $tontine->currency ?? 'XOF',
                description: "Cotisation Tontine: {$tontine->title} - Cycle #{$cycle}",
                customer: [
                    'name' => $user->fullname,
                    'phone' => $user->phone,
                ]
            );

            // Ne pas écraser payment_reference : le webhook utilise metadata.order_id = notre référence (TON-xxx)

            return [
                'payment' => $payment,
                'payment_url' => $paymentData['payment_url'],
                'payment_token' => $paymentData['payment_token'],
            ];
        });
    }

    /**
     * @param string|array $reference Référence unique ou tableau (notre ref + ref GeniusPay) pour rétrocompatibilité
     */
    public function completePayment(string|array $reference): bool
    {
        $refs = is_array($reference) ? array_filter($reference) : [$reference];

        return DB::transaction(function () use ($refs) {
            $payment = \App\Models\TontinePayment::query()
                ->whereIn('payment_reference', $refs)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return false;
            }

            $payment->update(['status' => 'success']);
            $tontine = $payment->tontine;

            $this->auditService->log(
                action: 'tontine.payment_completed',
                actorUserId: $payment->member?->user_id,
                auditableType: 'tontine',
                auditableId: $tontine->id,
                metadata: [
                    'payment_id' => $payment->id,
                    'amount' => $payment->amount,
                    'cycle' => $payment->cycle_number,
                ],
            );

            // Notify Member
            if ($payment->member?->user) {
                $this->fcmService->sendToUser(
                    $payment->member->user,
                    "Cotisation confirmée",
                    "Votre paiement de {$payment->amount} XOF pour la tontine '{$tontine->title}' a été validé."
                );
            }

            // Check if cycle is complete and process payout
            $this->checkAndProcessPayout($tontine, $payment->cycle_number);

            return true;
        });
    }

    public function checkAndProcessPayout(Tontine $tontine, int $cycle): void
    {
        // 1. Check if everyone in this cycle has paid
        $expectedCount = $tontine->members()->where('status', 'accepted')->count();
        $paidCount = $tontine->payments()
            ->where('cycle_number', $cycle)
            ->where('status', 'success')
            ->count();

        if ($paidCount < $expectedCount) {
            return; // Not everyone paid yet
        }

        // 2. Find the member who should receive the payout for this cycle
        // Rank matches cycle (Cycle 1 -> Rank 1, etc.)
        $beneficiary = $tontine->members()
            ->where('payout_rank', $cycle)
            ->where('status', 'accepted')
            ->first();

        if (!$beneficiary) {
            Log::warning("No beneficiary found for Tontine {$tontine->id} Cycle {$cycle} (Rank {$cycle})");
            return;
        }

        // 3. Check if already paid out
        $alreadyPaid = $tontine->payouts()
            ->where('tontine_member_id', $beneficiary->id)
            ->where('cycle_number', $cycle)
            ->exists();

        if ($alreadyPaid) {
            return;
        }

        // 4. Create Payout Record
        $totalAmount = $tontine->payments()->where('cycle_number', $cycle)->sum('amount');
        $commission = $totalAmount * ($tontine->creator_percentage / 100);
        $netAmount = $totalAmount - $commission;

        DB::transaction(function () use ($tontine, $beneficiary, $cycle, $netAmount, $commission) {
            $payout = \App\Models\TontinePayout::query()->create([
                'tontine_id' => $tontine->id,
                'tontine_member_id' => $beneficiary->id,
                'amount' => $netAmount,
                'cycle_number' => $cycle,
                'status' => 'pending', // Will be marked success after payment service call
            ]);

            // 5. Trigger Payout if automatic
            if ($tontine->payout_mode === 'automatic') {
                try {
                    $payoutAccount = $beneficiary->user?->phone ?? $beneficiary->phone;
                    $success = $this->paymentService->payout(
                        account: $payoutAccount,
                        amount: (float) $netAmount,
                        description: "Versement Tontine: {$tontine->title} - Cycle #{$cycle}",
                        method: 'orange' // Fallback or use user's preferred method
                    );

                    if ($success) {
                        $payout->update(['status' => 'success', 'paid_at' => now()]);

                        if ($beneficiary->user) {
                            $this->fcmService->sendToUser(
                                $beneficiary->user,
                                "Tontine reçue !",
                                "Félicitations ! Vous avez reçu votre part de la tontine '{$tontine->title}' pour un montant de {$netAmount} XOF."
                            );
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Tontine Payout Failed: " . $e->getMessage());
                }
            }
        });
    }
}
