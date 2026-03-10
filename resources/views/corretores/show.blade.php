<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Corretor: {{ $corretor->name }}</h2>
            <a href="{{ route('corretores.edit', $corretor) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Editar</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nome</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $corretor->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Telefone</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $corretor->phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if($corretor->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Ativo</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inativo</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Chave API Claude</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $corretor->getRawOriginal('claude_api_key') ? '••••••••' : 'Não configurada' }}</dd>
                    </div>
                </dl>

                @if($corretor->seguradoras->isNotEmpty())
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Seguradoras Vinculadas</h3>
                        <div class="space-y-3">
                            @foreach($corretor->seguradoras as $seguradora)
                                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                    <div>
                                        <span class="text-sm font-medium text-gray-900">{{ $seguradora->name }}</span>
                                        <span class="text-xs text-gray-500 ml-2">Login: {{ $seguradora->pivot->login_username ?: '-' }}</span>
                                    </div>
                                    @if($seguradora->pivot->is_enabled)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Habilitada</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Desabilitada</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
