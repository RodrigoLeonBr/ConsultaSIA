<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\RelatorioExport;
use App\Exports\MatrixReportExport;
use App\Http\Controllers\Concerns\HasMatrixReport;

class RelatorioBpiController extends BaseRelatorioController
{
    use HasMatrixReport;
    /**
     * Display the report builder interface
     */
    public function index()
    {
        return view('relatorios.bpi.index');
    }

    /**
     * Get available fields for report building
     */
    public function getFields()
    {
        return response()->json([
            'fields' => [
                'BPI_CMP' => [
                    'label' => 'Data Competência',
                    'type' => 'date',
                    'table' => 's_bpi',
                    'operators' => ['=', '>=', '<=', 'between']
                ],
                'BPI_MVM' => [
                    'label' => 'Data Movimento',
                    'type' => 'date',
                    'table' => 's_bpi',
                    'operators' => ['=', '>=', '<=', 'between']
                ],
                'BPI_UID' => [
                    'label' => 'Prestador',
                    'type' => 'lookup',
                    'table' => 's_bpi',
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
                'BPI_CBO' => [
                    'label' => 'CBO',
                    'type' => 'lookup',
                    'table' => 's_bpi',
                    'lookup_table' => 'cbo',
                    'lookup_key' => 'cbo',
                    'lookup_display' => 'ds_cbo',
                    'operators' => ['=', 'in']
                ],
                'BPI_PA' => [
                    'label' => 'Procedimento',
                    'type' => 'lookup',
                    'table' => 's_bpi',
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
                'BPI_QT_P' => [
                    'label' => 'Quantidade',
                    'type' => 'number',
                    'table' => 's_bpi',
                    'operators' => ['=', '>', '<', '>=', '<=', 'between']
                ],
                'BPI_CID' => [
                    'label' => 'CID',
                    'type' => 'text',
                    'table' => 's_bpi',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                'BPI_CNSMED' => [
                    'label' => 'CNS Profissional',
                    'type' => 'text',
                    'table' => 's_bpi',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                'BPI_CNSPAC' => [
                    'label' => 'CNS Paciente',
                    'type' => 'text',
                    'table' => 's_bpi',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                'BPI_NMPAC' => [
                    'label' => 'Nome do Paciente',
                    'type' => 'text',
                    'table' => 's_bpi',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                'BPI_DTNASC' => [
                    'label' => 'Data de Nascimento',
                    'type' => 'text',
                    'table' => 's_bpi',
                    'operators' => ['=', 'like', 'starts_with']
                ],
                'BPI_SEXO' => [
                    'label' => 'Sexo',
                    'type' => 'choice',
                    'table' => 's_bpi',
                    'options' => [
                        'M' => 'Masculino',
                        'F' => 'Feminino',
                    ],
                    'operators' => ['=']
                ],
                'BPI_DTATEN' => [
                    'label' => 'Data de Atendimento',
                    'type' => 'text',
                    'table' => 's_bpi',
                    'operators' => ['=', '>=', '<=', 'between']
                ],
                'BPI_IDADE' => [
                    'label' => 'Idade',
                    'type' => 'number',
                    'table' => 's_bpi',
                    'operators' => ['=', '>=', '<=']
                ],
                'faixa_etaria_1' => [
                    'label' => 'Faixa Etária (detalhada)',
                    'type' => 'calculated',
                    'table' => 's_bpi',
                    'operators' => []
                ],
                'faixa_etaria_2' => [
                    'label' => 'Faixa Etária (resumida)',
                    'type' => 'calculated',
                    'table' => 's_bpi',
                    'operators' => []
                ],
                'BPI_CATEN' => [
                    'label' => 'Caráter de Atendimento',
                    'type' => 'text',
                    'table' => 's_bpi',
                    'operators' => ['=', 'in']
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
        return 's_bpi';
    }

    protected function getTableAlias(): string
    {
        return 'sb';
    }

    protected function getCompetenciaField(): string
    {
        return 'BPI_CMP';
    }

    protected function getMovimentoField(): ?string
    {
        return 'BPI_MVM';
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
        return 'Relatório Produção Individualizada';
    }

    protected function getMatrixExportClass(): string
    {
        return MatrixReportExport::class;
    }

    protected function idadeIgnoradaCondition(string $alias): string
    {
        return "TRIM({$alias}.BPI_FLIDA) <> ''";
    }

    protected function faixaEtaria1Expression(string $alias): string
    {
        $ign = $this->idadeIgnoradaCondition($alias);
        $idade = "CAST({$alias}.BPI_IDADE AS SIGNED)";

        return "CASE
            WHEN {$ign} THEN 'Ignorado'
            WHEN {$idade} = 0 THEN 'Menor que 1 ano'
            WHEN {$idade} BETWEEN 1 AND 4 THEN '1 a 4 anos'
            WHEN {$idade} BETWEEN 5 AND 9 THEN '5 a 9 anos'
            WHEN {$idade} BETWEEN 10 AND 14 THEN '10 a 14 anos'
            WHEN {$idade} BETWEEN 15 AND 19 THEN '15 a 19 anos'
            WHEN {$idade} BETWEEN 20 AND 24 THEN '20 a 24 anos'
            WHEN {$idade} BETWEEN 25 AND 29 THEN '25 a 29 anos'
            WHEN {$idade} BETWEEN 30 AND 34 THEN '30 a 34 anos'
            WHEN {$idade} BETWEEN 35 AND 39 THEN '35 a 39 anos'
            WHEN {$idade} BETWEEN 40 AND 44 THEN '40 a 44 anos'
            WHEN {$idade} BETWEEN 45 AND 49 THEN '45 a 49 anos'
            WHEN {$idade} BETWEEN 50 AND 54 THEN '50 a 54 anos'
            WHEN {$idade} BETWEEN 55 AND 59 THEN '55 a 59 anos'
            WHEN {$idade} BETWEEN 60 AND 64 THEN '60 a 64 anos'
            WHEN {$idade} BETWEEN 65 AND 69 THEN '65 a 69 anos'
            WHEN {$idade} BETWEEN 70 AND 74 THEN '70 a 74 anos'
            WHEN {$idade} BETWEEN 75 AND 79 THEN '75 a 79 anos'
            WHEN {$idade} >= 80 THEN '80 anos ou mais'
            ELSE 'Ignorado'
        END";
    }

    protected function faixaEtaria2Expression(string $alias): string
    {
        $ign = $this->idadeIgnoradaCondition($alias);
        $idade = "CAST({$alias}.BPI_IDADE AS SIGNED)";

        return "CASE
            WHEN {$ign} THEN 'Ignorado'
            WHEN {$idade} <= 9 THEN 'Criança'
            WHEN {$idade} BETWEEN 10 AND 17 THEN 'Infantil'
            WHEN {$idade} BETWEEN 18 AND 59 THEN 'Adulto'
            WHEN {$idade} >= 60 THEN 'Idoso'
            ELSE 'Ignorado'
        END";
    }

    protected function faixaEtaria1OrderExpression(string $alias): string
    {
        $expr = $this->faixaEtaria1Expression($alias);

        return "FIELD(({$expr}), 'Menor que 1 ano', '1 a 4 anos', '5 a 9 anos', '10 a 14 anos', '15 a 19 anos', '20 a 24 anos', '25 a 29 anos', '30 a 34 anos', '35 a 39 anos', '40 a 44 anos', '45 a 49 anos', '50 a 54 anos', '55 a 59 anos', '60 a 64 anos', '65 a 69 anos', '70 a 74 anos', '75 a 79 anos', '80 anos ou mais', 'Ignorado')";
    }

    protected function faixaEtaria2OrderExpression(string $alias): string
    {
        $expr = $this->faixaEtaria2Expression($alias);

        return "FIELD(({$expr}), 'Criança', 'Infantil', 'Adulto', 'Idoso', 'Ignorado')";
    }

    protected function getFaixaEtariaFieldIds(): array
    {
        return ['faixa_etaria_1', 'faixa_etaria_2'];
    }

    /**
     * Campos derivados da tabela forma via s_bpi.BPI_PA
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
     * JOINs forma: grupo (??0000), subgrupo (????00), forma (6 chars de BPI_PA)
     */
    protected function addFormaJoins($query): void
    {
        $query->leftJoin('forma as fg', function ($join) {
            $join->on(DB::raw('SUBSTRING(sb.BPI_PA, 1, 2)'), '=', 'fg.grupo')
                 ->where('fg.subgrupo', '=', DB::raw('CONCAT(SUBSTRING(sb.BPI_PA, 1, 2), "00")'))
                 ->where('fg.forma', '=', DB::raw('CONCAT(SUBSTRING(sb.BPI_PA, 1, 2), "0000")'));
        });
        $query->leftJoin('forma as fs', function ($join) {
            $join->on(DB::raw('SUBSTRING(sb.BPI_PA, 1, 4)'), '=', 'fs.subgrupo')
                 ->where('fs.forma', '=', DB::raw('CONCAT(SUBSTRING(sb.BPI_PA, 1, 4), "00")'));
        });
        $query->leftJoin('forma as ff', function ($join) {
            $join->on(DB::raw('SUBSTRING(sb.BPI_PA, 1, 6)'), '=', 'ff.forma');
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
     * JOIN prestador (alias pr) quando tipo_relatorio é usado sem BPI_UID
     */
    protected function addPrestadorJoinIfNeeded($query, &$joins): void
    {
        if (!in_array('prestador', $joins, true)) {
            $query->leftJoin('prestador as pr', 'sb.BPI_UID', '=', 'pr.re_cunid');
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
        $query = DB::table('s_bpi as sb');
        
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

        $this->addReportJoins($query, $selectedFields, $filters, 'sb', $joins);
        
        // Add cismetro join if needed
        if ($needsCismetro && !in_array('cismetro', $joins)) {
            $query->leftJoin('cismetro as cs', 'sb.BPI_PA', '=', 'cs.codigo');
            $joins[] = 'cismetro';
        }
        
        // Build select fields with grouping and aggregation
        $selectFields = [];
        $groupByFields = [];
        
        foreach ($selectedFields as $field) {
            $fieldConfig = $this->getFieldConfig($field);
            if ($fieldConfig) {
                if ($fieldConfig['type'] === 'lookup') {
                    $alias = $this->getTableAliasForJoin($fieldConfig['lookup_table']);
                    
                    // Add both code and display fields
                    if ($field === 'BPI_UID') {
                        $selectFields[] = "sb.BPI_UID as cnes";
                        $selectFields[] = "pr.re_cnome as prestador_nome";
                        $groupByFields[] = "sb.BPI_UID";
                        $groupByFields[] = "pr.re_cnome";
                    } elseif ($field === 'BPI_PA') {
                        $selectFields[] = "sb.BPI_PA as procedimento_codigo";
                        $selectFields[] = "pc.procedimento as procedimento_nome";
                        $groupByFields[] = "sb.BPI_PA";
                        $groupByFields[] = "pc.procedimento";
                    } elseif ($field === 'cismetro_descricao') {
                        $selectFields[] = "sb.BPI_PA as cismetro_codigo";
                        $selectFields[] = "cs.descricao as cismetro_descricao";
                        $groupByFields[] = "sb.BPI_PA";
                        $groupByFields[] = "cs.descricao";
                    } else {
                        $selectFields[] = "sb.{$field}";
                        $selectFields[] = "{$alias}.{$fieldConfig['lookup_display']} as {$field}_display";
                        $groupByFields[] = "sb.{$field}";
                        $groupByFields[] = "{$alias}.{$fieldConfig['lookup_display']}";
                    }
                } elseif ($field === 'procedimento_descricao') {
                    // Campo especial: não adiciona ao SELECT pois é apenas para filtro
                    // A descrição já está disponível através do join com procedimento
                    continue;
                } elseif ($field === 'BPI_QT_P') {
                    // Sum quantities
                    $selectFields[] = DB::raw("SUM(CAST(sb.BPI_QT_P as UNSIGNED)) as total_quantidade");
                } elseif ($field === 'cismetro_valor') {
                    // Cismetro unit value
                    $selectFields[] = "cs.valor as cismetro_valor";
                    $groupByFields[] = "cs.valor";
                } elseif ($field === 'cismetro_total') {
                    // Cismetro total value (quantity * unit value)
                    $selectFields[] = DB::raw("SUM(CAST(sb.BPI_QT_P as UNSIGNED) * COALESCE(cs.valor, 0)) as cismetro_total");
                } elseif ($field === 'BPI_CMP') {
                    // Format competencia as YYYY-MM
                    $selectFields[] = DB::raw("CONCAT(SUBSTRING(sb.BPI_CMP, 1, 4), '-', SUBSTRING(sb.BPI_CMP, 5, 2)) as competencia");
                    $groupByFields[] = "sb.BPI_CMP";
                } elseif ($field === 'BPI_MVM') {
                    $selectFields[] = DB::raw("CONCAT(SUBSTRING(sb.BPI_MVM, 1, 4), '-', SUBSTRING(sb.BPI_MVM, 5, 2)) as movimento");
                    $groupByFields[] = "sb.BPI_MVM";
                } elseif ($field === 'grupo') {
                    $selectFields[] = DB::raw('SUBSTRING(sb.BPI_PA, 1, 2) as grupo');
                    $groupByFields[] = DB::raw('SUBSTRING(sb.BPI_PA, 1, 2)');
                } elseif ($field === 'descgrupo') {
                    $selectFields[] = 'fg.descricao as descgrupo';
                    $groupByFields[] = 'fg.descricao';
                } elseif ($field === 'subgrupo') {
                    $selectFields[] = DB::raw('SUBSTRING(sb.BPI_PA, 1, 4) as subgrupo');
                    $groupByFields[] = DB::raw('SUBSTRING(sb.BPI_PA, 1, 4)');
                } elseif ($field === 'descsubgrupo') {
                    $selectFields[] = 'fs.descricao as descsubgrupo';
                    $groupByFields[] = 'fs.descricao';
                } elseif ($field === 'forma') {
                    $selectFields[] = DB::raw('SUBSTRING(sb.BPI_PA, 1, 6) as forma');
                    $groupByFields[] = DB::raw('SUBSTRING(sb.BPI_PA, 1, 6)');
                } elseif ($field === 'descforma') {
                    $selectFields[] = 'ff.descricao as descforma';
                    $groupByFields[] = 'ff.descricao';
                } elseif ($field === 'tipo_relatorio') {
                    $selectFields[] = 'pr.relatorio as tipo_relatorio';
                    $groupByFields[] = 'pr.relatorio';
                } elseif ($field === 'faixa_etaria_1') {
                    $expr = $this->faixaEtaria1Expression('sb');
                    $selectFields[] = DB::raw("({$expr}) as faixa_etaria_1");
                    $groupByFields[] = DB::raw("({$expr})");
                } elseif ($field === 'faixa_etaria_2') {
                    $expr = $this->faixaEtaria2Expression('sb');
                    $selectFields[] = DB::raw("({$expr}) as faixa_etaria_2");
                    $groupByFields[] = DB::raw("({$expr})");
                } elseif ($field === 'BPI_IDADE') {
                    $selectFields[] = 'sb.BPI_IDADE';
                    $groupByFields[] = 'sb.BPI_IDADE';
                } else {
                    $selectFields[] = "sb.{$field}";
                    $groupByFields[] = "sb.{$field}";
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
        
        // Order by first dimensional field
        $firstOrderField = collect($selectedFields)->first(
            fn ($field) => !in_array($field, ['BPI_QT_P', 'cismetro_total', 'procedimento_descricao'], true)
        );

        if ($firstOrderField === 'faixa_etaria_1') {
            $query->orderBy(DB::raw($this->faixaEtaria1OrderExpression('sb')));
        } elseif ($firstOrderField === 'faixa_etaria_2') {
            $query->orderBy(DB::raw($this->faixaEtaria2OrderExpression('sb')));
        } elseif (!empty($groupByFields)) {
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
                $query->leftJoin("prestador as {$alias}", "{$tableAlias}.BPI_UID", '=', "{$alias}.re_cunid");
                break;
            case 'cbo':
                $query->leftJoin("cbo as {$alias}", "{$tableAlias}.BPI_CBO", '=', "{$alias}.cbo");
                break;
            case 'procedimento':
                $query->leftJoin("procedimento as {$alias}", "{$tableAlias}.BPI_PA", '=', "{$alias}.codigo");
                break;
            case 'cismetro':
                $query->leftJoin("cismetro as {$alias}", "{$tableAlias}.BPI_PA", '=', "{$alias}.codigo");
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
                $query->whereIn('sb.BPI_PA', $procedimentoCodigos);
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
     * Filtro em código derivado de BPI_PA (grupo/subgrupo/forma)
     */
    protected function applyFormaCodeFilter($query, int $length, string $operator, $value): void
    {
        $expr = DB::raw("SUBSTRING(sb.BPI_PA, 1, {$length})");
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
                if ($field === 'BPI_UID') {
                    $formatted['CNES'] = $row->cnes ?? '';
                    $formatted['Prestador'] = $row->prestador_nome ?? '';
                } elseif ($field === 'BPI_PA') {
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
                } elseif ($field === 'BPI_QT_P') {
                    $formatted['Quantidade Total'] = number_format((float)($row->total_quantidade ?? 0), 0, ',', '.');
                } elseif ($field === 'BPI_CMP') {
                    $formatted['Data Competência'] = $row->competencia ?? '';
                } elseif ($field === 'BPI_MVM') {
                    $formatted['Data Movimento'] = $row->movimento ?? '';
                } elseif (in_array($field, $this->getFaixaEtariaFieldIds(), true)) {
                    $formatted[$fieldConfig['label']] = $row->{$field} ?? '';
                } elseif ($field === 'BPI_IDADE') {
                    $formatted['Idade'] = number_format((float)($row->BPI_IDADE ?? 0), 0, ',', '.');
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
                        case 'choice':
                            $options = $fieldConfig['options'] ?? [];
                            $formatted[$fieldConfig['label']] = $options[trim((string) $value)] ?? $value;
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
        
        if (in_array('BPI_QT_P', $selectedFields)) {
            $totalQty = $data->sum(function($item) {
                return $item->total_quantidade ?? 0;
            });
            $totals['Quantidade Total'] = number_format($totalQty, 0, ',', '.');
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
            'BPI_CMP' => [
                'label' => 'Data Competência',
                'type' => 'date',
                'table' => 's_bpi',
                'operators' => ['=', '>=', '<=', 'between']
            ],
            'BPI_MVM' => [
                'label' => 'Data Movimento',
                'type' => 'date',
                'table' => 's_bpi',
                'operators' => ['=', '>=', '<=', 'between']
            ],
            'BPI_UID' => [
                'label' => 'Prestador',
                'type' => 'lookup',
                'table' => 's_bpi',
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
            'BPI_CBO' => [
                'label' => 'CBO',
                'type' => 'lookup',
                'table' => 's_bpi',
                'lookup_table' => 'cbo',
                'lookup_key' => 'cbo',
                'lookup_display' => 'ds_cbo',
                'operators' => ['=', 'in']
            ],
            'BPI_PA' => [
                'label' => 'Procedimento',
                'type' => 'lookup',
                'table' => 's_bpi',
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
            'BPI_QT_P' => [
                'label' => 'Quantidade',
                'type' => 'number',
                'table' => 's_bpi',
                'operators' => ['=', '>', '<', '>=', '<=', 'between']
            ],
            'BPI_CID' => [
                'label' => 'CID',
                'type' => 'text',
                'table' => 's_bpi',
                'operators' => ['=', 'like', 'starts_with']
            ],
            'BPI_CNSMED' => [
                'label' => 'CNS Profissional',
                'type' => 'text',
                'table' => 's_bpi',
                'operators' => ['=', 'like', 'starts_with']
            ],
            'BPI_CNSPAC' => [
                'label' => 'CNS Paciente',
                'type' => 'text',
                'table' => 's_bpi',
                'operators' => ['=', 'like', 'starts_with']
            ],
            'BPI_NMPAC' => [
                'label' => 'Nome do Paciente',
                'type' => 'text',
                'table' => 's_bpi',
                'operators' => ['=', 'like', 'starts_with']
            ],
            'BPI_DTNASC' => [
                'label' => 'Data de Nascimento',
                'type' => 'text',
                'table' => 's_bpi',
                'operators' => ['=', 'like', 'starts_with']
            ],
            'BPI_SEXO' => [
                'label' => 'Sexo',
                'type' => 'choice',
                'table' => 's_bpi',
                'options' => [
                    'M' => 'Masculino',
                    'F' => 'Feminino',
                ],
                'operators' => ['=']
            ],
            'BPI_DTATEN' => [
                'label' => 'Data de Atendimento',
                'type' => 'text',
                'table' => 's_bpi',
                'operators' => ['=', '>=', '<=', 'between']
            ],
            'BPI_IDADE' => [
                'label' => 'Idade',
                'type' => 'number',
                'table' => 's_bpi',
                'operators' => ['=', '>=', '<=']
            ],
            'faixa_etaria_1' => [
                'label' => 'Faixa Etária (detalhada)',
                'type' => 'calculated',
                'table' => 's_bpi',
                'operators' => []
            ],
            'faixa_etaria_2' => [
                'label' => 'Faixa Etária (resumida)',
                'type' => 'calculated',
                'table' => 's_bpi',
                'operators' => []
            ],
            'BPI_CATEN' => [
                'label' => 'Caráter de Atendimento',
                'type' => 'text',
                'table' => 's_bpi',
                'operators' => ['=', 'in']
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
        return 'BPI_UID';
    }

    protected function getMatrixSplitCandidates(): array
    {
        return [$this->getPrestadorField(), 'tipo_relatorio'];
    }

    protected function getCboField(): string
    {
        return 'BPI_CBO';
    }

    protected function getRubField(): ?string
    {
        return null;
    }

    protected function getProcedimentoFieldForCismetro(): string
    {
        return 'BPI_PA';
    }

    protected function getMatrixLookupFields($field, $tableAlias): array
    {
        $selectFields = [];
        $groupByFields = [];
        
        if ($field === 'BPI_UID') {
            $selectFields[] = "{$tableAlias}.BPI_UID as prestador_codigo";
            $selectFields[] = "pr.re_cnome as prestador_nome";
            $groupByFields[] = "{$tableAlias}.BPI_UID";
            $groupByFields[] = "pr.re_cnome";
        } elseif ($field === 'BPI_PA') {
            $selectFields[] = "{$tableAlias}.BPI_PA as procedimento_codigo";
            $selectFields[] = "pc.procedimento as procedimento_nome";
            $groupByFields[] = "{$tableAlias}.BPI_PA";
            $groupByFields[] = "pc.procedimento";
        } elseif ($field === 'BPI_CBO') {
            $selectFields[] = "{$tableAlias}.BPI_CBO as cbo_codigo";
            $selectFields[] = "cb.ds_cbo as cbo_nome";
            $groupByFields[] = "{$tableAlias}.BPI_CBO";
            $groupByFields[] = "cb.ds_cbo";
        } elseif ($field === 'cismetro_descricao') {
            $selectFields[] = "{$tableAlias}.BPI_PA as cismetro_codigo";
            $selectFields[] = "cs.descricao as cismetro_descricao";
            $groupByFields[] = "{$tableAlias}.BPI_PA";
            $groupByFields[] = "cs.descricao";
        } elseif ($field === 'grupo') {
            $selectFields[] = DB::raw("SUBSTRING({$tableAlias}.BPI_PA, 1, 2) as grupo");
            $groupByFields[] = DB::raw("SUBSTRING({$tableAlias}.BPI_PA, 1, 2)");
        } elseif ($field === 'descgrupo') {
            $selectFields[] = 'fg.descricao as descgrupo';
            $groupByFields[] = 'fg.descricao';
        } elseif ($field === 'subgrupo') {
            $selectFields[] = DB::raw("SUBSTRING({$tableAlias}.BPI_PA, 1, 4) as subgrupo");
            $groupByFields[] = DB::raw("SUBSTRING({$tableAlias}.BPI_PA, 1, 4)");
        } elseif ($field === 'descsubgrupo') {
            $selectFields[] = 'fs.descricao as descsubgrupo';
            $groupByFields[] = 'fs.descricao';
        } elseif ($field === 'forma') {
            $selectFields[] = DB::raw("SUBSTRING({$tableAlias}.BPI_PA, 1, 6) as forma");
            $groupByFields[] = DB::raw("SUBSTRING({$tableAlias}.BPI_PA, 1, 6)");
        } elseif ($field === 'descforma') {
            $selectFields[] = 'ff.descricao as descforma';
            $groupByFields[] = 'ff.descricao';
        } elseif ($field === 'tipo_relatorio') {
            $selectFields[] = 'pr.relatorio as tipo_relatorio';
            $groupByFields[] = 'pr.relatorio';
        } elseif ($field === 'faixa_etaria_1') {
            $expr = $this->faixaEtaria1Expression($tableAlias);
            $selectFields[] = DB::raw("({$expr}) as faixa_etaria_1");
            $groupByFields[] = DB::raw("({$expr})");
        } elseif ($field === 'faixa_etaria_2') {
            $expr = $this->faixaEtaria2Expression($tableAlias);
            $selectFields[] = DB::raw("({$expr}) as faixa_etaria_2");
            $groupByFields[] = DB::raw("({$expr})");
        } elseif ($field === 'BPI_IDADE') {
            $selectFields[] = "{$tableAlias}.BPI_IDADE";
            $groupByFields[] = "{$tableAlias}.BPI_IDADE";
        }
        
        return ['select' => $selectFields, 'groupBy' => $groupByFields];
    }

    protected function getMatrixNumericFields($field, $tableAlias): array
    {
        $fields = [];
        
        if ($field === 'BPI_QT_P') {
            $fields[] = DB::raw("SUM(CAST({$tableAlias}.BPI_QT_P as UNSIGNED)) as total_quantidade");
        } elseif ($field === 'cismetro_total') {
            $fields[] = DB::raw("SUM(CAST({$tableAlias}.BPI_QT_P as UNSIGNED) * COALESCE(cs.valor, 0)) as cismetro_total");
        }
        
        return $fields;
    }

    protected function getGroupKeyPart($item, $field)
    {
        if ($field === 'BPI_UID') {
            return ($item->prestador_codigo ?? '') . '|' . ($item->prestador_nome ?? '');
        } elseif ($field === 'BPI_PA') {
            return ($item->procedimento_codigo ?? '') . '|' . ($item->procedimento_nome ?? '');
        } elseif ($field === 'BPI_CBO') {
            return ($item->cbo_codigo ?? '') . '|' . ($item->cbo_nome ?? '');
        } elseif ($field === 'cismetro_descricao') {
            return ($item->cismetro_codigo ?? '') . '|' . ($item->cismetro_descricao ?? '');
        } elseif (in_array($field, $this->getFaixaEtariaFieldIds(), true)) {
            return $item->{$field} ?? '';
        } elseif (in_array($field, $this->getFormaFieldIds(), true)) {
            return $item->{$field} ?? '';
        } elseif ($field === 'tipo_relatorio') {
            return $item->tipo_relatorio ?? '';
        } elseif ($field === 'BPI_IDADE') {
            return (string)($item->BPI_IDADE ?? '');
        }
        
        return $item->{$field} ?? '';
    }

    protected function getNumericValue($item, $field)
    {
        switch ($field) {
            case 'BPI_QT_P':
                return (float)($item->total_quantidade ?? 0);
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
        return 'BPI_QT_P';
    }
}