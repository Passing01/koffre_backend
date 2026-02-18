@extends('admin.layout')

@section('title', 'Logs d\'audit')

@section('content')
    <div class="space-y-6">
        <!-- En-tête -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                    Logs d'audit
                </h1>
                <p class="text-gray-500 mt-1">Historique des actions</p>
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <form action="{{ route('admin.audit.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                    <input type="text" name="action" value="{{ request('action') }}" placeholder="Ex: cagnotte.created"
                        class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">User ID</label>
                    <input type="number" name="user_id" value="{{ request('user_id') }}" placeholder="Ex: 1"
                        class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date début</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                        class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Date fin</label>
                    <div class="flex gap-2">
                        <input type="date" name="date_to" value="{{ request('date_to') }}"
                            class="w-full rounded-lg border-gray-300 focus:border-purple-500 focus:ring-purple-500">
                        <button type="submit"
                            class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tableau -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="text-left py-4 px-6 text-xs font-semibold text-gray-500 uppercase">Date</th>
                            <th class="text-left py-4 px-6 text-xs font-semibold text-gray-500 uppercase">Acteur</th>
                            <th class="text-left py-4 px-6 text-xs font-semibold text-gray-500 uppercase">Action</th>
                            <th class="text-left py-4 px-6 text-xs font-semibold text-gray-500 uppercase">Cible</th>
                            <th class="text-right py-4 px-6 text-xs font-semibold text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-4 px-6 text-sm text-gray-600">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="py-4 px-6">
                                    @if($log->actor)
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-8 h-8 rounded-full bg-purple-100 flex items-center justify-center text-purple-600 font-bold text-xs">
                                                {{ substr($log->actor->fullname, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900">{{ $log->actor->fullname }}</div>
                                                <div class="text-xs text-gray-500">{{ $log->actor->phone }}</div>
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-sm italic">Système / Inconnu</span>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-600">
                                    @if($log->auditable_type)
                                        <span class="text-xs text-gray-500">{{ class_basename($log->auditable_type) }}
                                            #{{ $log->auditable_id }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('admin.audit.show', $log->id) }}"
                                        class="text-gray-400 hover:text-purple-600 transition-colors">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-500">
                                    Aucun log trouvé
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
                <div class="p-4 border-t border-gray-100">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection