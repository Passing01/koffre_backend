@extends('admin.layout')

@section('title', 'Revenus Plateforme Kofre')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Revenus Plateforme</h2>
                <p class="text-gray-600 mt-1">Commissions prélevées sur les transactions (4,8% dépôt)</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-white/80 text-sm font-medium">Récompenses Plateforme Totales</p>
                <p class="text-4xl font-bold mt-2">{{ number_format($totalFees, 0, ',', ' ') }} XOF</p>
                <p class="text-white/60 text-xs mt-2">Cumul historique depuis le début</p>
            </div>

            <div class="bg-white border border-indigo-100 rounded-2xl p-6 shadow-lg flex flex-col justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">En attente de virement</p>
                    <p class="text-4xl font-black mt-2 text-indigo-700">{{ number_format($pendingEarningsSum, 0, ',', ' ') }} XOF</p>
                </div>
                
                <form action="{{ route('admin.platform-earnings.transfer') }}" method="POST" class="mt-4" onsubmit="return confirm('Voulez-vous vraiment virer ce montant vers votre compte PayDunya personnel ?')">
                    @csrf
                    <button type="submit" class="w-full bg-indigo-600 text-white font-bold py-3 rounded-xl hover:bg-indigo-700 transition-all shadow-md flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i> Virer vers mon compte PayDunya
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6">
            <form method="GET" action="{{ route('admin.platform-earnings.index') }}"
                class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Module</label>
                    <select name="module"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">Tous les modules</option>
                        @foreach($modules as $key => $label)
                            <option value="{{ $key }}" {{ request('module') == $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>
                <div class="flex items-end gap-2">
                    <button type="submit"
                        class="bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                    <a href="{{ route('admin.platform-earnings.index') }}"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Date</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Module</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Référence</th>
                            <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wider">Détails</th>
                            <th class="px-6 py-4 text-right text-xs font-bold uppercase tracking-wider">Commission</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($earnings as $earning)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $earning->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold tracking-wide 
                                        {{ $earning->module === 'cagnotte' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ strtoupper($earning->module) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-500">
                                    {{ $earning->reference }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    @if($earning->module === 'cagnotte')
                                        Cagnotte #{{ $earning->metadata['cagnotte_id'] ?? '—' }}
                                    @else
                                        Tontine #{{ $earning->metadata['tontine_id'] ?? '—' }}
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-emerald-600">
                                    +{{ number_format($earning->amount, 0, ',', ' ') }} XOF
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-coins text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-lg">Aucun gain enregistré pour le moment</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($earnings->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $earnings->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
