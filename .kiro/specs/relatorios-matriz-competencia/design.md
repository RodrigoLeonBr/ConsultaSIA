# Design Document - Relatórios em Matriz por Competência

## Overview

Esta funcionalidade implementa uma visualização de matriz (Pivot Table) para relatórios quando o campo "Data Competência" é selecionado. O sistema transformará dados de listagem linear em uma estrutura bidimensional onde competências formam colunas e outras categorias formam linhas, permitindo análises temporais eficientes.

## Architecture

### Componentes Principais

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend (Blade + JS)                    │
├─────────────────────────────────────────────────────────────┤
│  • Toggle Visualização (Lista/Matriz)                      │
│  • Renderização Dinâmica da Matriz                         │
│  • Controles de Exportação                                 │
└─────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────┐
│                RelatorioController (Enhanced)               │
├─────────────────────────────────────────────────────────────┤
│  • generateMatrix() - Nova função                          │
│  • buildMatrixQuery() - Query otimizada                    │
│  • pivotData() - Transformação dos dados                   │
│  • exportMatrix() - Exportações específicas                │
└─────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database Layer                           │
├─────────────────────────────────────────────────────────────┤
│  • Query com GROUP BY otimizada                            │
│  • Agregações por competência                              │
│  • Índices específicos para performance                    │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Frontend Interface

#### Toggle de Visualização
```html
<!-- Novo controle na interface -->
<div class="visualization-toggle" id="visualization-controls" style="display: none;">
    <label class="text-sm font-medium text-gray-700">Visualização:</label>
    <div class="mt-1 flex space-x-4">
        <label class="inline-flex items-center">
            <input type="radio" name="view_type" value="list" checked class="form-radio">
            <span class="ml-2">Lista Simples</span>
        </label>
        <label class="inline-flex items-center">
            <input type="radio" name="view_type" value="matrix" class="form-radio">
            <span class="ml-2">Matriz por Competência</span>
        </label>
    </div>
</div>
```

#### Estrutura da Matriz
```html
<!-- Template para renderização da matriz -->
<div class="matrix-container">
    <div class="matrix-scroll-wrapper">
        <table class="matrix-table">
            <thead>
                <tr>
                    <th class="sticky-left">Categoria</th>
                    <!-- Competências como colunas -->
                    <th data-competencia="202401">01/2024</th>
                    <th data-competencia="202402">02/2024</th>
                    <!-- ... -->
                    <th class="total-column">Total</th>
                </tr>
            </thead>
            <tbody>
                <!-- Linhas de dados -->
                <tr data-category="prestador-1">
                    <td class="sticky-left">Hospital ABC</td>
                    <td class="numeric">1,250</td>
                    <td class="numeric">1,180</td>
                    <!-- ... -->
                    <td class="numeric total">2,430</td>
                </tr>
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td class="sticky-left">Total</td>
                    <td class="numeric">5,420</td>
                    <td class="numeric">5,180</td>
                    <!-- ... -->
                    <td class="numeric grand-total">10,600</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
```

### 2. Backend Controller Enhancement

#### Nova Rota
```php
// routes/web.php
Route::post('/relatorios/generate-matrix', [RelatorioController::class, 'generateMatrix'])
    ->name('relatorios.generate-matrix');
```

#### Método Principal
```php
/**
 * Generate matrix report based on competencia field
 */
public function generateMatrix(Request $request)
{
    try {
        $selectedFields = $request->get('fields', []);
        $filters = $request->get('filters', []);
        $format = $request->get('format', 'html');
        
        // Validar que competência está selecionada
        if (!in_array('prd_cmp', $selectedFields)) {
            return response()->json([
                'error' => 'Campo "Data Competência" é obrigatório para visualização matriz'
            ], 400);
        }
        
        // Construir query específica para matriz
        $matrixData = $this->buildMatrixData($selectedFields, $filters);
        
        // Transformar em estrutura pivot
        $pivotData = $this->pivotData($matrixData, $selectedFields);
        
        switch ($format) {
            case 'excel':
                return $this->exportMatrixExcel($pivotData);
            case 'pdf':
                return $this->exportMatrixPdf($pivotData);
            case 'csv':
                return $this->exportMatrixCsv($pivotData);
            default:
                return response()->json([
                    'success' => true,
                    'data' => $pivotData,
                    'type' => 'matrix'
                ]);
        }
    } catch (\Exception $e) {
        \Log::error('Error generating matrix report: ' . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
```

