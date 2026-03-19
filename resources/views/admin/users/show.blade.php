@extends('admin.layout')

@section('title', 'Détails Utilisateur - ' . $user->fullname)

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="{{ route('admin.users.index') }}" class="text-gray-500 hover:text-gray-700 transition-all">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">{{ $user->fullname ?? 'Sans nom' }}</h2>
                    <p class="text-gray-600 mt-1">Membre depuis le {{ $user->created_at->format('d/m/Y') }}</p>
                </div>
            </div>
            
            <div class="flex gap-3">
                @if(!$user->is_admin)
                    @if($user->is_blocked)
                        <form method="POST" action="{{ route('admin.users.unblock', $user->id) }}">
                            @csrf
                            <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-all font-bold">
                                <i class="fas fa-unlock mr-2"></i>Débloquer
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.users.block', $user->id) }}" onsubmit="return confirm('Confirmer le blocage ?')">
                            @csrf
                            <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-all font-bold">
                                <i class="fas fa-ban mr-2"></i>Bloquer l'utilisateur
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- User Info Card -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Informations Profil</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Téléphone</span>
                            <span class="font-semibold text-gray-900">{{ $user->phone }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Pays</span>
                            <span class="font-semibold text-gray-900">{{ $user->country_code }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Statut Compte</span>
                            <span class="px-2 py-1 text-xs font-bold rounded-full {{ $user->is_blocked ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700' }}">
                                {{ $user->is_blocked ? 'Bloqué' : 'Actif' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Vérifié</span>
                            <span class="@if($user->is_verified) text-green-600 @else text-red-600 @endif font-semibold">
                                {{ $user->is_verified ? 'Oui' : 'Non' }}
                            </span>
                        </div>
                        <div class="flex justify-between border-t pt-4">
                            <span class="text-gray-600">Acceptation CGU</span>
                            <span class="font-semibold @if($user->accepted_terms_at) text-green-600 @else text-gray-400 @endif">
                                {{ $user->accepted_terms_at ? 'Le ' . $user->accepted_terms_at->format('d/m/Y H:i') : 'En attente' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Suivi d'Activité</h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Dernière Connexion</p>
                            <p class="text-gray-900 font-medium">
                                {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Jamais' }}
                                @if($user->last_login_at)
                                    <span class="text-[10px] text-gray-400 block">{{ $user->last_login_at->format('d/m/Y H:i:s') }}</span>
                                @endif
                            </p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-bold mb-1">Dernière Activité</p>
                            <p class="text-gray-900 font-medium">
                                {{ $user->last_activity_at ? $user->last_activity_at->diffForHumans() : 'Aucune' }}
                                @if($user->last_activity_at)
                                    <span class="text-[10px] text-gray-400 block">{{ $user->last_activity_at->format('d/m/Y H:i:s') }}</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 border-b pb-2">Statistiques</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-purple-50 p-3 rounded-xl border border-purple-100">
                            <p class="text-xs text-purple-600 font-bold uppercase">Cagnottes</p>
                            <p class="text-2xl font-bold text-purple-900">{{ $user->cagnottes_count }}</p>
                        </div>
                        <div class="bg-pink-50 p-3 rounded-xl border border-pink-100">
                            <p class="text-xs text-pink-600 font-bold uppercase">Contribs.</p>
                            <p class="text-2xl font-bold text-pink-900">{{ $user->contributions_count }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity Logs Table -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden h-full flex flex-col">
                    <div class="bg-gradient-to-r from-gray-800 to-gray-700 px-6 py-4">
                        <h3 class="text-lg font-bold text-white"><i class="fas fa-list-ul mr-2"></i>Journal d'Activités (Logs d'Audit)</h3>
                    </div>
                    <div class="overflow-x-auto flex-1">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Action</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cible</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Détails</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">IP</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @forelse($activities as $log)
                                    <tr class="hover:bg-gray-50 transition-all">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-[10px] font-bold rounded bg-purple-100 text-purple-700 uppercase">
                                                {{ str_replace('.', ' ', $log->action) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                            @if($log->auditable_type)
                                                <span class="font-medium">{{ class_basename($log->auditable_type) }}</span>
                                                <span class="text-xs text-gray-400">#{{ $log->auditable_id }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-xs text-gray-500">
                                            @if($log->metadata)
                                                <div class="max-w-xs overflow-hidden truncate" title="{{ json_encode($log->metadata) }}">
                                                    {{ json_encode($log->metadata) }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-[10px] text-gray-400">
                                            {{ $log->ip }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-900">
                                            {{ $log->created_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                                            <i class="fas fa-history text-3xl mb-2 text-gray-200 block"></i>
                                            Aucune activité enregistrée
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($activities->hasPages())
                        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                            {{ $activities->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
