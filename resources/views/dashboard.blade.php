<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Dashboard - OpenClaw
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Indicadores -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Corretores</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $totalCorretores }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Seguradoras</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $totalSeguradoras }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Solicitações Hoje</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $solicitacoesHoje }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-500">Taxa de Sucesso</div>
                    <div class="text-3xl font-bold text-gray-900">{{ $taxaSucesso }}%</div>
                </div>
            </div>

            <!-- Ultimas Solicitacoes -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Últimas Solicitações</h3>

                    @if($ultimasSolicitacoes->isEmpty())
                        <p class="text-gray-500">Nenhuma solicitação registrada.</p>
                    @else
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Corretor</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($ultimasSolicitacoes as $solicitacao)
                                    <tr>
                                        <td class="px-4 py-3 text-sm">
                                            <a href="{{ route('solicitacoes.show', $solicitacao) }}" class="text-indigo-600 hover:underline">
                                                {{ Str::limit($solicitacao->id, 8) }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">{{ $solicitacao->corretor->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $solicitacao->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <x-status-badge :status="$solicitacao->status" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
