<?php

namespace App\Services\Tontines;

use App\Models\Tontine;
use App\Models\TontineMember;
use App\Models\Earning;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\FcmService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

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

            $membersData = $data['members'] ?? [];
            unset($data['members']);

            if (isset($data['starts_at'])) {
                $data['starts_at'] = Carbon::parse($data['starts_at'])->startOfDay();
            }

            if (isset($data['identity_document'])) {
                $data['identity_document_path'] = $data['identity_document']->store('tontines/documents', 'public');
                unset($data['identity_document']);
            }

            /** @var Tontine $tontine */
            $tontine = Tontine::query()->create($data);

            // Add the creator
            $creatorMember = TontineMember::query()->create([
                'tontine_id' => $tontine->id,
                'phone' => $user->phone,
                'user_id' => $user->id,
                'status' => 'accepted',
                'payout_rank' => $tontine->is_random_payout ? null : 1,
                'permissions' => ['is_admin' => true, 'can_invite' => true, 'can_view_stats' => true],
            ]);

            $createdMembers = collect([$creatorMember]);

            foreach ($membersData as $m) {
                // Skip if it's the creator's phone
                if ($m['phone'] === $user->phone)
                    continue;

                $invitedUser = User::where('phone', $m['phone'])->first();
                $requiresReg = (bool) ($tontine->requires_member_registration ?? false);

                $newMember = TontineMember::query()->create([
                    'tontine_id' => $tontine->id,
                    'phone' => $m['phone'],
                    'user_id' => $invitedUser?->id,
                    'payout_rank' => $tontine->is_random_payout ? null : ($m['payout_rank'] ?? null),
                    'permissions' => $m['permissions'] ?? ['can_view_stats' => true],
                    'status' => $requiresReg ? 'pending' : 'accepted',
                ]);

                $createdMembers->push($newMember);

                if ($invitedUser) {
                    $this->fcmService->sendToUser(
                        $invitedUser,
                        "Invitation Tontine",
                        "{$user->fullname} vous a invité à rejoindre la tontine '{$tontine->title}'."
                    );
                }
            }

            // Handle Randomization
            if ($tontine->is_random_payout) {
                $shuffledIds = $createdMembers->pluck('id')->shuffle();
                foreach ($shuffledIds as $index => $memberId) {
                    $rank = $index + 1;
                    TontineMember::where('id', $memberId)->update(['payout_rank' => $rank]);

                    // Notify member of their rank
                    $member = TontineMember::with('user')->find($memberId);
                    if ($member && $member->user) {
                        $this->fcmService->sendToUser(
                            $member->user,
                            "Tour de prise défini",
                            "Le tirage au sort a été effectué ! Votre rang de passage pour la tontine '{$tontine->title}' est le #{$rank}."
                        );
                    }
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
                    'is_random' => $tontine->is_random_payout,
                    'members_count' => $createdMembers->count(),
                ],
            );

            return $tontine->load('members');
        });
    }

    public function createIndividual(User $user, array $data): Tontine
    {
        return DB::transaction(function () use ($user, $data) {
            $data['user_id'] = $user->id;
            $data['type'] = 'individual';
            $data['is_random_payout'] = false;
            $data['max_participants'] = 1;
            $data['requires_member_registration'] = false;
            $data['creator_percentage'] = 0; // Pas de commission sur soi-même

            if (isset($data['starts_at'])) {
                $data['starts_at'] = Carbon::parse($data['starts_at'])->startOfDay();
            }

            if (isset($data['target_payout_date'])) {
                $data['target_payout_date'] = Carbon::parse($data['target_payout_date'])->endOfDay();
            }

            if (isset($data['identity_document'])) {
                $data['identity_document_path'] = $data['identity_document']->store('tontines/documents', 'public');
                unset($data['identity_document']);
            }


            /** @var Tontine $tontine */
            $tontine = Tontine::query()->create($data);

            // Add the creator as the unique member
            TontineMember::query()->create([
                'tontine_id' => $tontine->id,
                'phone' => $user->phone,
                'user_id' => $user->id,
                'status' => 'accepted',
                'payout_rank' => 1,
                'permissions' => ['is_admin' => true, 'can_view_stats' => true],
                'first_name' => $user->fullname,
            ]);

            $this->auditService->log(
                action: 'tontine.individual.created',
                actorUserId: $user->id,
                auditableType: 'tontine',
                auditableId: $tontine->id,
                metadata: [
                    'amount' => $tontine->amount_per_installment,
                    'frequency' => $tontine->frequency,
                    'target_date' => $tontine->target_payout_date ? \Illuminate\Support\Carbon::parse($tontine->target_payout_date)->toDateString() : null,
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

        $currentContribCycle = $this->calculateCurrentCycle($tontine, 'contribution');
        $currentPayoutCycle = $this->calculateCurrentCycle($tontine, 'payout');

        // Stats calculation
        $totalPaid = $tontine->payments()->where('status', 'success')->sum('amount');

        $stats = [
            'total_members' => $tontine->members()->count(),
            'active_members' => $tontine->members()->where('status', 'accepted')->count(),
            'total_paid' => $totalPaid,
            'current_contribution_cycle' => $currentContribCycle,
            'current_payout_cycle' => $currentPayoutCycle,
            'current_cycle' => $currentPayoutCycle, // Backwards compatibility
        ];

        // Member specific stats - fullname visible par tous, identity_document uniquement créateur/admin
        $isCreatorOrAdmin = $isCreator || $user->is_admin;
        $membersStats = $tontine->members->map(function ($member) use ($tontine, $currentContribCycle, $isCreatorOrAdmin) {
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
                'attendance_rate' => $currentContribCycle > 1 ? round(($paidCycles / ($currentContribCycle - 1)) * 100, 2) : 100,
                'total_contributed' => $totalAmountPaid,
                'payout_received' => $tontine->payouts()->where('tontine_member_id', $member->id)->where('status', 'success')->exists(),
            ];
            if ($isCreatorOrAdmin && $member->identity_document_path) {
                $row['identity_document_url'] = $member->identity_document_url;
            }
            return $row;
        });

        $result = [
            'tontine' => $tontine,
            'stats' => $stats,
            'members_stats' => $membersStats,
            'my_membership' => $tontine->members()->where('phone', $user->phone)->first(),
        ];

        if ($isCreatorOrAdmin) {
            $result['payments'] = $tontine->payments()
                ->with('member.user:id,fullname,phone')
                ->where('status', 'success')
                ->orderByDesc('id')
                ->get();

            $result['payouts'] = $tontine->payouts()
                ->with('member.user:id,fullname,phone')
                ->where('status', 'success')
                ->orderByDesc('id')
                ->get();

            $result['pending_payout_requests'] = $tontine->payoutRequests()
                ->where('status', 'pending')
                ->with('beneficiary')
                ->get()
                ->map(fn($r) => [
                    'cycle_number' => $r->cycle_number,
                    'amount' => (float) $r->amount,
                    'unpaid_member_ids' => $r->unpaid_member_ids ?? [],
                    'beneficiary' => $r->beneficiary ? ['id' => $r->beneficiary->id, 'display_name' => $r->beneficiary->display_name] : null,
                ]);
        }

        return $result;
    }

    public function close(int $tontineId, User $user): Tontine
    {
        $tontine = Tontine::query()->findOrFail($tontineId);

        if ((int) $tontine->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'tontine_id' => ['Seul le créateur peut clôturer cette tontine.'],
            ]);
        }

        if ($tontine->status === 'closed') {
            return $tontine;
        }

        // Check if all members have received their payout
        $acceptedMembersCount = $tontine->members()->where('status', 'accepted')->count();
        $payoutsCount = $tontine->payouts()->where('status', 'success')->count();

        if ($payoutsCount < $acceptedMembersCount) {
            throw ValidationException::withMessages([
                'tontine_id' => ["La tontine ne peut être clôturée que si tous les membres ({$acceptedMembersCount}) ont reçu leur virement. Actuellement: {$payoutsCount}."],
            ]);
        }

        $tontine->update(['status' => 'closed']);

        $this->auditService->log(
            action: 'tontine.closed',
            actorUserId: $user->id,
            auditableType: 'tontine',
            auditableId: $tontine->id,
            metadata: ['status' => 'closed'],
        );

        return $tontine;
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

    private function calculateCurrentCycle(Tontine $tontine, string $type = 'contribution'): int
    {
        if ($tontine->status !== 'active')
            return 0;

        $start = $tontine->starts_at;
        if (!$start) return 0;

        if ($type === 'payout') {
            $freq = $tontine->payout_frequency ?: $tontine->frequency;
            $num = $tontine->payout_frequency_number ?: $tontine->frequency_number;
        } else {
            $freq = $tontine->contribution_frequency ?: $tontine->frequency;
            $num = $tontine->contribution_frequency_number ?: $tontine->frequency_number;
        }

        return $this->calculateCyclesSinceStart($start, $freq, $num);
    }

    private function calculateCyclesSinceStart(\Carbon\Carbon $start, string $frequency, int $number): int
    {
        $now = now();
        if ($now->lt($start)) return 0;

        $diff = 0;
        switch ($frequency) {
            case 'days':
                $diff = $start->diffInDays($now);
                break;
            case 'weeks':
                $diff = $start->diffInWeeks($now);
                break;
            case 'months':
                $diff = $start->diffInMonths($now);
                break;
            case 'years':
                $diff = $start->diffInYears($now);
                break;
            default:
                $diff = $start->diffInDays($now);
                break;
        }

        return (int) floor($diff / max(1, $number)) + 1;
    }

    public function getPayoutCycleFromContributionCycle(Tontine $tontine, int $contributionCycle): int
    {
        $cpp = $this->getContributionsCountPerPayout($tontine);
        if ($cpp <= 1) return $contributionCycle;
        
        return (int) floor(($contributionCycle - 1) / $cpp) + 1;
    }

    private function getContributionsCountPerPayout(Tontine $tontine): int
    {
        if (!$tontine->contribution_frequency || !$tontine->payout_frequency) {
            return 1;
        }

        $units = ['days' => 1, 'weeks' => 7, 'months' => 30, 'years' => 365];
        
        $c_total = ($units[$tontine->contribution_frequency] ?? 1) * $tontine->contribution_frequency_number;
        $p_total = ($units[$tontine->payout_frequency] ?? 1) * $tontine->payout_frequency_number;

        if ($c_total <= 0) return 1;
        return (int) max(1, floor($p_total / $c_total));
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

        if ($member->status === 'blocked') {
            throw ValidationException::withMessages([
                'tontine_id' => ['Vous êtes bloqué et ne pouvez plus participer à cette tontine.'],
            ]);
        }
        if ($member->status !== 'accepted') {
            throw ValidationException::withMessages([
                'tontine_id' => ['Vous devez compléter votre inscription avant de contribuer.'],
            ]);
        }

        if ($tontine->status !== 'active') {
            throw ValidationException::withMessages(['tontine_id' => ['La tontine n\'est pas active.']]);
        }

        $cycle = $this->calculateCurrentCycle($tontine, 'contribution');
        $reference = 'TON-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(12));

        $baseAmount = (float) $tontine->amount_per_installment;
        $feeRate = (float) config('services.platform.tontine_payment_fee_rate', 0.045);
        $platformFee = round($baseAmount * $feeRate, 2);
        $totalCharged = $baseAmount + $platformFee;

        return DB::transaction(function () use ($tontine, $member, $cycle, $reference, $user, $baseAmount, $platformFee, $totalCharged) {
            $payment = \App\Models\TontinePayment::query()->create([
                'tontine_id' => $tontine->id,
                'tontine_member_id' => $member->id,
                'cycle_number' => $cycle,
                'amount' => $baseAmount,
                'platform_fee' => $platformFee,
                'total_charged' => $totalCharged,
                'payment_reference' => $reference,
                'status' => 'pending',
            ]);

            $paymentData = $this->paymentService->initiatePayment(
                transactionId: $reference,
                amount: (float) $totalCharged,
                currency: $tontine->currency ?? 'XOF',
                description: "Cotisation Tontine: {$tontine->title} - Cycle #{$cycle}",
                customer: [
                    'name' => $user->fullname,
                    'email' => "user-{$user->id}@kofre.com",
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

            // Enregistrer la commission plateforme (Earnings global)
            if ($payment->platform_fee > 0) {
                Earning::query()->create([
                    'module' => 'tontine',
                    'amount' => (float) $payment->platform_fee,
                    'reference' => 'EARN-TON-' . $payment->payment_reference,
                    'metadata' => [
                        'tontine_id' => $tontine->id,
                        'payment_id' => $payment->id,
                        'member_id' => $payment->tontine_member_id,
                    ],
                ]);
            }
            
            // On garde TontineEarning pour la compatibilité existante si besoin
            if ($payment->platform_fee > 0) {
                \App\Models\TontineEarning::query()->create([
                    'tontine_id' => $tontine->id,
                    'user_id' => null,
                    'type' => \App\Models\TontineEarning::TYPE_PLATFORM_FEE,
                    'amount' => $payment->platform_fee,
                    'tontine_payment_id' => $payment->id,
                    'reference' => 'PAY-FEE-' . $payment->payment_reference,
                ]);
            }

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
            $payoutCycle = $this->getPayoutCycleFromContributionCycle($tontine, $payment->cycle_number);
            $this->checkAndProcessPayout($tontine, $payoutCycle);

            return true;
        });
    }

    public function checkAndProcessPayout(Tontine $tontine, int $payoutCycle): void
    {
        if ($tontine->type === 'individual') {
            return;
        }

        // 1. Determine the range of contribution cycles for this payout cycle
        $cpp = $this->getContributionsCountPerPayout($tontine);
        $startCycle = ($payoutCycle - 1) * $cpp + 1;
        $endCycle = $payoutCycle * $cpp;
        $contributionCycles = range($startCycle, $endCycle);

        // 2. Check if everyone in these cycles has paid
        $acceptedMembersCount = $tontine->members()->where('status', 'accepted')->count();
        if ($acceptedMembersCount === 0) return;

        $paidMembersInAllCycles = true;
        $paidMemberIds = [];

        foreach ($contributionCycles as $cCycle) {
            $paidInThisCycle = $tontine->payments()
                ->where('cycle_number', $cCycle)
                ->where('status', 'success')
                ->pluck('tontine_member_id')
                ->toArray();
            
            if (count($paidInThisCycle) < $acceptedMembersCount) {
                $paidMembersInAllCycles = false;
                break;
            }
        }

        // 3. Find the member who should receive the payout for this payout cycle
        $rankToFind = $payoutCycle % $acceptedMembersCount;
        if ($rankToFind === 0)
            $rankToFind = $acceptedMembersCount;

        $beneficiary = $tontine->members()
            ->where('payout_rank', $rankToFind)
            ->where('status', 'accepted')
            ->first();

        if (!$beneficiary) {
            Log::warning("No beneficiary found for Tontine {$tontine->id} Payout Cycle {$payoutCycle} (Rank {$rankToFind})");
            return;
        }

        // 4. Check if already paid out
        $alreadyPaid = $tontine->payouts()
            ->where('tontine_member_id', $beneficiary->id)
            ->where('cycle_number', $payoutCycle)
            ->where('status', 'success')
            ->exists();

        if ($alreadyPaid) {
            return;
        }

        $totalAmount = (float) $tontine->payments()->whereIn('cycle_number', $contributionCycles)->where('status', 'success')->sum('amount');
        $creatorPct = (float) ($tontine->creator_percentage ?? 0);
        $creatorAmount = round($totalAmount * ($creatorPct / 100), 2);
        $platformAmount = 0; 
        $beneficiaryAmount = $totalAmount - $creatorAmount;

        if (!$paidMembersInAllCycles) {
            // Find who hasn't paid in which cycle
            $unpaidMemberIds = [];
            foreach ($contributionCycles as $cCycle) {
                $paidInThisCycle = $tontine->payments()
                    ->where('cycle_number', $cCycle)
                    ->where('status', 'success')
                    ->pluck('tontine_member_id')
                    ->toArray();
                
                $unpaidInThisCycle = $tontine->members()
                    ->where('status', 'accepted')
                    ->whereNotIn('id', $paidInThisCycle)
                    ->pluck('id')
                    ->toArray();
                
                $unpaidMemberIds = array_unique(array_merge($unpaidMemberIds, $unpaidInThisCycle));
            }

            if (empty($unpaidMemberIds)) return; // Should not happen if paidMembersInAllCycles is false

            $unpaidNames = $tontine->members()->whereIn('id', $unpaidMemberIds)->get()->map(fn($m) => $m->display_name)->join(', ');

            \App\Models\TontinePayoutRequest::query()->firstOrCreate(
                ['tontine_id' => $tontine->id, 'cycle_number' => $payoutCycle],
                [
                    'tontine_member_id' => $beneficiary->id,
                    'amount' => $beneficiaryAmount,
                    'unpaid_member_ids' => $unpaidMemberIds,
                    'status' => 'pending',
                ]
            );

            $msg = "Virement #{$payoutCycle} : {$unpaidNames} n'ont pas encore payé toutes leurs cotisations. Le créateur peut approuver le transfert en acceptant de bloquer ces membres.";
            if ($tontine->user) {
                $this->fcmService->sendToUser($tontine->user, "Tontine - Virement #{$payoutCycle} en attente", $msg);
            }
            return;
        }

        if ($tontine->payout_mode === 'automatic') {
            $this->executePayout($tontine, $beneficiary, $payoutCycle, $beneficiaryAmount, $creatorAmount, $platformAmount, $totalAmount);
        }
    }

    private function executePayout(Tontine $tontine, TontineMember $beneficiary, int $cycle, float $beneficiaryAmount, float $creatorAmount, float $platformAmount, float $totalAmount): void
    {
        DB::transaction(function () use ($tontine, $beneficiary, $cycle, $beneficiaryAmount, $creatorAmount, $platformAmount, $totalAmount) {
            // Utiliser updateOrCreate pour éviter de créer plusieurs entrées Payout pour le même cycle si on ré-essaie
            $payout = \App\Models\TontinePayout::query()->updateOrCreate(
                [
                    'tontine_id' => $tontine->id,
                    'cycle_number' => $cycle,
                ],
                [
                    'tontine_member_id' => $beneficiary->id,
                    'amount' => $beneficiaryAmount,
                    'creator_amount' => $creatorAmount,
                    'platform_amount' => $platformAmount,
                    'status' => 'pending',
                ]
            );

            // Si déjà payé (même si on est arrivé ici par erreur), on s'arrête
            if ($payout->status === 'success') {
                return;
            }

            $ref = 'TON-PAY-' . $tontine->id . '-' . $cycle;
            $beneficiaryAccount = $tontine->payout_account ?? ($beneficiary->user?->phone ?? $beneficiary->phone);
            $success = $this->paymentService->payout(
                account: $beneficiaryAccount,
                amount: (float) $beneficiaryAmount,
                description: "Versement Tontine: {$tontine->title} - Cycle #{$cycle}",
                method: $tontine->payout_method
            );


            if ($success && $creatorAmount > 0 && $tontine->user?->phone) {
                $this->paymentService->payout(
                    account: $tontine->user->phone,
                    amount: (float) $creatorAmount,
                    description: "Commission créateur - Tontine {$tontine->title} - Cycle #{$cycle}",
                    method: null
                );
                \App\Models\TontineEarning::query()->create([
                    'tontine_id' => $tontine->id,
                    'user_id' => $tontine->user_id,
                    'type' => \App\Models\TontineEarning::TYPE_CREATOR_COMMISSION,
                    'amount' => $creatorAmount,
                    'tontine_payout_id' => $payout->id,
                    'reference' => $ref . '-creator',
                ]);
            }

            if ($success && $platformAmount > 0) {
                $platformPhone = config('services.platform.payout_phone');
                if ($platformPhone) {
                    $this->paymentService->payout(account: $platformPhone, amount: (float) $platformAmount, description: "Commission plateforme - Tontine {$tontine->title} - Cycle #{$cycle}", method: null);
                }
                \App\Models\TontineEarning::query()->create([
                    'tontine_id' => $tontine->id,
                    'user_id' => null,
                    'type' => \App\Models\TontineEarning::TYPE_PLATFORM_FEE,
                    'amount' => $platformAmount,
                    'tontine_payout_id' => $payout->id,
                    'reference' => $ref . '-platform',
                ]);
            }

            if ($success) {
                $payout->update(['status' => 'success', 'paid_at' => now()]);
                if ($beneficiary->user) {
                    $this->fcmService->sendToUser($beneficiary->user, "Tontine reçue !", "Félicitations ! Vous avez reçu votre part de la tontine '{$tontine->title}' pour un montant de {$beneficiaryAmount} XOF.");
                }

                // If this was the last person in a full cycle, inform admin/creator that it restarts
                $acceptedMembersCount = $tontine->members()->where('status', 'accepted')->count();
                if ($cycle % $acceptedMembersCount === 0) {
                    $msg = "La tontine '{$tontine->title}' vient de terminer un cycle complet (Cycle #{$cycle}). Elle recommencera automatiquement pour le prochain tour sauf si vous décidez de la clôturer.";
                    if ($tontine->user) {
                        $this->fcmService->sendToUser($tontine->user, "Cycle Tontine Terminé", $msg);
                    }
                    foreach (\App\Models\User::where('is_admin', true)->get() as $admin) {
                        /** @var \App\Models\User $admin */
                        $this->fcmService->sendToUser($admin, "Tontine - Virement #{$cycle} en attente", $msg);
                    }
                }
            }
        });
    }

    public function approvePayoutWithBlocking(int $tontineId, int $cycle, User $creator): void
    {
        $tontine = Tontine::query()->findOrFail($tontineId);
        if ((int) $tontine->user_id !== (int) $creator->id) {
            throw ValidationException::withMessages(['tontine_id' => ['Seul le créateur peut approuver.']]);
        }

        $request = \App\Models\TontinePayoutRequest::query()->where('tontine_id', $tontineId)->where('cycle_number', $cycle)->where('status', 'pending')->firstOrFail();
        $beneficiary = $request->beneficiary;
        $unpaidIds = $request->unpaid_member_ids ?? [];

        DB::transaction(function () use ($request, $tontine, $beneficiary, $cycle, $unpaidIds, $creator) {
            TontineMember::query()->whereIn('id', $unpaidIds)->update(['status' => 'blocked']);

            $totalAmount = (float) $tontine->payments()->where('cycle_number', $cycle)->where('status', 'success')->sum('amount');
            $creatorPct = (float) ($tontine->creator_percentage ?? 0);
            $creatorAmount = round($totalAmount * ($creatorPct / 100), 2);
            $platformAmount = 0; // Déjà pris au dépôt
            $beneficiaryAmount = $totalAmount - $creatorAmount;

            $this->executePayout($tontine, $beneficiary, $cycle, $beneficiaryAmount, $creatorAmount, $platformAmount, $totalAmount);

            $request->update(['status' => 'approved', 'approved_by_user_id' => $creator->id, 'approved_at' => now()]);

            $this->auditService->log(action: 'tontine.payout_approved_with_blocking', actorUserId: $creator->id, auditableType: 'tontine', auditableId: $tontine->id, metadata: ['cycle' => $cycle, 'blocked_members' => $unpaidIds]);
        });
    }

    public function processPayoutByAdmin(int $tontineId, int $cycle, User $adminUser): void
    {
        $tontine = Tontine::query()->findOrFail($tontineId);
        if (!$adminUser->is_admin) {
            throw ValidationException::withMessages(['tontine_id' => ['Non autorisé.']]);
        }

        // Trouver le bénéficiaire selon la même logique que checkAndProcessPayout
        $expectedCount = $tontine->members()->where('status', 'accepted')->count();
        if ($expectedCount === 0) {
            throw ValidationException::withMessages(['cycle' => ['Aucun membre actif dans cette tontine.']]);
        }

        $rankToFind = $cycle % $expectedCount;
        if ($rankToFind === 0) $rankToFind = $expectedCount;

        $beneficiary = $tontine->members()
            ->where('payout_rank', $rankToFind)
            ->whereIn('status', ['accepted', 'blocked']) // on permet même pour les bloqués (c'est l'admin qui décide)
            ->first();

        if (!$beneficiary) {
            // Fallback : chercher par rang égal au cycle (ancien comportement)
            $beneficiary = $tontine->members()
                ->where('payout_rank', $cycle)
                ->whereIn('status', ['accepted', 'blocked'])
                ->first();
        }

        if (!$beneficiary) {
            throw ValidationException::withMessages([
                'cycle' => ["Aucun bénéficiaire trouvé pour le cycle #{$cycle} (rang attendu : #{$rankToFind})."]
            ]);
        }

        // Vérifier si un payout RÉUSSI existe déjà
        $existingPayout = $tontine->payouts()->where('cycle_number', $cycle)->first();
        if ($existingPayout && $existingPayout->status === 'success') {
            throw ValidationException::withMessages(['cycle' => ['Ce cycle a déjà été reversé avec succès.']]);
        }

        $totalAmount     = (float) $tontine->payments()->where('cycle_number', $cycle)->where('status', 'success')->sum('amount');
        $creatorPct      = (float) ($tontine->creator_percentage ?? 0);
        $creatorAmount   = round($totalAmount * ($creatorPct / 100), 2);
        $platformAmount  = 0; // Déjà pris au dépôt
        $beneficiaryAmount = $totalAmount - $creatorAmount;

        Log::info('Admin Retry Payout', [
            'admin'        => $adminUser->id,
            'tontine'      => $tontineId,
            'cycle'        => $cycle,
            'beneficiary'  => $beneficiary->phone,
            'rank_found'   => $rankToFind,
            'total_amount' => $totalAmount,
            'net_amount'   => $beneficiaryAmount,
        ]);

        $this->executePayout($tontine, $beneficiary, $cycle, $beneficiaryAmount, $creatorAmount, $platformAmount, $totalAmount);
    }

    public function withdrawIndividual(int $tontineId, User $user): \App\Models\TontinePayout
    {
        $tontine = Tontine::query()->findOrFail($tontineId);

        if ($tontine->type !== 'individual') {
            throw ValidationException::withMessages(['tontine_id' => ['Cette action est réservée aux tontines individuelles.']]);
        }

        if ((int) $tontine->user_id !== (int) $user->id) {
            throw ValidationException::withMessages(['tontine_id' => ['Vous n\'êtes pas propriétaire.']]);
        }

        if ($tontine->status === 'closed') {
            throw ValidationException::withMessages(['tontine_id' => ['Cette tontine est déjà clôturée.']]);
        }
        
        if (!$tontine->target_payout_date || Carbon::parse($tontine->target_payout_date)->startOfDay()->lt(now()->startOfDay())) {
            $formattedDate = $tontine->target_payout_date ? Carbon::parse($tontine->target_payout_date)->format('d/m/Y') : 'inconnu';
            throw ValidationException::withMessages(['tontine_id' => ['La date de retrait n\'est pas encore atteinte (prévue le ' . $formattedDate . ').']]);
        }

        $totalSaved = (float) $tontine->payments()->where('status', 'success')->sum('amount');
        if ($totalSaved <= 0) {
            throw ValidationException::withMessages(['tontine_id' => ['Vous n\'avez encore rien épargné.']]);
        }
        
        return DB::transaction(function () use ($tontine, $user, $totalSaved) {
            $member = $tontine->members()->first();
            
            $platformAmount = 0; // Déjà pris au dépôt (4.5%)
            $beneficiaryAmount = $totalSaved;
            
            // Le cycle_number est fixé à 1 pour la tontine individuelle
            $this->executePayout(
                tontine: $tontine, 
                beneficiary: $member, 
                cycle: 1, 
                beneficiaryAmount: $beneficiaryAmount, 
                creatorAmount: 0, 
                platformAmount: $platformAmount, 
                totalAmount: $totalSaved
            );

            $tontine->update(['status' => 'closed']);

            $this->auditService->log(
                action: 'tontine.individual.withdrawn',
                actorUserId: $user->id,
                auditableType: 'tontine',
                auditableId: $tontine->id,
                metadata: ['total_saved' => $totalSaved, 'beneficiary_amount' => $beneficiaryAmount]
            );
            
            return $tontine->payouts()->latest()->first();
        });
    }
}
