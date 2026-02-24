@extends('admin.layout')

@section('title', 'Tontine — ' . $tontine->title)

@section('content')
    <div class="space-y-8">

        {{-- Breadcrumb & Header --}}
        <div class="flex flex-wrap justify-between items-start gap-4">
            <div>
                <nav class="flex items-center gap-2 text-sm text-gray-500 mb-2">
                    <a href="{{ route('admin.tontines.index') }}" class="hover:text-purple-600">
                        <i class="fas fa-circle-nodes mr-1"></i>Tontines
                    </a>
                    <i class="fas fa-chevron-right text-xs"></i>
                    <span class="text-gray-800 font-medium">{{ Str::limit($tontine->title, 50) }}</span>
                </nav>
                <h2 class="text-3xl font-bold text-gray-900">{{ $tontine->title }}</h2>
                @if($tontine->description)
                    <p class="text-gray-600 mt-1">{{ $tontine->description }}</p>
                @endif
            </div>
            <div class="flex gap-3">
                @if($tontine->status === 'active')
                    <button onclick="openDisableModal()"
                        class="px-5 py-2.5 bg-red-600 text-white rounded-xl text-sm font-semibold hover:bg-red-700 transition flex items-center gap-2">
                        <i class="fas fa-ban"></i> Désactiver
                    </button>
                @else
                    <form method="POST" action="{{ route('admin.tontines.enable', $tontine->id) }}">
                        @csrf
                        <button type="submit"
                            class="px-5 py-2.5 bg-green-600 text-white rounded-xl text-sm font-semibold hover:bg-green-700 transition flex items-center gap-2">
                            <i class="fas fa-check-circle"></i> Réactiver
                        </button>
                    </form>
                @endif
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

        {{-- Statut bloqué --}}
        @if($tontine->status === 'disabled')
            <div class="bg-red-50 border border-red-300 rounded-xl px-5 py-4 flex items-start gap-4">
                <i class="fas fa-ban text-red-500 text-2xl mt-0.5"></i>
                <div>
                    <p class="font-semibold text-red-800">Cette tontine est désactivée par l'administration.</p>
                    @if($tontine->moderation_reason)
                        <p class="text-sm text-red-700 mt-1">Motif : <em>{{ $tontine->moderation_reason }}</em></p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Statistiques --}}
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
            @php
                $statCards = [
                    ['label' => 'Membres total', 'value' => $stats['total_members'], 'icon' => 'fa-users', 'color' => 'from-purple-500 to-indigo-500'],
                    ['label' => 'Membres actifs', 'value' => $stats['active_members'], 'icon' => 'fa-user-check', 'color' => 'from-green-500 to-teal-500'],
                    ['label' => 'En attente', 'value' => $stats['pending_members'], 'icon' => 'fa-user-clock', 'color' => 'from-yellow-400 to-orange-400'],
                    ['label' => 'Total collecté', 'value' => number_format($stats['total_collected'], 0, ',', ' ') . ' XOF', 'icon' => 'fa-coins', 'color' => 'from-blue-500 to-cyan-500'],
                    ['label' => 'Total reversé', 'value' => number_format($stats['total_payouts'], 0, ',', ' ') . ' XOF', 'icon' => 'fa-money-bill-transfer', 'color' => 'from-pink-500 to-rose-500'],
                ];
            @endphp
            @foreach($statCards as $card)
                <div class="bg-gradient-to-br {{ $card['color'] }} rounded-2xl p-5 text-white shadow-md">
                    <i class="fas {{ $card['icon'] }} text-2xl opacity-80 mb-2 block"></i>
                    <p class="text-2xl font-bold">{{ $card['value'] }}</p>
                    <p class="text-white/70 text-xs mt-1">{{ $card['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            {{-- Informations générales --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="fas fa-info-circle text-purple-500 mr-2"></i>Informations
                    </h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Statut</dt>
                            <dd>
                                @if($tontine->status === 'active')
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                        <i class="fas fa-circle text-green-500 mr-1 text-[8px]"></i>Actif
                                    </span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                        <i class="fas fa-ban mr-1"></i>Désactivé
                                    </span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Cotisation</dt>
                            <dd class="font-semibold text-purple-700">
                                {{ number_format($tontine->amount_per_installment, 0, ',', ' ') }}
                                {{ $tontine->currency ?? 'XOF' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Fréquence</dt>
                            <dd class="font-medium text-gray-800">
                                @php $freqLabels = ['days' => 'jour(s)', 'weeks' => 'semaine(s)', 'months' => 'mois']; @endphp
                                Tous les {{ $tontine->frequency_number }}
                                {{ $freqLabels[$tontine->frequency] ?? $tontine->frequency }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Mode de reversement</dt>
                            <dd class="font-medium text-gray-800">{{ ucfirst($tontine->payout_mode ?? 'manuel') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Commission créateur</dt>
                            <dd class="font-medium text-gray-800">{{ $tontine->creator_percentage ?? 0 }}%</dd>
                        </div>
                        @if($tontine->max_participants)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Max participants</dt>
                                <dd class="font-medium text-gray-800">{{ $tontine->max_participants }}</dd>
                            </div>
                        @endif
                        @if($tontine->late_fee_amount)
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Pénalité retard</dt>
                                <dd class="font-medium text-red-600">{{ number_format($tontine->late_fee_amount, 0, ',', ' ') }}
                                    XOF</dd>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Débute le</dt>
                            <dd class="font-medium text-gray-800">
                                {{ $tontine->starts_at ? $tontine->starts_at->format('d/m/Y') : '—' }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Créée le</dt>
                            <dd class="font-medium text-gray-800">{{ $tontine->created_at->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Créateur --}}
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="fas fa-user-tie text-indigo-500 mr-2"></i>Créateur
                    </h3>
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-400 to-indigo-400 flex items-center justify-center text-white font-bold text-lg">
                            {{ strtoupper(substr($tontine->user->fullname ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-semibold text-gray-900">{{ $tontine->user->fullname ?? '—' }}</p>
                            <p class="text-sm text-gray-500">{{ $tontine->user->phone ?? '' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Membres & Paiements --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Liste membres --}}
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="fas fa-users text-blue-500 mr-2"></i>Membres ({{ $tontine->members->count() }})
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="text-left px-4 py-3 font-semibold text-gray-600 rounded-l-lg">Membre</th>
                                    <th class="text-center px-4 py-3 font-semibold text-gray-600">Rang</th>
                                    <th class="text-center px-4 py-3 font-semibold text-gray-600">Statut</th>
                                    <th class="text-right px-4 py-3 font-semibold text-gray-600 rounded-r-lg">Permissions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @forelse($tontine->members as $member)
                                    <tr class="hover:bg-purple-50 transition">
                                        <td class="px-4 py-3">
                                            <p class="font-medium text-gray-800">{{ $member->user->fullname ?? 'Invité' }}</p>
                                            <p class="text-xs text-gray-500">{{ $member->phone }}</p>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-purple-100 text-purple-700 font-bold text-xs">
                                                {{ $member->payout_rank ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @php
                                                $statusMap = [
                                                    'accepted' => ['label' => 'Accepté', 'class' => 'bg-green-100 text-green-700'],
                                                    'pending' => ['label' => 'En attente', 'class' => 'bg-yellow-100 text-yellow-700'],
                                                    'rejected' => ['label' => 'Rejeté', 'class' => 'bg-red-100 text-red-700'],
                                                ];
                                                $s = $statusMap[$member->status] ?? ['label' => ucfirst($member->status), 'class' => 'bg-gray-100 text-gray-700'];
                                            @endphp
                                            <span
                                                class="px-2 py-1 rounded-full text-xs font-semibold {{ $s['class'] }}">{{ $s['label'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            @if($member->permissions)
                                                @foreach((array) $member->permissions as $perm => $val)
                                                    @if($val)
                                                        <span
                                                            class="inline-block px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-600 text-[10px] font-medium mr-1">
                                                            {{ str_replace('_', ' ', $perm) }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            @else
                                                <span class="text-gray-400 text-xs">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center py-8 text-gray-400">Aucun membre</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Derniers paiements --}}
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="fas fa-credit-card text-green-500 mr-2"></i>Derniers paiements
                    </h3>
                    @if($tontine->payments->isEmpty())
                        <p class="text-center text-gray-400 py-6">Aucun paiement enregistré.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="text-left px-4 py-3 font-semibold text-gray-600 rounded-l-lg">Référence</th>
                                        <th class="text-center px-4 py-3 font-semibold text-gray-600">Cycle</th>
                                        <th class="text-right px-4 py-3 font-semibold text-gray-600">Montant</th>
                                        <th class="text-center px-4 py-3 font-semibold text-gray-600">Statut</th>
                                        <th class="text-center px-4 py-3 font-semibold text-gray-600 rounded-r-lg">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($tontine->payments->take(15) as $payment)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-3 font-mono text-xs text-gray-600">{{ $payment->payment_reference }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    class="px-2 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-semibold">#{{ $payment->cycle_number }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                                {{ number_format($payment->amount, 0, ',', ' ') }} XOF
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @php
                                                    $ps = [
                                                        'success' => 'bg-green-100 text-green-700',
                                                        'pending' => 'bg-yellow-100 text-yellow-700',
                                                        'failed' => 'bg-red-100 text-red-700',
                                                    ];
                                                @endphp
                                                <span
                                                    class="px-2 py-1 rounded-full text-xs font-semibold {{ $ps[$payment->status] ?? 'bg-gray-100 text-gray-600' }}">
                                                    {{ ucfirst($payment->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs text-gray-500">
                                                {{ $payment->created_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>

                {{-- Reversements --}}
                @if($tontine->payouts->isNotEmpty())
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">
                            <i class="fas fa-money-bill-transfer text-pink-500 mr-2"></i>Reversements (payouts)
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="text-left px-4 py-3 font-semibold text-gray-600 rounded-l-lg">Bénéficiaire
                                        </th>
                                        <th class="text-center px-4 py-3 font-semibold text-gray-600">Cycle</th>
                                        <th class="text-right px-4 py-3 font-semibold text-gray-600">Montant net</th>
                                        <th class="text-center px-4 py-3 font-semibold text-gray-600">Statut</th>
                                        <th class="text-center px-4 py-3 font-semibold text-gray-600 rounded-r-lg">Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach($tontine->payouts as $payout)
                                        <tr class="hover:bg-gray-50 transition">
                                            <td class="px-4 py-3">
                                                <p class="font-medium text-gray-800">
                                                    {{ $payout->tontineMember->user->fullname ?? 'Invité' }}</p>
                                                <p class="text-xs text-gray-500">{{ $payout->tontineMember->phone ?? '' }}</p>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <span
                                                    class="px-2 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-semibold">#{{ $payout->cycle_number }}</span>
                                            </td>
                                            <td class="px-4 py-3 text-right font-bold text-green-700">
                                                {{ number_format($payout->amount, 0, ',', ' ') }} XOF
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                @php
                                                    $ps = ['success' => 'bg-green-100 text-green-700', 'pending' => 'bg-yellow-100 text-yellow-700', 'failed' => 'bg-red-100 text-red-700'];
                                                @endphp
                                                <span
                                                    class="px-2 py-1 rounded-full text-xs font-semibold {{ $ps[$payout->status] ?? 'bg-gray-100 text-gray-700' }}">
                                                    {{ ucfirst($payout->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center text-xs text-gray-500">
                                                {{ $payout->created_at->format('d/m/Y H:i') }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>
        </div>
    </div>

    {{-- Modal Désactivation --}}
    <div id="disableModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-1">
                <i class="fas fa-ban text-red-500 mr-2"></i>Désactiver la tontine
            </h3>
            <p class="text-sm text-gray-500 mb-4">{{ $tontine->title }}</p>
            <form method="POST" action="{{ route('admin.tontines.disable', $tontine->id) }}">
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
        function openDisableModal() {
            document.getElementById('disableModal').classList.remove('hidden');
            document.getElementById('disableModal').classList.add('flex');
        }

        function closeDisableModal() {
            document.getElementById('disableModal').classList.add('hidden');
            document.getElementById('disableModal').classList.remove('flex');
        }

        document.getElementById('disableModal').addEventListener('click', function (e) {
            if (e.target === this) closeDisableModal();
        });
    </script>
@endpush