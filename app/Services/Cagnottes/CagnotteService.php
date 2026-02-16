<?php

namespace App\Services\Cagnottes;

use App\Models\Cagnotte;
use App\Models\Participant;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Notifications\FcmService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CagnotteService
{
    public function __construct(
        private readonly AuditService $auditService,
        private readonly FcmService $fcmService
    ) {
    }

    public function listPublic(): Collection
    {
        return Cagnotte::query()
            ->where('visibility', 'public')
            ->whereIn('status', ['active', 'closed'])
            ->orderByDesc('id')
            ->get();
    }

    public function listMine(User $user): Collection
    {
        return Cagnotte::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();
    }

    public function create(User $user, array $data): Cagnotte
    {
        return DB::transaction(function () use ($user, $data) {
            $data['user_id'] = $user->id;

            $participants = $data['participants'] ?? [];
            unset($data['participants']);

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

        if ($cagnotte->visibility === 'public') {
            $myContributions = $cagnotte->contributions()
                ->where('payment_status', 'success')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
                ->get();

            return [
                'cagnotte' => $cagnotte,
                'my_contributions' => $myContributions,
                'my_contributed_total' => $myContributions->sum('amount'),
            ];
        }

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
}
