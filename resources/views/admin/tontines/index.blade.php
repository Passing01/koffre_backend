@extends('admin.layout')

@section('title', 'Tontines')

@section('content')
    <div class="space-y-8">

        {{-- Header --}}
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Tontines</h2>
                <p class="text-gray-600 mt-1">Gestion et supervision des tontines</p>
            </div>
        </div>

        {{-- Alertes --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-4 flex items-center gap-3">
                <i class="fas fa-check-circle text-green-500 text-xl"></i>
                {{ session('success') }}
            </div>
        @endif
        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-4">
                @foreach($errors->all() as $err)
                    <p><i class="fas fa-exclamation-circle mr-2 text-red-500"></i>{{ $err }}</p>
                @endforeach
            </div>
        @endif

        {{-- Stats rapides --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="md:col-span-1 stat-card rounded-2xl p-5 text-white shadow-lg">
                <p class="text-white/80 text-xs font-medium">Total</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($stats['total']) }}</p>
                <p class="text-white/60 text-xs mt-1"><i class="fas fa-circle-nodes"></i> Tontines</p>
            </div>
            <div class="rounded-2xl p-5 text-white shadow-lg" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)">
                <p class="text-white/80 text-xs font-medium">Actives</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($stats['active']) }}</p>
                <p class="text-white/60 text-xs mt-1"><i class="fas fa-check-circle"></i> En cours</p>
            </div>
            <div class="rounded-2xl p-5 text-white shadow-lg" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%)">
                <p class="text-white/80 text-xs font-medium">Désactivées</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($stats['disabled']) }}</p>
                <p class="text-white/60 text-xs mt-1"><i class="fas fa-ban"></i> Modérées</p>
            </div>
            <div class="rounded-2xl p-5 text-white shadow-lg" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%)">
                <p class="text-white/80 text-xs font-medium">Groupe</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($stats['group']) }}</p>
                <p class="text-white/60 text-xs mt-1"><i class="fas fa-users"></i> Multi-membres</p>
            </div>
            <div class="rounded-2xl p-5 text-white shadow-lg" style="background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%)">
                <p class="text-white/80 text-xs font-medium">Individuelle</p>
                <p class="text-3xl font-bold mt-1">{{ number_format($stats['individual']) }}</p>
                <p class="text-white/60 text-xs mt-1"><i class="fas fa-piggy-bank"></i> Épargne perso</p>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <form method="GET" action="{{ route('admin.tontines.index') }}" class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-48">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Titre, créateur, téléphone..."
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                </div>
                <div class="w-44">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select name="status"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Tous</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Actif</option>
                        <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>Désactivé</option>
                    </select>
                </div>
                <div class="w-44">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fréquence</label>
                    <select name="frequency"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Toutes</option>
                        <option value="days" {{ request('frequency') === 'days' ? 'selected' : '' }}>Journalier</option>
                        <option value="weeks" {{ request('frequency') === 'weeks' ? 'selected' : '' }}>Hebdomadaire</option>
                        <option value="months" {{ request('frequency') === 'months' ? 'selected' : '' }}>Mensuel</option>
                    </select>
                </div>
                <div class="w-44">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type"
                        class="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500">
                        <option value="">Tous</option>
                        <option value="group" {{ request('type') === 'group' ? 'selected' : '' }}>Groupe</option>
                        <option value="individual" {{ request('type') === 'individual' ? 'selected' : '' }}>Individuelle</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="px-5 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-lg text-sm font-medium hover:opacity-90 transition">
                        <i class="fas fa-search mr-1"></i> Filtrer
                    </button>
                    <a href="{{ route('admin.tontines.index') }}"
                        class="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300 transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        {{-- Tableau --}}
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="text-left px-6 py-4 font-semibold text-gray-600">Tontine</th>
                            <th class="text-left px-6 py-4 font-semibold text-gray-600">Créateur</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Type</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Membres</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Cotisation</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Fréquence</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Date retrait</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Statut</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Créée le</th>
                            <th class="text-center px-6 py-4 font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($tontines as $tontine)
                            <tr class="table-row hover:bg-purple-50 transition">
                                <td class="px-6 py-4">
                                    <a href="{{ route('admin.tontines.show', $tontine->id) }}"
                                        class="font-semibold text-gray-900 hover:text-purple-600">
                                        {{ Str::limit($tontine->title, 40) }}
                                    </a>
                                    @if($tontine->moderation_reason)
                                        <p class="text-xs text-red-500 mt-1">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            {{ Str::limit($tontine->moderation_reason, 60) }}
                                        </p>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-medium text-gray-800">{{ $tontine->user->fullname ?? '—' }}</p>
                                    <p class="text-xs text-gray-500">{{ $tontine->user->phone ?? '' }}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($tontine->type === 'individual')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">
                                            <i class="fas fa-piggy-bank"></i> Individuelle
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700">
                                            <i class="fas fa-users"></i> Groupe
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 font-semibold text-gray-700">
                                        <i class="fas fa-users text-purple-400"></i>
                                        {{ $tontine->members->count() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center font-semibold text-purple-700">
                                    {{ number_format($tontine->amount_per_installment, 0, ',', ' ') }}
                                    {{ $tontine->currency ?? 'XOF' }}
                                </td>
                                <td class="px-6 py-4 text-center text-gray-600">
                                    @php
                                        $freqLabels = ['days' => 'Jour(s)', 'weeks' => 'Semaine(s)', 'months' => 'Mois'];
                                    @endphp
                                    {{ $tontine->frequency_number }}
                                    {{ $freqLabels[$tontine->frequency] ?? $tontine->frequency }}
                                </td>
                                <td class="px-6 py-4 text-center text-sm">
                                    @if($tontine->type === 'individual' && $tontine->target_payout_date)
                                        <span class="font-medium text-orange-600">
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            {{ $tontine->target_payout_date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($tontine->status === 'active')
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                            <i class="fas fa-circle text-green-500 mr-1 text-[8px]"></i> Actif
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                            <i class="fas fa-ban mr-1"></i> Désactivé
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center text-gray-500 text-xs">
                                    {{ $tontine->created_at->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="{{ route('admin.tontines.show', $tontine->id) }}"
                                            class="px-3 py-1.5 bg-purple-100 text-purple-700 rounded-lg text-xs font-medium hover:bg-purple-200 transition">
                                            <i class="fas fa-eye mr-1"></i> Voir
                                        </a>
                                        @if($tontine->status === 'active')
                                            <button
                                                onclick="openDisableModal({{ $tontine->id }}, '{{ addslashes($tontine->title) }}')"
                                                class="px-3 py-1.5 bg-red-100 text-red-700 rounded-lg text-xs font-medium hover:bg-red-200 transition">
                                                <i class="fas fa-ban mr-1"></i> Désactiver
                                            </button>
                                        @else
                                            <form method="POST" action="{{ route('admin.tontines.enable', $tontine->id) }}"
                                                class="inline">
                                                @csrf
                                                <button type="submit"
                                                    class="px-3 py-1.5 bg-green-100 text-green-700 rounded-lg text-xs font-medium hover:bg-green-200 transition">
                                                    <i class="fas fa-check-circle mr-1"></i> Réactiver
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-16 text-center text-gray-400">
                                    <i class="fas fa-circle-nodes text-5xl mb-4 block opacity-30"></i>
                                    Aucune tontine trouvée.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($tontines->hasPages())
                <div class="px-6 py-4 border-t border-gray-100">
                    {{ $tontines->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- Modal Désactivation --}}
    <div id="disableModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-1">
                <i class="fas fa-ban text-red-500 mr-2"></i>Désactiver cette tontine
            </h3>
            <p class="text-sm text-gray-500 mb-4" id="disableModalTitle"></p>
            <form id="disableForm" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Motif de désactivation *</label>
                    <textarea name="reason" rows="3" required placeholder="Ex : Activité frauduleuse détectée..."
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 resize-none"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeDisableModal()"
                        class="flex-1 px-4 py-2.5 border border-gray-300 text-gray-700 rounded-xl text-sm font-medium hover:bg-gray-50 transition">
                        Annuler
                    </button>
                    <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-xl text-sm font-medium hover:bg-red-700 transition">
                        <i class="fas fa-ban mr-1"></i> Désactiver
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openDisableModal(id, title) {
            document.getElementById('disableModal').classList.remove('hidden');
            document.getElementById('disableModal').classList.add('flex');
            document.getElementById('disableModalTitle').textContent = 'Tontine : ' + title;
            document.getElementById('disableForm').action = '/admin/tontines/' + id + '/disable';
        }

        function closeDisableModal() {
            document.getElementById('disableModal').classList.add('hidden');
            document.getElementById('disableModal').classList.remove('flex');
        }

        // Fermer en cliquant en dehors
        document.getElementById('disableModal').addEventListener('click', function (e) {
            if (e.target === this) closeDisableModal();
        });
    </script>
@endpush