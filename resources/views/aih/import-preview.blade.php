@extends('layouts.modern')

@section('title', 'Preview da Importação AIH')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Preview da Importação AIH</h1>
        <p class="text-gray-600 mt-1">Verifique os dados antes de confirmar</p>
    </div>
    <a href="{{ route('aih.import') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Nova importação
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
        @endif

        {{-- Resumo geral --}}
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-gray-900">{{ number_format($preview['total_aih'], 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">Registros AIH no arquivo</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ number_format($preview['total_hpa'], 0, ',', '.') }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">Procedimentos HPA</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600">{{ count($preview['competencias']) }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">CNES / Competências</div>
            </div>
        </div>

        {{-- Formulário principal --}}
        <form method="POST" action="{{ route('aih.import.apply') }}">
            @csrf

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">Detalhamento por CNES e Competência</h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Competências <span class="font-medium text-green-700">novas</span> serão importadas automaticamente.
                        Competências <span class="font-medium text-amber-700">já existentes</span> só serão reimportadas se você marcar a caixa de seleção.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CNES</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Competência</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">AIH no arquivo</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Proced. HPA</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Já no banco</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status / Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($preview['competencias'] as $pair)
                                @php $key = $pair['CNES'] . '|' . $pair['COMPETENCIA']; @endphp
                                <tr class="{{ $pair['exists_db'] ? 'bg-amber-50' : '' }}">
                                    <td class="px-6 py-3 text-sm font-mono">{{ $pair['CNES'] }}</td>
                                    <td class="px-6 py-3 text-sm font-mono">
                                        {{ substr($pair['COMPETENCIA'], 4, 2) }}/{{ substr($pair['COMPETENCIA'], 0, 4) }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right">{{ number_format($pair['count_aih'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 text-sm text-right">{{ number_format($pair['count_hpa'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 text-sm text-right">{{ number_format($pair['count_db'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 text-center">
                                        @if ($pair['exists_db'])
                                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                                <input type="checkbox"
                                                       name="replace[]"
                                                       value="{{ $key }}"
                                                       class="rounded border-amber-400 text-amber-600 focus:ring-amber-500">
                                                <span class="text-xs font-medium text-amber-800">
                                                    Excluir e reimportar
                                                </span>
                                            </label>
                                            <p class="text-xs text-amber-600 mt-1">
                                                ⚠ {{ number_format($pair['count_db'], 0, ',', '.') }} registro(s) serão apagados
                                            </p>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                ✓ Novo — será importado
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        @php
                            $newCount = collect($preview['competencias'])->where('exists_db', false)->count();
                            $existingCount = collect($preview['competencias'])->where('exists_db', true)->count();
                        @endphp
                        <span class="text-green-700 font-medium">{{ $newCount }} nova(s)</span>
                        @if ($existingCount > 0)
                            · <span class="text-amber-700 font-medium">{{ $existingCount }} já existe(m)</span>
                            — marque as caixas acima para substituir
                        @endif
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('aih.import') }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            ✓ Confirmar Importação
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
@endsection
