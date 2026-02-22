@extends('layouts.modern')

@section('title', 'Prestadores')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Prestadores</h1>
        <p class="text-gray-600 mt-1">Gerenciamento de unidades de saúde e prestadores de serviço</p>
    </div>
    <a href="{{ route('prestador.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Novo Prestador
    </a>
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
                    <form method="GET" action="{{ route('prestador.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Buscar por código, nome ou CNPJ..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <select name="tipo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos os tipos</option>
                                <option value="P" {{ request('tipo') == 'P' ? 'selected' : '' }}>Privado/Único</option>
                                <option value="U" {{ request('tipo') == 'U' ? 'selected' : '' }}>Unidade Básica</option>
                                <option value="M" {{ request('tipo') == 'M' ? 'selected' : '' }}>Hospital Municipal</option>
                            </select>
                        </div>
                        <div>
                            <select name="tipouni" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos os tipos de unidade</option>
                                <option value="M" {{ request('tipouni') == 'M' ? 'selected' : '' }}>Municipal</option>
                                <option value="F" {{ request('tipouni') == 'F' ? 'selected' : '' }}>Filantrópico</option>
                                <option value="P" {{ request('tipouni') == 'P' ? 'selected' : '' }}>Particular</option>
                                <option value="E" {{ request('tipouni') == 'E' ? 'selected' : '' }}>Estadual</option>
                            </select>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700 flex-1">
                                Buscar
                            </button>
                            @if(request()->hasAny(['search', 'tipo', 'tipouni', 'ativo']))
                                <a href="{{ route('prestador.index') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                                    Limpar
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Prestadores Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Código
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Nome
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Tipo
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        CNPJ/CPF
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Unidade
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($prestadores as $prestador)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $prestador->re_cunid }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            {{ $prestador->re_cnome }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $prestador->re_tipo == 'P' ? 'bg-blue-100 text-blue-800' : 
                                                   ($prestador->re_tipo == 'U' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800') }}">
                                                @switch($prestador->re_tipo)
                                                    @case('P') Privado/Único @break
                                                    @case('U') Unidade Básica @break
                                                    @case('M') Hospital Municipal @break
                                                    @default {{ $prestador->re_tipo }}
                                                @endswitch
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">
                                            {{ $prestador->cnpj }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @switch($prestador->tipouni)
                                                @case('M') Municipal @break
                                                @case('F') Filantrópico @break
                                                @case('P') Particular @break
                                                @case('E') Estadual @break
                                                @default {{ $prestador->tipouni }}
                                            @endswitch
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $prestador->ativo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $prestador->ativo ? 'Ativo' : 'Inativo' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end space-x-2">
                                                <a href="{{ route('prestador.show', $prestador) }}" 
                                                   class="text-blue-600 hover:text-blue-900">Ver</a>
                                                <a href="{{ route('prestador.edit', $prestador) }}" 
                                                   class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                                <form method="POST" action="{{ route('prestador.destroy', $prestador) }}" 
                                                      class="inline" 
                                                      onsubmit="return confirm('Tem certeza que deseja excluir este prestador?')">
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
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                            Nenhum prestador encontrado.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($prestadores->hasPages())
                        <div class="mt-6">
                            {{ $prestadores->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection