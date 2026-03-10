<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Editar Corretor: {{ $corretor->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('corretores.update', $corretor) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700">Nome</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $corretor->name) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="phone" class="block text-sm font-medium text-gray-700">Telefone (WhatsApp)</label>
                        <input type="text" name="phone" id="phone" value="{{ old('phone', $corretor->phone) }}" placeholder="5511999999999" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-4">
                        <label for="claude_api_key" class="block text-sm font-medium text-gray-700">Chave API Claude</label>
                        <input type="password" name="claude_api_key" id="claude_api_key" placeholder="Deixe vazio para manter a atual" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('claude_api_key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', $corretor->is_active) ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-600">Ativo</span>
                        </label>
                    </div>

                    <!-- Vinculos com Seguradoras -->
                    @if($seguradoras->isNotEmpty())
                        <div class="mb-6 border-t pt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Seguradoras</h3>

                            @php
                                $vinculadas = $corretor->seguradoras->keyBy('id');
                            @endphp

                            @foreach($seguradoras as $seguradora)
                                @php
                                    $vinculo = $vinculadas->get($seguradora->id);
                                @endphp
                                <div class="mb-4 p-4 border rounded-lg">
                                    <label class="flex items-center mb-3">
                                        <input type="checkbox" name="seguradoras[{{ $seguradora->id }}][enabled]" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $vinculo ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm font-medium text-gray-700">{{ $seguradora->name }}</span>
                                    </label>

                                    <div class="ml-6 grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-xs text-gray-500">Login</label>
                                            <input type="text" name="seguradoras[{{ $seguradora->id }}][login_username]" value="{{ $vinculo ? $vinculo->pivot->login_username : '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <div>
                                            <label class="block text-xs text-gray-500">Senha</label>
                                            <input type="password" name="seguradoras[{{ $seguradora->id }}][login_password]" placeholder="{{ $vinculo ? '••••••' : '' }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('corretores.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm hover:bg-gray-300">Cancelar</a>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Atualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
