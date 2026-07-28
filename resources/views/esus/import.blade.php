@extends('layouts.modern')

@section('title', 'Importar e-SUS (SIGTAP)')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Importar Produção — e-SUS</h1>
        <p class="text-gray-600 mt-1">Consome a API e-SUS e grava a produção SIGTAP por unidade (CNES).</p>
    </div>
    <a href="{{ route('esus.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Ver Produção
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">{{ session('error') }}</div>
        @endif
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">{{ session('success') }}</div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    <p class="font-medium mb-2">Como funciona</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>A produção já vem tratada e agregada por unidade + procedimento SIGTAP.</li>
                        <li>Todas as linhas são gravadas; o CNES vem no próprio arquivo (unidade sem CNES entra mesmo assim).</li>
                        <li>Importar substitui os dados da competência selecionada.</li>
                        <li>Na próxima tela você vê o resumo antes de confirmar.</li>
                    </ul>
                </div>

                @if ($apiError)
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-300 rounded-lg text-sm text-yellow-800">
                        Não foi possível listar competências na API: {{ $apiError }}
                    </div>
                @endif

                <form method="POST" action="{{ route('esus.import.store') }}" class="space-y-6">
                    @csrf
                    <div>
                        <label for="competencia" class="block text-sm font-medium text-gray-700 mb-2">Competência (AAAA-MM)</label>
                        @if (!empty($competencias))
                            <select id="competencia" name="competencia" required
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($competencias as $comp)
                                    <option value="{{ $comp }}">{{ $comp }}</option>
                                @endforeach
                            </select>
                        @else
                            <input type="text" id="competencia" name="competencia" required placeholder="2026-01"
                                   pattern="\d{4}-\d{2}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @endif
                        @error('competencia')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Analisar competência
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Competências Importadas</h2>
                <span class="text-sm text-gray-500">{{ count($history) }} competência(s)</span>
            </div>
            @if (empty($history))
                <div class="p-6 text-sm text-gray-500">Nenhuma importação registrada ainda.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Competência</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Unidades</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Linhas</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Quantidade</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($history as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm font-mono">{{ $row['competencia'] }}</td>
                                    <td class="px-6 py-3 text-sm text-right">{{ number_format($row['unidades'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 text-sm text-right">{{ number_format($row['linhas'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 text-sm text-right">{{ number_format($row['quantidade'], 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
