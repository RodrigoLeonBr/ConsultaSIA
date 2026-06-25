@extends('layouts.modern')

@section('title', 'SUS Paulista')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">SUS Paulista</h1>
        <p class="text-gray-600 mt-1">Tabela estadual SP — valores por modalidade e vigência</p>
    </div>
    <a href="{{ route('sus-paulista.import') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
        Importar XLSX
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">{{ session('success') }}</div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <form method="GET" action="{{ route('sus-paulista.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                    <div class="md:col-span-2">
                        <input type="text"
                               name="search"
                               value="{{ request('search') }}"
                               placeholder="Buscar código ou descrição..."
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select name="modalidade" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Todas modalidades</option>
                            <option value="sia" @selected(request('modalidade') === 'sia')>SIA</option>
                            <option value="sih" @selected(request('modalidade') === 'sih')>SIH</option>
                        </select>
                    </div>
                    <div>
                        <input type="text"
                               name="competencia"
                               value="{{ request('competencia') }}"
                               maxlength="6"
                               placeholder="Competência AAAAMM"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-center space-x-3">
                        <label class="inline-flex items-center text-sm text-gray-700">
                            <input type="checkbox"
                                   name="somente_vigentes"
                                   value="1"
                                   @checked(request()->has('somente_vigentes') ? request()->boolean('somente_vigentes') : true)
                                   class="rounded border-gray-300 text-blue-600 mr-2">
                            Só vigentes
                        </label>
                        <button type="submit" class="px-4 py-2 bg-gray-800 text-white text-sm rounded-md hover:bg-gray-900">Aplicar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Modalidade</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descrição</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tab Paulista</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Compl. TSP</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Vigência</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($registros as $registro)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-mono">{{ $registro->codigo }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $registro->modalidade === 'sia' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800' }}">
                                        {{ strtoupper($registro->modalidade) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">{{ $registro->descricao ?: '—' }}</td>
                                <td class="px-6 py-4 text-sm text-right font-medium">{{ $registro->formatted_tab_paulista }}</td>
                                <td class="px-6 py-4 text-sm text-right">{{ $registro->formatted_complementacao_tsp }}</td>
                                <td class="px-6 py-4 text-sm text-center text-gray-600">
                                    {{ $registro->competencia_inicial }} — {{ $registro->competencia_final }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Nenhum registro encontrado.
                                    <a href="{{ route('sus-paulista.import') }}" class="text-blue-600 hover:underline">Importar tabela</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($registros->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $registros->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
