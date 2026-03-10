<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Solicitação: {{ Str::limit($solicitacao->id, 8) }}</h2>
            <a href="{{ route('solicitacoes.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Voltar</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <!-- Info Geral -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Corretor</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $solicitacao->corretor->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Data</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $solicitacao->created_at->format('d/m/Y H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1"><x-status-badge :status="$solicitacao->status" /></dd>
                    </div>
                </div>

                <div class="mt-6">
                    <dt class="text-sm font-medium text-gray-500">Mensagem Original</dt>
                    <dd class="mt-1 text-sm text-gray-900 bg-gray-50 p-4 rounded whitespace-pre-wrap">{{ $solicitacao->raw_message }}</dd>
                </div>

                @if($solicitacao->vehicle_data)
                    <div class="mt-4">
                        <dt class="text-sm font-medium text-gray-500">Dados do Veículo</dt>
                        <dd class="mt-1 text-sm text-gray-900 bg-gray-50 p-4 rounded">
                            <pre class="text-xs">{{ json_encode($solicitacao->vehicle_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </dd>
                    </div>
                @endif

                @if($solicitacao->client_data)
                    <div class="mt-4">
                        <dt class="text-sm font-medium text-gray-500">Dados do Cliente</dt>
                        <dd class="mt-1 text-sm text-gray-900 bg-gray-50 p-4 rounded">
                            <pre class="text-xs">{{ json_encode($solicitacao->client_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </dd>
                    </div>
                @endif
            </div>

            <!-- Sub-Solicitacoes -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Sub-Solicitações por Seguradora</h3>

                @if($solicitacao->subSolicitacoes->isEmpty())
                    <p class="text-gray-500">Nenhuma sub-solicitação.</p>
                @else
                    <div class="space-y-4">
                        @foreach($solicitacao->subSolicitacoes as $sub)
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">{{ $sub->seguradora->name }}</span>
                                        <span class="text-xs text-gray-500 ml-2">Tentativas: {{ $sub->attempts }}</span>
                                    </div>
                                    <x-status-badge :status="$sub->status" />
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-gray-500">
                                    <div>Início: {{ $sub->started_at?->format('H:i:s') ?? '-' }}</div>
                                    <div>Fim: {{ $sub->completed_at?->format('H:i:s') ?? '-' }}</div>
                                    <div>
                                        @if($sub->pdf_path)
                                            <a href="{{ route('solicitacoes.pdf', [$solicitacao->id, $sub->id]) }}" class="text-indigo-600 hover:underline">Download PDF</a>
                                        @else
                                            PDF: -
                                        @endif
                                    </div>
                                    <div>ID: {{ Str::limit($sub->id, 8) }}</div>
                                </div>

                                @if($sub->error_message)
                                    <div class="mt-2 p-2 bg-red-50 rounded text-xs text-red-700">
                                        {{ $sub->error_message }}
                                    </div>
                                @endif

                                @if($sub->agent_log)
                                    <details class="mt-2">
                                        <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">Ver Log do Agente</summary>
                                        <pre class="mt-1 p-2 bg-gray-50 rounded text-xs text-gray-600 max-h-48 overflow-y-auto">{{ $sub->agent_log }}</pre>
                                    </details>
                                @endif

                                @if($sub->result_data)
                                    <details class="mt-2">
                                        <summary class="text-xs text-gray-500 cursor-pointer hover:text-gray-700">Ver Resultado</summary>
                                        <pre class="mt-1 p-2 bg-gray-50 rounded text-xs text-gray-600">{{ json_encode($sub->result_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    </details>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
