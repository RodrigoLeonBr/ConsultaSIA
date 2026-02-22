<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\RelatorioApacExport;
use App\Exports\MatrixReportExport;
use App\Http\Controllers\Concerns\HasMatrixReport;

class RelatorioApacController extends BaseRelatorioController
{
    use HasMatrixReport;
    /**
     * Display the APAC/OCI report builder interface
     */
    public function index()
    {
        return view('relatorios.apac.index');
    }

    /**
     * Get available fields for APAC/OCI report building
     */
    public function getFields()
    {
        return response()->json([
            'fields' => [
                'PAP_UID' => [
                    'label' => 'Unidade (CNES)',
                    'type' => 'lookup',
                    'table' => 's_pap',
                    'lookup_table' => 'prestador',
                    'lookup_key' => 're_cunid',
                    'lookup_display' => 're_cnome',
                    'operators' => ['=', 'in']
                ],
                'PAP_MVM' => [
                    'label' => 'Competência',
                    'type' => 'date',
                    'table' => 's_pap',
                    'operators' => ['=', '>=', '<=', 'between']
                ],
                'PAP_PA' => [
                    'label' => 'Procedimento',
                    'type' => 'lookup',
                    'table' => 's_pap',
                    'lookup_table' => 'procedimento',
                    'lookup_key' => 'codigo',
                    'lookup_display' => 'procedimento',
                    'operators' => ['=', 'in', 'like']
                ],
                'PAP_CBO' => [
                    'label' => 'CBO Profissional',
                    'type' => 'lookup',
                    'table' => 's_pap',
                    'lookup_table' => 'cbo',
                    'lookup_key' => 'cbo',
                    'lookup_display' => 'ds_cbo',
                    'operators' => ['=', 'in']
                ],
                'PAP_CIDPRI' => [
                    'label' => 'CID Principal',
                    'type' => 'text',
                    'table' => 's_pap',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                'PAP_QT_P' => [
                    'label' => 'Quantidade Produzida',
                    'type' => 'number',
                    'table' => 's_pap',
                    'operators' => ['=', '>', '<', '>=', '<=', 'between']
                ],
                'PAP_VALOR' => [
                    'label' => 'Valor (Unitário e Total)',
                    'type' => 'currency',
                    'table' => 's_pap',
                    'operators' => ['=', '>', '<', '>=', '<=', 'between']
                ],
                'APA_PRIPAL' => [
                    'label' => 'Procedimento Principal APAC',
                    'type' => 'text',
                    'table' => 's_apa',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                // NOVO CAMPO NOME
                'APA_NMPCN' => [
                    'label' => 'Nome do Paciente',
                    'type' => 'text',
                    'table' => 's_apa',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                // NOVOS CAMPOS CISMETRO
                'cismetro_valor' => [
                    'label' => 'Cismetro - Valor Unitário',
                    'type' => 'currency',
                    'table' => 'cismetro',
                    'operators' => ['=', '>', '<', '>=', '<=', 'between']
                ],
                'cismetro_total' => [
                    'label' => 'Cismetro - Valor Total',
                    'type' => 'currency',
                    'table' => 'calculated',
                    'operators' => ['=', '>', '<', '>=', '<=', 'between']
                ],
                'cismetro_descricao' => [
                    'label' => 'Cismetro - Descrição',
                    'type' => 'lookup',
                    'table' => 'cismetro',
                    'lookup_table' => 'cismetro',
                    'lookup_key' => 'codigo',
                    'lookup_display' => 'descricao',
                    'operators' => ['=', 'like']
                ]
            ]
        ]);
    }

    /**
     * Implement abstract methods from BaseRelatorioController
     */
    protected function getTableName(): string
    {
        return 's_pap';
    }

    protected function getTableAlias(): string
    {
        return 'pap';
    }

    protected function getCompetenciaField(): string
    {
        return 'PAP_MVM';
    }

    protected function getExportClass(): string
    {
        return RelatorioApacExport::class;
    }

    protected function getPdfView(): string
    {
        return 'relatorios.apac.pdf';
    }

    protected function getReportTitle(): string
    {
        return 'Relatório APAC/OCI';
    }

    protected function getMatrixExportClass(): string
    {
        return MatrixReportExport::class;
    }

    /**
     * Build database query for APAC/OCI data
     */
    protected function buildQuery($selectedFields, $filters, $groupBy = true)
    {
        // Check if OCI filter is applied
        $ociFilter = collect($filters)->firstWhere('field', 'filter_oci');
        
        // Check if cismetro fields are needed
        $needsCismetro = collect($selectedFields)->contains(function($field) {
            return str_starts_with($field, 'cismetro_');
        });
        
        // Start with s_pap as main table
        $query = DB::table('s_pap as pap');
        
        // Add s_apa join - with OCI condition if needed
        if ($ociFilter && $ociFilter['value'] === true) {
            // For OCI filter, join with condition that APA_PRIPAL starts with '09'
            $query->join('s_apa as apa', function($join) {
                $join->on(DB::raw('pap.PAP_NUM COLLATE utf8mb4_unicode_ci'), '=', DB::raw('apa.APA_NUM COLLATE utf8mb4_unicode_ci'))
                     ->where('apa.APA_PRIPAL', 'like', '09%');
            });
        } else {
            // Regular left join for all APAC data
            $query->leftJoin('s_apa as apa', function($join) {
                $join->on(DB::raw('pap.PAP_NUM COLLATE utf8mb4_unicode_ci'), '=', DB::raw('apa.APA_NUM COLLATE utf8mb4_unicode_ci'));
            });
        }
        
        // Add joins based on selected fields
        $joins = [];
        
        foreach ($selectedFields as $field) {
            $fieldConfig = $this->getFieldConfig($field);
            if ($fieldConfig && $fieldConfig['type'] === 'lookup') {
                $joinKey = $fieldConfig['lookup_table'];
                if (!in_array($joinKey, $joins)) {
                    $this->addJoin($query, $field, $fieldConfig);
                    $joins[] = $joinKey;
                }
            }
            // Special case: PAP_VALOR needs procedimento join for pa_total
            if ($field === 'PAP_VALOR' && !in_array('procedimento', $joins)) {
                $query->leftJoin('procedimento as pc', function($join) {
                    $join->on(DB::raw('pap.PAP_PA COLLATE utf8mb4_unicode_ci'), '=', DB::raw('pc.codigo COLLATE utf8mb4_unicode_ci'));
                });
                $joins[] = 'procedimento';
            }
        }
        
        // Add cismetro join if needed
        if ($needsCismetro && !in_array('cismetro', $joins)) {
            $query->leftJoin('cismetro as cs', function($join) {
                $join->on(DB::raw('pap.PAP_PA COLLATE utf8mb4_unicode_ci'), '=', DB::raw('cs.codigo COLLATE utf8mb4_unicode_ci'));
            });
            $joins[] = 'cismetro';
        }
        
        // Build select fields with grouping and aggregation
        $selectFields = [];
        $groupByFields = [];
        
        foreach ($selectedFields as $field) {
            // Skip filter_oci as it's not a selectable field, only a filter
            if ($field === 'filter_oci') {
                continue;
            }
            
            $fieldConfig = $this->getFieldConfig($field);
            if ($fieldConfig) {
                if ($fieldConfig['type'] === 'lookup') {
                    $alias = $this->getTableAlias($fieldConfig['lookup_table']);
                    
                    if ($field === 'PAP_UID') {
                        $selectFields[] = "pap.PAP_UID as cnes";
                        $selectFields[] = "pr.re_cnome as unidade_nome";
                        $groupByFields[] = "pap.PAP_UID";
                        $groupByFields[] = "pr.re_cnome";
                    } elseif ($field === 'PAP_PA') {
                        $selectFields[] = "pap.PAP_PA as procedimento_codigo";
                        $selectFields[] = "pc.procedimento as procedimento_nome";
                        $groupByFields[] = "pap.PAP_PA";
                        $groupByFields[] = "pc.procedimento";
                    } elseif ($field === 'cismetro_descricao') {
                        $selectFields[] = "pap.PAP_PA as cismetro_codigo";
                        $selectFields[] = "cs.descricao as cismetro_descricao";
                        $groupByFields[] = "pap.PAP_PA";
                        $groupByFields[] = "cs.descricao";
                    } else {
                        $selectFields[] = "pap.{$field}";
                        $selectFields[] = "{$alias}.{$fieldConfig['lookup_display']} as {$field}_display";
                        $groupByFields[] = "pap.{$field}";
                        $groupByFields[] = "{$alias}.{$fieldConfig['lookup_display']}";
                    }
                } elseif ($field === 'PAP_QT_P') {
                    // Sum quantities
                    $selectFields[] = DB::raw("SUM(CAST(pap.PAP_QT_P as DECIMAL(15,2))) as total_quantidade");
                } elseif ($field === 'PAP_VALOR') {
                    // Add valor unitário and valor total
                    $selectFields[] = "pc.pa_total as valor_unitario";
                    $selectFields[] = DB::raw("SUM(CAST(pap.PAP_QT_P as DECIMAL(15,2)) * CAST(pc.pa_total as DECIMAL(15,2))) as valor_total");
                    $groupByFields[] = "pc.pa_total";
                } elseif ($field === 'cismetro_valor') {
                    // Cismetro unit value
                    $selectFields[] = "cs.valor as cismetro_valor";
                    $groupByFields[] = "cs.valor";
                } elseif ($field === 'cismetro_total') {
                    // Cismetro total value (quantity * unit value)
                    $selectFields[] = DB::raw("SUM(CAST(pap.PAP_QT_P as DECIMAL(15,2)) * COALESCE(cs.valor, 0)) as cismetro_total");
                } elseif ($field === 'PAP_MVM') {
                    // Format competencia as YYYY-MM
                    $selectFields[] = DB::raw("CONCAT(SUBSTRING(pap.PAP_MVM, 1, 4), '-', SUBSTRING(pap.PAP_MVM, 5, 2)) as competencia");
                    $groupByFields[] = "pap.PAP_MVM";
                } elseif (strpos($field, 'APA_') === 0) {
                    // Fields from s_apa table
                    $selectFields[] = "apa.{$field}";
                    $groupByFields[] = "apa.{$field}";
                } else {
                    // Fields from s_pap table
                    $selectFields[] = "pap.{$field}";
                    $groupByFields[] = "pap.{$field}";
                }
            }
        }
        
        $query->select($selectFields);
        
        // Apply filters
        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }
        
        // Group by non-aggregate fields if grouping is enabled
        if ($groupBy && !empty($groupByFields)) {
            $query->groupBy($groupByFields);
        }
        
        // Order by first field
        if (!empty($groupByFields)) {
            $query->orderBy($groupByFields[0]);
        }
        
        return $query;
    }

    /**
     * Add appropriate join to query
     */
    protected function addJoin($query, $field, $fieldConfig)
    {
        $alias = $this->getTableAliasForJoin($fieldConfig['lookup_table']);
        $tableAlias = $this->getTableAlias();
        
        switch ($fieldConfig['lookup_table']) {
            case 'prestador':
                $query->leftJoin("prestador as {$alias}", function($join) use ($tableAlias, $alias) {
                    $join->on(DB::raw("{$tableAlias}.PAP_UID COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.re_cunid COLLATE utf8mb4_unicode_ci"));
                });
                break;
            case 'cbo':
                $query->leftJoin("cbo as {$alias}", function($join) use ($tableAlias, $alias) {
                    $join->on(DB::raw("{$tableAlias}.PAP_CBO COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.cbo COLLATE utf8mb4_unicode_ci"));
                });
                break;
            case 'procedimento':
                $query->leftJoin("procedimento as {$alias}", function($join) use ($tableAlias, $alias) {
                    $join->on(DB::raw("{$tableAlias}.PAP_PA COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.codigo COLLATE utf8mb4_unicode_ci"));
                });
                break;
            case 'cismetro':
                $query->leftJoin("cismetro as {$alias}", function($join) use ($tableAlias, $alias) {
                    $join->on(DB::raw("{$tableAlias}.PAP_PA COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.codigo COLLATE utf8mb4_unicode_ci"));
                });
                break;
        }
    }


    /**
     * Apply filter to query
     */
    protected function applyFilter($query, $filter)
    {
        $field = $filter['field'];
        $operator = $filter['operator'];
        $value = $filter['value'];
        
        // Skip OCI filter as it's handled in buildQuery
        if ($field === 'filter_oci') {
            return;
        }
        
        // Handle cismetro fields in filters
        if ($field === 'cismetro_valor') {
            $fullField = 'cs.valor';
        } elseif ($field === 'cismetro_total') {
            // For cismetro_total, we need to filter on the calculated field
            // This is more complex and might need HAVING clause
            return; // Skip for now, can be implemented later if needed
        } elseif (str_starts_with($field, 'cismetro_')) {
            $fullField = 'cs.' . substr($field, 9); // Remove 'cismetro_' prefix
        } else {
            // Determine table prefix
            $tablePrefix = strpos($field, 'APA_') === 0 ? 'apa' : $this->getTableAlias();
            $fullField = "{$tablePrefix}.{$field}";
        }
        
        switch ($operator) {
            case '=':
                $query->where($fullField, '=', $value);
                break;
            case '>':
                $query->where($fullField, '>', $value);
                break;
            case '<':
                $query->where($fullField, '<', $value);
                break;
            case '>=':
                $query->where($fullField, '>=', $value);
                break;
            case '<=':
                $query->where($fullField, '<=', $value);
                break;
            case 'like':
                $query->where($fullField, 'like', '%' . $value . '%');
                break;
            case 'starts_with':
                $query->where($fullField, 'like', $value . '%');
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($fullField, $value);
                }
                break;
            case 'in':
                if (is_array($value)) {
                    $query->whereIn($fullField, $value);
                }
                break;
        }
    }

     /**
     * Override formatData to handle specific field mappings
     */
    protected function formatData($data, $selectedFields)
    {
        return $data->map(function ($row) use ($selectedFields) {
            $formatted = [];
            
            foreach ($selectedFields as $field) {
                // Skip filter_oci as it's not a data field
                if ($field === 'filter_oci') {
                    continue;
                }
                
                $fieldConfig = $this->getFieldConfig($field);
                
                // Handle special field mappings
                if ($field === 'PAP_UID') {
                    $formatted['CNES'] = $row->cnes ?? '';
                    $formatted['Unidade'] = $row->unidade_nome ?? '';
                } elseif ($field === 'PAP_PA') {
                    $formatted['Código Procedimento'] = $row->procedimento_codigo ?? '';
                    $formatted['Procedimento'] = $row->procedimento_nome ?? '';
                } elseif ($field === 'cismetro_descricao') {
                    $formatted['Código Cismetro'] = $row->cismetro_codigo ?? '';
                    $formatted['Cismetro - Descrição'] = $row->cismetro_descricao ?? '';
                } elseif ($field === 'cismetro_valor') {
                    $formatted['Cismetro - Valor Unitário'] = $row->cismetro_valor ? 
                        'R$ ' . number_format((float)$row->cismetro_valor, 2, ',', '.') : 'R$ 0,00';
                } elseif ($field === 'cismetro_total') {
                    $formatted['Cismetro - Valor Total'] = $row->cismetro_total ? 
                        'R$ ' . number_format((float)$row->cismetro_total, 2, ',', '.') : 'R$ 0,00';
                } elseif ($field === 'PAP_QT_P') {
                    $formatted['Quantidade Total'] = number_format((float)($row->total_quantidade ?? 0), 0, ',', '.');
                } elseif ($field === 'PAP_VALOR') {
                    $formatted['Valor Unitário'] = 'R$ ' . number_format((float)($row->valor_unitario ?? 0), 2, ',', '.');
                    $formatted['Valor Total'] = 'R$ ' . number_format((float)($row->valor_total ?? 0), 2, ',', '.');
                } elseif ($field === 'PAP_MVM') {
                    $formatted['Competência'] = $row->competencia ?? '';
                } elseif ($field === 'APA_NMPCN') {
                    // NOVO CAMPO NOME
                    $formatted['Nome do Paciente'] = $row->APA_NMPCN ?? '';            
                } else {
                    $value = $row->{$field} ?? '';
                    
                    // Format based on field type
                    switch ($fieldConfig['type'] ?? 'text') {
                        case 'number':
                            $formatted[$fieldConfig['label']] = number_format((float)$value, 2, ',', '.');
                            break;
                        case 'date':
                            $formatted[$fieldConfig['label']] = $value ? date('d/m/Y', strtotime($value)) : '';
                            break;
                        case 'lookup':
                            $displayField = $field . '_display';
                            $formatted[$fieldConfig['label']] = $row->{$displayField} ?? $value;
                            break;
                        default:
                            $formatted[$fieldConfig['label']] = $value;
                    }
                }
            }
            return $formatted;
        });
    }

    /**
     * Override calculateTotals to handle specific numeric fields
     */
    protected function calculateTotals($data, $selectedFields)
    {
        $totals = [];
        
        if (in_array('PAP_QT_P', $selectedFields)) {
            $totalQty = $data->sum(function($item) {
                return $item->total_quantidade ?? 0;
            });
            $totals['Quantidade Total'] = number_format($totalQty, 0, ',', '.');
        }
        
        if (in_array('PAP_VALOR', $selectedFields)) {
            $totalValue = $data->sum(function($item) {
                return $item->valor_total ?? 0;
            });
            $totals['Valor Total Geral'] = 'R$ ' . number_format($totalValue, 2, ',', '.');
        }
        
        // NOVOS TOTAIS CISMETRO
        if (in_array('cismetro_total', $selectedFields)) {
            $totalCismetro = $data->sum(function($item) {
                return $item->cismetro_total ?? 0;
            });
            $totals['Cismetro - Valor Total Geral'] = 'R$ ' . number_format($totalCismetro, 2, ',', '.');
        }
        
        return $totals;
    }

    /**
     * Export to Excel
     */
    /**
 * Export to Excel - Simplified Version
 */
    protected function exportExcel($data, $selectedFields, $totals = [])
    {
        try {
            \Log::info('=== APAC Excel Export Started (Simplified) ===', [
                'data_type' => gettype($data),
                'data_count' => is_countable($data) ? count($data) : 'not countable',
                'fields_count' => count($selectedFields),
                'totals_count' => count($totals)
            ]);
            
            // Basic validation
            if (empty($data)) {
                throw new \Exception('Nenhum dado encontrado para exportação');
            }
            
            // Convert to Collection if needed
            if (!$data instanceof \Illuminate\Support\Collection) {
                \Log::info('APAC: Converting data to Collection');
                $data = collect($data);
            }
            
            \Log::info('APAC: Data prepared, count: ' . $data->count());
            
            // Create export instance
            \Log::info('APAC: Creating RelatorioApacExport instance...');
            $export = new RelatorioApacExport($data, $selectedFields, $totals);
            
            \Log::info('APAC: Starting Excel download...');
            
            // Simple download with timestamp
            return Excel::download($export, 'relatorio_apac_' . date('Y-m-d_H-i-s') . '.xlsx');
            
        } catch (\Exception $e) {
            \Log::error('APAC Excel Export Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'error' => 'Erro ao exportar Excel: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export to PDF
     */
    protected function exportPdf($data, $selectedFields, $totals = [])
    {
        $pdf = Pdf::loadView('relatorios.apac.pdf', [
            'data' => $data,
            'fields' => $selectedFields,
            'totals' => $totals,
            'title' => 'Relatório APAC/OCI - ConsultaProd'
        ]);
        
        return $pdf->download('relatorio_apac.pdf');
    }

    /**
     * Export to CSV
     */
    protected function exportCsv($data, $selectedFields, $totals = [])
    {
        $filename = 'relatorio_apac.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($data, $selectedFields, $totals) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers - get from first row if data exists
            if (!empty($data)) {
                $firstRow = $data->first();
                $fieldLabels = array_keys($firstRow);
                fputcsv($file, $fieldLabels, ';');
                
                // Data
                foreach ($data as $row) {
                    $csvRow = array_values($row);
                    fputcsv($file, $csvRow, ';');
                }
                
                // Add totals if available
                if (!empty($totals)) {
                    fputcsv($file, [], ';'); // Empty line
                    fputcsv($file, ['TOTAIS'], ';');
                    foreach ($totals as $label => $value) {
                        fputcsv($file, [$label, $value], ';');
                    }
                }
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get field configuration
     */
    protected function getFieldConfig($field)
    {
        $fields = [
            'PAP_UID' => [
                'label' => 'Unidade (CNES)',
                'type' => 'lookup',
                'table' => 's_pap',
                'lookup_table' => 'prestador',
                'lookup_key' => 're_cunid',
                'lookup_display' => 're_cnome',
                'operators' => ['=', 'in']
            ],
            'PAP_MVM' => [
                'label' => 'Competência',
                'type' => 'date',
                'table' => 's_pap',
                'operators' => ['=', '>=', '<=', 'between']
            ],
            'PAP_PA' => [
                'label' => 'Procedimento',
                'type' => 'lookup',
                'table' => 's_pap',
                'lookup_table' => 'procedimento',
                'lookup_key' => 'codigo',
                'lookup_display' => 'procedimento',
                'operators' => ['=', 'in', 'like']
            ],
            'PAP_CBO' => [
                'label' => 'CBO Profissional',
                'type' => 'lookup',
                'table' => 's_pap',
                'lookup_table' => 'cbo',
                'lookup_key' => 'cbo',
                'lookup_display' => 'ds_cbo',
                'operators' => ['=', 'in']
            ],
            'PAP_CIDPRI' => [
                'label' => 'CID Principal',
                'type' => 'text',
                'table' => 's_pap',
                'operators' => ['=', 'like', 'starts_with']
            ],
            'PAP_QT_P' => [
                'label' => 'Quantidade Produzida',
                'type' => 'number',
                'table' => 's_pap',
                'operators' => ['=', '>', '<', '>=', '<=', 'between']
            ],
            'PAP_VALOR' => [
                'label' => 'Valor (Unitário e Total)',
                'type' => 'currency',
                'table' => 's_pap',
                'operators' => ['=', '>', '<', '>=', '<=', 'between']
            ],
            'APA_PRIPAL' => [
                'label' => 'Procedimento Principal APAC',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'like', 'starts_with']
            ],
            // NOVO CAMPO NOME
            'APA_NMPCN' => [
                'label' => 'Nome do Paciente',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'like', 'starts_with']
            ],
            // CAMPOS CISMETRO
            'cismetro_valor' => [
                'label' => 'Cismetro - Valor Unitário',
                'type' => 'currency',
                'table' => 'cismetro',
                'operators' => ['=', '>', '<', '>=', '<=', 'between']
            ],
            'cismetro_total' => [
                'label' => 'Cismetro - Valor Total',
                'type' => 'currency',
                'table' => 'calculated',
                'operators' => ['=', '>', '<', '>=', '<=', 'between']
            ],
            'cismetro_descricao' => [
                'label' => 'Cismetro - Descrição',
                'type' => 'lookup',
                'table' => 'cismetro',
                'lookup_table' => 'cismetro',
                'lookup_key' => 'codigo',
                'lookup_display' => 'descricao',
                'operators' => ['=', 'like']
            ]
        ];
        
        return $fields[$field] ?? null;
    }

    /**
     * Implement trait methods for matrix
     */
    protected function getPrestadorField(): string
    {
        return 'PAP_UID';
    }

    protected function getCboField(): string
    {
        return 'PAP_CBO';
    }

    protected function getProcedimentoFieldForCismetro(): string
    {
        return 'PAP_PA';
    }

    protected function getMatrixLookupFields($field, $tableAlias): array
    {
        $selectFields = [];
        $groupByFields = [];
        
        if ($field === 'PAP_UID') {
            $selectFields[] = "{$tableAlias}.PAP_UID as prestador_codigo";
            $selectFields[] = "pr.re_cnome as prestador_nome";
            $groupByFields[] = "{$tableAlias}.PAP_UID";
            $groupByFields[] = "pr.re_cnome";
        } elseif ($field === 'PAP_PA') {
            $selectFields[] = "{$tableAlias}.PAP_PA as procedimento_codigo";
            $selectFields[] = "pc.procedimento as procedimento_nome";
            $groupByFields[] = "{$tableAlias}.PAP_PA";
            $groupByFields[] = "pc.procedimento";
        } elseif ($field === 'PAP_CBO') {
            $selectFields[] = "{$tableAlias}.PAP_CBO as cbo_codigo";
            $selectFields[] = "cb.ds_cbo as cbo_nome";
            $groupByFields[] = "{$tableAlias}.PAP_CBO";
            $groupByFields[] = "cb.ds_cbo";
        } elseif ($field === 'cismetro_descricao') {
            $selectFields[] = "{$tableAlias}.PAP_PA as cismetro_codigo";
            $selectFields[] = "cs.descricao as cismetro_descricao";
            $groupByFields[] = "{$tableAlias}.PAP_PA";
            $groupByFields[] = "cs.descricao";
        }
        
        return ['select' => $selectFields, 'groupBy' => $groupByFields];
    }

    protected function getMatrixNumericFields($field, $tableAlias): array
    {
        $fields = [];
        
        if ($field === 'PAP_QT_P') {
            $fields[] = DB::raw("SUM(CAST({$tableAlias}.PAP_QT_P as DECIMAL(15,2))) as total_quantidade");
        } elseif ($field === 'PAP_VALOR') {
            $fields[] = DB::raw("SUM(CAST({$tableAlias}.PAP_QT_P as DECIMAL(15,2)) * CAST(pc.pa_total as DECIMAL(15,2))) as valor_total");
        } elseif ($field === 'cismetro_total') {
            $fields[] = DB::raw("SUM(CAST({$tableAlias}.PAP_QT_P as DECIMAL(15,2)) * COALESCE(cs.valor, 0)) as cismetro_total");
        }
        
        return $fields;
    }

    protected function getGroupKeyPart($item, $field)
    {
        if ($field === 'PAP_UID') {
            return ($item->prestador_codigo ?? '') . '|' . ($item->prestador_nome ?? '');
        } elseif ($field === 'PAP_PA') {
            return ($item->procedimento_codigo ?? '') . '|' . ($item->procedimento_nome ?? '');
        } elseif ($field === 'PAP_CBO') {
            return ($item->cbo_codigo ?? '') . '|' . ($item->cbo_nome ?? '');
        } elseif ($field === 'cismetro_descricao') {
            return ($item->cismetro_codigo ?? '') . '|' . ($item->cismetro_descricao ?? '');
        }
        
        return $item->{$field} ?? '';
    }

    protected function getNumericValue($item, $field)
    {
        switch ($field) {
            case 'PAP_QT_P':
                return (float)($item->total_quantidade ?? 0);
            case 'PAP_VALOR':
                return (float)($item->valor_total ?? 0);
            case 'cismetro_total':
                return (float)($item->cismetro_total ?? 0);
            case 'cismetro_valor':
                return (float)($item->cismetro_valor ?? 0);
            default:
                return (float)($item->{$field} ?? 0);
        }
    }
    
    /**
     * Get default numeric field for matrix
     */
    protected function getDefaultNumericField(): ?string
    {
        return 'PAP_QT_P';
    }

    /**
     * Teste ultra simples
     */
    public function testUltraSimple()
    {
        try {
            \Log::info('=== Test Ultra Simple ===');
            
            // Dados mínimos
            $testData = collect([
                ['Nome' => 'João', 'Idade' => '30'],
                ['Nome' => 'Maria', 'Idade' => '25']
            ]);
            
            \Log::info('Creating simple export...');
            
            $export = new \App\Exports\RelatorioApacExport($testData, [], []);
            
            \Log::info('Export created, starting download...');
            
            return \Maatwebsite\Excel\Facades\Excel::download($export, 'teste_ultra_simples.xlsx');
            
        } catch (\Exception $e) {
            \Log::error('Test Ultra Simple Error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

}