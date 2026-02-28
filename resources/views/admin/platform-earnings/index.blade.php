@extends('admin.layout')

@section('title', 'Commissions tontine (plateforme)')

@section('content')
    <div class="space-y-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Commissions tontine (plateforme)</h2>
                <p class="text-gray-600 mt-1">Frais 4,5% à la contribution + commission au transfert</p>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white shadow-lg">
            <p class="text-white/80 text-sm font-medium">Total commissions plateforme</p>
            <p class="text-4xl font-bold mt-2">{{ number_format($totalFees, 0, ',', ' ') }} XOF</p>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6">
            <form method="GET" action="{{ route('admin.platform-earnings.index') }}"
                class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tontine</label>
                    <select name="tontine_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">Toutes les tontines</option>
                        @foreach($tontines as $t)
                            <option value="{{ $t->id }}" {{ request('tontine_id') == $t->id ? 'selected' : '' }}>
                                {{ Str::limit($t->title, 40) }}
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
                        class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                    <a href="{{ route('admin.platform-earnings.index') }}"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Tontine</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Source</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Référence</th>
                            <th class="px-6 py-4 text-right text-sm font-semibold">Montant</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($earnings as $earning)
                            <tr class="table-row">
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $earning->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($earning->tontine)
                                        <a href="{{ route('admin.tontines.show', $earning->tontine->id) }}"
                                            class="text-purple-600 hover:text-purple-700 font-medium">
                                            {{ Str::limit($earning->tontine->title, 35) }}
                                        </a>
                                    @else
                                        <span class="text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    @if($earning->tontine_payment_id)
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                            Contribution
                                        </span>
                                    @else
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                                            Transfert cycle #{{ $earning->tontinePayout?->cycle_number ?? '—' }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-gray-600">
                                    {{ $earning->reference ?? $earning->tontinePayment?->payment_reference ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-green-700">
                                    +{{ number_format($earning->amount, 0, ',', ' ') }} XOF
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-coins text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-lg">Aucune commission enregistrée</p>
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