### 3. Query Building Strategy

#### Estratégia de Agregação
```php
/**
 * Build optimized query for matrix data
 */
private function buildMatrixData($selectedFields, $filters)
{
    $query = DB::table('s_prd as sp');
    
    // Campos de agrupamento (exceto competência)
    $groupFields = array_filter($selectedFields, fn($field) => $field !== 'prd_cmp');
    
    // Campos de seleção
    $selectFields = [];
    $groupByFields = [];
    
    // Sempre incluir competência
    $selectFields[] = 'sp.prd_cmp as competencia';
    $groupByFields[] = 'sp.prd_cmp';
    
    // Processar outros campos
    foreach ($groupFields as $field) {
        $fieldConfig = $this->getFieldConfig($field);
        
        if ($fieldConfig['type'] === 'lookup') {
            // Adicionar joins necessários
            $this->addJoin($query, $field, $fieldConfig);
            
            // Campos de agrupamento
            if ($field === 'prd_uid') {
                $selectFields[] = 'sp.prd_uid as prestador_codigo';
                $selectFields[] = 'pr.re_cnome as prestador_nome';
                $groupByFields[] = 'sp.prd_uid';
                $groupByFields[] = 'pr.re_cnome';
            } elseif ($field === 'prd_pa') {
                $selectFields[] = 'sp.prd_pa as procedimento_codigo';
                $selectFields[] = 'pc.procedimento as procedimento_nome';
                $groupByFields[] = 'sp.prd_pa';
                $groupByFields[] = 'pc.procedimento';
            }
            // ... outros campos lookup
        } elseif ($fieldConfig['type'] === 'number' || $fieldConfig['type'] === 'currency') {
            // Campos numéricos - agregação
            if ($field === 'PRD_QT_P') {
                $selectFields[] = 'SUM(CAST(sp.PRD_QT_P as UNSIGNED)) as total_quantidade';
            } elseif ($field === 'PRD_VL_P') {
                $selectFields[] = 'SUM(CAST(sp.PRD_VL_P as DECIMAL(15,2))) as total_valor';
            }
        } else {
            // Campos de texto
            $selectFields[] = "sp.{$field}";
            $groupByFields[] = "sp.{$field}";
        }
    }
    
    $query->select($selectFields);
    
    // Aplicar filtros
    foreach ($filters as $filter) {
        $this->applyFilter($query, $filter);
    }
    
    // Agrupar por campos não-numéricos
    if (!empty($groupByFields)) {
        $query->groupBy($groupByFields);
    }
    
    // Ordenar por competência e primeiro campo de agrupamento
    $query->orderBy('sp.prd_cmp');
    if (!empty($groupByFields) && $groupByFields[1] !== 'sp.prd_cmp') {
        $query->orderBy($groupByFields[1]);
    }
    
    return $query->get();
}
```

### 4. Data Transformation (Pivot Logic)

