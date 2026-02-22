<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Faturamento por Prestador - {{ $competenciaFormatada }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-page-break {
            page-break-before: avoid;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
        }
        
        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .competencia {
            font-size: 12px;
            color: #666;
        }
        
        .prestador-header {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #007bff;
        }
        
        .prestador-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .prestador-totals {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
        }
        
        .tipo-financiamento {
            margin-bottom: 10px;
        }
        
        .tipo-title {
            font-size: 13px;
            font-weight: bold;
            background-color: #e9ecef;
            padding: 5px;
            margin-bottom: 5px;
        }
        
        .grupo {
            margin-bottom: 8px;
        }
        
        .grupo-title {
            font-size: 12px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 3px;
        }
        
        .subgrupo {
            margin-bottom: 6px;
        }
        
        .subgrupo-title {
            font-size: 11px;
            font-weight: bold;
            color: #6c757d;
            margin-bottom: 3px;
        }
        
        .forma {
            margin-bottom: 4px;
        }
        
        .forma-title {
            font-size: 10px;
            font-weight: bold;
            color: #868e96;
            margin-bottom: 3px;
        }
        
        .procedimentos-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        
        .procedimentos-table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 3px;
            text-align: left;
            font-weight: bold;
        }
        
        .procedimentos-table td {
            border: 1px solid #dee2e6;
            padding: 2px;
        }
        
        .procedimentos-table .codigo {
            font-family: monospace;
            width: 80px;
        }
        
        .procedimentos-table .procedimento {
            width: 200px;
        }
        
        .procedimentos-table .quantidade {
            text-align: right;
            width: 60px;
        }
        
        .procedimentos-table .valor {
            text-align: right;
            width: 80px;
        }
        
        .totals {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        @media print {
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    @foreach($dadosProcessados as $index => $prestador)
        @if($index > 0)
            <div class="page-break"></div>
        @endif
        
        <!-- Cabeçalho da Página -->
        <div class="header">
            <img src="{{ public_path('images/brasao.png') }}" alt="Brasão" class="logo">
            <div class="title">Prefeitura Municipal de Americana</div>
            <div class="subtitle">Estado de São Paulo - Unidade de Avaliação e Auditoria</div>
            <div class="subtitle">SECRETARIA DA SAÚDE</div>
            <div class="competencia">Produção Geral - Por Prestador | {{ $competenciaFormatada }}</div>
        </div>

        <!-- Cabeçalho do Prestador -->
        <div class="prestador-header">
            <div class="prestador-title">
                Prestador: {{ $prestador['codigo'] }} {{ $prestador['nome'] }}
            </div>
            <div class="prestador-totals">
                <div>
                    <strong>Qt. Apresentada:</strong> {{ number_format($prestador['total_quantidade_apresentada'], 0, ',', '.') }}
                </div>
                <div>
                    <strong>Vl. Apresentado:</strong> R$ {{ number_format($prestador['total_valor_apresentado'], 2, ',', '.') }}
                </div>
                <div>
                    <strong>Qt. Aprovada:</strong> {{ number_format($prestador['total_quantidade_aprovada'], 0, ',', '.') }}
                </div>
                <div>
                    <strong>Vl. Aprovado:</strong> R$ {{ number_format($prestador['total_valor_aprovado'], 2, ',', '.') }}
                </div>
            </div>
        </div>

        <!-- Conteúdo do Prestador -->
        @foreach($prestador['tipos_financiamento'] as $tipoFinanciamento)
            <div class="tipo-financiamento">
                <div class="tipo-title">
                    Tipo: {{ $tipoFinanciamento['descricao'] }} | 
                    {{ number_format($tipoFinanciamento['total_quantidade_apresentada'], 0, ',', '.') }} | 
                    R$ {{ number_format($tipoFinanciamento['total_valor_apresentado'], 2, ',', '.') }} |
                    {{ number_format($tipoFinanciamento['total_quantidade_aprovada'], 0, ',', '.') }} | 
                    R$ {{ number_format($tipoFinanciamento['total_valor_aprovado'], 2, ',', '.') }}
                </div>

                @foreach($tipoFinanciamento['grupos'] as $grupo)
                    <div class="grupo">
                        <div class="grupo-title">
                            Grupo: {{ $grupo['descricao'] }} | 
                            {{ number_format($grupo['total_quantidade_apresentada'], 0, ',', '.') }} | 
                            R$ {{ number_format($grupo['total_valor_apresentado'], 2, ',', '.') }} |
                            {{ number_format($grupo['total_quantidade_aprovada'], 0, ',', '.') }} | 
                            R$ {{ number_format($grupo['total_valor_aprovado'], 2, ',', '.') }}
                        </div>

                        @foreach($grupo['subgrupos'] as $subgrupo)
                            <div class="subgrupo">
                                <div class="subgrupo-title">
                                    Sub-Grupo: {{ $subgrupo['descricao'] }} | 
                                    {{ number_format($subgrupo['total_quantidade_apresentada'], 0, ',', '.') }} | 
                                    R$ {{ number_format($subgrupo['total_valor_apresentado'], 2, ',', '.') }} |
                                    {{ number_format($subgrupo['total_quantidade_aprovada'], 0, ',', '.') }} | 
                                    R$ {{ number_format($subgrupo['total_valor_aprovado'], 2, ',', '.') }}
                                </div>

                                @foreach($subgrupo['formas'] as $forma)
                                    <div class="forma">
                                        <div class="forma-title">
                                            Forma de Organização: {{ $forma['descricao'] }} | 
                                            {{ number_format($forma['total_quantidade_apresentada'], 0, ',', '.') }} | 
                                            R$ {{ number_format($forma['total_valor_apresentado'], 2, ',', '.') }} |
                                            {{ number_format($forma['total_quantidade_aprovada'], 0, ',', '.') }} | 
                                            R$ {{ number_format($forma['total_valor_aprovado'], 2, ',', '.') }}
                                        </div>

                                        <!-- Tabela de Procedimentos -->
                                        <table class="procedimentos-table">
                                            <thead>
                                                <tr>
                                                    <th class="codigo">Código</th>
                                                    <th class="procedimento">Procedimento</th>
                                                    <th class="quantidade">Vl Unit.</th>
                                                    <th class="quantidade">Qt Ap.</th>
                                                    <th class="valor">Vl Ap.</th>
                                                    <th class="quantidade">Qt Av.</th>
                                                    <th class="valor">Vl Av.</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($forma['procedimentos'] as $procedimento)
                                                    <tr>
                                                        <td class="codigo">{{ $procedimento['codigo'] }}</td>
                                                        <td class="procedimento">{{ $procedimento['nome'] }}</td>
                                                        <td class="quantidade">R$ {{ number_format($procedimento['valor_unitario'], 2, ',', '.') }}</td>
                                                        <td class="quantidade">{{ number_format($procedimento['quantidade_apresentada'], 0, ',', '.') }}</td>
                                                        <td class="valor">R$ {{ number_format($procedimento['valor_apresentado'], 2, ',', '.') }}</td>
                                                        <td class="quantidade">{{ number_format($procedimento['quantidade_aprovada'], 0, ',', '.') }}</td>
                                                        <td class="valor">R$ {{ number_format($procedimento['valor_aprovado'], 2, ',', '.') }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endforeach
    @endforeach
</body>
</html>
