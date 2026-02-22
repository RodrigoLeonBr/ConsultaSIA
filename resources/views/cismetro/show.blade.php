@extends('layouts.modern')

@section('title', 'Detalhes do Cismetro')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Detalhes do Cismetro</h1>
        <p class="text-gray-600 mt-1">Informações completas do registro</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('cismetro.edit', $cismetro) }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Editar
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
        <!-- Flash Messages -->
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                <!-- Informações Principais -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informações Básicas</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Código</dt>
                                <dd class="mt-1 text-lg font-mono text-gray-900">{{ $cismetro->codigo }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Valor</dt>
                                <dd class="mt-1 text-lg font-mono text-gray-900">{{ $cismetro->formatted_valor }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Classificação</h3>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Grupo</dt>
                                <dd class="mt-1 text-lg text-gray-900">
                                    @if($cismetro->grupo)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            {{ $cismetro->grupo }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">Não informado</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Credenciamento</dt>
                                <dd class="mt-1 text-lg text-gray-900">
                                    @if($cismetro->credenciamento)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                            {{ $cismetro->credenciamento }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">Não informado</span>
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Descrição -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Descrição</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <p class="text-gray-900 leading-relaxed">{{ $cismetro->descricao }}</p>
                    </div>
                </div>

                <!-- Informações do Sistema -->
                <div class="mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informações do Sistema</h3>
                    <div class="bg-gray-50 rounded-lg p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">ID do Registro</dt>
                                <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $cismetro->id }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Criado em</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $cismetro->created_at ? $cismetro->created_at->format('d/m/Y H:i') : 'N/A' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Atualizado em</dt>
                                <dd class="mt-1 text-sm text-gray-900">{{ $cismetro->updated_at ? $cismetro->updated_at->format('d/m/Y H:i') : 'N/A' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Relacionamentos -->
                @if($cismetro->sPrds->count() > 0 || $cismetro->sPaps->count() > 0)
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Registros Relacionados</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($cismetro->sPrds->count() > 0)
                                <div class="bg-blue-50 rounded-lg p-6">
                                    <h4 class="text-md font-medium text-blue-900 mb-3">S_PRD ({{ $cismetro->sPrds->count() }} registros)</h4>
                                    <p class="text-sm text-blue-700">Este cismetro possui {{ $cismetro->sPrds->count() }} registro(s) relacionado(s) na tabela S_PRD.</p>
                                </div>
                            @endif

                            @if($cismetro->sPaps->count() > 0)
                                <div class="bg-green-50 rounded-lg p-6">
                                    <h4 class="text-md font-medium text-green-900 mb-3">S_PAP ({{ $cismetro->sPaps->count() }} registros)</h4>
                                    <p class="text-sm text-green-700">Este cismetro possui {{ $cismetro->sPaps->count() }} registro(s) relacionado(s) na tabela S_PAP.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Ações -->
                <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                    <div class="flex space-x-3">
                        <a href="{{ route('cismetro.edit', $cismetro) }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Editar Cismetro
                        </a>
                    </div>

                    <form method="POST" action="{{ route('cismetro.destroy', $cismetro) }}" 
                          class="inline" 
                          onsubmit="return confirm('Tem certeza que deseja excluir este cismetro? Esta ação não pode ser desfeita.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Excluir Cismetro
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