#### Algoritmo de Pivotagem
```php
/**
 * Transform linear data into pivot structure
 */
private function pivotData($data, $selectedFields)
{
    // Identificar competências únicas
    $competencias = $data->pluck('competencia')->unique()->sort()->values();
    
    // Identificar campos de agrupamento
    $groupFields = array_filter($selectedFields, fn($field) => $field !== 'prd_cmp');
    $numericFields = $this->getNumericFields($selectedFields);
    
    // Estrutura de resultado
    $result = [
        'competencias' => $competencias->map(function($comp) {
            return [
                'code' => $comp,
                'label' => $this->formatCompetencia($comp)
            ];
        }),
        'rows' => [],
        'totals' => [],
        'grand_totals' => []
    ];
    
    // Agrupar dados por categoria (linhas)
    $groupedData = $data->groupBy(function($item) use ($groupFields) {
        $key = [];
        foreach ($groupFields as $field) {
            if ($field === 'prd_uid') {
                $key[] = $item->prestador_codigo . '|' . $item->prestador_nome;
            } elseif ($field === 'prd_pa') {
                $key[] = $item->procedimento_codigo . '|' . $item->procedimento_nome;
            } else {
                $key[] = $item->{$field} ?? '';
            }
        }
        return implode('||', $key);
    });
    
    // Processar cada grupo (linha da matriz)
    foreach ($groupedData as $groupKey => $groupItems) {
        $rowData = [
            'category' => $this->formatRowCategory($groupKey, $groupFields),
            'values' => [],
            'totals' => []
        ];
        
        // Inicializar valores para todas as competências
        foreach ($competencias as $comp) {
            $rowData['values'][$comp] = [];
            foreach ($numericFields as $field) {
                $rowData['values'][$comp][$field] = 0;
            }
        }
        
        // Preencher valores reais
        foreach ($groupItems as $item) {
            $comp = $item->competencia;
            foreach ($numericFields as $field) {
                $value = $this->getNumericValue($item, $field);
                $rowData['values'][$comp][$field] = $value;
            }
        }
        
        // Calcular totais da linha
        foreach ($numericFields as $field) {
            $rowData['totals'][$field] = array_sum(
                array_column($rowData['values'], $field)
            );
        }
        
        $result['rows'][] = $rowData;
    }
    
    // Calcular totais das colunas
    foreach ($competencias as $comp) {
        $result['totals'][$comp] = [];
        foreach ($numericFields as $field) {
            $result['totals'][$comp][$field] = array_sum(
                array_column(array_column($result['rows'], 'values'), $comp)
            );
        }
    }
    
    // Calcular total geral
    foreach ($numericFields as $field) {
        $result['grand_totals'][$field] = array_sum(
            array_column($result['totals'], $field)
        );
    }
    
    return $result;
}
```

## Data Models

### Estrutura de Dados da Matriz

```php
// Estrutura de retorno da matriz
[
    'competencias' => [
        ['code' => '202401', 'label' => '01/2024'],
        ['code' => '202402', 'label' => '02/2024'],
        // ...
    ],
    'rows' => [
        [
            'category' => 'Hospital ABC - Consultas',
            'values' => [
                '202401' => ['quantidade' => 150, 'valor' => 15000.00],
                '202402' => ['quantidade' => 180, 'valor' => 18000.00],
                // ...
            ],
            'totals' => ['quantidade' => 330, 'valor' => 33000.00]
        ],
        // ...
    ],
    'totals' => [
        '202401' => ['quantidade' => 1500, 'valor' => 150000.00],
        '202402' => ['quantidade' => 1800, 'valor' => 180000.00],
        // ...
    ],
    'grand_totals' => ['quantidade' => 3300, 'valor' => 330000.00]
]
```

## Error Handling

### Validações Específicas

```php
/**
 * Validate matrix generation requirements
 */
private function validateMatrixRequest($selectedFields, $filters)
{
    $errors = [];
    
    // Competência obrigatória
    if (!in_array('prd_cmp', $selectedFields)) {
        $errors[] = 'Campo "Data Competência" é obrigatório para visualização matriz';
    }
    
    // Pelo menos um campo de agrupamento
    $groupFields = array_filter($selectedFields, fn($f) => $f !== 'prd_cmp');
    if (empty($groupFields)) {
        $errors[] = 'Pelo menos um campo além de "Data Competência" deve ser selecionado';
    }
    
    // Limite de competências (performance)
    $competenciaFilter = collect($filters)->firstWhere('field', 'prd_cmp');
    if (!$competenciaFilter) {
        // Verificar se há muitas competências no banco
        $competenciaCount = DB::table('s_prd')
            ->distinct('prd_cmp')
            ->count();
            
        if ($competenciaCount > 24) { // Mais de 2 anos
            $errors[] = 'Muitas competências encontradas. Use filtros para limitar o período.';
        }
    }
    
    return $errors;
}
```

### Tratamento de Timeouts

```php
/**
 * Execute matrix query with timeout handling
 */
private function executeMatrixQuery($query)
{
    try {
        // Definir timeout específico para queries de matriz
        DB::statement('SET SESSION max_execution_time = 60');
        
        $startTime = microtime(true);
        $result = $query->get();
        $executionTime = microtime(true) - $startTime;
        
        \Log::info('Matrix query executed', [
            'execution_time' => $executionTime,
            'result_count' => $result->count()
        ]);
        
        return $result;
        
    } catch (\Exception $e) {
        if (str_contains($e->getMessage(), 'timeout')) {
            throw new \Exception('Consulta muito complexa. Tente usar mais filtros para reduzir o volume de dados.');
        }
        throw $e;
    }
}
```

