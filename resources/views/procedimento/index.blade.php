@extends('layouts.modern')

@section('title', 'Procedimentos Médicos')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Procedimentos</h1>
        <p class="text-gray-600 mt-1">Gerenciamento de procedimentos médicos e valores</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('procedimento.import') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
            </svg>
            Importar
        </a>
        <a href="{{ route('procedimento.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Novo Procedimento
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

            <!-- Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('procedimento.index') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Buscar por código, nome ou financiamento..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <input type="text" 
                                   name="financiamento" 
                                   value="{{ request('financiamento') }}"
                                   placeholder="Filtrar por financiamento..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 flex-1">
                                Buscar
                            </button>
                            @if(request()->hasAny(['search', 'financiamento']))
                                <a href="{{ route('procedimento.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                    Limpar
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Procedimentos Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ route('procedimento.index', ['order_by' => 'codigo', 'order_direction' => request('order_direction') == 'asc' ? 'desc' : 'asc'] + request()->except(['order_by', 'order_direction'])) }}" class="flex items-center space-x-1 hover:text-gray-700">
                                            <span>Código</span>
                                            @if(request('order_by') == 'codigo')
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    @if(request('order_direction') == 'asc')
                                                        <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                                    @else
                                                        <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                                    @endif
                                                </svg>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ route('procedimento.index', ['order_by' => 'procedimento', 'order_direction' => request('order_direction') == 'asc' ? 'desc' : 'asc'] + request()->except(['order_by', 'order_direction'])) }}" class="flex items-center space-x-1 hover:text-gray-700">
                                            <span>Procedimento</span>
                                            @if(request('order_by') == 'procedimento')
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    @if(request('order_direction') == 'asc')
                                                        <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                                    @else
                                                        <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                                    @endif
                                                </svg>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <a href="{{ route('procedimento.index', ['order_by' => 'pa_total', 'order_direction' => request('order_direction') == 'asc' ? 'desc' : 'asc'] + request()->except(['order_by', 'order_direction'])) }}" class="flex items-center space-x-1 hover:text-gray-700">
                                            <span>PA (SIA)</span>
                                            @if(request('order_by') == 'pa_total')
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    @if(request('order_direction') == 'asc')
                                                        <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                                    @else
                                                        <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                                    @endif
                                                </svg>
                                            @endif
                                        </a>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-indigo-600">
                                        SP (AIH)
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-indigo-600">
                                        SH (AIH)
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider text-indigo-600">
                                        Total AIH
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Financiamento
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($procedimentos as $procedimento)
                                    <!-- Procedimento Principal -->
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 font-mono">
                                            {{ $procedimento->codigo }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ Str::limit($procedimento->procedimento, 50) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                            {{ $procedimento->formatted_total }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono {{ $procedimento->vl_sp > 0 ? 'text-indigo-700' : 'text-gray-400' }}">
                                            R$ {{ number_format((float)$procedimento->vl_sp, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono {{ $procedimento->vl_sh > 0 ? 'text-indigo-700' : 'text-gray-400' }}">
                                            R$ {{ number_format((float)$procedimento->vl_sh, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono {{ $procedimento->vl_total > 0 ? 'text-indigo-900 font-semibold' : 'text-gray-400' }}">
                                            R$ {{ number_format($procedimento->vl_total, 2, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            @if($procedimento->financiamento)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $procedimento->financiamento }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <a href="{{ route('procedimento.show', $procedimento) }}" 
                                                   class="text-blue-600 hover:text-blue-900">Ver</a>
                                                <a href="{{ route('procedimento.edit', $procedimento) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                                <form method="POST" action="{{ route('procedimento.destroy', $procedimento) }}" 
                                                      class="inline" 
                                                      onsubmit="return confirm('Tem certeza que deseja excluir este procedimento?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-900">
                                                        Excluir
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Dados do Cismetro -->
                                    @if($procedimento->cismetros->count() > 0)
                                        <tr class="bg-gray-50">
                                            <td colspan="8" class="px-6 py-3">
                                                <div class="ml-4">
                                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Detalhes do Cismetro:</h4>
                                                    <div class="space-y-2">
                                                        @foreach($procedimento->cismetros as $cismetro)
                                                            <div class="bg-white rounded-lg p-3 border border-gray-200">
                                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                                                    <div>
                                                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</span>
                                                                        <p class="text-sm text-gray-900 mt-1">{{ $cismetro->descricao }}</p>
                                                                    </div>
                                                                    <div>
                                                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</span>
                                                                        <p class="text-sm font-mono text-gray-900 mt-1">{{ $cismetro->formatted_valor }}</p>
                                                                    </div>
                                                                    <div>
                                                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Grupo</span>
                                                                        <p class="text-sm text-gray-900 mt-1">{{ $cismetro->grupo ?? '-' }}</p>
                                                                    </div>
                                                                </div>
                                                                @if($cismetro->credenciamento)
                                                                    <div class="mt-2">
                                                                        <span class="text-xs font-medium text-gray-500 uppercase tracking-wider">Credenciamento</span>
                                                                        <p class="text-sm text-gray-900 mt-1">{{ $cismetro->credenciamento }}</p>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            Nenhum procedimento encontrado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($procedimentos->hasPages())
                        <div class="mt-6">
                            {{ $procedimentos->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection