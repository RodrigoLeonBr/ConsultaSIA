<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Criar Nova Fonte de Financiamento') }}
            </h2>
            <a href="{{ route('srub.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors duration-200">
                Voltar para Lista
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('srub.store') }}">
                        @csrf

                        <!-- ID da Fonte -->
                        <div class="mb-6">
                            <label for="rub_id" class="block text-sm font-medium text-gray-700 mb-2">
                                ID da Fonte de Financiamento <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="rub_id" 
                                   name="rub_id" 
                                   value="{{ old('rub_id') }}"
                                   maxlength="4"
                                   placeholder="Ex: 0001"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('rub_id') border-red-500 @enderror">
                            @error('rub_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Exatamente 4 caracteres.</p>
                        </div>

                        <!-- Descrição -->
                        <div class="mb-6">
                            <label for="rub_dc" class="block text-sm font-medium text-gray-700 mb-2">
                                Descrição da Fonte <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="rub_dc" 
                                   name="rub_dc" 
                                   value="{{ old('rub_dc') }}"
                                   maxlength="40"
                                   placeholder="Ex: Sistema Único de Saúde"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('rub_dc') border-red-500 @enderror">
                            @error('rub_dc')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Máximo 40 caracteres.</p>
                        </div>

                        <!-- Total da Rubrica -->
                        <div class="mb-6">
                            <label for="rub_total" class="block text-sm font-medium text-gray-700 mb-2">
                                Total da Rubrica
                            </label>
                            <input type="text" 
                                   id="rub_total" 
                                   name="rub_total" 
                                   value="{{ old('rub_total') }}"
                                   maxlength="2"
                                   placeholder="Ex: 01"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('rub_total') border-red-500 @enderror">
                            @error('rub_total')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">Opcional. Máximo 2 caracteres.</p>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('srub.index') }}" 
                               class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors duration-200">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors duration-200">
                                Criar Fonte
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>