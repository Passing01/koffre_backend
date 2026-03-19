@extends('admin.layout')

@section('title', 'Gestion des Utilisateurs')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Gestion des Utilisateurs</h2>
                <p class="text-gray-600 mt-1">{{ $users->total() }} utilisateurs au total</p>
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Succès !</strong>
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <strong class="font-bold">Erreur !</strong>
                <span class="block sm:inline">{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <form method="GET" action="{{ route('admin.users.index') }}"
                class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Nom, téléphone..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Statut</label>
                    <select name="is_blocked"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm">
                        <option value="">Tous les statuts</option>
                        <option value="0" {{ request('is_blocked') === '0' ? 'selected' : '' }}>Actifs</option>
                        <option value="1" {{ request('is_blocked') === '1' ? 'selected' : '' }}>Bloqués</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all text-sm font-bold">
                        Filtrer
                    </button>
                    <a href="{{ route('admin.users.index') }}"
                        class="px-3 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Nom complet</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Téléphone</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Cagnottes</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Contributions</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Conditions</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Status</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($users as $user)
                            <tr class="table-row">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold">
                                            {{ strtoupper(substr($user->fullname ?? 'U', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $user->fullname ?? 'Sans nom' }}</p>
                                            @if($user->is_admin)
                                                <span class="px-2 py-0.5 text-[10px] bg-indigo-100 text-indigo-700 rounded-full font-bold">ADMIN</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $user->phone }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 font-semibold">{{ $user->cagnottes_count }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 font-semibold">{{ $user->contributions_count }}</td>
                                <td class="px-6 py-4">
                                    @if($user->accepted_terms_at)
                                        <div class="flex flex-col">
                                            <span class="text-green-600 text-xs font-semibold">
                                                <i class="fas fa-check-circle mr-1"></i> Acceptées
                                            </span>
                                            <span class="text-[10px] text-gray-400">{{ $user->accepted_terms_at->format('d/m/Y') }}</span>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs italic">En attente</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($user->is_blocked)
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">
                                            <i class="fas fa-user-slash mr-1 text-[10px]"></i> Bloqué
                                        </span>
                                    @else
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                            <i class="fas fa-user-check mr-1 text-[10px]"></i> Actif
                                        </span>
                                    @endif
                                    @if($user->last_activity_at)
                                        <p class="text-[10px] text-gray-400 mt-1 whitespace-nowrap">
                                            <i class="far fa-clock mr-1"></i> Accès : {{ $user->last_activity_at->diffForHumans() }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-2">
                                        <a href="{{ route('admin.users.show', $user->id) }}" class="inline-flex items-center px-3 py-1 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 transition-all text-sm font-medium">
                                            <i class="fas fa-eye mr-1"></i> Voir
                                        </a>
                                        @if(!$user->is_admin)
                                            @if($user->is_blocked)
                                                <form method="POST" action="{{ route('admin.users.unblock', $user->id) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="p-1 hover:text-green-600 transition-all" title="Débloquer">
                                                        <i class="fas fa-unlock"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.users.block', $user->id) }}" class="inline" onsubmit="return confirm('Confirmer le blocage ?')">
                                                    @csrf
                                                    <button type="submit" class="p-1 hover:text-red-600 transition-all" title="Bloquer">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-users-slash text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-lg">Aucun utilisateur trouvé</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
