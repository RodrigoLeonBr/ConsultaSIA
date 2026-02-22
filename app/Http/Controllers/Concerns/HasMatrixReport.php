<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait HasMatrixReport
{
    /**
     * Generate matrix report based on competencia field
     */
    public function generateMatrix(Request $request)
    {
        try {
            $selectedFields = $request->get('fields', []);
            $filters = $request->get('filters', []);
            $format = $request->get('format', 'html');
            
            $competenciaField = $this->getCompetenciaField();
            
            // Validar que competência está selecionada
            if (!in_array($competenciaField, $selectedFields)) {
                return response()->json([
                    'error' => 'Campo "' . $this->getFieldConfig($competenciaField)['label'] . '" é obrigatório para visualização matriz'
                ], 400);
            }
            
            // Validar que há pelo menos um campo além da competência
            $groupFields = array_filter($selectedFields, fn($field) => $field !== $competenciaField);
            if (empty($groupFields)) {
                return response()->json([
                    'error' => 'Pelo menos um campo além de "' . $this->getFieldConfig($competenciaField)['label'] . '" deve ser selecionado'
                ], 400);
            }
            
            // Verificar se há campos numéricos selecionados
            $numericFields = $this->getNumericFields($selectedFields);
            if (empty($numericFields)) {
                // Se não há campos numéricos, adicionar automaticamente o campo padrão
                $defaultNumericField = $this->getDefaultNumericField();
                if ($defaultNumericField && !in_array($defaultNumericField, $selectedFields)) {
                    $selectedFields[] = $defaultNumericField;
                }
            }
            
            // Construir query específica para matriz
            $matrixResult = $this->buildMatrixData($selectedFields, $filters);
            $matrixData = $matrixResult['data'];
            $sql = $matrixResult['sql'];
            $bindings = $matrixResult['bindings'];
            
            // Transformar em estrutura pivot
            $pivotData = $this->pivotData($matrixData, $selectedFields);
            
            switch ($format) {
                case 'excel':
                    return $this->exportMatrixExcel($pivotData, $selectedFields);
                case 'pdf':
                    return $this->exportMatrixPdf($pivotData);
                case 'csv':
                    return $this->exportMatrixCsv($pivotData);
                default:
                    return response()->json([
                        'success' => true,
                        'data' => $pivotData,
                        'type' => 'matrix',
                        'sql' => $sql,
                        'bindings' => $bindings
                    ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error generating matrix report: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json([
                'error' => 'Erro ao gerar relatório matriz: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Build optimized query for matrix data
     */
    protected function buildMatrixData($selectedFields, $filters)
    {
        $tableName = $this->getTableName();
        $tableAlias = $this->getTableAlias();
        $competenciaField = $this->getCompetenciaField();
        
        $query = DB::table("{$tableName} as {$tableAlias}");
        
        // Campos de agrupamento (exceto competência)
        $groupFields = array_filter($selectedFields, fn($field) => $field !== $competenciaField);
        
        // Verificar se precisa de joins
        $needsCismetro = collect($selectedFields)->contains(function($field) {
            return str_starts_with($field, 'cismetro_');
        });
        
        // Adicionar joins baseado nos campos selecionados
        $joins = [];
        
        foreach ($selectedFields as $field) {
            $fieldConfig = $this->getFieldConfig($field);
            if ($fieldConfig && $fieldConfig['type'] === 'lookup') {
                $joinKey = $fieldConfig['lookup_table'];
                if (!in_array($joinKey, $joins)) {
                    $this->addMatrixJoin($query, $field, $fieldConfig, $tableAlias);
                    $joins[] = $joinKey;
                }
            }
        }
        
        // Adicionar join do cismetro se necessário
        if ($needsCismetro && !in_array('cismetro', $joins)) {
            $procedimentoField = $this->getProcedimentoFieldForCismetro();
            $query->leftJoin('cismetro as cs', function($join) use ($tableAlias, $procedimentoField) {
                $join->on(DB::raw("{$tableAlias}.{$procedimentoField} COLLATE utf8mb4_unicode_ci"), '=', DB::raw('cs.codigo COLLATE utf8mb4_unicode_ci'));
            });
            $joins[] = 'cismetro';
        }
        
        // Campos de seleção
        $selectFields = [];
        $groupByFields = [];
        
        // Sempre incluir competência
        $selectFields[] = "{$tableAlias}.{$competenciaField} as competencia";
        $groupByFields[] = "{$tableAlias}.{$competenciaField}";
        
        // Processar outros campos
        foreach ($groupFields as $field) {
            $fieldConfig = $this->getFieldConfig($field);
            
            if ($fieldConfig && $fieldConfig['type'] === 'lookup') {
                // Campos de lookup - usar método específico da classe
                $lookupFields = $this->getMatrixLookupFields($field, $tableAlias);
                $selectFields = array_merge($selectFields, $lookupFields['select']);
                $groupByFields = array_merge($groupByFields, $lookupFields['groupBy']);
            } elseif ($fieldConfig && ($fieldConfig['type'] === 'number' || $fieldConfig['type'] === 'currency')) {
                // Campos numéricos - agregação
                $numericFields = $this->getMatrixNumericFields($field, $tableAlias);
                $selectFields = array_merge($selectFields, $numericFields);
            } elseif ($field === 'cismetro_valor') {
                // Valor unitário do cismetro
                $selectFields[] = 'cs.valor as cismetro_valor';
                $groupByFields[] = 'cs.valor';
            } elseif ($field === 'procedimento_descricao') {
                // Campo apenas para filtro, ignorar na seleção/agrupamento
                continue;
            } else {
                // Campos de texto simples
                $selectFields[] = "{$tableAlias}.{$field}";
                $groupByFields[] = "{$tableAlias}.{$field}";
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
        $query->orderBy("{$tableAlias}.{$competenciaField}");
        if (!empty($groupByFields) && count($groupByFields) > 1) {
            $query->orderBy($groupByFields[1]);
        }
        
        // Capturar SQL e bindings antes de executar
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        $data = $query->get();
        
        // Retornar dados, SQL e bindings
        return [
            'data' => $data,
            'sql' => $sql,
            'bindings' => $bindings
        ];
    }

    /**
     * Get lookup fields for matrix - must be implemented by using class
     */
    abstract protected function getMatrixLookupFields($field, $tableAlias): array;

    /**
     * Get numeric fields for matrix - must be implemented by using class
     */
    abstract protected function getMatrixNumericFields($field, $tableAlias): array;

    /**
     * Get procedimento field name for cismetro join
     */
    abstract protected function getProcedimentoFieldForCismetro(): string;

    /**
     * Add join for matrix query
     */
    protected function addMatrixJoin($query, $field, $fieldConfig, $tableAlias)
    {
        $alias = $this->getTableAliasForJoin($fieldConfig['lookup_table']);
        
        switch ($fieldConfig['lookup_table']) {
            case 'prestador':
                $prestadorField = $this->getPrestadorField();
                $query->leftJoin("prestador as {$alias}", function($join) use ($tableAlias, $alias, $prestadorField) {
                    $join->on(DB::raw("{$tableAlias}.{$prestadorField} COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.re_cunid COLLATE utf8mb4_unicode_ci"));
                });
                break;
            case 'cbo':
                $cboField = $this->getCboField();
                $query->leftJoin("cbo as {$alias}", function($join) use ($tableAlias, $alias, $cboField) {
                    $join->on(DB::raw("{$tableAlias}.{$cboField} COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.cbo COLLATE utf8mb4_unicode_ci"));
                });
                break;
            case 'procedimento':
                $procedimentoField = $this->getProcedimentoFieldForCismetro();
                $query->leftJoin("procedimento as {$alias}", function($join) use ($tableAlias, $alias, $procedimentoField) {
                    $join->on(DB::raw("{$tableAlias}.{$procedimentoField} COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.codigo COLLATE utf8mb4_unicode_ci"));
                });
                break;
            case 's_rub':
                $rubField = $this->getRubField();
                if ($rubField) {
                    $query->leftJoin("s_rub as {$alias}", function($join) use ($tableAlias, $alias, $rubField) {
                        $join->on(DB::raw("{$tableAlias}.{$rubField} COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.RUB_ID COLLATE utf8mb4_unicode_ci"));
                    });
                }
                break;
            case 'cismetro':
                $procedimentoField = $this->getProcedimentoFieldForCismetro();
                $query->leftJoin("cismetro as {$alias}", function($join) use ($tableAlias, $alias, $procedimentoField) {
                    $join->on(DB::raw("{$tableAlias}.{$procedimentoField} COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.codigo COLLATE utf8mb4_unicode_ci"));
                });
                break;
        }
    }

    /**
     * Get prestador field name
     */
    abstract protected function getPrestadorField(): string;

    /**
     * Get CBO field name
     */
    abstract protected function getCboField(): string;

    /**
     * Get RUB field name (can return null if not applicable)
     */
    protected function getRubField(): ?string
    {
        return null;
    }

    /**
     * Transform linear data into pivot structure
     * Supports grouping by prestador if prestador field is present
     */
    protected function pivotData($data, $selectedFields)
    {
        $competenciaField = $this->getCompetenciaField();
        $prestadorField = $this->getPrestadorFieldForMatrix($selectedFields);
        
        // Identificar competências únicas
        $competencias = $data->pluck('competencia')->unique()->sort()->values();
        
        // Identificar campos de agrupamento (excluindo competência e prestador se presente)
        $groupFields = array_filter($selectedFields, function($field) use ($competenciaField, $prestadorField) {
            return $field !== $competenciaField && $field !== $prestadorField;
        });
        $numericFields = $this->getNumericFields($selectedFields);
        
        // Se há campo de prestador, agrupar por prestador
        if ($prestadorField) {
            return $this->pivotDataByPrestador($data, $competencias, $groupFields, $numericFields, $prestadorField);
        }
        
        // Estrutura de resultado padrão (sem agrupamento por prestador)
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
            return $this->getGroupKey($item, $groupFields);
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
                $rowData['totals'][$field] = 0;
                foreach ($competencias as $comp) {
                    $rowData['totals'][$field] += $rowData['values'][$comp][$field] ?? 0;
                }
            }
            
            $result['rows'][] = $rowData;
        }
        
        // Calcular totais das colunas
        foreach ($competencias as $comp) {
            $result['totals'][$comp] = [];
            foreach ($numericFields as $field) {
                $result['totals'][$comp][$field] = 0;
                foreach ($result['rows'] as $row) {
                    $result['totals'][$comp][$field] += $row['values'][$comp][$field] ?? 0;
                }
            }
        }
        
        // Calcular total geral
        foreach ($numericFields as $field) {
            $result['grand_totals'][$field] = 0;
            foreach ($competencias as $comp) {
                $result['grand_totals'][$field] += $result['totals'][$comp][$field] ?? 0;
            }
        }
        
        return $result;
    }
    
    /**
     * Transform data into pivot structure grouped by prestador
     */
    protected function pivotDataByPrestador($data, $competencias, $groupFields, $numericFields, $prestadorField)
    {
        // Agrupar dados por prestador primeiro
        $prestadorGroups = $data->groupBy(function($item) use ($prestadorField) {
            return $this->getPrestadorKey($item, $prestadorField);
        });
        
        $result = [
            'competencias' => $competencias->map(function($comp) {
                return [
                    'code' => $comp,
                    'label' => $this->formatCompetencia($comp)
                ];
            }),
            'prestadores' => []
        ];
        
        // Processar cada prestador
        foreach ($prestadorGroups as $prestadorKey => $prestadorData) {
            $prestadorInfo = $this->getPrestadorInfo($prestadorData->first(), $prestadorField);
            $prestadorCode = $prestadorInfo['code'];
            $prestadorNome = $prestadorInfo['nome'];
            
            // Criar estrutura de matriz para este prestador
            $prestadorMatrix = [
                'competencias' => $result['competencias'],
                'rows' => [],
                'totals' => [],
                'grand_totals' => []
            ];
            
            // Agrupar dados por categoria (linhas) para este prestador
            $groupedData = $prestadorData->groupBy(function($item) use ($groupFields) {
                return $this->getGroupKey($item, $groupFields);
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
                    $rowData['totals'][$field] = 0;
                    foreach ($competencias as $comp) {
                        $rowData['totals'][$field] += $rowData['values'][$comp][$field] ?? 0;
                    }
                }
                
                $prestadorMatrix['rows'][] = $rowData;
            }
            
            // Calcular totais das colunas para este prestador
            foreach ($competencias as $comp) {
                $prestadorMatrix['totals'][$comp] = [];
                foreach ($numericFields as $field) {
                    $prestadorMatrix['totals'][$comp][$field] = 0;
                    foreach ($prestadorMatrix['rows'] as $row) {
                        $prestadorMatrix['totals'][$comp][$field] += $row['values'][$comp][$field] ?? 0;
                    }
                }
            }
            
            // Calcular total geral para este prestador
            foreach ($numericFields as $field) {
                $prestadorMatrix['grand_totals'][$field] = 0;
                foreach ($competencias as $comp) {
                    $prestadorMatrix['grand_totals'][$field] += $prestadorMatrix['totals'][$comp][$field] ?? 0;
                }
            }
            
            $result['prestadores'][$prestadorCode] = array_merge($prestadorMatrix, [
                'nome' => $prestadorNome
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get prestador field name for matrix grouping (if present in selected fields)
     */
    protected function getPrestadorFieldForMatrix($selectedFields)
    {
        $prestadorField = $this->getPrestadorField();
        return in_array($prestadorField, $selectedFields) ? $prestadorField : null;
    }
    
    /**
     * Get prestador key from item
     */
    protected function getPrestadorKey($item, $prestadorField)
    {
        return $this->getGroupKeyPart($item, $prestadorField);
    }
    
    /**
     * Get prestador info (code and name) from item
     */
    protected function getPrestadorInfo($item, $prestadorField)
    {
        $key = $this->getPrestadorKey($item, $prestadorField);
        $parts = explode('|', $key);
        
        return [
            'code' => $parts[0] ?? '',
            'nome' => $parts[1] ?? $parts[0] ?? 'Prestador Desconhecido'
        ];
    }

    /**
     * Get group key for grouping data
     */
    protected function getGroupKey($item, $groupFields)
    {
        $key = [];
        foreach ($groupFields as $field) {
            $key[] = $this->getGroupKeyPart($item, $field);
        }
        return implode('||', $key);
    }

    /**
     * Get part of group key for a specific field
     */
    protected function getGroupKeyPart($item, $field)
    {
        // Default implementation - can be overridden
        return $item->{$field} ?? '';
    }

    /**
     * Get numeric fields from selected fields
     */
    protected function getNumericFields($selectedFields)
    {
        $numericFields = [];
        foreach ($selectedFields as $field) {
            $fieldConfig = $this->getFieldConfig($field);
            if ($fieldConfig && ($fieldConfig['type'] === 'number' || $fieldConfig['type'] === 'currency')) {
                $numericFields[] = $field;
            }
        }
        return $numericFields;
    }

    /**
     * Format competencia for display
     */
    protected function formatCompetencia($competencia)
    {
        if (strlen($competencia) === 6) {
            return substr($competencia, 4, 2) . '/' . substr($competencia, 0, 4);
        }
        return $competencia;
    }

    /**
     * Format row category for display
     */
    protected function formatRowCategory($groupKey, $groupFields)
    {
        $parts = explode('||', $groupKey);
        $formatted = [];
        
        foreach ($parts as $i => $part) {
            if (str_contains($part, '|')) {
                $subParts = explode('|', $part);
                $codigo = $subParts[0] ?? '';
                $nome = $subParts[1] ?? '';
                
                // Mostrar código e nome quando ambos existirem
                if ($codigo && $nome) {
                    $formatted[] = $codigo . ' - ' . $nome;
                } elseif ($nome) {
                    $formatted[] = $nome;
                } elseif ($codigo) {
                    $formatted[] = $codigo;
                }
            } else {
                $formatted[] = $part;
            }
        }
        
        return implode(' - ', $formatted);
    }

    /**
     * Get numeric value from item
     */
    protected function getNumericValue($item, $field)
    {
        // Default implementation - can be overridden
        $fieldConfig = $this->getFieldConfig($field);
        
        if ($fieldConfig && $fieldConfig['type'] === 'currency') {
            // Try common field names
            $value = $item->{'total_' . strtolower($field)} ?? 
                     $item->{'valor_total'} ?? 
                     $item->{$field} ?? 0;
        } elseif ($fieldConfig && $fieldConfig['type'] === 'number') {
            $value = $item->{'total_' . strtolower($field)} ?? 
                     $item->{'total_quantidade'} ?? 
                     $item->{$field} ?? 0;
        } else {
            $value = $item->{$field} ?? 0;
        }
        
        return (float)$value;
    }

    /**
     * Export matrix to Excel
     */
    protected function exportMatrixExcel($pivotData, $selectedFields = [])
    {
        try {
            $exportClass = $this->getMatrixExportClass();
            $numericFields = $this->getNumericFields($selectedFields);
            $prestadorField = $this->getPrestadorFieldForMatrix($selectedFields);
            
            // Se há prestador e dados agrupados por prestador, usar exportação com múltiplas planilhas
            if ($prestadorField && isset($pivotData['prestadores'])) {
                $export = new \App\Exports\MatrixReportByPrestadorExport($pivotData, $numericFields, $prestadorField);
            } else {
                $export = new $exportClass($pivotData, $numericFields);
            }
            
            return \Maatwebsite\Excel\Facades\Excel::download($export, $this->getMatrixExportFilename());
        } catch (\Exception $e) {
            \Log::error('Error in matrix Excel export: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erro ao exportar Excel matriz: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export matrix to PDF
     */
    protected function exportMatrixPdf($pivotData)
    {
        // TODO: Implementar exportação PDF para matriz
        return response()->json(['error' => 'Exportação PDF para matriz ainda não implementada'], 501);
    }

    /**
     * Export matrix to CSV
     */
    protected function exportMatrixCsv($pivotData)
    {
        // TODO: Implementar exportação CSV para matriz
        return response()->json(['error' => 'Exportação CSV para matriz ainda não implementada'], 501);
    }

    /**
     * Get matrix export class name
     */
    abstract protected function getMatrixExportClass(): string;

    /**
     * Get matrix export filename
     */
    protected function getMatrixExportFilename()
    {
        return 'relatorio-matriz.xlsx';
    }
    
    /**
     * Get default numeric field to include in matrix if none selected
     * Override in child classes if needed
     */
    protected function getDefaultNumericField(): ?string
    {
        return null; // Child classes should override this
    }
}

