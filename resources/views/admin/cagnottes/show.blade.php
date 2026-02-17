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
@endsection