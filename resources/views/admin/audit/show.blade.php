@extends('admin.layout')

@section('content')
    <div class="space-y-6">
        <!-- En-tête -->
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent">
                    Détail Log #{{ $log->id }}
                </h1>
                <p class="text-gray-500 mt-1">Information détaillée de l'action</p>
            </div>
            <a href="{{ route('admin.audit.index') }}"
                class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
                <i class="fas fa-arrow-left mr-2"></i>Retour
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Informations principales -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Informations Générales</h3>
                <dl class="space-y-4">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Date</dt>
                        <dd class="font-medium">{{ $log->created_at->format('d/m/Y H:i:s') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Action</dt>
                        <dd>
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $log->action }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Acteur</dt>
                        <dd class="text-right">
                            @if($log->actor)
                                <div class="font-medium">{{ $log->actor->fullname }}</div>
                                <div class="text-xs text-gray-500">{{ $log->actor->phone }}</div>
                            @else
                                <span class="text-gray-400 italic">Système / Inconnu</span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">IP</dt>
                        <dd class="font-mono text-sm">{{ $log->ip ?? 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">User Agent</dt>
                        <dd class="text-xs text-gray-500 max-w-xs truncate" title="{{ $log->user_agent }}">
                            {{ $log->user_agent ?? 'N/A' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Cible -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Cible de l'action</h3>
                @if($log->auditable_type)
                    <dl class="space-y-4">
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Type</dt>
                            <dd class="font-mono text-sm">{{ $log->auditable_type }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">ID</dt>
                            <dd class="font-mono text-sm">#{{ $log->auditable_id }}</dd>
                        </div>
                        <!-- Lien vers la ressource si applicable -->
                        @if(str_contains($log->auditable_type, 'Cagnotte'))
                            <div class="mt-4 pt-4 border-t">
                                <a href="{{ route('admin.cagnottes.show', $log->auditable_id) }}"
                                    class="text-purple-600 hover:text-purple-800 text-sm font-medium">
                                    Voir la cagnotte <i class="fas fa-external-link-alt ml-1"></i>
                                </a>
                            </div>
                        @endif
                    </dl>
                @else
                    <p class="text-gray-500 italic">Aucune cible spécifique associée.</p>
                @endif
            </div>

            <!-- Métadonnées (JSON) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:col-span-2">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b pb-2">Métadonnées</h3>
                @if($log->metadata)
                    <div class="bg-gray-50 rounded-lg p-4 overflow-x-auto">
                        <pre
                            class="text-xs text-gray-700 font-mono">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @else
                    <p class="text-gray-500 italic">Aucune métadonnée disponible.</p>
                @endif
            </div>
        </div>
    </div>
@endsection