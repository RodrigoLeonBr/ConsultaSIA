<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 20px;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 16px;
            color: #2563eb;
        }
        
        .header p {
            margin: 5px 0 0 0;
            font-size: 10px;
            color: #666;
        }
        
        .matrix-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8px;
        }
        
        .matrix-table th,
        .matrix-table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
        }
        
        .matrix-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            font-size: 7px;
        }
        
        .matrix-table .category-cell {
            text-align: left;
            font-weight: bold;
            background-color: #f9fafb;
            max-width: 120px;
            word-wrap: break-word;
        }
        
        .matrix-table .total-row {
            background-color: #dbeafe;
            font-weight: bold;
        }
        
        .matrix-table .total-cell {
            background-color: #bfdbfe;
            font-weight: bold;
        }
        
        .matrix-table .grand-total {
            background-color: #93c5fd;
            font-weight: bold;
        }
        
        .info {
            margin-bottom: 15px;
            font-size: 9px;
            color: #666;
        }
        
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        @page {
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <p>Sistema ConsultaProd - Gerado em {{ date('d/m/Y H:i:s') }}</p>
    </div>

    @if(isset($matrixData['competencias']) && count($matrixData['competencias']) > 0)
        <div class="info">
            <strong>Visualização Matriz:</strong> 
            {{ count($matrixData['rows']) }} categorias × {{ count($matrixData['competencias']) }} competências
        </div>

        <table class="matrix-table">
            <thead>
                <tr>
                    <th class="category-cell">Categoria</th>
                    @foreach($matrixData['competencias'] as $comp)
                        <th>{{ $comp['label'] }}</th>
                    @endforeach
                    <th class="total-cell">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matrixData['rows'] as $row)
                    <tr>
                        <td class="category-cell">{{ $row['category'] }}</td>
                        @foreach($matrixData['competencias'] as $comp)
                            <td>
                                @php
                                    $values = $row['values'][$comp['code']] ?? [];
                                    $cellContent = [];
                                    foreach($values as $field => $value) {
                                        if($field === 'PRD_VL_P') {
                                            $cellContent[] = 'R$ ' . number_format($value, 2, ',', '.');
                                        } else {
                                            $cellContent[] = number_format($value, 0, ',', '.');
                                        }
                                    }
                                @endphp
                                {{ implode('<br>', $cellContent) ?: '-' }}
                            </td>
                        @endforeach
                        <td class="total-cell">
                            @php
                                $totalContent = [];
                                foreach($row['totals'] as $field => $value) {
                                    if($field === 'PRD_VL_P') {
                                        $totalContent[] = 'R$ ' . number_format($value, 2, ',', '.');
                                    } else {
                                        $totalContent[] = number_format($value, 0, ',', '.');
                                    }
                                }
                            @endphp
                            {{ implode('<br>', $totalContent) }}
                        </td>
                    </tr>
                @endforeach
                
                <!-- Totals Row -->
                <tr class="total-row">
                    <td class="category-cell">TOTAL</td>
                    @foreach($matrixData['competencias'] as $comp)
                        <td>
                            @php
                                $totals = $matrixData['totals'][$comp['code']] ?? [];
                                $totalContent = [];
                                foreach($totals as $field => $value) {
                                    if($field === 'PRD_VL_P') {
                                        $totalContent[] = 'R$ ' . number_format($value, 2, ',', '.');
                                    } else {
                                        $totalContent[] = number_format($value, 0, ',', '.');
                                    }
                                }
                            @endphp
                            {{ implode('<br>', $totalContent) }}
                        </td>
                    @endforeach
                    <td class="grand-total">
                        @php
                            $grandTotalContent = [];
                            foreach($matrixData['grand_totals'] as $field => $value) {
                                if($field === 'PRD_VL_P') {
                                    $grandTotalContent[] = 'R$ ' . number_format($value, 2, ',', '.');
                                } else {
                                    $grandTotalContent[] = number_format($value, 0, ',', '.');
                                }
                            }
                        @endphp
                        {{ implode('<br>', $grandTotalContent) }}
                    </td>
                </tr>
            </tbody>
        </table>
    @else
        <div class="info">
            <p>Nenhum dado encontrado para gerar a matriz.</p>
        </div>
    @endif

    <div class="footer">
        <p>Sistema ConsultaProd - Relatório Matriz por Competência</p>
        <p>Laravel {{ app()->version() }} - Gerado automaticamente</p>
    </div>
</body>
</html>