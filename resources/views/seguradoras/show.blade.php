<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Seguradora: {{ $seguradora->name }}</h2>
            <a href="{{ route('seguradoras.edit', $seguradora) }}" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Editar</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <dl class="grid grid-cols-1 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Nome</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $seguradora->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">URL do Sistema</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $seguradora->system_url ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            @if($seguradora->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Ativa</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Inativa</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Instruções do Prompt</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-wrap bg-gray-50 p-4 rounded">{{ $seguradora->prompt_instructions ?? '-' }}</dd>
                    </div>
                </dl>

                @if($seguradora->corretores->isNotEmpty())
                    <div class="mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Corretores Vinculados</h3>
                        <ul class="divide-y divide-gray-200">
                            @foreach($seguradora->corretores as $corretor)
                                <li class="py-2 flex justify-between">
                                    <span class="text-sm text-gray-900">{{ $corretor->name }}</span>
                                    <span class="text-sm text-gray-500">{{ $corretor->phone }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
