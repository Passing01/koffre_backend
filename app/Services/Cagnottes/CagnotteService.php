<?php

namespace App\Services\Cagnottes;

use App\Models\Cagnotte;
use App\Models\Participant;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\FcmService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Carbon;

use App\Models\Transaction;
use App\Services\Payments\PaymentServiceInterface;

class CagnotteService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly FcmService $fcmService,
        private readonly PaymentServiceInterface $paymentService
    ) {
    }

    public function listPublic(): Collection
    {
        return Cagnotte::query()
            ->where('visibility', 'public')
            ->where('status', 'active')
            ->orderByDesc('id')
            ->get();
    }

    public function listMine(User $user): Collection
    {
        return Cagnotte::query()
            ->where(function ($query) use ($user) {
                // Cagnottes created by me
                $query->where('user_id', $user->id)
                    // OR Cagnottes where I am a participant
                    ->orWhereHas('participants', function ($q) use ($user) {
                    $q->where('phone', $user->phone);
                });
            })
            ->orderByDesc('id')
            ->get();
    }

    public function create(User $user, array $data): Cagnotte
    {
        return DB::transaction(function () use ($user, $data) {
            $data['user_id'] = $user->id;

            $participants = $data['participants'] ?? [];
            unset($data['participants']);

            // Force ends_at to end of day if it's a date string
            if (isset($data['ends_at'])) {
                $data['ends_at'] = Carbon::parse($data['ends_at'])->endOfDay();
            }

            // Handle file uploads
            if (isset($data['profile_photo'])) {
                $data['profile_photo_path'] = $data['profile_photo']->store('cagnottes/profiles', 'public');
                unset($data['profile_photo']);
            }
            if (isset($data['company_logo'])) {
                $data['company_logo_path'] = $data['company_logo']->store('cagnottes/logos', 'public');
                unset($data['company_logo']);
            }
            if (isset($data['identity_document'])) {
                $data['identity_document_path'] = $data['identity_document']->store('cagnottes/documents', 'public');
                unset($data['identity_document']);
            }
            if (isset($data['rccm_document'])) {
                $data['rccm_document_path'] = $data['rccm_document']->store('cagnottes/documents', 'public');
                unset($data['rccm_document']);
            }
            if (isset($data['ifu_document'])) {
                $data['ifu_document_path'] = $data['ifu_document']->store('cagnottes/documents', 'public');
                unset($data['ifu_document']);
            }
            if (isset($data['signed_contract'])) {
                $data['signed_contract_path'] = $data['signed_contract']->store('cagnottes/contracts', 'public');
                unset($data['signed_contract']);
            }
            if (isset($data['background_image'])) {
                $data['background_image_path'] = $data['background_image']->store('cagnottes/backgrounds', 'public');
                unset($data['background_image']);
            }

            $data['status'] = 'active';

            $cagnotte = Cagnotte::query()->create($data);

            foreach ($participants as $phone) {
                Participant::query()->create([
                    'cagnotte_id' => $cagnotte->id,
                    'phone' => $phone,
                ]);
            }

            $this->auditService->log(
                action: 'cagnotte.created',
                actorUserId: $user->id,
                auditableType: 'cagnotte',
                auditableId: $cagnotte->id,
                metadata: [
                    'visibility' => $cagnotte->visibility,
                    'payout_mode' => $cagnotte->payout_mode,
                    'creator_type' => $cagnotte->creator_type,
                    'ends_at' => optional($cagnotte->ends_at)->toISOString(),
                    'participants_count' => count($participants),
                ],
            );

            $this->fcmService->sendToUser(
                $user,
                "Félicitations !",
                "Votre cagnotte '{$cagnotte->title}' a été créée avec succès."
            );

            return $cagnotte;
        });
    }

    public function createPrivateCoffre(User $user, array $data): Cagnotte
    {
        $data['visibility'] = 'private';
        $data['payout_mode'] = 'escrow';
        $data['is_private_coffre'] = true;
        $data['creator_type'] = 'physique';
        // accepted_policy is already validated as true in the request

        return $this->create($user, $data);
    }

    public function createPublicDirect(User $user, array $data): Cagnotte
    {
        $data['visibility'] = 'public';
        $data['payout_mode'] = 'direct';
        $data['is_private_coffre'] = false;

        return $this->create($user, $data);
    }

    public function createPublicCoffre(User $user, array $data): Cagnotte
    {
        $data['visibility'] = 'public';
        $data['payout_mode'] = 'escrow';
        $data['is_private_coffre'] = false;

        return $this->create($user, $data);
    }

    public function updateCagnotte(int $cagnotteId, User $user, array $data): Cagnotte
    {
        $cagnotte = Cagnotte::query()->findOrFail($cagnotteId);

        if ((int) $cagnotte->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'cagnotte_id' => ['Seul le créateur peut modifier cette cagnotte.'],
            ]);
        }

        return DB::transaction(function () use ($cagnotte, $user, $data) {
            // Handle background image upload
            if (isset($data['background_image'])) {
                $data['background_image_path'] = $data['background_image']->store('cagnottes/backgrounds', 'public');
                unset($data['background_image']);
            }

            $cagnotte->update($data);

            $this->auditService->log(
                action: 'cagnotte.updated',
                actorUserId: $user->id,
                auditableType: 'cagnotte',
                auditableId: $cagnotte->id,
                metadata: array_keys($data),
            );

            return $cagnotte->fresh();
        });
    }

    public function getAccessibleOrFail(int $cagnotteId, User $user): Cagnotte
    {
        $cagnotte = Cagnotte::query()->with(['participants', 'user'])->find($cagnotteId);

        if (!$cagnotte) {
            throw ValidationException::withMessages([
                'cagnotte_id' => ['Cagnotte introuvable.'],
            ]);
        }

        if ($cagnotte->visibility === 'public') {
            return $cagnotte;
        }

        $isOwner = (int) $cagnotte->user_id === (int) $user->id;
        $isParticipant = $cagnotte->participants()->where('phone', $user->phone)->exists();

        if (!$isOwner && !$isParticipant) {
            throw ValidationException::withMessages([
                'cagnotte_id' => ['Accès refusé.'],
            ]);
        }

        return $cagnotte;
    }

    public function getDetails(int $cagnotteId, User $user): array
    {
        $cagnotte = $this->getAccessibleOrFail($cagnotteId, $user);

        $isOwner = (int) $cagnotte->user_id === (int) $user->id;
        $isAdmin = $user->is_admin;

        // Respecter les flags de visibilité pour le créateur (si pas owner/admin)
        if (!$isOwner && !$isAdmin) {
            if (!$cagnotte->show_creator_name) {
                $cagnotte->user->fullname = 'Anonyme';
            }
            if (!$cagnotte->show_creator_phone) {
                $cagnotte->user->phone = 'Masqué';
            }
        }

        if ($cagnotte->visibility === 'public') {
            $myContributions = $cagnotte->contributions()
                ->where('payment_status', 'success')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->get();

            $result = [
                'cagnotte' => $cagnotte,
                'my_contributions' => $myContributions,
                'my_contributed_total' => $myContributions->sum('amount'),
            ];

            // Liste globale des contributeurs pour le public si autorisé
            if ($cagnotte->show_contributors || $isOwner || $isAdmin) {
                $result['contributors'] = $cagnotte->contributions()
                    ->where('payment_status', 'success')
                    ->with(['user:id,fullname,phone'])
                    ->orderByDesc('id')
                    ->get();
                $result['contributors_total'] = $result['contributors']->sum('amount');
            }

            return $result;
        }

        // Pour les cagnottes privées, le comportement reste le même ou est ajusté
        $contributors = $cagnotte->contributions()
            ->where('payment_status', 'success')
            ->with(['user:id,fullname,phone'])
            ->orderByDesc('id')
            ->get();

        $contributingPhones = $contributors->pluck('user.phone')->filter()->unique()->toArray();

        $participantsStatus = $cagnotte->participants->map(function ($participant) use ($contributingPhones) {
            $hasContributed = in_array($participant->phone, $contributingPhones);
            return [
                'id' => $participant->id,
                'phone' => $participant->phone,
                'has_contributed' => $hasContributed,
                'status_label' => $hasContributed ? 'A contribué' : 'En attente',
            ];
        });

        // Hider les contributeurs si flag à false (même privé?)
        // Généralement pour privé on veut voir, mais ici on respecte le souhait global s'il y a lieu.
        if (!$cagnotte->show_contributors && !$isOwner && !$isAdmin) {
            $contributors = collect([]); // Masquer
        }

        return [
            'cagnotte' => $cagnotte,
            'contributors' => $contributors,
            'contributors_total' => $contributors->sum('amount'),
            'participants_status' => $participantsStatus,
        ];
    }

    public function addParticipant(Cagnotte $cagnotte, User $user, string $phone): Participant
    {
        if ((int) $cagnotte->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'cagnotte_id' => ['Accès refusé.'],
            ]);
        }

        if ($cagnotte->visibility !== 'private') {
            throw ValidationException::withMessages([
                'cagnotte_id' => ['Cette cagnotte n\'est pas privée.'],
            ]);
        }

        return DB::transaction(function () use ($cagnotte, $user, $phone) {
            $participant = Participant::query()->firstOrCreate([
                'cagnotte_id' => $cagnotte->id,
                'phone' => $phone,
            ]);

            $this->auditService->log(
                action: 'cagnotte.participant_added',
                actorUserId: $user->id,
                auditableType: 'cagnotte',
                auditableId: $cagnotte->id,
                metadata: [
                    'participant_id' => $participant->id,
                    'phone' => $participant->phone,
                ],
            );

            return $participant;
        });
    }

    public function requestUnlock(int $cagnotteId, User $user, $identityDocument): Cagnotte
    {
        $cagnotte = Cagnotte::query()->findOrFail($cagnotteId);

        if ((int) $cagnotte->user_id !== (int) $user->id) {
            throw ValidationException::withMessages([
                'cagnotte_id' => ['Seul le créateur peut demander le déblocage.'],
            ]);
        }

        if ($cagnotte->payout_mode !== 'escrow') {
            throw ValidationException::withMessages([
                'payout_mode' => ['Cette cagnotte n\'est pas en mode coffre.'],
            ]);
        }

        if ($cagnotte->is_private_coffre) {
            throw ValidationException::withMessages([
                'cagnotte_id' => ['Le système privé/coffre ne permet pas de déblocage anticipé.'],
            ]);
        }

        if ($cagnotte->unlock_status === 'pending') {
            throw ValidationException::withMessages([
                'unlock_status' => ['Une demande de déblocage est déjà en cours.'],
            ]);
        }

        if ($cagnotte->unlock_status === 'approved' || $cagnotte->unlocked_at) {
            throw ValidationException::withMessages([
                'unlock_status' => ['Cette cagnotte a déjà été débloquée.'],
            ]);
        }

        return DB::transaction(function () use ($cagnotte, $user, $identityDocument) {
            $path = $identityDocument->store('cagnottes/unlocks', 'public');

            $cagnotte->update([
                'unlock_requested_at' => now(),
                'unlock_document_path' => $path,
                'unlock_status' => 'pending',
            ]);

            $this->auditService->log(
                action: 'cagnotte.unlock_requested',
                actorUserId: $user->id,
                auditableType: 'cagnotte',
                auditableId: $cagnotte->id,
                metadata: [
                    'requested_at' => now()->toDateTimeString(),
                ],
            );

            // TODO: Notifier les admins par email ou notification syst

            return $cagnotte;
        });
    }

    public function approveUnlock(int $cagnotteId, User $adminUser): Cagnotte
    {
        $cagnotte = Cagnotte::query()->findOrFail($cagnotteId);

        if ($cagnotte->unlock_status !== 'pending') {
            throw ValidationException::withMessages([
                'unlock_status' => ['Aucune demande de déblocage en attente.'],
            ]);
        }

        return DB::transaction(function () use ($cagnotte, $adminUser) {
            // Déblocage 48h (2 jours ouvrables) après la DEMANDE
            $releaseDate = $cagnotte->unlock_requested_at->copy()->addWeekdays(2);

            // Si les 48h sont déjà passées, le déblocage est immédiat
            $unlockedAt = $releaseDate->isPast() ? now() : $releaseDate;

            $cagnotte->update([
                'unlock_status' => 'approved',
                'unlocked_at' => $unlockedAt,
            ]);

            $this->auditService->log(
                action: 'cagnotte.unlock_approved',
                actorUserId: $adminUser->id,
                auditableType: 'cagnotte',
                auditableId: $cagnotte->id,
                metadata: [
                    'unlocked_at' => $unlockedAt->toDateTimeString(),
                ],
            );

            // TODO: Notification user

            return $cagnotte;
        });
    }

    public function rejectUnlock(int $cagnotteId, User $adminUser, string $reason): Cagnotte
    {
        $cagnotte = Cagnotte::query()->findOrFail($cagnotteId);

        if ($cagnotte->unlock_status !== 'pending') {
            throw ValidationException::withMessages([
                'unlock_status' => ['Aucune demande de déblocage en attente.'],
            ]);
        }

        return DB::transaction(function () use ($cagnotte, $adminUser, $reason) {
            $cagnotte->update([
                'unlock_status' => 'rejected',
                'unlocked_at' => null,
            ]);

            $this->auditService->log(
                action: 'cagnotte.unlock_rejected',
                actorUserId: $adminUser->id,
                auditableType: 'cagnotte',
                auditableId: $cagnotte->id,
                metadata: [
                    'reason' => $reason,
                ],
            );

            // TODO: Notification user

            return $cagnotte;
        });
    }

    public function processPayout(int $cagnotteId, ?User $adminUser = null): Cagnotte
    {
        $cagnotte = Cagnotte::query()->with('user')->findOrFail($cagnotteId);

        if ($cagnotte->payout_processed_at) {
            throw ValidationException::withMessages(['payout' => ['Le versement a déjà été effectué.']]);
        }

        // Pour les cagnottes en mode direct, le versement se fait déjà à chaque contribution.
        if ($cagnotte->payout_mode !== 'escrow') {
            throw ValidationException::withMessages(['payout' => ['Seules les cagnottes en mode coffre (escrow) nécessitent un versement global.']]);
        }

        // Si c'est un admin qui le fait manuellement, on vérifie le statut de déblocage
        if ($adminUser !== null && $cagnotte->unlock_status !== 'approved') {
            throw ValidationException::withMessages(['payout' => ['La demande de déblocage doit être approuvée par un admin avant le versement manuel.']]);
        }

        // Si c'est automatique (adminUser est null), on vérifie que la cagnotte est bien fermée (date atteinte)
        if ($adminUser === null && $cagnotte->status !== 'closed' && ($cagnotte->ends_at && $cagnotte->ends_at->isFuture())) {
            throw new \Exception("Le versement automatique ne peut être fait qu'après la clôture de la cagnotte.");
        }

        if ($cagnotte->current_amount <= 0) {
            throw ValidationException::withMessages(['payout' => ['Le montant de la cagnotte est nul.']]);
        }

        return DB::transaction(function () use ($cagnotte, $adminUser) {
            $payoutAccount = $cagnotte->payout_account ?? $cagnotte->user->phone;

            $commissionRate = (float) config('services.platform.commission_rate', 0.01);
            $commission = (float) $cagnotte->current_amount * $commissionRate;
            $netAmount = (float) $cagnotte->current_amount - $commission;

            $success = $this->paymentService->payout(
                account: $payoutAccount,
                amount: (float) $netAmount,
                description: "Versement Koffre - Cagnotte #{$cagnotte->id}: {$cagnotte->title}",
                method: $cagnotte->payout_method
            );

            if (!$success) {
                Log::error("Payout failed for cagnotte #{$cagnotte->id}");
                throw new \Exception("Échec lors de l'appel à l'API de paiement pour le versement.");
            }

            $cagnotte->update(['payout_processed_at' => now(), 'status' => 'disbursed']);

            Transaction::query()->create([
                'cagnotte_id' => $cagnotte->id,
                'type' => 'debit',
                'amount' => $cagnotte->current_amount,
                'balance_after' => 0,
                'reference' => 'PAYOUT-' . $cagnotte->id . '-' . time(),
            ]);

            $this->auditService->log(
                action: $adminUser ? 'cagnotte.payout_processed' : 'cagnotte.payout_auto_processed',
                actorUserId: $adminUser?->id,
                auditableType: 'cagnotte',
                auditableId: $cagnotte->id,
                metadata: [
                    'amount' => $cagnotte->current_amount,
                    'payout_account' => $payoutAccount,
                    'is_automatic' => $adminUser === null
                ],
            );

            $this->fcmService->sendToUser(
                $cagnotte->user,
                "Fonds transférés !",
                "Le versement automatique de {$cagnotte->current_amount} XOF pour votre cagnotte '{$cagnotte->title}' a été effectué."
            );

            return $cagnotte;
        });
    }
}
