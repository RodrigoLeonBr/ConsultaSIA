@extends('layouts.modern')

@section('title', 'Novo Cismetro')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Novo Cismetro</h1>
        <p class="text-gray-600 mt-1">Cadastrar novo registro no cismetro</p>
    </div>
    <a href="{{ route('cismetro.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Voltar
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <form method="POST" action="{{ route('cismetro.store') }}" class="space-y-6">
                    @csrf
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Código -->
                        <div>
                            <label for="codigo" class="block text-sm font-medium text-gray-700 mb-2">
                                Código <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="codigo"
                                   name="codigo"
                                   value="{{ old('codigo') }}"
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
                                   value="{{ old('valor') }}"
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
                                  required>{{ old('descricao') }}</textarea>
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
                                   value="{{ old('grupo') }}"
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
                                   value="{{ old('credenciamento') }}"
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
                            Salvar Cismetro
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
