<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Criar Novo Prestador') }}
            </h2>
            <a href="{{ route('prestador.index') }}" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 transition-colors duration-200">
                Voltar para Lista
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('prestador.store') }}">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Código do Prestador -->
                            <div>
                                <label for="re_cunid" class="block text-sm font-medium text-gray-700 mb-2">
                                    Código do Prestador <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="re_cunid" 
                                       name="re_cunid" 
                                       value="{{ old('re_cunid') }}"
                                       maxlength="7"
                                       placeholder="Ex: 2077915"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('re_cunid') border-red-500 @enderror">
                                @error('re_cunid')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Nome do Prestador -->
                            <div>
                                <label for="re_cnome" class="block text-sm font-medium text-gray-700 mb-2">
                                    Nome do Prestador <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="re_cnome" 
                                       name="re_cnome" 
                                       value="{{ old('re_cnome') }}"
                                       maxlength="35"
                                       placeholder="Ex: Hospital São José"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('re_cnome') border-red-500 @enderror">
                                @error('re_cnome')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Tipo de Prestador -->
                            <div>
                                <label for="re_tipo" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tipo de Prestador <span class="text-red-500">*</span>
                                </label>
                                <select id="re_tipo" 
                                        name="re_tipo" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('re_tipo') border-red-500 @enderror">
                                    <option value="">Selecione...</option>
                                    <option value="P" {{ old('re_tipo') == 'P' ? 'selected' : '' }}>Privado/Único</option>
                                    <option value="U" {{ old('re_tipo') == 'U' ? 'selected' : '' }}>Unidade Básica</option>
                                    <option value="M" {{ old('re_tipo') == 'M' ? 'selected' : '' }}>Hospital Municipal</option>
                                </select>
                                @error('re_tipo')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- CNPJ/CPF -->
                            <div>
                                <label for="cnpj" class="block text-sm font-medium text-gray-700 mb-2">
                                    CNPJ/CPF <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="cnpj" 
                                       name="cnpj" 
                                       value="{{ old('cnpj') }}"
                                       maxlength="14"
                                       placeholder="Apenas números"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('cnpj') border-red-500 @enderror">
                                @error('cnpj')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-sm text-gray-500">Digite apenas números, sem pontos ou traços.</p>
                            </div>

                            <!-- Área -->
                            <div>
                                <label for="area" class="block text-sm font-medium text-gray-700 mb-2">
                                    Área <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       id="area" 
                                       name="area" 
                                       value="{{ old('area') }}"
                                       min="0"
                                       placeholder="Ex: 1001"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('area') border-red-500 @enderror">
                                @error('area')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Natureza da Unidade -->
                            <div>
                                <label for="tipouni" class="block text-sm font-medium text-gray-700 mb-2">
                                    Natureza da Unidade <span class="text-red-500">*</span>
                                </label>
                                <select id="tipouni" 
                                        name="tipouni" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('tipouni') border-red-500 @enderror">
                                    <option value="">Selecione...</option>
                                    <option value="M" {{ old('tipouni') == 'M' ? 'selected' : '' }}>Municipal</option>
                                    <option value="F" {{ old('tipouni') == 'F' ? 'selected' : '' }}>Filantrópico</option>
                                    <option value="P" {{ old('tipouni') == 'P' ? 'selected' : '' }}>Particular</option>
                                    <option value="E" {{ old('tipouni') == 'E' ? 'selected' : '' }}>Estadual</option>
                                </select>
                                @error('tipouni')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Relatório -->
                            <div>
                                <label for="relatorio" class="block text-sm font-medium text-gray-700 mb-2">
                                    Relatório
                                </label>
                                <select id="relatorio" 
                                        name="relatorio" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('relatorio') border-red-500 @enderror">
                                    <option value="">Selecione...</option>
                                    <option value="Atenção Básica" {{ old('relatorio') == 'Atenção Básica' ? 'selected' : '' }}>Atenção Básica</option>
                                    <option value="Urgência e Emergência" {{ old('relatorio') == 'Urgência e Emergência' ? 'selected' : '' }}>Urgência e Emergência</option>
                                    <option value="Atenção Psicossocial" {{ old('relatorio') == 'Atenção Psicossocial' ? 'selected' : '' }}>Atenção Psicossocial</option>
                                    <option value="Atenção Ambulatorial Especializada" {{ old('relatorio') == 'Atenção Ambulatorial Especializada' ? 'selected' : '' }}>Atenção Ambulatorial Especializada</option>
                                    <option value="Hospitalar" {{ old('relatorio') == 'Hospitalar' ? 'selected' : '' }}>Hospitalar</option>
                                </select>
                                @error('relatorio')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Status Ativo -->
                            <div class="flex items-center">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" 
                                           id="ativo" 
                                           name="ativo" 
                                           value="1"
                                           {{ old('ativo', true) ? 'checked' : '' }}
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="ativo" class="font-medium text-gray-700">Prestador Ativo</label>
                                    <p class="text-gray-500">Marque se o prestador deve estar ativo no sistema.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="mt-8 flex justify-end space-x-3">
                            <a href="{{ route('prestador.index') }}" 
                               class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition-colors duration-200">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors duration-200">
                                Criar Prestador
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>