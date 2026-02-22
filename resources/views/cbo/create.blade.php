<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Criar Novo CBO') }}
            </h2>
            <a href="{{ route('cbo.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors duration-200">
                Voltar para Lista
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('cbo.store') }}">
                        @csrf

                        <!-- CBO Code -->
                        <div class="mb-6">
                            <label for="cbo" class="block text-sm font-medium text-gray-700 mb-2">
                                Código CBO <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="cbo" 
                                   name="cbo" 
                                   value="{{ old('cbo') }}"
                                   maxlength="6"
                                   placeholder="Ex: 225125"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('cbo') border-red-500 @enderror">
                            @error('cbo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Máximo 6 caracteres. Use apenas números e hífens.</p>
                        </div>

                        <!-- Description -->
                        <div class="mb-6">
                            <label for="ds_cbo" class="block text-sm font-medium text-gray-700 mb-2">
                                Descrição da Ocupação <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="ds_cbo" 
                                   name="ds_cbo" 
                                   value="{{ old('ds_cbo') }}"
                                   maxlength="120"
                                   placeholder="Ex: Médico clínico"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('ds_cbo') border-red-500 @enderror">
                            @error('ds_cbo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Máximo 120 caracteres.</p>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('cbo.index') }}" 
                               class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors duration-200">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors duration-200">
                                Criar CBO
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>