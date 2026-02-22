<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 8px; /* Reduzido de 12px para 8px */
            margin: 10mm; /* Reduzido de 20px para 10mm */
            line-height: 1.2;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px; /* Reduzido de 30px */
            border-bottom: 2px solid #28A745; /* Verde para APAC */
            padding-bottom: 8px; /* Reduzido de 10px */
        }
        
        .title {
            font-size: 14px; /* Reduzido de 18px */
            font-weight: bold;
            margin-bottom: 3px; /* Reduzido de 5px */
            color: #28A745; /* Verde APAC */
        }
        
        .subtitle {
            font-size: 10px; /* Reduzido de 14px */
            color: #666;
        }
        
        .info {
            margin-bottom: 12px; /* Reduzido de 20px */
            font-size: 7px; /* Reduzido de 11px */
            background-color: #d4edda; /* Verde claro para APAC */
            border: 1px solid #c3e6cb;
            border-radius: 3px;
            padding: 6px;
        }
        
        .apac-badge {
            background-color: #28A745;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 6px;
            font-weight: bold;
            margin-left: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px; /* Reduzido de 10px */
            font-size: 7px; /* Fonte ainda menor para tabela */
        }
        
        th {
            border: 1px solid #333;
            padding: 3px 2px; /* Reduzido de 8px para 3px/2px */
            text-align: center;
            background-color: #28A745; /* Verde APAC */
            color: white;
            font-weight: bold;
            font-size: 7px;
            vertical-align: middle;
        }
        
        td {
            border: 1px solid #ddd;
            padding: 2px 3px; /* Reduzido de 8px para 2px/3px */
            text-align: left;
            vertical-align: top;
            font-size: 7px;
        }
        
        /* Zebra striping para melhor legibilidade */
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        /* Formatação específica para campos monetários */
        .currency {
            text-align: right;
            font-weight: normal;
        }
        
        /* Formatação específica para campos numéricos */
        .number {
            text-align: right;
        }

        .nome-paciente {
            background-color: #fff3cd !important;
            color: #856404;
            font-weight: bold;
        }
        
        /* Destaque especial para campos Cismetro */
        .cismetro-valor {
            background-color: #e8f4fd !important;
            font-weight: bold;
            color: #1565c0;
        }
        
        .cismetro-total {
            background-color: #e3f2fd !important;
            font-weight: bold;
            color: #0d47a1;
        }
        
        .cismetro-descricao {
            background-color: #f3e5f5 !important;
            color: #4a148c;
        }
        
        /* Campos específicos APAC */
        .apac-valor {
            background-color: #d4edda !important;
            font-weight: bold;
            color: #155724;
        }
        
        .apac-quantidade {
            background-color: #d1ecf1 !important;
            color: #0c5460;
            text-align: right;
        }
        
        .apac-procedimento {
            background-color: #fff3cd !important;
            color: #856404;
        }
        
        .total {
            margin-top: 12px; /* Reduzido de 15px */
            font-weight: bold;
            background-color: #d4edda; /* Verde claro para totais APAC */
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            padding: 8px;
        }
        
        .total h3 {
            font-size: 9px;
            margin: 0 0 6px 0;
            color: #155724;
        }
        
        .total table {
            margin-top: 6px; /* Reduzido de 10px */
            width: auto;
            background-color: transparent;
        }
        
        .total td {
            background-color: transparent !important;
            font-weight: bold;
            font-size: 7px;
            padding: 2px 8px 2px 0; /* Ajustado padding */
            color: #155724;
        }
        
        /* Orientação paisagem para mais colunas */
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        
        /* Quebra de página para tabelas grandes */
        .page-break {
            page-break-before: always;
        }
        
        /* Evitar quebra de linha nos cabeçalhos */
        th {
            white-space: nowrap;
        }
        
        /* Estilo para campos de código */
        .codigo {
            font-family: 'Courier New', monospace;
            font-size: 6px;
            text-align: center;
        }
        
        /* Estilo para competência */
        .competencia {
            text-align: center;
            font-weight: bold;
        }
        
        /* Estilo para CNES */
        .cnes {
            font-family: 'Courier New', monospace;
            font-size: 6px;
            text-align: center;
            background-color: #e9ecef !important;
        }
        
        /* Estilo para CID */
        .cid {
            font-family: 'Courier New', monospace;
            font-size: 6px;
            color: #dc3545;
        }
        
        /* Indicador OCI */
        .oci-indicator {
            background-color: #ffc107;
            color: #212529;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">
            {{ $title }}
            <span class="apac-badge">APAC/OCI</span>
        </div>
        <div class="subtitle">Sistema ConsultAsia - Relatório APAC/OCI</div>
    </div>

    <div class="info">
        <strong>Data de Geração:</strong> {{ date('d/m/Y H:i:s') }} | 
        <strong>Total de Registros:</strong> {{ count($data) }} |
        <strong>Tipo:</strong> APAC (Autorização de Procedimentos de Alta Complexidade) e OCI
        @if(!empty($fields))
            <br><strong>Campos:</strong> {{ implode(', ', $fields) }}
        @endif
    </div>

    @if(count($data) > 0)
        <table>
            <thead>
                <tr>
                    @if(!empty($data))
                        @php $firstRow = $data->first(); @endphp
                        @foreach(array_keys($firstRow) as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($data as $row)
                    <tr>
                        @foreach($row as $key => $value)
                            @php
                                // Determinar classe CSS baseada no tipo de campo APAC
                                $cellClass = '';
                                $keyLower = strtolower($key);
                                
                                // Campos Cismetro (prioridade)
                                if (str_contains($keyLower, 'cismetro - valor unitário')) {
                                    $cellClass = 'currency cismetro-valor';
                                } elseif (str_contains($keyLower, 'cismetro - valor total')) {
                                    $cellClass = 'currency cismetro-total';
                                } elseif (str_contains($keyLower, 'cismetro - descrição')) {
                                    $cellClass = 'cismetro-descricao';
                                }
                                // Campos APAC específicos
                                elseif (str_contains($keyLower, 'valor unitário') || str_contains($keyLower, 'valor total')) {
                                    $cellClass = 'currency apac-valor';
                                } elseif (str_contains($keyLower, 'quantidade') && (str_contains($keyLower, 'produzida') || str_contains($keyLower, 'total'))) {
                                    $cellClass = 'number apac-quantidade';
                                } elseif (str_contains($keyLower, 'procedimento') && !str_contains($keyLower, 'código')) {
                                    $cellClass = 'apac-procedimento';
                                } elseif (str_contains($keyLower, 'cnes')) {
                                    $cellClass = 'cnes';
                                } elseif (str_contains($keyLower, 'código')) {
                                    $cellClass = 'codigo';
                                } elseif (str_contains($keyLower, 'competência')) {
                                    $cellClass = 'competencia';
                                } elseif (str_contains($keyLower, 'cid')) {
                                    $cellClass = 'cid';
                                } elseif (str_contains($keyLower, 'valor')) {
                                    $cellClass = 'currency';
                                } elseif (str_contains($keyLower, 'quantidade')) {
                                    $cellClass = 'number';
                                } elseif (str_contains($keyLower, 'nome do paciente') || $key === 'APA_NMPCN') {
                                    $cellClass = 'nome-paciente';
                                }
                                
                                // Adicionar indicador OCI se procedimento começar com '09'
                                $displayValue = $value ?? '';
                                if (str_contains($keyLower, 'procedimento principal') && str_starts_with($displayValue, '09')) {
                                    $displayValue .= ' <span class="oci-indicator">OCI</span>';
                                }
                            @endphp
                            <td class="{{ $cellClass }}">{!! $displayValue !!}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        @if(!empty($totals))
            <div class="total">
                <h3>Totais Gerais APAC/OCI</h3>
                <table>
                    @foreach($totals as $label => $value)
                        <tr>
                            <td style="width: 60%;">{{ $label }}</td>
                            <td style="width: 40%; text-align: right;">{{ $value }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    @else
        <p style="text-align: center; margin: 30px 0; color: #666; font-size: 10px;">
            Nenhum registro APAC/OCI encontrado com os filtros aplicados.
        </p>
    @endif
</body>
</html>