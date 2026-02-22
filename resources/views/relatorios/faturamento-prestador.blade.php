@extends('layouts.modern')

@section('title', 'Relatório de Faturamento por Prestador')

@section('header')
<div class="flex items-center justify-between">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">Faturamento por Prestador</h1>
        <p class="text-gray-600 mt-1">Relatório detalhado de faturamento por prestador de serviço</p>
    </div>
</div>
@endsection

@section('content')
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between mb-6">
                    <h1 class="text-2xl font-bold text-gray-800">
                        Relatório de Faturamento por Prestador
                    </h1>
                    <div class="flex items-center space-x-2">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="text-sm text-gray-600">Relatório Analítico</span>
                    </div>
                </div>

        <form id="relatorioForm" method="POST" action="{{ route('faturamento-prestador.gerar') }}" class="space-y-6">
            @csrf
            
            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Competência -->
                <div>
                    <label for="competencia" class="block text-sm font-medium text-gray-700 mb-2">
                        Competência (Mês/Ano) <span class="text-red-500">*</span>
                    </label>
                    <select name="competencia" id="competencia" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecione a competência</option>
                        @foreach($competencias as $comp)
                            <option value="{{ $comp['value'] }}" 
                                    @if($ultimaCompetencia && $comp['value'] === $ultimaCompetencia->prd_cmp) selected @endif>
                                {{ $comp['label'] }}
                            </option>
                        @endforeach
                    </select>
                    @error('competencia')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Prestador -->
                <div>
                    <label for="prestador_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Prestador (Opcional)
                    </label>
                    <select name="prestador_id" id="prestador_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Todos os prestadores</option>
                        @foreach($prestadores as $prest)
                            <option value="{{ $prest['value'] }}">{{ $prest['label'] }}</option>
                        @endforeach
                    </select>
                    @error('prestador_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="flex flex-wrap gap-4 pt-4 border-t border-gray-200">
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Gerar Relatório
                </button>

                <button type="button" onclick="exportarPdf()" 
                        class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Exportar PDF
                </button>

                <a href="{{ route('relatorios.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </form>

        <!-- Informações sobre o relatório -->
        <div class="mt-8 p-4 bg-blue-50 rounded-lg">
            <h3 class="text-lg font-medium text-blue-900 mb-2">Sobre este Relatório</h3>
            <div class="text-sm text-blue-800 space-y-1">
                <p><strong>Estrutura:</strong> Relatório hierárquico com quebra por prestador, tipo de financiamento, grupo, sub-grupo e forma de organização.</p>
                <p><strong>Totalização:</strong> Totais em todos os níveis de agrupamento (quantidade e valor).</p>
                <p><strong>Quebra de Página:</strong> Nova página para cada prestador no PDF.</p>
                <p><strong>Dados:</strong> Baseado na tabela de produção (s_prd) com relacionamentos para descrições.</p>
            </div>
            </div>
        </div>
    </div>

    <script>
function exportarPdf() {
    const form = document.getElementById('relatorioForm');
    const competencia = document.getElementById('competencia').value;
    const prestadorId = document.getElementById('prestador_id').value;
    
    if (!competencia) {
        alert('Por favor, selecione uma competência antes de exportar.');
        return;
    }
    
    // Criar form temporário para PDF
    const pdfForm = document.createElement('form');
    pdfForm.method = 'POST';
    pdfForm.action = '{{ route("faturamento-prestador.pdf") }}';
    pdfForm.style.display = 'none';
    
    // Adicionar CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    pdfForm.appendChild(csrfInput);
    
    // Adicionar competência
    const compInput = document.createElement('input');
    compInput.type = 'hidden';
    compInput.name = 'competencia';
    compInput.value = competencia;
    pdfForm.appendChild(compInput);
    
    // Adicionar prestador se selecionado
    if (prestadorId) {
        const prestInput = document.createElement('input');
        prestInput.type = 'hidden';
        prestInput.name = 'prestador_id';
        prestInput.value = prestadorId;
        pdfForm.appendChild(prestInput);
    }
    
    document.body.appendChild(pdfForm);
    pdfForm.submit();
    document.body.removeChild(pdfForm);
}
    </script>
@endsection