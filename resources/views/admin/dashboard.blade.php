@extends('admin.layout')

@section('title', 'Tableau de bord')

@section('content')
    <div class="space-y-8">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Tableau de bord</h2>
                <p class="text-gray-600 mt-1">Vue d'ensemble de la plateforme</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">Dernière mise à jour</p>
                <p class="text-lg font-semibold text-gray-900">{{ now()->format('d/m/Y H:i') }}</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <!-- Total Cagnottes -->
            <div class="stat-card rounded-2xl p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Total Cagnottes</p>
                        <p class="text-4xl font-bold mt-2">{{ number_format($stats['total_cagnottes']) }}</p>
                        <p class="text-white/70 text-xs mt-2">
                            <i class="fas fa-check-circle"></i> {{ $stats['active_cagnottes'] }} actives
                        </p>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-piggy-bank text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Users -->
            <div class="stat-card rounded-2xl p-6 text-white shadow-lg"
                style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Utilisateurs</p>
                        <p class="text-4xl font-bold mt-2">{{ number_format($stats['total_users']) }}</p>
                        <p class="text-white/70 text-xs mt-2">
                            <i class="fas fa-user-plus"></i> Inscrits
                        </p>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-users text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Contributions -->
            <div class="stat-card rounded-2xl p-6 text-white shadow-lg"
                style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Contributions</p>
                        <p class="text-4xl font-bold mt-2">{{ number_format($stats['total_contributions']) }}</p>
                        <p class="text-white/70 text-xs mt-2">
                            <i class="fas fa-clock"></i> {{ $stats['pending_contributions'] }} en attente
                        </p>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-hand-holding-heart text-3xl"></i>
                    </div>
                </div>
            </div>

            <!-- Total Amount -->
            <div class="stat-card rounded-2xl p-6 text-white shadow-lg"
                style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">Montant Total</p>
                        <p class="text-4xl font-bold mt-2">
                            {{ number_format($stats['total_amount_collected'], 0, ',', ' ') }}</p>
                        <p class="text-white/70 text-xs mt-2">
                            <i class="fas fa-coins"></i> FCFA collectés
                        </p>
                    </div>
                    <div class="w-16 h-16 bg-white/20 rounded-full flex items-center justify-center backdrop-blur-sm">
                        <i class="fas fa-money-bill-wave text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Daily Statistics -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-chart-bar text-purple-600"></i> Statistiques des 7 derniers jours
                </h3>
                <div class="space-y-3">
                    @forelse($daily_stats as $stat)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-semibold text-gray-900">{{ \Carbon\Carbon::parse($stat->date)->format('d/m/Y') }}
                                </p>
                                <p class="text-sm text-gray-600">{{ $stat->count }} contributions</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-purple-600">{{ number_format($stat->total, 0, ',', ' ') }} FCFA
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">Aucune donnée disponible</p>
                    @endforelse
                </div>
            </div>

            <!-- Status Overview -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-info-circle text-blue-600"></i> Aperçu des statuts
                </h3>
                <div class="space-y-4">
                    <div class="p-4 bg-green-50 rounded-lg border-l-4 border-green-500">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-green-900">Cagnottes actives</span>
                            <span class="text-2xl font-bold text-green-600">{{ $stats['active_cagnottes'] }}</span>
                        </div>
                    </div>
                    <div class="p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-blue-900">Cagnottes complétées</span>
                            <span class="text-2xl font-bold text-blue-600">{{ $stats['completed_cagnottes'] }}</span>
                        </div>
                    </div>
                    <div class="p-4 bg-yellow-50 rounded-lg border-l-4 border-yellow-500">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-yellow-900">Contributions en attente</span>
                            <span class="text-2xl font-bold text-yellow-600">{{ $stats['pending_contributions'] }}</span>
                        </div>
                    </div>
                    <div class="p-4 bg-red-50 rounded-lg border-l-4 border-red-500">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-red-900">Contributions échouées</span>
                            <span class="text-2xl font-bold text-red-600">{{ $stats['failed_contributions'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Cagnottes -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-piggy-bank text-purple-600"></i> Cagnottes récentes
                    </h3>
                    <a href="{{ route('admin.cagnottes.index') }}"
                        class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                        Voir tout <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="space-y-3">
                    @forelse($recent_cagnottes as $cagnotte)
                        <div class="table-row p-3 rounded-lg border border-gray-200">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <a href="{{ route('admin.cagnottes.show', $cagnotte->id) }}"
                                        class="font-semibold text-gray-900 hover:text-purple-600">
                                        {{ Str::limit($cagnotte->title, 40) }}
                                    </a>
                                    <p class="text-sm text-gray-600 mt-1">
                                        Par {{ $cagnotte->user->fullname ?? $cagnotte->user->phone }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-2">
                                        <span
                                            class="px-2 py-1 text-xs rounded-full {{ $cagnotte->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                            {{ ucfirst($cagnotte->status) }}
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ $cagnotte->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right ml-4">
                                    <p class="text-sm font-semibold text-purple-600">
                                        {{ number_format($cagnotte->current_amount, 0, ',', ' ') }}</p>
                                    <p class="text-xs text-gray-500">/
                                        {{ number_format($cagnotte->target_amount, 0, ',', ' ') }} FCFA</p>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">Aucune cagnotte récente</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Contributions -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-bold text-gray-900">
                        <i class="fas fa-hand-holding-heart text-blue-600"></i> Contributions récentes
                    </h3>
                </div>
                <div class="space-y-3">
                    @forelse($recent_contributions as $contribution)
                                <div class="table-row p-3 rounded-lg border border-gray-200">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900">{{ $contribution->contributor_name }}</p>
                                            <p class="text-sm text-gray-600 mt-1">
                                                {{ Str::limit($contribution->cagnotte->title, 35) }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-2">
                                                <span
                                                    class="px-2 py-1 text-xs rounded-full 
                                                    {{ $contribution->payment_status === 'success' ? 'bg-green-100 text-green-700' :
                        ($contribution->payment_status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                                    {{ ucfirst($contribution->payment_status) }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    {{ $contribution->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="text-right ml-4">
                                            <p class="text-lg font-bold text-blue-600">
                                                {{ number_format($contribution->amount, 0, ',', ' ') }}</p>
                                            <p class="text-xs text-gray-500">FCFA</p>
                                        </div>
                                    </div>
                                </div>
                    @empty
                        <p class="text-gray-500 text-center py-8">Aucune contribution récente</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection