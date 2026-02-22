@extends('layouts.modern')

@section('title', 'Editar Cismetro')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Editar Cismetro</h1>
        <p class="text-gray-600 mt-1">Editar registro do cismetro</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('cismetro.show', $cismetro) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
            Ver Detalhes
        </a>
        <a href="{{ route('cismetro.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Voltar
        </a>
    </div>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form method="POST" action="{{ route('cismetro.update', $cismetro) }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Código -->
                        <div>
                            <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">
                                Código <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="codigo"
                                   name="codigo"
                                   value="{{ old('codigo', $cismetro->codigo) }}"
                                   maxlength="11"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('codigo') border-red-500 @enderror"
                                   placeholder="Ex: 12345678901"
                                   required>
                            @error('codigo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Valor -->
                        <div>
                            <label for="valor" class="block text-sm font-medium text-gray-700 mb-2">
                                Valor <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   id="valor"
                                   name="valor"
                                   value="{{ old('valor', $cismetro->valor) }}"
                                   step="0.01"
                                   min="0"
                                   max="999999999.99"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('valor') border-red-500 @enderror"
                                   placeholder="0.00"
                                   required>
                            @error('valor')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Descrição -->
                    <div>
                        <label for="descricao" class="block text-sm font-medium text-gray-700 mb-2">
                            Descrição <span class="text-red-500">*</span>
                        </label>
                        <textarea id="descricao"
                                  name="descricao"
                                  rows="3"
                                  maxlength="180"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('descricao') border-red-500 @enderror"
                                  placeholder="Descrição detalhada do procedimento..."
                                  required>{{ old('descricao', $cismetro->descricao) }}</textarea>
                        @error('descricao')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Grupo -->
                        <div>
                            <label for="grupo" class="block text-sm font-medium text-gray-700 mb-2">
                                Grupo
                            </label>
                            <input type="text" 
                                   id="grupo"
                                   name="grupo"
                                   value="{{ old('grupo', $cismetro->grupo) }}"
                                   maxlength="40"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('grupo') border-red-500 @enderror"
                                   placeholder="Ex: Consultas, Exames, Cirurgias">
                            @error('grupo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Credenciamento -->
                        <div>
                            <label for="credenciamento" class="block text-sm font-medium text-gray-700 mb-2">
                                Credenciamento
                            </label>
                            <input type="text" 
                                   id="credenciamento"
                                   name="credenciamento"
                                   value="{{ old('credenciamento', $cismetro->credenciamento) }}"
                                   maxlength="40"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('credenciamento') border-red-500 @enderror"
                                   placeholder="Ex: Hospital ABC, Clínica XYZ">
                            @error('credenciamento')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Botões -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <a href="{{ route('cismetro.index') }}" 
                           class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Atualizar Cismetro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
