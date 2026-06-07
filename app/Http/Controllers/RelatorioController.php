<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\RelatorioExport;
use App\Exports\MatrixReportExport;
use App\Http\Controllers\Concerns\HasMatrixReport;

class RelatorioController extends BaseRelatorioController
{
    use HasMatrixReport;
    /**
     * Display the report builder interface
     */
    public function index()
    {
        return view('relatorios.index');
    }

    /**
     * Get available fields for report building
     */
    public function getFields()
    {
        return response()->json([
            'fields' => [
                'prd_cmp' => [
                    'label' => 'Data Competência',
                    'type' => 'date',
                    'table' => 's_prd',
                    'operators' => ['=', '>=', '<=', 'between']
                ],
                'prd_mvm' => [
                    'label' => 'Data Movimento',
                    'type' => 'date',
                    'table' => 's_prd',
                    'operators' => ['=', '>=', '<=', 'between']
                ],
                'prd_uid' => [
                    'label' => 'Prestador',
                    'type' => 'lookup',
                    'table' => 's_prd',
                    'lookup_table' => 'prestador',
                    'lookup_key' => 're_cunid',
                    'lookup_display' => 're_cnome',
                    'operators' => ['=', 'in']
                ],
                'tipo_relatorio' => [
                    'label' => 'Tipo de Relatório',
                    'type' => 'text',
                    'table' => 'prestador',
                    'field' => 'relatorio',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                'prd_cbo' => [
                    'label' => 'CBO',
                    'type' => 'lookup',
                    'table' => 's_prd',
                    'lookup_table' => 'cbo',
                    'lookup_key' => 'cbo',
                    'lookup_display' => 'ds_cbo',
                    'operators' => ['=', 'in']
                ],
                'prd_pa' => [
                    'label' => 'Procedimento',
                    'type' => 'lookup',
                    'table' => 's_prd',
                    'lookup_table' => 'procedimento',
                    'lookup_key' => 'codigo',
                    'lookup_display' => 'procedimento',
                    'operators' => ['=', 'in', 'like']
                ],
                'procedimento_descricao' => [
                    'label' => 'Descrição do Procedimento',
                    'type' => 'text',
                    'table' => 'procedimento',
                    'field' => 'procedimento',
                    'operators' => ['=', 'like', 'starts_with', 'ends_with']
                ],
                'PRD_QT_P' => [
                    'label' => 'Quantidade',
                    'type' => 'number',
                    'table' => 's_prd',
                    'operators' => ['=', '>', '<', '>=', '<=', 'between']
                ],
                'PRD_VL_P' => [
                    'label' => 'Valor',
                    'type' => 'currency',
                    'table' => 's_prd',
                    'operators' => ['=', '>', '<', '>=', '<=', 'between']
                ],
                'PRD_RUB' => [
                    'label' => 'Rubrica',
                    'type' => 'lookup',
                    'table' => 's_prd',
                    'lookup_table' => 's_rub',
                    'lookup_key' => 'RUB_ID',
                    'lookup_display' => 'RUB_DC',
                    'operators' => ['=', 'in']
                ],
                'PRD_CIDPRI' => [
                    'label' => 'CID Principal',
                    'type' => 'text',
                    'table' => 's_prd',
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
                ],
                'grupo' => [
                    'label' => 'Grupo',
                    'type' => 'text',
                    'table' => 'forma',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                'descgrupo' => [
                    'label' => 'Descrição do Grupo',
                    'type' => 'text',
                    'table' => 'forma',
                    'operators' => []
                ],
                'subgrupo' => [
                    'label' => 'Subgrupo',
                    'type' => 'text',
                    'table' => 'forma',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                'descsubgrupo' => [
                    'label' => 'Descrição do Subgrupo',
                    'type' => 'text',
                    'table' => 'forma',
                    'operators' => []
                ],
                'forma' => [
                    'label' => 'Forma de Organização',
                    'type' => 'text',
                    'table' => 'forma',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                'descforma' => [
                    'label' => 'Descrição da Forma',
                    'type' => 'text',
                    'table' => 'forma',
                    'operators' => ['=', 'like', 'starts_with']
                ]
            ]
        ]);
    }

    /**
     * Implement abstract methods from BaseRelatorioController
     */
    protected function getTableName(): string
    {
        return 's_prd';
    }

    protected function getTableAlias(): string
    {
        return 'sp';
    }

    protected function getCompetenciaField(): string
    {
        return 'prd_cmp';
    }

    protected function getMovimentoField(): ?string
    {
        return 'prd_mvm';
    }

    protected function getExportClass(): string
    {
        return RelatorioExport::class;
    }

    protected function getPdfView(): string
    {
        return 'relatorios.pdf';
    }

    protected function getReportTitle(): string
    {
        return 'Relatório ConsultaProd';
    }

    protected function getMatrixExportClass(): string
    {
        return MatrixReportExport::class;
    }

    /**
     * Campos derivados da tabela forma via s_prd.prd_pa
     */
    protected function getFormaFieldIds(): array
    {
        return ['grupo', 'descgrupo', 'subgrupo', 'descsubgrupo', 'forma', 'descforma'];
    }

    /**
     * Verifica se campos forma são necessários (seleção ou filtros)
     */
    protected function needsFormaJoins(array $selectedFields, array $filters): bool
    {
        $referenced = array_merge(
            $selectedFields,
            array_column($filters, 'field')
        );

        return collect($referenced)->contains(fn ($field) => in_array($field, $this->getFormaFieldIds(), true));
    }

    /**
     * JOINs forma: grupo (??0000), subgrupo (????00), forma (6 chars de prd_pa)
     */
    protected function addFormaJoins($query): void
    {
        $query->leftJoin('forma as fg', function ($join) {
            $join->on(DB::raw('SUBSTRING(sp.prd_pa, 1, 2)'), '=', 'fg.grupo')
                 ->where('fg.subgrupo', '=', DB::raw('CONCAT(SUBSTRING(sp.prd_pa, 1, 2), "00")'))
                 ->where('fg.forma', '=', DB::raw('CONCAT(SUBSTRING(sp.prd_pa, 1, 2), "0000")'));
        });
        $query->leftJoin('forma as fs', function ($join) {
            $join->on(DB::raw('SUBSTRING(sp.prd_pa, 1, 4)'), '=', 'fs.subgrupo')
                 ->where('fs.forma', '=', DB::raw('CONCAT(SUBSTRING(sp.prd_pa, 1, 4), "00")'));
        });
        $query->leftJoin('forma as ff', function ($join) {
            $join->on(DB::raw('SUBSTRING(sp.prd_pa, 1, 6)'), '=', 'ff.forma');
        });
    }

    /**
     * Verifica se join prestador é necessário para tipo_relatorio
     */
    protected function needsPrestadorJoin(array $selectedFields, array $filters): bool
    {
        $referenced = array_merge(
            $selectedFields,
            array_column($filters, 'field')
        );

        return in_array('tipo_relatorio', $referenced, true);
    }

    /**
     * JOIN prestador (alias pr) quando tipo_relatorio é usado sem prd_uid
     */
    protected function addPrestadorJoinIfNeeded($query, &$joins): void
    {
        if (!in_array('prestador', $joins, true)) {
            $query->leftJoin('prestador as pr', 'sp.prd_uid', '=', 'pr.re_cunid');
            $joins[] = 'prestador';
        }
    }

    /**
     * Hook para relatório matriz (HasMatrixReport)
     */
    protected function addReportJoins($query, $selectedFields, $filters, $tableAlias, &$joins): void
    {
        if ($this->needsPrestadorJoin($selectedFields, $filters)) {
            $this->addPrestadorJoinIfNeeded($query, $joins);
        }

        if ($this->needsFormaJoins($selectedFields, $filters) && !in_array('forma', $joins, true)) {
            $this->addFormaJoins($query);
            $joins[] = 'forma';
        }
    }

    /**
     * Build database query based on filters and selected fields
     */
    protected function buildQuery($selectedFields, $filters, $groupBy = true)
    {
        $query = DB::table('s_prd as sp');
        
        // Check if cismetro fields are needed
        $needsCismetro = collect($selectedFields)->contains(function($field) {
            return str_starts_with($field, 'cismetro_');
        }) || collect($filters)->contains(fn ($f) => str_starts_with($f['field'] ?? '', 'cismetro_'));
        
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
        }

        $this->addReportJoins($query, $selectedFields, $filters, 'sp', $joins);
        
        // Add cismetro join if needed
        if ($needsCismetro && !in_array('cismetro', $joins)) {
            $query->leftJoin('cismetro as cs', 'sp.prd_pa', '=', 'cs.codigo');
            $joins[] = 'cismetro';
        }
        
        // Build select fields with grouping and aggregation
        $selectFields = [];
        $groupByFields = [];
        
        foreach ($selectedFields as $field) {
            $fieldConfig = $this->getFieldConfig($field);
            if ($fieldConfig) {
                if ($fieldConfig['type'] === 'lookup') {
                    $alias = $this->getTableAlias($fieldConfig['lookup_table']);
                    
                    // Add both code and display fields
                    if ($field === 'prd_uid') {
                        $selectFields[] = "sp.prd_uid as cnes";
                        $selectFields[] = "pr.re_cnome as prestador_nome";
                        $groupByFields[] = "sp.prd_uid";
                        $groupByFields[] = "pr.re_cnome";
                    } elseif ($field === 'prd_pa') {
                        $selectFields[] = "sp.prd_pa as procedimento_codigo";
                        $selectFields[] = "pc.procedimento as procedimento_nome";
                        $groupByFields[] = "sp.prd_pa";
                        $groupByFields[] = "pc.procedimento";
                    } elseif ($field === 'cismetro_descricao') {
                        $selectFields[] = "sp.prd_pa as cismetro_codigo";
                        $selectFields[] = "cs.descricao as cismetro_descricao";
                        $groupByFields[] = "sp.prd_pa";
                        $groupByFields[] = "cs.descricao";
                    } else {
                        $selectFields[] = "sp.{$field}";
                        $selectFields[] = "{$alias}.{$fieldConfig['lookup_display']} as {$field}_display";
                        $groupByFields[] = "sp.{$field}";
                        $groupByFields[] = "{$alias}.{$fieldConfig['lookup_display']}";
                    }
                } elseif ($field === 'procedimento_descricao') {
                    // Campo especial: não adiciona ao SELECT pois é apenas para filtro
                    // A descrição já está disponível através do join com procedimento
                    continue;
                } elseif ($field === 'PRD_QT_P') {
                    // Sum quantities
                    $selectFields[] = DB::raw("SUM(CAST(sp.PRD_QT_P as UNSIGNED)) as total_quantidade");
                } elseif ($field === 'PRD_VL_P') {
                    // Sum values
                    $selectFields[] = DB::raw("SUM(CAST(sp.PRD_VL_P as DECIMAL(15,2))) as total_valor");
                } elseif ($field === 'cismetro_valor') {
                    // Cismetro unit value
                    $selectFields[] = "cs.valor as cismetro_valor";
                    $groupByFields[] = "cs.valor";
                } elseif ($field === 'cismetro_total') {
                    // Cismetro total value (quantity * unit value)
                    $selectFields[] = DB::raw("SUM(CAST(sp.PRD_QT_P as UNSIGNED) * COALESCE(cs.valor, 0)) as cismetro_total");
                } elseif ($field === 'prd_cmp') {
                    // Format competencia as YYYY-MM
                    $selectFields[] = DB::raw("CONCAT(SUBSTRING(sp.prd_cmp, 1, 4), '-', SUBSTRING(sp.prd_cmp, 5, 2)) as competencia");
                    $groupByFields[] = "sp.prd_cmp";
                } elseif ($field === 'prd_mvm') {
                    $selectFields[] = DB::raw("CONCAT(SUBSTRING(sp.prd_mvm, 1, 4), '-', SUBSTRING(sp.prd_mvm, 5, 2)) as movimento");
                    $groupByFields[] = "sp.prd_mvm";
                } elseif ($field === 'grupo') {
                    $selectFields[] = DB::raw('SUBSTRING(sp.prd_pa, 1, 2) as grupo');
                    $groupByFields[] = DB::raw('SUBSTRING(sp.prd_pa, 1, 2)');
                } elseif ($field === 'descgrupo') {
                    $selectFields[] = 'fg.descricao as descgrupo';
                    $groupByFields[] = 'fg.descricao';
                } elseif ($field === 'subgrupo') {
                    $selectFields[] = DB::raw('SUBSTRING(sp.prd_pa, 1, 4) as subgrupo');
                    $groupByFields[] = DB::raw('SUBSTRING(sp.prd_pa, 1, 4)');
                } elseif ($field === 'descsubgrupo') {
                    $selectFields[] = 'fs.descricao as descsubgrupo';
                    $groupByFields[] = 'fs.descricao';
                } elseif ($field === 'forma') {
                    $selectFields[] = DB::raw('SUBSTRING(sp.prd_pa, 1, 6) as forma');
                    $groupByFields[] = DB::raw('SUBSTRING(sp.prd_pa, 1, 6)');
                } elseif ($field === 'descforma') {
                    $selectFields[] = 'ff.descricao as descforma';
                    $groupByFields[] = 'ff.descricao';
                } elseif ($field === 'tipo_relatorio') {
                    $selectFields[] = 'pr.relatorio as tipo_relatorio';
                    $groupByFields[] = 'pr.relatorio';
                } else {
                    $selectFields[] = "sp.{$field}";
                    $groupByFields[] = "sp.{$field}";
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
                $query->leftJoin("prestador as {$alias}", "{$tableAlias}.prd_uid", '=', "{$alias}.re_cunid");
                break;
            case 'cbo':
                $query->leftJoin("cbo as {$alias}", "{$tableAlias}.prd_cbo", '=', "{$alias}.cbo");
                break;
            case 'procedimento':
                $query->leftJoin("procedimento as {$alias}", "{$tableAlias}.prd_pa", '=', "{$alias}.codigo");
                break;
            case 's_rub':
                $query->leftJoin("s_rub as {$alias}", "{$tableAlias}.PRD_RUB", '=', "{$alias}.RUB_ID");
                break;
            case 'cismetro':
                $query->leftJoin("cismetro as {$alias}", "{$tableAlias}.prd_pa", '=', "{$alias}.codigo");
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
        
        // Otimização especial para filtro de descrição de procedimento
        if ($field === 'procedimento_descricao') {
            // Primeiro, buscar os códigos dos procedimentos que atendem o critério
            $subquery = DB::table('procedimento')->select('codigo');
            
            switch ($operator) {
                case '=':
                    $subquery->where('procedimento', '=', $value);
                    break;
                case 'like':
                    $subquery->where('procedimento', 'like', '%' . $value . '%');
                    break;
                case 'starts_with':
                    $subquery->where('procedimento', 'like', $value . '%');
                    break;
                case 'ends_with':
                    $subquery->where('procedimento', 'like', '%' . $value);
                    break;
            }
            
            $procedimentoCodigos = $subquery->pluck('codigo')->toArray();
            
            // Aplicar filtro IN na tabela principal
            if (!empty($procedimentoCodigos)) {
                $query->whereIn('sp.prd_pa', $procedimentoCodigos);
            } else {
                // Se nenhum procedimento foi encontrado, garantir que nenhum resultado seja retornado
                $query->whereRaw('1 = 0');
            }
            
            return;
        }

        if ($field === 'grupo') {
            $this->applyFormaCodeFilter($query, 2, $operator, $value);
            return;
        }
        if ($field === 'subgrupo') {
            $this->applyFormaCodeFilter($query, 4, $operator, $value);
            return;
        }
        if ($field === 'forma') {
            $this->applyFormaCodeFilter($query, 6, $operator, $value);
            return;
        }
        if ($field === 'descforma') {
            $this->applyTextFilter($query, 'ff.descricao', $operator, $value);
            return;
        }
        if ($field === 'tipo_relatorio') {
            $this->applyTextFilter($query, 'pr.relatorio', $operator, $value);
            return;
        }
        
        // Handle cismetro fields in filters
        if ($field === 'cismetro_valor') {
            $field = 'cs.valor';
        } elseif ($field === 'cismetro_total') {
            // For cismetro_total, we need to filter on the calculated field
            // This is more complex and might need HAVING clause
            return; // Skip for now, can be implemented later if needed
        } elseif (str_starts_with($field, 'cismetro_')) {
            $field = 'cs.' . substr($field, 9); // Remove 'cismetro_' prefix
        } else {
            $tableAlias = $this->getTableAlias();
            $field = "{$tableAlias}.{$field}";
        }
        
        switch ($operator) {
            case '=':
                $query->where($field, '=', $value);
                break;
            case '>':
                $query->where($field, '>', $value);
                break;
            case '<':
                $query->where($field, '<', $value);
                break;
            case '>=':
                $query->where($field, '>=', $value);
                break;
            case '<=':
                $query->where($field, '<=', $value);
                break;
            case 'like':
                $query->where($field, 'like', '%' . $value . '%');
                break;
            case 'starts_with':
                $query->where($field, 'like', $value . '%');
                break;
            case 'ends_with':
                $query->where($field, 'like', '%' . $value);
                break;
            case 'between':
                if (is_array($value) && count($value) === 2) {
                    $query->whereBetween($field, $value);
                }
                break;
            case 'in':
                if (is_array($value)) {
                    $query->whereIn($field, $value);
                }
                break;
        }
    }

    /**
     * Filtro em código derivado de prd_pa (grupo/subgrupo/forma)
     */
    protected function applyFormaCodeFilter($query, int $length, string $operator, $value): void
    {
        $expr = DB::raw("SUBSTRING(sp.prd_pa, 1, {$length})");
        $this->applyTextFilter($query, $expr, $operator, $value);
    }

    /**
     * Filtro textual (=, contém, inicia com)
     */
    protected function applyTextFilter($query, $field, string $operator, $value): void
    {
        switch ($operator) {
            case '=':
                $query->where($field, '=', $value);
                break;
            case 'like':
                $query->where($field, 'like', '%' . $value . '%');
                break;
            case 'starts_with':
                $query->where($field, 'like', $value . '%');
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
                $fieldConfig = $this->getFieldConfig($field);
                
                // Handle special field mappings
                if ($field === 'prd_uid') {
                    $formatted['CNES'] = $row->cnes ?? '';
                    $formatted['Prestador'] = $row->prestador_nome ?? '';
                } elseif ($field === 'prd_pa') {
                    $formatted['Código Procedimento'] = $row->procedimento_codigo ?? '';
                    $formatted['Procedimento'] = $row->procedimento_nome ?? '';
                } elseif ($field === 'cismetro_descricao') {
                    $formatted['Código Cismetro'] = $row->cismetro_codigo ?? '';
                    $formatted['Cismetro - Descrição'] = $row->cismetro_descricao ?? '';
                } elseif ($field === 'procedimento_descricao') {
                    // Campo especial: não adiciona à formatação pois é apenas para filtro
                    continue;
                } elseif ($field === 'cismetro_valor') {
                    $formatted['Cismetro - Valor Unitário'] = $row->cismetro_valor ? 
                        'R$ ' . number_format((float)$row->cismetro_valor, 2, ',', '.') : 'R$ 0,00';
                } elseif ($field === 'cismetro_total') {
                    $formatted['Cismetro - Valor Total'] = $row->cismetro_total ? 
                        'R$ ' . number_format((float)$row->cismetro_total, 2, ',', '.') : 'R$ 0,00';
                } elseif ($field === 'PRD_QT_P') {
                    $formatted['Quantidade Total'] = number_format((float)($row->total_quantidade ?? 0), 0, ',', '.');
                } elseif ($field === 'PRD_VL_P') {
                    $formatted['Valor Total'] = 'R$ ' . number_format((float)($row->total_valor ?? 0), 2, ',', '.');
                } elseif ($field === 'prd_cmp') {
                    $formatted['Data Competência'] = $row->competencia ?? '';
                } elseif ($field === 'prd_mvm') {
                    $formatted['Data Movimento'] = $row->movimento ?? '';
                } elseif (in_array($field, $this->getFormaFieldIds(), true)) {
                    $formatted[$fieldConfig['label']] = $row->{$field} ?? '';
                } else {
                    $value = $row->{$field} ?? '';
                    
                    // Format based on field type
                    switch ($fieldConfig['type'] ?? 'text') {
                        case 'currency':
                            $formatted[$fieldConfig['label']] = 'R$ ' . number_format((float)$value, 2, ',', '.');
                            break;
                        case 'number':
                            $formatted[$fieldConfig['label']] = number_format((float)$value, 0, ',', '.');
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
        
        if (in_array('PRD_QT_P', $selectedFields)) {
            $totalQty = $data->sum(function($item) {
                return $item->total_quantidade ?? 0;
            });
            $totals['Quantidade Total'] = number_format($totalQty, 0, ',', '.');
        }
        
        if (in_array('PRD_VL_P', $selectedFields)) {
            $totalValue = $data->sum(function($item) {
                return $item->total_valor ?? 0;
            });
            $totals['Valor Total'] = 'R$ ' . number_format($totalValue, 2, ',', '.');
        }
        
        // NOVOS TOTAIS CISMETRO
        if (in_array('cismetro_total', $selectedFields)) {
            $totalCismetro = $data->sum(function($item) {
                return $item->cismetro_total ?? 0;
            });
            $totals['Cismetro - Valor Total'] = 'R$ ' . number_format($totalCismetro, 2, ',', '.');
        }
        
        return $totals;
    }


    /**
     * Get field configuration
     */
    protected function getFieldConfig($field)
    {
        $fields = [
            'prd_cmp' => [
                'label' => 'Data Competência',
                'type' => 'date',
                'table' => 's_prd',
                'operators' => ['=', '>=', '<=', 'between']
            ],
            'prd_mvm' => [
                'label' => 'Data Movimento',
                'type' => 'date',
                'table' => 's_prd',
                'operators' => ['=', '>=', '<=', 'between']
            ],
            'prd_uid' => [
                'label' => 'Prestador',
                'type' => 'lookup',
                'table' => 's_prd',
                'lookup_table' => 'prestador',
                'lookup_key' => 're_cunid',
                'lookup_display' => 're_cnome',
                'operators' => ['=', 'in']
            ],
            'tipo_relatorio' => [
                'label' => 'Tipo de Relatório',
                'type' => 'text',
                'table' => 'prestador',
                'field' => 'relatorio',
                'operators' => ['=', 'like', 'starts_with']
            ],
            'prd_cbo' => [
                'label' => 'CBO',
                'type' => 'lookup',
                'table' => 's_prd',
                'lookup_table' => 'cbo',
                'lookup_key' => 'cbo',
                'lookup_display' => 'ds_cbo',
                'operators' => ['=', 'in']
            ],
            'prd_pa' => [
                'label' => 'Procedimento',
                'type' => 'lookup',
                'table' => 's_prd',
                'lookup_table' => 'procedimento',
                'lookup_key' => 'codigo',
                'lookup_display' => 'procedimento',
                'operators' => ['=', 'in', 'like']
            ],
            'procedimento_descricao' => [
                'label' => 'Descrição do Procedimento',
                'type' => 'text',
                'table' => 'procedimento',
                'field' => 'procedimento',
                'operators' => ['=', 'like', 'starts_with', 'ends_with']
            ],
            'PRD_QT_P' => [
                'label' => 'Quantidade',
                'type' => 'number',
                'table' => 's_prd',
                'operators' => ['=', '>', '<', '>=', '<=', 'between']
            ],
            'PRD_VL_P' => [
                'label' => 'Valor',
                'type' => 'currency',
                'table' => 's_prd',
                'operators' => ['=', '>', '<', '>=', '<=', 'between']
            ],
            'PRD_RUB' => [
                'label' => 'Rubrica',
                'type' => 'lookup',
                'table' => 's_prd',
                'lookup_table' => 's_rub',
                'lookup_key' => 'RUB_ID',
                'lookup_display' => 'RUB_DC',
                'operators' => ['=', 'in']
            ],
            'PRD_CIDPRI' => [
                'label' => 'CID Principal',
                'type' => 'text',
                'table' => 's_prd',
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
            ],
            'grupo' => [
                'label' => 'Grupo',
                'type' => 'text',
                'table' => 'forma',
                'operators' => ['=', 'like', 'starts_with']
            ],
            'descgrupo' => [
                'label' => 'Descrição do Grupo',
                'type' => 'text',
                'table' => 'forma',
                'operators' => []
            ],
            'subgrupo' => [
                'label' => 'Subgrupo',
                'type' => 'text',
                'table' => 'forma',
                'operators' => ['=', 'like', 'starts_with']
            ],
            'descsubgrupo' => [
                'label' => 'Descrição do Subgrupo',
                'type' => 'text',
                'table' => 'forma',
                'operators' => []
            ],
            'forma' => [
                'label' => 'Forma de Organização',
                'type' => 'text',
                'table' => 'forma',
                'operators' => ['=', 'like', 'starts_with']
            ],
            'descforma' => [
                'label' => 'Descrição da Forma',
                'type' => 'text',
                'table' => 'forma',
                'operators' => ['=', 'like', 'starts_with']
            ]
        ];
        
        return $fields[$field] ?? null;
    }    /**

     * Debug endpoint to test query building
     */
    public function debug(Request $request)
    {
        try {
            $selectedFields = $request->get('fields', []);
            $filters = $request->get('filters', []);
            
            if (empty($selectedFields)) {
                return response()->json(['error' => 'No fields selected']);
            }
            
            $query = $this->buildQuery($selectedFields, $filters, true);
            
            return response()->json([
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
                'fields' => $selectedFields,
                'filters' => $filters
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Test Excel export with simple data
     */
    public function testExcel()
    {
        try {
            $testData = collect([
                ['Campo 1' => 'Valor 1', 'Campo 2' => 'Valor 2'],
                ['Campo 1' => 'Valor 3', 'Campo 2' => 'Valor 4']
            ]);
            
            $export = new RelatorioExport($testData, ['campo1', 'campo2'], []);
            return Excel::download($export, 'teste.xlsx');
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro no teste Excel: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Implement trait methods for matrix
     */
    protected function getPrestadorField(): string
    {
        return 'prd_uid';
    }

    protected function getMatrixSplitCandidates(): array
    {
        return [$this->getPrestadorField(), 'tipo_relatorio'];
    }

    protected function getCboField(): string
    {
        return 'prd_cbo';
    }

    protected function getRubField(): ?string
    {
        return 'PRD_RUB';
    }

    protected function getProcedimentoFieldForCismetro(): string
    {
        return 'prd_pa';
    }

    protected function getMatrixLookupFields($field, $tableAlias): array
    {
        $selectFields = [];
        $groupByFields = [];
        
        if ($field === 'prd_uid') {
            $selectFields[] = "{$tableAlias}.prd_uid as prestador_codigo";
            $selectFields[] = "pr.re_cnome as prestador_nome";
            $groupByFields[] = "{$tableAlias}.prd_uid";
            $groupByFields[] = "pr.re_cnome";
        } elseif ($field === 'prd_pa') {
            $selectFields[] = "{$tableAlias}.prd_pa as procedimento_codigo";
            $selectFields[] = "pc.procedimento as procedimento_nome";
            $groupByFields[] = "{$tableAlias}.prd_pa";
            $groupByFields[] = "pc.procedimento";
        } elseif ($field === 'prd_cbo') {
            $selectFields[] = "{$tableAlias}.prd_cbo as cbo_codigo";
            $selectFields[] = "cb.ds_cbo as cbo_nome";
            $groupByFields[] = "{$tableAlias}.prd_cbo";
            $groupByFields[] = "cb.ds_cbo";
        } elseif ($field === 'PRD_RUB') {
            $selectFields[] = "{$tableAlias}.PRD_RUB as rubrica_codigo";
            $selectFields[] = "sr.RUB_DC as rubrica_nome";
            $groupByFields[] = "{$tableAlias}.PRD_RUB";
            $groupByFields[] = "sr.RUB_DC";
        } elseif ($field === 'cismetro_descricao') {
            $selectFields[] = "{$tableAlias}.prd_pa as cismetro_codigo";
            $selectFields[] = "cs.descricao as cismetro_descricao";
            $groupByFields[] = "{$tableAlias}.prd_pa";
            $groupByFields[] = "cs.descricao";
        } elseif ($field === 'grupo') {
            $selectFields[] = DB::raw("SUBSTRING({$tableAlias}.prd_pa, 1, 2) as grupo");
            $groupByFields[] = DB::raw("SUBSTRING({$tableAlias}.prd_pa, 1, 2)");
        } elseif ($field === 'descgrupo') {
            $selectFields[] = 'fg.descricao as descgrupo';
            $groupByFields[] = 'fg.descricao';
        } elseif ($field === 'subgrupo') {
            $selectFields[] = DB::raw("SUBSTRING({$tableAlias}.prd_pa, 1, 4) as subgrupo");
            $groupByFields[] = DB::raw("SUBSTRING({$tableAlias}.prd_pa, 1, 4)");
        } elseif ($field === 'descsubgrupo') {
            $selectFields[] = 'fs.descricao as descsubgrupo';
            $groupByFields[] = 'fs.descricao';
        } elseif ($field === 'forma') {
            $selectFields[] = DB::raw("SUBSTRING({$tableAlias}.prd_pa, 1, 6) as forma");
            $groupByFields[] = DB::raw("SUBSTRING({$tableAlias}.prd_pa, 1, 6)");
        } elseif ($field === 'descforma') {
            $selectFields[] = 'ff.descricao as descforma';
            $groupByFields[] = 'ff.descricao';
        } elseif ($field === 'tipo_relatorio') {
            $selectFields[] = 'pr.relatorio as tipo_relatorio';
            $groupByFields[] = 'pr.relatorio';
        }
        
        return ['select' => $selectFields, 'groupBy' => $groupByFields];
    }

    protected function getMatrixNumericFields($field, $tableAlias): array
    {
        $fields = [];
        
        if ($field === 'PRD_QT_P') {
            $fields[] = DB::raw("SUM(CAST({$tableAlias}.PRD_QT_P as UNSIGNED)) as total_quantidade");
        } elseif ($field === 'PRD_VL_P') {
            $fields[] = DB::raw("SUM(CAST({$tableAlias}.PRD_VL_P as DECIMAL(15,2))) as total_valor");
        } elseif ($field === 'cismetro_total') {
            $fields[] = DB::raw("SUM(CAST({$tableAlias}.PRD_QT_P as UNSIGNED) * COALESCE(cs.valor, 0)) as cismetro_total");
        }
        
        return $fields;
    }

    protected function getGroupKeyPart($item, $field)
    {
        if ($field === 'prd_uid') {
            return ($item->prestador_codigo ?? '') . '|' . ($item->prestador_nome ?? '');
        } elseif ($field === 'prd_pa') {
            return ($item->procedimento_codigo ?? '') . '|' . ($item->procedimento_nome ?? '');
        } elseif ($field === 'prd_cbo') {
            return ($item->cbo_codigo ?? '') . '|' . ($item->cbo_nome ?? '');
        } elseif ($field === 'PRD_RUB') {
            return ($item->rubrica_codigo ?? '') . '|' . ($item->rubrica_nome ?? '');
        } elseif ($field === 'cismetro_descricao') {
            return ($item->cismetro_codigo ?? '') . '|' . ($item->cismetro_descricao ?? '');
        } elseif (in_array($field, $this->getFormaFieldIds(), true)) {
            return $item->{$field} ?? '';
        } elseif ($field === 'tipo_relatorio') {
            return $item->tipo_relatorio ?? '';
        }
        
        return $item->{$field} ?? '';
    }

    protected function getNumericValue($item, $field)
    {
        switch ($field) {
            case 'PRD_QT_P':
                return (float)($item->total_quantidade ?? 0);
            case 'PRD_VL_P':
                return (float)($item->total_valor ?? 0);
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
        return 'PRD_QT_P';
    }
}