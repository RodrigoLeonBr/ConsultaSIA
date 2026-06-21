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
                <div class="text-2xl font-bold text-gray-900">{{ $preview['total_aih'] }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">Registros AIH</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $preview['total_hpa'] }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">Procedimentos HPA</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4 text-center">
                <div class="text-2xl font-bold text-indigo-600">{{ count($preview['competencias']) }}</div>
                <div class="text-xs text-gray-500 uppercase mt-1">CNES / Competências</div>
            </div>
        </div>

        {{-- Tabela por CNES+Competência --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Detalhamento por CNES e Competência</h2>
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
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($preview['competencias'] as $pair)
                            <tr>
                                <td class="px-6 py-3 text-sm font-mono">{{ $pair['CNES'] }}</td>
                                <td class="px-6 py-3 text-sm font-mono">{{ $pair['COMPETENCIA'] }}</td>
                                <td class="px-6 py-3 text-sm text-right">{{ number_format($pair['count_aih'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-sm text-right">{{ number_format($pair['count_hpa'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-sm text-right">{{ number_format($pair['count_db'], 0, ',', '.') }}</td>
                                <td class="px-6 py-3 text-center">
                                    @if ($pair['exists_db'])
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            Já existe
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Novo
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Formulário de confirmação --}}
        @php
            $hasExisting = collect($preview['competencias'])->where('exists_db', true)->count() > 0;
        @endphp

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4">Confirmar Importação</h2>

                @if ($hasExisting)
                    <div class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <p class="text-sm font-medium text-amber-800 mb-3">
                            Existem registros no banco para algumas combinações CNES/Competência. Escolha o que fazer:
                        </p>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="replace_choice" value="1" id="replace_yes" class="mr-2" checked>
                                <span class="text-sm text-gray-700">
                                    <strong>Substituir</strong> — apaga os registros existentes e insere os novos
                                </span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="replace_choice" value="0" id="replace_no" class="mr-2">
                                <span class="text-sm text-gray-700">
                                    <strong>Ignorar existentes</strong> — insere apenas os novos, mantém os que já estão no banco
                                </span>
                            </label>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('aih.import.apply') }}">
                    @csrf
                    <input type="hidden" name="replace" id="replace_value" value="{{ $hasExisting ? '1' : '0' }}">

                    @if ($hasExisting)
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                document.querySelectorAll('input[name="replace_choice"]').forEach(function(radio) {
                                    radio.addEventListener('change', function() {
                                        document.getElementById('replace_value').value = this.value;
                                    });
                                });
                            });
                        </script>
                    @endif

                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('aih.import') }}"
                           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            Cancelar
                        </a>
                        <button type="submit"
                                class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            ✓ Confirmar Importação
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
