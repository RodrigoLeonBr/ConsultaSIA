<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Relatório de Faturamento por Prestador - Resultado') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Cabeçalho do Relatório -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                <div>
                        <h1 class="text-2xl font-bold text-gray-800">
                            Relatório de Faturamento por Prestador
                        </h1>
                        <p class="text-gray-600 mt-1">
                            Competência: <strong>{{ $competenciaFormatada }}</strong>
                            @if($prestadorId)
                                | Prestador: <strong>{{ $prestadorId }}</strong>
                            @endif
                        </p>
                </div>
                    <div class="flex items-center space-x-2">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        <span class="text-sm text-gray-600">Relatório Analítico</span>
                    </div>
                </div>

        <!-- Botões de Ação -->
        <div class="flex flex-wrap gap-4">
            <button onclick="exportarPdf()" 
                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-900 focus:outline-none focus:border-red-900 focus:ring ring-red-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Exportar PDF
            </button>

            <a href="{{ route('faturamento-prestador.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Nova Consulta
            </a>
            </div>
        </div>

        <!-- Conteúdo do Relatório -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
            @if(empty($dadosProcessados))
            <div class="p-8 text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Nenhum dado encontrado</h3>
                <p class="text-gray-600">Não foram encontrados registros para os filtros selecionados.</p>
                </div>
            @else
                @foreach($dadosProcessados as $prestador)
                <!-- Prestador -->
                <div class="border-b border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            Prestador: {{ $prestador['codigo'] }} {{ $prestador['nome'] }}
                            </h2>
                        <div class="text-right">
                            <div class="text-sm text-gray-600">Qt. Apresentada</div>
                            <div class="text-lg font-semibold text-blue-600">
                                {{ number_format($prestador['total_quantidade_apresentada'], 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-600">Vl. Apresentado</div>
                            <div class="text-lg font-semibold text-green-600">
                                R$ {{ number_format($prestador['total_valor_apresentado'], 2, ',', '.') }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-600">Qt. Aprovada</div>
                            <div class="text-lg font-semibold text-purple-600">
                                {{ number_format($prestador['total_quantidade_aprovada'], 0, ',', '.') }}
                                </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm text-gray-600">Vl. Aprovado</div>
                            <div class="text-lg font-semibold text-orange-600">
                                R$ {{ number_format($prestador['total_valor_aprovado'], 2, ',', '.') }}
                                </div>
                            </div>
                        </div>

                            @foreach($prestador['tipos_financiamento'] as $tipoFinanciamento)
                        <!-- Tipo de Financiamento -->
                        <div class="ml-4 mb-4">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="text-lg font-semibold text-gray-700">
                                            Tipo: {{ $tipoFinanciamento['descricao'] }}
                                        </h3>
                                <div class="flex space-x-8 text-sm">
                                    <span class="text-blue-600 w-20 text-right">
                                        {{ number_format($tipoFinanciamento['total_quantidade_apresentada'], 0, ',', '.') }}
                                    </span>
                                    <span class="text-green-600 w-24 text-right">
                                        R$ {{ number_format($tipoFinanciamento['total_valor_apresentado'], 2, ',', '.') }}
                                    </span>
                                    <span class="text-purple-600 w-20 text-right">
                                        {{ number_format($tipoFinanciamento['total_quantidade_aprovada'], 0, ',', '.') }}
                                    </span>
                                    <span class="text-orange-600 w-24 text-right">
                                        R$ {{ number_format($tipoFinanciamento['total_valor_aprovado'], 2, ',', '.') }}
                                    </span>
                                </div>
                            </div>

                            @foreach($tipoFinanciamento['grupos'] as $grupo)
                                <!-- Grupo -->
                                <div class="mb-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="text-md font-medium text-gray-600">
                                            Grupo: {{ $grupo['descricao'] }}
                                        </h4>
                                        <div class="flex space-x-8 text-sm">
                                            <span class="text-blue-600 w-20 text-right">
                                                {{ number_format($grupo['total_quantidade_apresentada'], 0, ',', '.') }}
                                            </span>
                                            <span class="text-green-600 w-24 text-right">
                                                R$ {{ number_format($grupo['total_valor_apresentado'], 2, ',', '.') }}
                                            </span>
                                            <span class="text-purple-600 w-20 text-right">
                                                {{ number_format($grupo['total_quantidade_aprovada'], 0, ',', '.') }}
                                            </span>
                                            <span class="text-orange-600 w-24 text-right">
                                                R$ {{ number_format($grupo['total_valor_aprovado'], 2, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>

                                    @foreach($grupo['subgrupos'] as $subgrupo)
                                        <!-- Sub-grupo -->
                                        <div class="mb-2">
                                            <div class="flex items-center justify-between mb-2">
                                                <h5 class="text-sm font-medium text-gray-500">
                                                    Sub-Grupo: {{ $subgrupo['descricao'] }}
                                                </h5>
                                                <div class="flex space-x-8 text-sm">
                                                    <span class="text-blue-600 w-20 text-right">
                                                        {{ number_format($subgrupo['total_quantidade_apresentada'], 0, ',', '.') }}
                                                    </span>
                                                    <span class="text-green-600 w-24 text-right">
                                                        R$ {{ number_format($subgrupo['total_valor_apresentado'], 2, ',', '.') }}
                                                    </span>
                                                    <span class="text-purple-600 w-20 text-right">
                                                        {{ number_format($subgrupo['total_quantidade_aprovada'], 0, ',', '.') }}
                                                    </span>
                                                    <span class="text-orange-600 w-24 text-right">
                                                        R$ {{ number_format($subgrupo['total_valor_aprovado'], 2, ',', '.') }}
                                                    </span>
                                                </div>
                                            </div>

                                            @foreach($subgrupo['formas'] as $forma)
                                                <!-- Forma de Organização -->
                                                <div class="mb-2">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <h6 class="text-sm font-medium text-gray-500">
                                                            Forma de Organização: {{ $forma['descricao'] }}
                                                        </h6>
                                                        <div class="flex space-x-8 text-sm">
                                                            <span class="text-blue-600 w-20 text-right">
                                                                {{ number_format($forma['total_quantidade_apresentada'], 0, ',', '.') }}
                                                            </span>
                                                            <span class="text-green-600 w-24 text-right">
                                                                R$ {{ number_format($forma['total_valor_apresentado'], 2, ',', '.') }}
                                                            </span>
                                                            <span class="text-purple-600 w-20 text-right">
                                                                {{ number_format($forma['total_quantidade_aprovada'], 0, ',', '.') }}
                                                            </span>
                                                            <span class="text-orange-600 w-24 text-right">
                                                                R$ {{ number_format($forma['total_valor_aprovado'], 2, ',', '.') }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    <!-- Tabela de Procedimentos -->
                                                    <div class="overflow-x-auto">
                                                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                                                            <thead class="bg-gray-50">
                                                                <tr>
                                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                        Código
                                                                    </th>
                                                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                                        Procedimento
                                                                    </th>
                                                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                                                        Vl Unit.
                                                                    </th>
                                                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                                                        Qt Apresentada
                                                                    </th>
                                                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                                                        Vl Apresentado
                                                                    </th>
                                                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                                                        Qt Aprovada
                                                                    </th>
                                                                    <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                                                        Vl Aprovado
                                                                    </th>
                                                                </tr>
                                                            </thead>
                                                            <tbody class="bg-white divide-y divide-gray-200">
                                                                @foreach($forma['procedimentos'] as $procedimento)
                                                                    <tr>
                                                                        <td class="px-3 py-2 whitespace-nowrap text-sm font-mono text-gray-900">
                                                                            {{ $procedimento['codigo'] }}
                                                                        </td>
                                                                        <td class="px-3 py-2 text-sm text-gray-900">
                                                                            {{ $procedimento['nome'] }}
                                                                        </td>
                                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-gray-600 w-20">
                                                                            R$ {{ number_format($procedimento['valor_unitario'], 2, ',', '.') }}
                                                                        </td>
                                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-blue-600 w-20">
                                                                            {{ number_format($procedimento['quantidade_apresentada'], 0, ',', '.') }}
                                                                        </td>
                                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-green-600 w-24">
                                                                            R$ {{ number_format($procedimento['valor_apresentado'], 2, ',', '.') }}
                                                                        </td>
                                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-purple-600 w-20">
                                                                            {{ number_format($procedimento['quantidade_aprovada'], 0, ',', '.') }}
                                                                        </td>
                                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-right text-orange-600 w-24">
                                                                            R$ {{ number_format($procedimento['valor_aprovado'], 2, ',', '.') }}
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                    </div>
                @endforeach
            @endif
        </div>
    </div>

<script>
function exportarPdf() {
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
    compInput.value = '{{ $competencia }}';
    pdfForm.appendChild(compInput);
    
    // Adicionar prestador se selecionado
    @if($prestadorId)
        const prestInput = document.createElement('input');
        prestInput.type = 'hidden';
        prestInput.name = 'prestador_id';
        prestInput.value = '{{ $prestadorId }}';
        pdfForm.appendChild(prestInput);
    @endif
    
    document.body.appendChild(pdfForm);
    pdfForm.submit();
    document.body.removeChild(pdfForm);
}
    </script>
</x-app-layout>