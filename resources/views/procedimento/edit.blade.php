<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Editar Procedimento: ' . $procedimento->codigo) }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('procedimento.show', $procedimento) }}" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition-colors duration-200">
                    Ver Detalhes
                </a>
                <a href="{{ route('procedimento.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors duration-200">
                    Voltar para Lista
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('procedimento.update', $procedimento) }}">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Código -->
                            <div>
                                <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Código do Procedimento <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="codigo" 
                                       name="codigo" 
                                       value="{{ old('codigo', $procedimento->codigo) }}"
                                       maxlength="10"
                                       placeholder="Ex: 0301010010"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('codigo') border-red-500 @enderror">
                                @error('codigo')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Nome do Procedimento -->
                            <div>
                                <label for="procedimento" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome do Procedimento <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="procedimento" 
                                       name="procedimento" 
                                       value="{{ old('procedimento', $procedimento->procedimento) }}"
                                       maxlength="63"
                                       placeholder="Ex: Consulta médica em atenção básica"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('procedimento') border-red-500 @enderror">
                                @error('procedimento')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Valor Total -->
                            <div>
                                <label for="pa_total" class="block text-sm font-medium text-gray-700 mb-2">
                                    Valor Total (R$) <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="pa_total" 
                                       name="pa_total" 
                                       value="{{ old('pa_total', str_replace('.', ',', $procedimento->pa_total)) }}"
                                       placeholder="Ex: 10,50"
                                       step="0.01"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('pa_total') border-red-500 @enderror">
                                @error('pa_total')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Use vírgula para decimais (ex: 10,50).</p>
                            </div>

                            <!-- Financiamento -->
                            <div>
                                <label for="financiamento" class="block text-sm font-medium text-gray-700 mb-2">
                                    Financiamento
                                </label>
                                <input type="text" 
                                       id="financiamento" 
                                       name="financiamento" 
                                       value="{{ old('financiamento', $procedimento->financiamento) }}"
                                       maxlength="50"
                                       placeholder="Ex: SUS, Particular, Convênio"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('financiamento') border-red-500 @enderror">
                                @error('financiamento')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Total da Rubrica -->
                            <div>
                                <label for="rub_total" class="block text-sm font-medium text-gray-700 mb-2">
                                    Total da Rubrica
                                </label>
                                <input type="text" 
                                       id="rub_total" 
                                       name="rub_total" 
                                       value="{{ old('rub_total', $procedimento->rub_total) }}"
                                       maxlength="20"
                                       placeholder="Opcional"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('rub_total') border-red-500 @enderror">
                                @error('rub_total')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Descrição da Rubrica -->
                            <div>
                                <label for="rub_dc" class="block text-sm font-medium text-gray-700 mb-2">
                                    Descrição da Rubrica
                                </label>
                                <input type="text" 
                                       id="rub_dc" 
                                       name="rub_dc" 
                                       value="{{ old('rub_dc', $procedimento->rub_dc) }}"
                                       maxlength="40"
                                       placeholder="Opcional"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('rub_dc') border-red-500 @enderror">
                                @error('rub_dc')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- PA Rubrica -->
                            <div>
                                <label for="pa_rub" class="block text-sm font-medium text-gray-700 mb-2">
                                    PA Rubrica
                                </label>
                                <input type="text" 
                                       id="pa_rub" 
                                       name="pa_rub" 
                                       value="{{ old('pa_rub', $procedimento->pa_rub) }}"
                                       maxlength="20"
                                       placeholder="Opcional"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('pa_rub') border-red-500 @enderror">
                                @error('pa_rub')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- PA ID -->
                            <div>
                                <label for="pa_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    PA ID
                                </label>
                                <input type="text" 
                                       id="pa_id" 
                                       name="pa_id" 
                                       value="{{ old('pa_id', $procedimento->pa_id) }}"
                                       maxlength="20"
                                       placeholder="Opcional"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('pa_id') border-red-500 @enderror">
                                @error('pa_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="mt-8 flex justify-end space-x-3">
                            <a href="{{ route('procedimento.show', $procedimento) }}" 
                               class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors duration-200">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors duration-200">
                                Atualizar Procedimento
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>