## Testing Strategy

### Testes Unitários

```php
/**
 * Test matrix data transformation
 */
public function testPivotDataTransformation()
{
    // Dados de entrada simulados
    $inputData = collect([
        (object)['competencia' => '202401', 'prestador_nome' => 'Hospital A', 'total_quantidade' => 100],
        (object)['competencia' => '202402', 'prestador_nome' => 'Hospital A', 'total_quantidade' => 150],
        (object)['competencia' => '202401', 'prestador_nome' => 'Hospital B', 'total_quantidade' => 200],
    ]);
    
    $selectedFields = ['prd_cmp', 'prd_uid', 'PRD_QT_P'];
    
    $result = $this->controller->pivotData($inputData, $selectedFields);
    
    // Verificações
    $this->assertCount(2, $result['competencias']); // 2 competências
    $this->assertCount(2, $result['rows']); // 2 hospitais
    $this->assertEquals(300, $result['totals']['202401']['quantidade']); // Total jan/2024
}
```

### Testes de Performance

```php
/**
 * Test matrix generation performance
 */
public function testMatrixPerformanceWithLargeDataset()
{
    // Simular dataset grande
    $this->seedLargeDataset(100000); // 100k registros
    
    $startTime = microtime(true);
    
    $response = $this->postJson('/relatorios/generate-matrix', [
        'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
        'filters' => [
            ['field' => 'prd_cmp', 'operator' => 'between', 'value' => ['202401', '202412']]
        ]
    ]);
    
    $executionTime = microtime(true) - $startTime;
    
    $response->assertStatus(200);
    $this->assertLessThan(30, $executionTime); // Menos de 30 segundos
}
```

### Testes de Integração

```php
/**
 * Test complete matrix workflow
 */
public function testCompleteMatrixWorkflow()
{
    // 1. Verificar detecção de competência
    $response = $this->postJson('/relatorios/fields');
    $this->assertArrayHasKey('prd_cmp', $response->json('fields'));
    
    // 2. Gerar matriz
    $matrixResponse = $this->postJson('/relatorios/generate-matrix', [
        'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
        'format' => 'html'
    ]);
    
    $matrixResponse->assertStatus(200);
    $matrixResponse->assertJsonStructure([
        'success',
        'data' => [
            'competencias',
            'rows',
            'totals',
            'grand_totals'
        ],
        'type'
    ]);
    
    // 3. Testar exportação Excel
    $excelResponse = $this->postJson('/relatorios/generate-matrix', [
        'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
        'format' => 'excel'
    ]);
    
    $excelResponse->assertStatus(200);
    $this->assertEquals('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
                       $excelResponse->headers->get('content-type'));
}
```

## Performance Considerations

### Índices Recomendados

```sql
-- Índice composto para queries de matriz
CREATE INDEX idx_s_prd_matrix ON s_prd (prd_cmp, prd_uid, prd_pa, PRD_QT_P, PRD_VL_P);

-- Índice para filtros de competência
CREATE INDEX idx_s_prd_competencia ON s_prd (prd_cmp);

-- Índices para joins frequentes
CREATE INDEX idx_s_prd_prestador ON s_prd (prd_uid);
CREATE INDEX idx_s_prd_procedimento ON s_prd (prd_pa);
```

### Otimizações de Query

1. **Limit de Competências**: Máximo 24 meses por consulta
2. **Paginação**: Para matrizes com muitas linhas (>1000)
3. **Cache**: Cache de 5 minutos para consultas idênticas
4. **Agregação no Banco**: Usar SUM/COUNT no SQL, não no PHP

### Monitoramento

```php
/**
 * Log matrix generation metrics
 */
private function logMatrixMetrics($executionTime, $dataCount, $matrixSize)
{
    \Log::info('Matrix generation completed', [
        'execution_time_seconds' => $executionTime,
        'source_records' => $dataCount,
        'matrix_rows' => $matrixSize['rows'],
        'matrix_columns' => $matrixSize['columns'],
        'memory_usage_mb' => memory_get_peak_usage(true) / 1024 / 1024
    ]);
}
```