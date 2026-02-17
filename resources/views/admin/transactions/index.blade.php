@extends('admin.layout')

@section('title', 'Gestion des Transactions')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Gestion des Transactions</h2>
                <p class="text-gray-600 mt-1">{{ $transactions->total() }} transactions au total</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-white/80 text-sm font-medium">Total Transactions</p>
                <p class="text-4xl font-bold mt-2">{{ number_format($stats['total_transactions']) }}</p>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-white/80 text-sm font-medium">Montant Total</p>
                <p class="text-3xl font-bold mt-2">{{ number_format($stats['total_amount'], 0, ',', ' ') }}</p>
                <p class="text-white/70 text-xs mt-1">FCFA</p>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-700 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-white/80 text-sm font-medium">Crédits</p>
                <p class="text-3xl font-bold mt-2">{{ number_format($stats['credit_total'], 0, ',', ' ') }}</p>
                <p class="text-white/70 text-xs mt-1">FCFA</p>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-700 rounded-2xl p-6 text-white shadow-lg">
                <p class="text-white/80 text-sm font-medium">Débits</p>
                <p class="text-3xl font-bold mt-2">{{ number_format($stats['debit_total'], 0, ',', ' ') }}</p>
                <p class="text-white/70 text-xs mt-1">FCFA</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-2xl shadow-lg p-6">
            <form method="GET" action="{{ route('admin.transactions.index') }}"
                class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Référence, cagnotte..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                    <select name="type"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">Tous les types</option>
                        <option value="credit" {{ request('type') === 'credit' ? 'selected' : '' }}>Crédit</option>
                        <option value="debit" {{ request('type') === 'debit' ? 'selected' : '' }}>Débit</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cagnotte</label>
                    <select name="cagnotte_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                        <option value="">Toutes les cagnottes</option>
                        @foreach($cagnottes as $cagnotte)
                            <option value="{{ $cagnotte->id }}" {{ request('cagnotte_id') == $cagnotte->id ? 'selected' : '' }}>
                                {{ Str::limit($cagnotte->title, 30) }}
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

                <div class="md:col-span-5 flex gap-2">
                    <button type="submit"
                        class="bg-gradient-to-r from-purple-600 to-pink-600 text-white px-6 py-2 rounded-lg hover:shadow-lg transition-all">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                    <a href="{{ route('admin.transactions.index') }}"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gradient-to-r from-purple-600 to-pink-600 text-white">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold">ID</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Référence</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Cagnotte</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Type</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Montant</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Solde après</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Date</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold">Métadonnées</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($transactions as $transaction)
                            <tr class="table-row">
                                <td class="px-6 py-4 text-sm text-gray-900 font-mono">#{{ $transaction->id }}</td>
                                <td class="px-6 py-4">
                                    <p class="font-mono text-sm text-gray-900">{{ $transaction->reference }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @if($transaction->cagnotte)
                                        <a href="{{ route('admin.cagnottes.show', $transaction->cagnotte->id) }}"
                                            class="text-purple-600 hover:text-purple-700 font-medium">
                                            {{ Str::limit($transaction->cagnotte->title, 30) }}
                                        </a>
                                    @else
                                        <span class="text-gray-500">N/A</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="px-3 py-1 text-xs font-semibold rounded-full
                                        {{ $transaction->type === 'credit' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                        <i class="fas fa-{{ $transaction->type === 'credit' ? 'arrow-down' : 'arrow-up' }}"></i>
                                        {{ ucfirst($transaction->type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p
                                        class="text-lg font-bold {{ $transaction->type === 'credit' ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 0, ',', ' ') }}
                                        FCFA
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="font-semibold text-gray-900">
                                        {{ number_format($transaction->balance_after, 0, ',', ' ') }} FCFA</p>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <p>{{ $transaction->created_at->format('d/m/Y') }}</p>
                                    <p class="text-xs text-gray-500">{{ $transaction->created_at->format('H:i:s') }}</p>
                                </td>
                                <td class="px-6 py-4">
                                    @if($transaction->meta)
                                        <button onclick="showMeta{{ $transaction->id }}()"
                                            class="text-purple-600 hover:text-purple-700 text-sm">
                                            <i class="fas fa-info-circle"></i> Voir
                                        </button>
                                        <div id="meta{{ $transaction->id }}"
                                            class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50"
                                            onclick="this.classList.add('hidden')">
                                            <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4"
                                                onclick="event.stopPropagation()">
                                                <h4 class="font-bold text-lg mb-3">Métadonnées de la transaction
                                                    #{{ $transaction->id }}</h4>
                                                <pre
                                                    class="bg-gray-100 p-4 rounded text-xs overflow-auto max-h-96">{{ json_encode($transaction->meta, JSON_PRETTY_PRINT) }}</pre>
                                                <button
                                                    onclick="document.getElementById('meta{{ $transaction->id }}').classList.add('hidden')"
                                                    class="mt-4 px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                                                    Fermer
                                                </button>
                                            </div>
                                        </div>
                                        <script>
                                            function showMeta{{ $transaction->id }}() {
                                                document.getElementById('meta{{ $transaction->id }}').classList.remove('hidden');
                                            }
                                        </script>
                                    @else
                                        <span class="text-gray-400 text-sm">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-300"></i>
                                    <p class="text-lg">Aucune transaction trouvée</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($transactions->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $transactions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection