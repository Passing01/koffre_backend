@extends('admin.layout')

@section('title', 'Gestion des Cagnottes')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Gestion des Cagnottes</h2>
                <p class="text-gray-600 mt-1">{{ $cagnottes->total() }} cagnottes au total</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <form method="GET" action="{{ route('admin.cagnottes.index') }}"
                class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Titre, créateur..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                        <option value="">Tous les statuts</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Complétée</option>
                        <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expirée</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Visibilité</label>
                    <select name="visibility"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                        <option value="">Toutes</option>
                        <option value="public" {{ request('visibility') === 'public' ? 'selected' : '' }}>Publique</option>
                        <option value="private" {{ request('visibility') === 'private' ? 'selected' : '' }}>Privée</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Déblocage (Coffre)</label>
                    <select name="unlock_status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                        <option value="">Tous</option>
                        <option value="pending" {{ request('unlock_status') === 'pending' ? 'selected' : '' }}>En attente 🔑
                        </option>
                        <option value="approved" {{ request('unlock_status') === 'approved' ? 'selected' : '' }}>Approuvé ✅
                        </option>
                        <option value="rejected" {{ request('unlock_status') === 'rejected' ? 'selected' : '' }}>Rejeté ❌
                        </option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all text-sm font-bold">
                        Filtrer
                    </button>
                    <a href="{{ route('admin.cagnottes.index') }}"
                        class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Cagnottes Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Titre</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Créateur</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Objectif</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Collecté</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Progression</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Statut</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Visibilité</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($cagnottes as $cagnotte)
                                        <tr class="table-row">
                                            <td class="px-6 py-4 text-sm text-gray-900 font-mono">#{{ $cagnotte->id }}</td>
                                            <td class="px-6 py-4">
                                                <div class="max-w-xs">
                                                    <p class="font-semibold text-gray-900">{{ Str::limit($cagnotte->title, 40) }}</p>
                                                    <p class="text-sm text-gray-600">{{ Str::limit($cagnotte->description, 50) }}</p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div>
                                                    <p class="font-medium text-gray-900">{{ $cagnotte->user->fullname ?? 'N/A' }}</p>
                                                    <p class="text-sm text-gray-600">{{ $cagnotte->user->phone }}</p>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm font-semibold text-gray-900">
                                                {{ number_format($cagnotte->target_amount, 0, ',', ' ') }} FCFA
                                            </td>
                                            <td class="px-6 py-4 text-sm font-semibold text-purple-600">
                                                {{ number_format($cagnotte->current_amount, 0, ',', ' ') }} FCFA
                                            </td>
                                            <td class="px-6 py-4">
                                                @php
                                                    $progress = $cagnotte->target_amount > 0 ? ($cagnotte->current_amount / $cagnotte->target_amount) * 100 : 0;
                                                @endphp
                                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                    <div class="bg-gradient-to-r from-purple-600 to-pink-600 h-2.5 rounded-full"
                                                        style="width: {{ min($progress, 100) }}%"></div>
                                                </div>
                                                <p class="text-xs text-gray-600 mt-1">{{ number_format($progress, 1) }}%</p>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span
                                                    class="px-3 py-1 text-xs font-semibold rounded-full
                                                                                                                                    {{ $cagnotte->status === 'active' ? 'bg-green-100 text-green-700' :
                            ($cagnotte->status === 'completed' ? 'bg-blue-100 text-blue-700' :
                                ($cagnotte->status === 'expired' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700')) }}">
                                                    {{ ucfirst($cagnotte->status) }}
                                                </span>
                                                @if($cagnotte->payout_mode === 'escrow' && $cagnotte->unlock_status === 'pending')
                                                    <div
                                                        class="mt-1 flex items-center gap-1 text-[10px] font-bold text-orange-600 animate-pulse">
                                                        <i class="fas fa-key scale-75"></i> 🔑 ATTENTE DÉBLOCAGE
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4">
                                                <span
                                                    class="px-3 py-1 text-xs font-semibold rounded-full
                                                                                                                {{ $cagnotte->visibility === 'public' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-700' }}">
                                                    <i class="fas fa-{{ $cagnotte->visibility === 'public' ? 'globe' : 'lock' }}"></i>
                                                    {{ ucfirst($cagnotte->visibility) }}
                                                </span>
                                                @if($cagnotte->is_private_coffre)
                                                    <div class="mt-1 flex items-center gap-1 text-[10px] font-bold text-indigo-600">
                                                        <i class="fas fa-shield-alt scale-75"></i> SYSTÈME COFFRE
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">
                                                {{ $cagnotte->created_at->format('d/m/Y') }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <a href="{{ route('admin.cagnottes.show', $cagnotte->id) }}"
                                                    class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-all text-sm font-medium">
                                                    <i class="fas fa-eye mr-1"></i> Voir
                                                </a>
                                            </td>
                                        </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-lg">Aucune cagnotte trouvée</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($cagnottes->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $cagnottes->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection