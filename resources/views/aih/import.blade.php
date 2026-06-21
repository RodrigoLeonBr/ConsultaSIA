@extends('layouts.modern')

@section('title', 'Importar AIH (SIHD)')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Importar Internações — AIH</h1>
        <p class="text-gray-600 mt-1">Upload dos arquivos texto gerados pelo SIHD (resumo + procedimentos)</p>
    </div>
    <a href="{{ route('relatorios.aih.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
        Ver Relatório AIH
    </a>
</div>
@endsection

@section('content')
<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg text-sm text-blue-800">
                    <p class="font-medium mb-2">Como funciona</p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Gere os dois arquivos no SIHD com as consultas SQL fornecidas, filtrando por CNES e Competência.</li>
                        <li>O arquivo de <strong>Resumo AIH</strong> contém os cabeçalhos das internações (TB_HAIH).</li>
                        <li>O arquivo de <strong>Procedimentos AIH</strong> contém os itens por internação (TB_HPA).</li>
                        <li>Formato: texto separado por <strong>ponto e vírgula (;)</strong>, sem cabeçalho de coluna.</li>
                        <li>Na próxima tela você verá o preview e escolherá substituir ou ignorar registros já existentes.</li>
                    </ul>
                </div>

                <form method="POST" action="{{ route('aih.import.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    <div>
                        <label for="aih_file" class="block text-sm font-medium text-gray-700 mb-2">
                            Arquivo Resumo AIH (ex.: <code>202501con.txt</code>)
                        </label>
                        <input type="file"
                               id="aih_file"
                               name="aih_file"
                               accept=".txt,.TXT"
                               required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @error('aih_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="hpa_file" class="block text-sm font-medium text-gray-700 mb-2">
                            Arquivo Procedimentos AIH (ex.: <code>hpa202501con.txt</code>)
                        </label>
                        <input type="file"
                               id="hpa_file"
                               name="hpa_file"
                               accept=".txt,.TXT"
                               required
                               class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        @error('hpa_file')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Enviar e verificar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Histórico de competências importadas --}}
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-800">Histórico de Importações</h2>
                <span class="text-sm text-gray-500">{{ count($history) }} competência(s) no banco</span>
            </div>

            @if (empty($history))
                <div class="p-6 text-sm text-gray-500">Nenhuma importação registrada ainda.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">CNES</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Prestador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Competência</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">AIH</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Procedimentos HPA</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach ($history as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm font-mono">{{ $row['CNES'] }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-700">{{ $row['CNES_nome'] ?: '—' }}</td>
                                    <td class="px-6 py-3 text-sm font-mono">
                                        {{ substr($row['COMPETENCIA'], 4, 2) }}/{{ substr($row['COMPETENCIA'], 0, 4) }}
                                    </td>
                                    <td class="px-6 py-3 text-sm text-right">{{ number_format($row['count_aih'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-3 text-sm text-right">{{ number_format($row['count_hpa'], 0, ',', '.') }}</td>
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
