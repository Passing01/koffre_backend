@extends('admin.layout')

@section('title', 'Détails de la Cagnotte')

@section('content')
    <div class="space-y-6">
        <!-- Header with Back Button -->
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.cagnottes.index') }}"
                class="w-10 h-10 bg-white rounded-lg shadow flex items-center justify-center hover:bg-gray-50 transition-all">
                <i class="fas fa-arrow-left text-gray-700"></i>
            </a>
            <div>
                <h2 class="text-3xl font-bold text-gray-900">{{ $cagnotte->title }}</h2>
                <p class="text-gray-600 mt-1">Cagnotte #{{ $cagnotte->id }}</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-white/80 text-sm font-medium">Progression</p>
                <p class="text-4xl font-bold mt-2">{{ number_format($stats['progress_percentage'], 1) }}%</p>
                <p class="text-white/70 text-xs mt-2">de l'objectif atteint</p>
            </div>

            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-white/80 text-sm font-medium">Contributions</p>
                <p class="text-4xl font-bold mt-2">{{ $stats['successful_contributions'] }}</p>
                <p class="text-white/70 text-xs mt-2">sur {{ $stats['total_contributions'] }} total</p>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-white/80 text-sm font-medium">Participants</p>
                <p class="text-4xl font-bold mt-2">{{ $stats['total_participants'] }}</p>
                <p class="text-white/70 text-xs mt-2">invités</p>
            </div>

            <div class="bg-gradient-to-br from-pink-500 to-pink-700 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-white/80 text-sm font-medium">Montant Collecté</p>
                <p class="text-3xl font-bold mt-2">{{ number_format($cagnotte->current_amount, 0, ',', ' ') }}</p>
                <p class="text-white/70 text-xs mt-2">/ {{ number_format($cagnotte->target_amount, 0, ',', ' ') }} FCFA</p>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Column - Details -->
            <div class="lg:col-span-2 space-y-6">
                @if($cagnotte->payout_mode === 'escrow')
                <!-- Escrow Unlock Section -->
                <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 {{ $cagnotte->unlock_status === 'pending' ? 'border-orange-500' : ($cagnotte->unlock_status === 'approved' ? 'border-green-500' : ($cagnotte->unlock_status === 'rejected' ? 'border-red-500' : 'border-gray-200')) }}">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-lock-open text-orange-600"></i> Gestion du déblocage (Mode Coffre)
                    </h3>
                    
                    <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                        <div class="flex-1">
                            <p class="text-sm text-gray-600">Statut actuel du déblocage</p>
                            @php
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                                    'approved' => 'bg-green-100 text-green-700 border-green-200',
                                    'rejected' => 'bg-red-100 text-red-700 border-red-200',
                                ];
                                $statusLabel = [
                                    'pending' => 'En attente de vérification',
                                    'approved' => 'Approuvé',
                                    'rejected' => 'Rejeté',
                                ];
                            @endphp
                            
                            @if($cagnotte->unlock_status)
                                <div class="mt-1 flex items-center gap-3">
                                    <span class="px-4 py-2 rounded-full text-sm font-bold border {{ $statusClasses[$cagnotte->unlock_status] }}">
                                        {{ $statusLabel[$cagnotte->unlock_status] }}
                                    </span>
                                </div>
                                <div class="mt-4 space-y-2">
                                    @if($cagnotte->unlock_requested_at)
                                        <p class="text-sm text-gray-600">
                                            <i class="far fa-calendar-alt mr-1"></i> Demandé le : <span class="font-medium text-gray-900">{{ $cagnotte->unlock_requested_at->format('d/m/Y à H:i') }}</span>
                                        </p>
                                    @endif
                                    @if($cagnotte->unlocked_at && $cagnotte->unlock_status === 'approved')
                                        <p class="text-sm text-green-600 font-bold bg-green-50 p-3 rounded-lg border border-green-100 mt-3">
                                            <i class="fas fa-clock mr-2"></i> Montant débloqué le : {{ $cagnotte->unlocked_at->format('d/m/Y à H:i') }}
                                            <br><small class="text-xs font-normal text-green-500">(48h ouvrables à compter de l'approbation)</small>
                                        </p>
                                    @endif
                                    @if($cagnotte->unlock_document_path)
                                        <div class="mt-3">
                                            <a href="{{ $cagnotte->unlock_document_url }}" target="_blank" class="inline-flex items-center px-4 py-2 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition-colors border border-purple-100 font-semibold text-sm">
                                                <i class="fas fa-id-card mr-2"></i> Consulter la pièce d'identité (KYC Déblocage)
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <div class="mt-1">
                                    <span class="px-4 py-2 rounded-full text-sm font-bold border bg-gray-100 text-gray-600 border-gray-200">
                                        Aucune demande de déblocage effectuée
                                    </span>
                                </div>
                            @endif
                        </div>

                        @if($cagnotte->unlock_status === 'pending')
                        <div class="flex gap-3 flex-shrink-0">
                            <form action="{{ route('admin.cagnottes.approve-unlock', $cagnotte->id) }}" method="POST" onsubmit="return confirm('Approuver cette demande ? L\'argent sera débloqué dans 48h ouvrables.')">
                                @csrf
                                <button type="submit" class="bg-green-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-green-700 transition shadow-lg shadow-green-100 flex items-center">
                                    <i class="fas fa-check mr-2"></i> Approuver
                                </button>
                            </form>
                            
                            <button onclick="openRejectModal()" class="bg-red-500 text-white px-6 py-3 rounded-xl font-bold hover:bg-red-600 transition shadow-lg shadow-red-100 flex items-center">
                                <i class="fas fa-times mr-2"></i> Rejeter
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Cagnotte Information -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-info-circle text-purple-600"></i> Informations de la cagnotte
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-600">Titre</p>
                            <p class="font-semibold text-gray-900">{{ $cagnotte->title }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Statut</p>
                            <span
                                class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                                {{ $cagnotte->status === 'active' ? 'bg-green-100 text-green-700' :
        ($cagnotte->status === 'completed' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700') }}">
                                {{ ucfirst($cagnotte->status) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Visibilité</p>
                            <span
                                class="inline-block px-3 py-1 text-xs font-semibold rounded-full
                                {{ $cagnotte->visibility === 'public' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                <i class="fas fa-{{ $cagnotte->visibility === 'public' ? 'globe' : 'lock' }}"></i>
                                {{ ucfirst($cagnotte->visibility) }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Type de créateur</p>
                            <p class="font-semibold text-gray-900">{{ ucfirst($cagnotte->creator_type ?? 'N/A') }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-sm text-gray-600">Description</p>
                            <p class="text-gray-900">{{ $cagnotte->description }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Date de début</p>
                            <p class="font-semibold text-gray-900">
                                {{ $cagnotte->starts_at ? $cagnotte->starts_at->format('d/m/Y H:i') : 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Date de fin</p>
                            <p class="font-semibold text-gray-900">
                                {{ $cagnotte->ends_at ? $cagnotte->ends_at->format('d/m/Y H:i') : 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Mode de paiement</p>
                            <p class="font-semibold text-gray-900">{{ ucfirst($cagnotte->payout_mode ?? 'N/A') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Méthode de paiement</p>
                            <p class="font-semibold text-gray-900">{{ ucfirst($cagnotte->payout_method ?? 'N/A') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Contributions List -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-hand-holding-heart text-blue-600"></i> Contributions
                        ({{ $cagnotte->contributions->count() }})
                    </h3>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @forelse($cagnotte->contributions as $contribution)
                                        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <p class="font-semibold text-gray-900">{{ $contribution->contributor_name }}</p>
                                                    <p class="text-sm text-gray-600">{{ $contribution->user->phone ?? 'N/A' }}</p>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        {{ $contribution->created_at->format('d/m/Y H:i') }}</p>
                                                    <span
                                                        class="inline-block mt-2 px-2 py-1 text-xs font-semibold rounded-full
                                                        {{ $contribution->payment_status === 'success' ? 'bg-green-100 text-green-700' :
                            ($contribution->payment_status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                                        {{ ucfirst($contribution->payment_status) }}
                                                    </span>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xl font-bold text-purple-600">
                                                        {{ number_format($contribution->amount, 0, ',', ' ') }}</p>
                                                    <p class="text-xs text-gray-500">FCFA</p>
                                                    <p class="text-xs text-gray-500 mt-1">{{ $contribution->payment_method }}</p>
                                                </div>
                                            </div>
                                        </div>
                        @empty
                            <p class="text-gray-500 text-center py-8">Aucune contribution</p>
                        @endforelse
                    </div>
                </div>

                <!-- Transactions List -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-exchange-alt text-green-600"></i> Transactions
                        ({{ $cagnotte->transactions->count() }})
                    </h3>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @forelse($cagnotte->transactions as $transaction)
                            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ ucfirst($transaction->type) }}</p>
                                        <p class="text-sm text-gray-600">Réf: {{ $transaction->reference }}</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p
                                            class="text-xl font-bold {{ $transaction->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 0, ',', ' ') }}
                                        </p>
                                        <p class="text-xs text-gray-500">Solde:
                                            {{ number_format($transaction->balance_after, 0, ',', ' ') }} FCFA</p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-8">Aucune transaction</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Right Column - Creator & Participants -->
            <div class="space-y-6">
                <!-- Creator Information -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-user text-purple-600"></i> Créateur
                    </h3>
                    <div class="text-center">
                        <div
                            class="w-20 h-20 mx-auto bg-gradient-to-r from-purple-400 to-pink-400 rounded-full flex items-center justify-center text-white text-2xl font-bold mb-3">
                            {{ strtoupper(substr($cagnotte->user->fullname ?? $cagnotte->user->phone, 0, 2)) }}
                        </div>
                        <p class="font-bold text-gray-900">{{ $cagnotte->user->fullname ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-600">{{ $cagnotte->user->phone }}</p>
                        <p class="text-xs text-gray-500 mt-2">Membre depuis
                            {{ $cagnotte->user->created_at->format('d/m/Y') }}</p>
                    </div>
                </div>

                <!-- Participants List -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-users text-blue-600"></i> Participants ({{ $cagnotte->participants->count() }})
                    </h3>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @forelse($cagnotte->participants as $participant)
                            <div class="p-3 bg-gray-50 rounded-lg">
                                <p class="font-semibold text-gray-900">{{ $participant->name }}</p>
                                <p class="text-sm text-gray-600">{{ $participant->phone }}</p>
                            </div>
                        @empty
                            <p class="text-gray-500 text-center py-4 text-sm">Aucun participant</p>
                        @endforelse
                    </div>
                </div>

                <!-- KYC Documents (if applicable) -->
                @if($cagnotte->creator_type === 'person' || $cagnotte->creator_type === 'company')
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-file-alt text-yellow-600"></i> Documents KYC
                        </h3>
                        <div class="space-y-2">
                            @if($cagnotte->profile_photo_path)
                                <a href="{{ $cagnotte->profile_photo_url }}" target="_blank"
                                    class="block p-2 bg-gray-50 rounded hover:bg-gray-100 transition-all">
                                    <i class="fas fa-image text-blue-600"></i> Photo de profil
                                </a>
                            @endif
                            @if($cagnotte->identity_document_path)
                                <a href="{{ $cagnotte->identity_document_url }}" target="_blank"
                                    class="block p-2 bg-gray-50 rounded hover:bg-gray-100 transition-all">
                                    <i class="fas fa-id-card text-green-600"></i> Pièce d'identité
                                </a>
                            @endif
                            @if($cagnotte->signed_contract_path)
                                <a href="{{ $cagnotte->signed_contract_url }}" target="_blank"
                                    class="block p-2 bg-gray-50 rounded hover:bg-gray-100 transition-all">
                                    <i class="fas fa-file-contract text-purple-600"></i> Contrat signé
                                </a>
                            @endif
                            @if($cagnotte->creator_type === 'company')
                                @if($cagnotte->company_logo_path)
                                    <a href="{{ $cagnotte->company_logo_url }}" target="_blank"
                                        class="block p-2 bg-gray-50 rounded hover:bg-gray-100 transition-all">
                                        <i class="fas fa-building text-indigo-600"></i> Logo entreprise
                                    </a>
                                @endif
                                @if($cagnotte->rccm_document_path)
                                    <a href="{{ $cagnotte->rccm_document_url }}" target="_blank"
                                        class="block p-2 bg-gray-50 rounded hover:bg-gray-100 transition-all">
                                        <i class="fas fa-file-pdf text-red-600"></i> Document RCCM
                                    </a>
                                @endif
                                @if($cagnotte->ifu_document_path)
                                    <a href="{{ $cagnotte->ifu_document_url }}" target="_blank"
                                        class="block p-2 bg-gray-50 rounded hover:bg-gray-100 transition-all">
                                        <i class="fas fa-file-pdf text-orange-600"></i> Document IFU
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    
    @if($cagnotte->payout_mode === 'escrow')
    <!-- Rejection Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl w-full max-w-md p-8 shadow-2xl transform transition-all duration-300">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center text-red-500">
                    <i class="fas fa-exclamation-triangle text-xl"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900">Motif du rejet</h3>
            </div>
            
            <p class="text-gray-600 mb-6">Veuillez expliquer pourquoi vous rejetez cette demande de déblocage. Ce message sera transmis au créateur.</p>
            
            <form action="{{ route('admin.cagnottes.reject-unlock', $cagnotte->id) }}" method="POST">
                @csrf
                <textarea name="reason" rows="4" required 
                    class="w-full rounded-2xl border-gray-200 focus:border-red-500 focus:ring-red-500 p-4 mb-6 transition-all" 
                    placeholder="Ex: Document non conforme, photo illisible..."></textarea>
                
                <div class="flex gap-3">
                    <button type="button" onclick="closeRejectModal()" 
                        class="flex-1 px-6 py-3 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors">
                        Annuler
                    </button>
                    <button type="submit" 
                        class="flex-1 px-6 py-3 rounded-xl font-bold text-white bg-red-500 hover:bg-red-600 transition shadow-lg shadow-red-100">
                        Confirmer le rejet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openRejectModal() {
        const modal = document.getElementById('rejectModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        // Add a small animation delay to content
        setTimeout(() => {
            modal.querySelector('div').classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    function closeRejectModal() {
        const modal = document.getElementById('rejectModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    
    // Close on click outside
    window.onclick = function(event) {
        let modal = document.getElementById('rejectModal');
        if (event.target == modal) {
            closeRejectModal();
        }
    }
    </script>
    @endif
@endsection