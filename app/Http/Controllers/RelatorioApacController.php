<?php

namespace App\Http\Controllers;

use App\Exports\MatrixReportExport;
use App\Exports\RelatorioApacExport;
use App\Http\Controllers\Concerns\HasMatrixReport;
use App\Http\Controllers\Concerns\HasSusPaulistaReport;
use App\Http\Controllers\Concerns\JoinsCismetroByPrestadorTipo;
use Illuminate\Support\Facades\DB;

class RelatorioApacController extends BaseRelatorioController
{
    use HasMatrixReport;
    use HasSusPaulistaReport;
    use JoinsCismetroByPrestadorTipo;

    protected function usesCollatedCismetroJoins(): bool
    {
        return true;
    }

    public function index()
    {
        return view('relatorios.apac.index');
    }

    public function getFields()
    {
        return response()->json([
            'fields' => $this->getAllFieldConfigs(),
        ]);
    }

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
        return 'PAP_CMP';
    }

    protected function getMovimentoField(): ?string
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
        return 'Relatório de APAC';
    }

    protected function getMatrixExportClass(): string
    {
        return MatrixReportExport::class;
    }

    protected function getFaixaEtariaFieldIds(): array
    {
        return ['faixa_etaria_1', 'faixa_etaria_2'];
    }

    protected function getFormaFieldIds(): array
    {
        return ['grupo', 'descgrupo', 'subgrupo', 'descsubgrupo', 'forma', 'descforma'];
    }

    protected function faixaEtaria1Expression(string $alias): string
    {
        $idade = "CAST({$alias}.PAP_IDADE AS SIGNED)";

        $max = self::IDADE_MAXIMA_SIGTAP;

        return "CASE
            WHEN {$idade} > {$max} THEN 'Ignorado'
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
        $idade = "CAST({$alias}.PAP_IDADE AS SIGNED)";

        $max = self::IDADE_MAXIMA_SIGTAP;

        return "CASE
            WHEN {$idade} > {$max} THEN 'Ignorado'
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

    protected function needsFormaJoins(array $selectedFields, array $filters): bool
    {
        $referenced = array_merge(
            $selectedFields,
            array_column($filters, 'field')
        );

        return collect($referenced)->contains(fn ($field) => in_array($field, $this->getFormaFieldIds(), true));
    }

    protected function needsPrestadorJoin(array $selectedFields, array $filters): bool
    {
        $referenced = array_merge(
            $selectedFields,
            array_column($filters, 'field')
        );

        return in_array('tipo_relatorio', $referenced, true)
            || in_array('PAP_UID', $referenced, true);
    }

    protected function needsApaJoin(array $selectedFields, array $filters): bool
    {
        $referenced = array_merge(
            $selectedFields,
            array_column($filters, 'field')
        );

        if (collect($referenced)->contains(fn ($field) => str_starts_with($field, 'APA_'))) {
            return true;
        }

        return collect($filters)->contains(fn ($filter) => ($filter['field'] ?? '') === 'filter_oci');
    }

    protected function addApaJoin($query, array $filters): void
    {
        $ociFilter = collect($filters)->firstWhere('field', 'filter_oci');

        if ($ociFilter && $ociFilter['value'] === true) {
            $query->join('s_apa as apa', function ($join) {
                $join->on(DB::raw('pap.PAP_NUM COLLATE utf8mb4_unicode_ci'), '=', DB::raw('apa.APA_NUM COLLATE utf8mb4_unicode_ci'))
                    ->where('apa.APA_PRIPAL', 'like', '09%');
            });
        } else {
            $query->leftJoin('s_apa as apa', function ($join) {
                $join->on(DB::raw('pap.PAP_NUM COLLATE utf8mb4_unicode_ci'), '=', DB::raw('apa.APA_NUM COLLATE utf8mb4_unicode_ci'));
            });
        }
    }

    protected function addFormaJoins($query): void
    {
        $query->leftJoin('forma as fg', function ($join) {
            $join->on(DB::raw('SUBSTRING(pap.PAP_PA, 1, 2)'), '=', 'fg.grupo')
                ->where('fg.subgrupo', '=', DB::raw('CONCAT(SUBSTRING(pap.PAP_PA, 1, 2), "00")'))
                ->where('fg.forma', '=', DB::raw('CONCAT(SUBSTRING(pap.PAP_PA, 1, 2), "0000")'));
        });
        $query->leftJoin('forma as fs', function ($join) {
            $join->on(DB::raw('SUBSTRING(pap.PAP_PA, 1, 4)'), '=', 'fs.subgrupo')
                ->where('fs.forma', '=', DB::raw('CONCAT(SUBSTRING(pap.PAP_PA, 1, 4), "00")'));
        });
        $query->leftJoin('forma as ff', function ($join) {
            $join->on(DB::raw('SUBSTRING(pap.PAP_PA, 1, 6)'), '=', 'ff.forma');
        });
    }

    protected function addPrestadorJoinIfNeeded($query, &$joins): void
    {
        if (! in_array('prestador', $joins, true)) {
            $query->leftJoin('prestador as pr', function ($join) {
                $join->on(DB::raw('pap.PAP_UID COLLATE utf8mb4_unicode_ci'), '=', DB::raw('pr.re_cunid COLLATE utf8mb4_unicode_ci'));
            });
            $joins[] = 'prestador';
        }
    }

    protected function addReportJoins($query, $selectedFields, $filters, $tableAlias, &$joins): void
    {
        $this->addApaJoin($query, $filters);

        if ($this->needsPrestadorJoin($selectedFields, $filters)) {
            $this->addPrestadorJoinIfNeeded($query, $joins);
        }

        if ($this->needsFormaJoins($selectedFields, $filters) && ! in_array('forma', $joins, true)) {
            $this->addFormaJoins($query);
            $joins[] = 'forma';
        }

        if (collect($selectedFields)->contains('PAP_VALOR') && ! in_array('procedimento', $joins, true)) {
            $query->leftJoin('procedimento as pc', function ($join) use ($tableAlias) {
                $join->on(DB::raw("{$tableAlias}.PAP_PA COLLATE utf8mb4_unicode_ci"), '=', DB::raw('pc.codigo COLLATE utf8mb4_unicode_ci'));
            });
            $joins[] = 'procedimento';
        }

        if ($this->needsSusPaulista($selectedFields, $filters) && ! in_array('sus_paulista', $joins, true)) {
            $this->addSusPaulistaJoin($query, $filters, $tableAlias);
            $joins[] = 'sus_paulista';
        }
    }

    protected function buildQuery($selectedFields, $filters, $groupBy = true)
    {
        $needsCismetro = collect($selectedFields)->contains(fn ($field) => str_starts_with($field, 'cismetro_'))
            || collect($filters)->contains(fn ($f) => str_starts_with($f['field'] ?? '', 'cismetro_'));

        $query = DB::table('s_pap as pap');
        $joins = [];

        $this->addReportJoins($query, $selectedFields, $filters, 'pap', $joins);

        foreach ($selectedFields as $field) {
            $fieldConfig = $this->getFieldConfig($field);
            if ($fieldConfig && $fieldConfig['type'] === 'lookup') {
                $joinKey = $fieldConfig['lookup_table'];
                if (! in_array($joinKey, $joins, true)) {
                    $this->addJoin($query, $field, $fieldConfig, $joins);
                }
            }

            if ($field === 'PAP_VALOR' && ! in_array('procedimento', $joins, true)) {
                $query->leftJoin('procedimento as pc', function ($join) {
                    $join->on(DB::raw('pap.PAP_PA COLLATE utf8mb4_unicode_ci'), '=', DB::raw('pc.codigo COLLATE utf8mb4_unicode_ci'));
                });
                $joins[] = 'procedimento';
            }
        }

        if (collect($filters)->contains(fn ($f) => ($f['field'] ?? '') === 'procedimento_descricao')
            && ! in_array('procedimento', $joins, true)) {
            $query->leftJoin('procedimento as pc', function ($join) {
                $join->on(DB::raw('pap.PAP_PA COLLATE utf8mb4_unicode_ci'), '=', DB::raw('pc.codigo COLLATE utf8mb4_unicode_ci'));
            });
            $joins[] = 'procedimento';
        }

        if ($needsCismetro) {
            $this->joinCismetroByPrestadorTipo($query, 'pap', $this->getProcedimentoFieldForCismetro(), $joins);
        }

        $selectFields = [];
        $groupByFields = [];

        foreach ($selectedFields as $field) {
            if (in_array($field, ['filter_oci', 'filter_sus_paulista'], true)) {
                continue;
            }

            $fieldConfig = $this->getFieldConfig($field);
            if (! $fieldConfig) {
                continue;
            }

            if ($fieldConfig['type'] === 'lookup') {
                $alias = $this->getTableAliasForJoin($fieldConfig['lookup_table']);

                if ($field === 'PAP_UID') {
                    $selectFields[] = 'pap.PAP_UID as cnes';
                    $selectFields[] = 'pr.re_cnome as unidade_nome';
                    $groupByFields[] = 'pap.PAP_UID';
                    $groupByFields[] = 'pr.re_cnome';
                } elseif ($field === 'PAP_PA') {
                    $selectFields[] = 'pap.PAP_PA as procedimento_codigo';
                    $selectFields[] = 'pc.procedimento as procedimento_nome';
                    $groupByFields[] = 'pap.PAP_PA';
                    $groupByFields[] = 'pc.procedimento';
                } elseif ($field === 'cismetro_descricao') {
                    $selectFields[] = 'pap.PAP_PA as cismetro_codigo';
                    $selectFields[] = 'cs.descricao as cismetro_descricao';
                    $groupByFields[] = 'pap.PAP_PA';
                    $groupByFields[] = 'cs.descricao';
                } else {
                    $selectFields[] = "pap.{$field}";
                    $selectFields[] = "{$alias}.{$fieldConfig['lookup_display']} as {$field}_display";
                    $groupByFields[] = "pap.{$field}";
                    $groupByFields[] = "{$alias}.{$fieldConfig['lookup_display']}";
                }
            } elseif ($field === 'procedimento_descricao') {
                continue;
            } elseif ($field === 'PAP_QT_P') {
                $selectFields[] = DB::raw('SUM(CAST(pap.PAP_QT_P as DECIMAL(15,2))) as total_quantidade');
            } elseif ($field === 'PAP_VALOR') {
                $selectFields[] = 'pc.pa_total as valor_unitario';
                $selectFields[] = DB::raw('SUM(CAST(pap.PAP_QT_P as DECIMAL(15,2)) * CAST(pc.pa_total as DECIMAL(15,2))) as valor_total');
                $groupByFields[] = 'pc.pa_total';
            } elseif ($field === 'cismetro_valor') {
                $selectFields[] = 'cs.valor as cismetro_valor';
                $groupByFields[] = 'cs.valor';
            } elseif ($field === 'cismetro_total') {
                $selectFields[] = DB::raw('SUM(CAST(pap.PAP_QT_P as DECIMAL(15,2)) * COALESCE(cs.valor, 0)) as cismetro_total');
            } elseif (($susPaulista = $this->buildSusPaulistaListSelect($field, 'pap'))['handled']) {
                $selectFields = array_merge($selectFields, $susPaulista['select']);
                if (! empty($susPaulista['groupBy'])) {
                    $groupByFields = array_merge($groupByFields, $susPaulista['groupBy']);
                }
            } elseif ($field === 'PAP_CMP') {
                $selectFields[] = DB::raw("CONCAT(SUBSTRING(pap.PAP_CMP, 1, 4), '-', SUBSTRING(pap.PAP_CMP, 5, 2)) as competencia");
                $groupByFields[] = 'pap.PAP_CMP';
            } elseif ($field === 'PAP_MVM') {
                $selectFields[] = DB::raw("CONCAT(SUBSTRING(pap.PAP_MVM, 1, 4), '-', SUBSTRING(pap.PAP_MVM, 5, 2)) as movimento");
                $groupByFields[] = 'pap.PAP_MVM';
            } elseif ($field === 'APA_CMP') {
                $selectFields[] = DB::raw("CONCAT(SUBSTRING(apa.APA_CMP, 1, 4), '-', SUBSTRING(apa.APA_CMP, 5, 2)) as apa_competencia");
                $groupByFields[] = 'apa.APA_CMP';
            } elseif ($field === 'APA_MVM') {
                $selectFields[] = DB::raw("CONCAT(SUBSTRING(apa.APA_MVM, 1, 4), '-', SUBSTRING(apa.APA_MVM, 5, 2)) as apa_movimento");
                $groupByFields[] = 'apa.APA_MVM';
            } elseif ($field === 'grupo') {
                $selectFields[] = DB::raw('SUBSTRING(pap.PAP_PA, 1, 2) as grupo');
                $groupByFields[] = DB::raw('SUBSTRING(pap.PAP_PA, 1, 2)');
            } elseif ($field === 'descgrupo') {
                $selectFields[] = 'fg.descricao as descgrupo';
                $groupByFields[] = 'fg.descricao';
            } elseif ($field === 'subgrupo') {
                $selectFields[] = DB::raw('SUBSTRING(pap.PAP_PA, 1, 4) as subgrupo');
                $groupByFields[] = DB::raw('SUBSTRING(pap.PAP_PA, 1, 4)');
            } elseif ($field === 'descsubgrupo') {
                $selectFields[] = 'fs.descricao as descsubgrupo';
                $groupByFields[] = 'fs.descricao';
            } elseif ($field === 'forma') {
                $selectFields[] = DB::raw('SUBSTRING(pap.PAP_PA, 1, 6) as forma');
                $groupByFields[] = DB::raw('SUBSTRING(pap.PAP_PA, 1, 6)');
            } elseif ($field === 'descforma') {
                $selectFields[] = 'ff.descricao as descforma';
                $groupByFields[] = 'ff.descricao';
            } elseif ($field === 'tipo_relatorio') {
                $selectFields[] = 'pr.relatorio as tipo_relatorio';
                $groupByFields[] = 'pr.relatorio';
            } elseif ($field === 'faixa_etaria_1') {
                $expr = $this->faixaEtaria1Expression('pap');
                $selectFields[] = DB::raw("({$expr}) as faixa_etaria_1");
                $groupByFields[] = DB::raw("({$expr})");
            } elseif ($field === 'faixa_etaria_2') {
                $expr = $this->faixaEtaria2Expression('pap');
                $selectFields[] = DB::raw("({$expr}) as faixa_etaria_2");
                $groupByFields[] = DB::raw("({$expr})");
            } elseif ($field === 'PAP_IDADE') {
                $expr = $this->idadeNormalizadaSql('pap.PAP_IDADE');
                $selectFields[] = DB::raw("{$expr} as PAP_IDADE");
                $groupByFields[] = DB::raw($expr);
            } elseif (str_starts_with($field, 'APA_')) {
                $selectFields[] = "apa.{$field}";
                $groupByFields[] = "apa.{$field}";
            } else {
                $selectFields[] = "pap.{$field}";
                $groupByFields[] = "pap.{$field}";
            }
        }

        $query->select($selectFields);

        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        if ($groupBy && ! empty($groupByFields)) {
            $query->groupBy($groupByFields);
        }

        $firstOrderField = collect($selectedFields)->first(
            fn ($field) => ! in_array($field, ['PAP_QT_P', 'PAP_VALOR', 'cismetro_total', 'procedimento_descricao', 'filter_oci', 'filter_sus_paulista', ...$this->getSusPaulistaAggregateFieldIds()], true)
        );

        if ($firstOrderField === 'faixa_etaria_1') {
            $query->orderBy(DB::raw($this->faixaEtaria1OrderExpression('pap')));
        } elseif ($firstOrderField === 'faixa_etaria_2') {
            $query->orderBy(DB::raw($this->faixaEtaria2OrderExpression('pap')));
        } elseif (! empty($groupByFields)) {
            $query->orderBy($groupByFields[0]);
        }

        return $query;
    }

    protected function addJoin($query, $field, $fieldConfig, array &$joins = []): void
    {
        $alias = $this->getTableAliasForJoin($fieldConfig['lookup_table']);
        $tableAlias = $this->getTableAlias();

        switch ($fieldConfig['lookup_table']) {
            case 'prestador':
                if (! in_array('prestador', $joins, true)) {
                    $query->leftJoin("prestador as {$alias}", function ($join) use ($tableAlias, $alias) {
                        $join->on(DB::raw("{$tableAlias}.PAP_UID COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.re_cunid COLLATE utf8mb4_unicode_ci"));
                    });
                    $joins[] = 'prestador';
                }
                break;
            case 'cbo':
                if (! in_array('cbo', $joins, true)) {
                    $query->leftJoin("cbo as {$alias}", function ($join) use ($tableAlias, $alias) {
                        $join->on(DB::raw("{$tableAlias}.PAP_CBO COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.cbo COLLATE utf8mb4_unicode_ci"));
                    });
                    $joins[] = 'cbo';
                }
                break;
            case 'procedimento':
                if (! in_array('procedimento', $joins, true)) {
                    $query->leftJoin("procedimento as {$alias}", function ($join) use ($tableAlias, $alias) {
                        $join->on(DB::raw("{$tableAlias}.PAP_PA COLLATE utf8mb4_unicode_ci"), '=', DB::raw("{$alias}.codigo COLLATE utf8mb4_unicode_ci"));
                    });
                    $joins[] = 'procedimento';
                }
                break;
            case 'cismetro':
                $this->joinCismetroByPrestadorTipo($query, $tableAlias, $this->getProcedimentoFieldForCismetro(), $joins);
                break;
        }
    }

    protected function applyFilter($query, $filter)
    {
        $field = $filter['field'];
        $operator = $filter['operator'];
        $value = $filter['value'];

        if (in_array($field, ['filter_oci', 'filter_sus_paulista'], true)) {
            return;
        }

        if ($field === 'procedimento_descricao') {
            $subquery = DB::table('procedimento')->select('codigo');

            switch ($operator) {
                case '=':
                    $subquery->where('procedimento', '=', $value);
                    break;
                case 'like':
                    $subquery->where('procedimento', 'like', '%'.$value.'%');
                    break;
                case 'starts_with':
                    $subquery->where('procedimento', 'like', $value.'%');
                    break;
                case 'ends_with':
                    $subquery->where('procedimento', 'like', '%'.$value);
                    break;
            }

            $procedimentoCodigos = $subquery->pluck('codigo')->toArray();

            if (! empty($procedimentoCodigos)) {
                $query->whereIn('pap.PAP_PA', $procedimentoCodigos);
            } else {
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

        $susPaulistaField = $this->resolveSusPaulistaFilterField($field);
        if ($susPaulistaField === null) {
            return;
        }
        if (is_string($susPaulistaField)) {
            $fullField = $susPaulistaField;
        } elseif ($susPaulistaField === false) {
            if ($field === 'cismetro_valor') {
                $fullField = 'cs.valor';
            } elseif ($field === 'cismetro_total') {
                return;
            } elseif (str_starts_with($field, 'cismetro_')) {
                $fullField = 'cs.'.substr($field, 9);
            } else {
                $tablePrefix = str_starts_with($field, 'APA_') ? 'apa' : $this->getTableAlias();
                $fullField = "{$tablePrefix}.{$field}";
            }
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
                $query->where($fullField, 'like', '%'.$value.'%');
                break;
            case 'starts_with':
                $query->where($fullField, 'like', $value.'%');
                break;
            case 'ends_with':
                $query->where($fullField, 'like', '%'.$value);
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

    protected function applyFormaCodeFilter($query, int $length, string $operator, $value): void
    {
        $expr = DB::raw("SUBSTRING(pap.PAP_PA, 1, {$length})");
        $this->applyTextFilter($query, $expr, $operator, $value);
    }

    protected function applyTextFilter($query, $field, string $operator, $value): void
    {
        switch ($operator) {
            case '=':
                $query->where($field, '=', $value);
                break;
            case 'like':
                $query->where($field, 'like', '%'.$value.'%');
                break;
            case 'starts_with':
                $query->where($field, 'like', $value.'%');
                break;
        }
    }

    protected function formatData($data, $selectedFields)
    {
        return $data->map(function ($row) use ($selectedFields) {
            $formatted = [];

            foreach ($selectedFields as $field) {
                if (in_array($field, ['filter_oci', 'filter_sus_paulista'], true)) {
                    continue;
                }

                $fieldConfig = $this->getFieldConfig($field);

                if ($field === 'PAP_UID') {
                    $formatted['CNES'] = $row->cnes ?? '';
                    $formatted['Unidade'] = $row->unidade_nome ?? '';
                } elseif ($field === 'PAP_PA') {
                    $formatted['Código Procedimento'] = $row->procedimento_codigo ?? '';
                    $formatted['Procedimento'] = $row->procedimento_nome ?? '';
                } elseif ($field === 'cismetro_descricao') {
                    $formatted['Código Cismetro'] = $row->cismetro_codigo ?? '';
                    $formatted['Cismetro - Descrição'] = $row->cismetro_descricao ?? '';
                } elseif ($field === 'procedimento_descricao') {
                    continue;
                } elseif ($field === 'cismetro_valor') {
                    $formatted['Cismetro - Valor Unitário'] = $row->cismetro_valor
                        ? 'R$ '.number_format((float) $row->cismetro_valor, 2, ',', '.')
                        : 'R$ 0,00';
                } elseif ($field === 'cismetro_total') {
                    $formatted['Cismetro - Valor Total'] = $row->cismetro_total
                        ? 'R$ '.number_format((float) $row->cismetro_total, 2, ',', '.')
                        : 'R$ 0,00';
                } elseif (($susPaulista = $this->formatSusPaulistaField($field, $row)) !== null) {
                    $formatted = array_merge($formatted, $susPaulista);
                } elseif ($field === 'PAP_QT_P') {
                    $formatted['Quantidade Total'] = number_format((float) ($row->total_quantidade ?? 0), 0, ',', '.');
                } elseif ($field === 'PAP_VALOR') {
                    $formatted['Valor Unitário'] = 'R$ '.number_format((float) ($row->valor_unitario ?? 0), 2, ',', '.');
                    $formatted['Valor Total'] = 'R$ '.number_format((float) ($row->valor_total ?? 0), 2, ',', '.');
                } elseif ($field === 'PAP_CMP') {
                    $formatted['Data Competência'] = $row->competencia ?? '';
                } elseif ($field === 'PAP_MVM') {
                    $formatted['Data Movimento'] = $row->movimento ?? '';
                } elseif ($field === 'APA_CMP') {
                    $formatted['Competência APAC'] = $row->apa_competencia ?? '';
                } elseif ($field === 'APA_MVM') {
                    $formatted['Movimento APAC'] = $row->apa_movimento ?? '';
                } elseif (in_array($field, $this->getFaixaEtariaFieldIds(), true)) {
                    $formatted[$fieldConfig['label']] = $row->{$field} ?? '';
                } elseif ($field === 'PAP_IDADE') {
                    $formatted['Idade'] = $this->formatIdadeExibicao($row->PAP_IDADE ?? null);
                } elseif (in_array($field, $this->getFormaFieldIds(), true)) {
                    $formatted[$fieldConfig['label']] = $row->{$field} ?? '';
                } else {
                    $value = $row->{$field} ?? '';

                    switch ($fieldConfig['type'] ?? 'text') {
                        case 'currency':
                            $formatted[$fieldConfig['label']] = 'R$ '.number_format((float) $value, 2, ',', '.');
                            break;
                        case 'number':
                            $formatted[$fieldConfig['label']] = number_format((float) $value, 0, ',', '.');
                            break;
                        case 'date':
                            $formatted[$fieldConfig['label']] = $value
                                ? (strlen((string) $value) === 6
                                    ? substr($value, 4, 2).'/'.substr($value, 0, 4)
                                    : $value)
                                : '';
                            break;
                        case 'lookup':
                            $displayField = $field.'_display';
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

    protected function calculateTotals($data, $selectedFields)
    {
        $totals = [];

        if (in_array('PAP_QT_P', $selectedFields)) {
            $totalQty = $data->sum(fn ($item) => $item->total_quantidade ?? 0);
            $totals['Quantidade Total'] = number_format($totalQty, 0, ',', '.');
        }

        if (in_array('PAP_VALOR', $selectedFields)) {
            $totalValue = $data->sum(fn ($item) => $item->valor_total ?? 0);
            $totals['Valor Total Geral'] = 'R$ '.number_format($totalValue, 2, ',', '.');
        }

        if (in_array('cismetro_total', $selectedFields)) {
            $totalCismetro = $data->sum(fn ($item) => $item->cismetro_total ?? 0);
            $totals['Cismetro - Valor Total Geral'] = 'R$ '.number_format($totalCismetro, 2, ',', '.');
        }

        $this->appendSusPaulistaTotals($selectedFields, $data, $totals);

        return $totals;
    }

    protected function exportExcel($data, $selectedFields, $totals = [])
    {
        try {
            if (empty($data)) {
                throw new \Exception('Nenhum dado encontrado para exportação');
            }

            if (! $data instanceof \Illuminate\Support\Collection) {
                $data = collect($data);
            }

            $export = new RelatorioApacExport($data, $selectedFields, $totals);

            return Excel::download($export, 'relatorio_apac_'.date('Y-m-d_H-i-s').'.xlsx');
        } catch (\Exception $e) {
            \Log::error('APAC Excel Export Error: '.$e->getMessage());

            return response()->json([
                'error' => 'Erro ao exportar Excel: '.$e->getMessage(),
            ], 500);
        }
    }

    protected function exportPdf($data, $selectedFields, $totals = [])
    {
        $pdf = Pdf::loadView('relatorios.apac.pdf', [
            'data' => $data,
            'fields' => $selectedFields,
            'totals' => $totals,
            'title' => 'Relatório de APAC - ConsultaProd',
        ]);

        return $pdf->download('relatorio_apac.pdf');
    }

    protected function exportCsv($data, $selectedFields, $totals = [])
    {
        $filename = 'relatorio_apac.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data, $totals) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            if (! empty($data)) {
                $firstRow = $data->first();
                $fieldLabels = array_keys($firstRow);
                fputcsv($file, $fieldLabels, ';');

                foreach ($data as $row) {
                    fputcsv($file, array_values($row), ';');
                }

                if (! empty($totals)) {
                    fputcsv($file, [], ';');
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

    protected function getFieldConfig($field)
    {
        return $this->getAllFieldConfigs()[$field] ?? null;
    }

    protected function getAllFieldConfigs(): array
    {
        return [
            'PAP_CMP' => [
                'label' => 'Data Competência',
                'type' => 'date',
                'table' => 's_pap',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'PAP_MVM' => [
                'label' => 'Data Movimento',
                'type' => 'date',
                'table' => 's_pap',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'PAP_UID' => [
                'label' => 'Unidade (CNES)',
                'type' => 'lookup',
                'table' => 's_pap',
                'lookup_table' => 'prestador',
                'lookup_key' => 're_cunid',
                'lookup_display' => 're_cnome',
                'operators' => ['=', 'in'],
            ],
            'tipo_relatorio' => [
                'label' => 'Tipo de Relatório',
                'type' => 'text',
                'table' => 'prestador',
                'field' => 'relatorio',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'PAP_PA' => [
                'label' => 'Procedimento',
                'type' => 'lookup',
                'table' => 's_pap',
                'lookup_table' => 'procedimento',
                'lookup_key' => 'codigo',
                'lookup_display' => 'procedimento',
                'operators' => ['=', 'in', 'like'],
            ],
            'procedimento_descricao' => [
                'label' => 'Descrição do Procedimento',
                'type' => 'text',
                'table' => 'procedimento',
                'field' => 'procedimento',
                'operators' => ['=', 'like', 'starts_with', 'ends_with'],
            ],
            'PAP_CBO' => [
                'label' => 'CBO Profissional',
                'type' => 'lookup',
                'table' => 's_pap',
                'lookup_table' => 'cbo',
                'lookup_key' => 'cbo',
                'lookup_display' => 'ds_cbo',
                'operators' => ['=', 'in'],
            ],
            'PAP_CIDPRI' => [
                'label' => 'CID Principal',
                'type' => 'text',
                'table' => 's_pap',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'PAP_QT_P' => [
                'label' => 'Quantidade Produzida',
                'type' => 'number',
                'table' => 's_pap',
                'operators' => ['=', '>', '<', '>=', '<=', 'between'],
            ],
            'PAP_VALOR' => [
                'label' => 'Valor (Unitário e Total)',
                'type' => 'currency',
                'table' => 's_pap',
                'operators' => ['=', '>', '<', '>=', '<=', 'between'],
            ],
            'PAP_IDADE' => [
                'label' => 'Idade',
                'type' => 'number',
                'table' => 's_pap',
                'operators' => ['=', '>=', '<='],
            ],
            'faixa_etaria_1' => [
                'label' => 'Faixa Etária (detalhada)',
                'type' => 'calculated',
                'table' => 's_pap',
                'operators' => [],
            ],
            'faixa_etaria_2' => [
                'label' => 'Faixa Etária (resumida)',
                'type' => 'calculated',
                'table' => 's_pap',
                'operators' => [],
            ],
            'APA_NUM' => [
                'label' => 'Número APAC',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'APA_CMP' => [
                'label' => 'Competência APAC',
                'type' => 'date',
                'table' => 's_apa',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'APA_MVM' => [
                'label' => 'Movimento APAC',
                'type' => 'date',
                'table' => 's_apa',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'APA_PRIPAL' => [
                'label' => 'Procedimento Principal APAC',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'APA_NMPCN' => [
                'label' => 'Nome do Paciente',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'APA_CNSPCT' => [
                'label' => 'CNS Paciente',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'APA_DTNASC' => [
                'label' => 'Data de Nascimento',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'APA_SEXPCN' => [
                'label' => 'Sexo',
                'type' => 'choice',
                'table' => 's_apa',
                'options' => [
                    'M' => 'Masculino',
                    'F' => 'Feminino',
                ],
                'operators' => ['='],
            ],
            'APA_CIDCA' => [
                'label' => 'CID Principal APAC',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'APA_RACA' => [
                'label' => 'Raça/Cor',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'in'],
            ],
            'APA_DTINIC' => [
                'label' => 'Data Início Validade',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'APA_DTFIM' => [
                'label' => 'Data Fim Validade',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'APA_TPATEN' => [
                'label' => 'Tipo de Atendimento',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'in'],
            ],
            'APA_TPAPAC' => [
                'label' => 'Tipo APAC',
                'type' => 'text',
                'table' => 's_apa',
                'operators' => ['=', 'in'],
            ],
            'cismetro_valor' => [
                'label' => 'Cismetro - Valor Unitário',
                'type' => 'currency',
                'table' => 'cismetro',
                'operators' => ['=', '>', '<', '>=', '<=', 'between'],
            ],
            'cismetro_total' => [
                'label' => 'Cismetro - Valor Total',
                'type' => 'currency',
                'table' => 'calculated',
                'operators' => ['=', '>', '<', '>=', '<=', 'between'],
            ],
            'cismetro_descricao' => [
                'label' => 'Cismetro - Descrição',
                'type' => 'lookup',
                'table' => 'cismetro',
                'lookup_table' => 'cismetro',
                'lookup_key' => 'codigo',
                'lookup_display' => 'descricao',
                'operators' => ['=', 'like'],
            ],
            ...$this->getSusPaulistaFieldConfigs(),
            'grupo' => [
                'label' => 'Grupo',
                'type' => 'text',
                'table' => 'forma',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'descgrupo' => [
                'label' => 'Descrição do Grupo',
                'type' => 'text',
                'table' => 'forma',
                'operators' => [],
            ],
            'subgrupo' => [
                'label' => 'Subgrupo',
                'type' => 'text',
                'table' => 'forma',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'descsubgrupo' => [
                'label' => 'Descrição do Subgrupo',
                'type' => 'text',
                'table' => 'forma',
                'operators' => [],
            ],
            'forma' => [
                'label' => 'Forma de Organização',
                'type' => 'text',
                'table' => 'forma',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'descforma' => [
                'label' => 'Descrição da Forma',
                'type' => 'text',
                'table' => 'forma',
                'operators' => ['=', 'like', 'starts_with'],
            ],
        ];
    }

    protected function getPrestadorField(): string
    {
        return 'PAP_UID';
    }

    protected function getMatrixSplitCandidates(): array
    {
        return [$this->getPrestadorField(), 'tipo_relatorio'];
    }

    protected function getCboField(): string
    {
        return 'PAP_CBO';
    }

    protected function getSusPaulistaQuantityField(): string
    {
        return 'PAP_QT_P';
    }

    protected function getSusPaulistaQuantityCastType(): string
    {
        return 'DECIMAL(15,2)';
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
            $selectFields[] = 'pr.re_cnome as prestador_nome';
            $groupByFields[] = "{$tableAlias}.PAP_UID";
            $groupByFields[] = 'pr.re_cnome';
        } elseif ($field === 'PAP_PA') {
            $selectFields[] = "{$tableAlias}.PAP_PA as procedimento_codigo";
            $selectFields[] = 'pc.procedimento as procedimento_nome';
            $groupByFields[] = "{$tableAlias}.PAP_PA";
            $groupByFields[] = 'pc.procedimento';
        } elseif ($field === 'PAP_CBO') {
            $selectFields[] = "{$tableAlias}.PAP_CBO as cbo_codigo";
            $selectFields[] = 'cb.ds_cbo as cbo_nome';
            $groupByFields[] = "{$tableAlias}.PAP_CBO";
            $groupByFields[] = 'cb.ds_cbo';
        } elseif ($field === 'cismetro_descricao') {
            $selectFields[] = "{$tableAlias}.PAP_PA as cismetro_codigo";
            $selectFields[] = 'cs.descricao as cismetro_descricao';
            $groupByFields[] = "{$tableAlias}.PAP_PA";
            $groupByFields[] = 'cs.descricao';
        } elseif ($field === 'grupo') {
            $selectFields[] = DB::raw("SUBSTRING({$tableAlias}.PAP_PA, 1, 2) as grupo");
            $groupByFields[] = DB::raw("SUBSTRING({$tableAlias}.PAP_PA, 1, 2)");
        } elseif ($field === 'descgrupo') {
            $selectFields[] = 'fg.descricao as descgrupo';
            $groupByFields[] = 'fg.descricao';
        } elseif ($field === 'subgrupo') {
            $selectFields[] = DB::raw("SUBSTRING({$tableAlias}.PAP_PA, 1, 4) as subgrupo");
            $groupByFields[] = DB::raw("SUBSTRING({$tableAlias}.PAP_PA, 1, 4)");
        } elseif ($field === 'descsubgrupo') {
            $selectFields[] = 'fs.descricao as descsubgrupo';
            $groupByFields[] = 'fs.descricao';
        } elseif ($field === 'forma') {
            $selectFields[] = DB::raw("SUBSTRING({$tableAlias}.PAP_PA, 1, 6) as forma");
            $groupByFields[] = DB::raw("SUBSTRING({$tableAlias}.PAP_PA, 1, 6)");
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
        } elseif ($field === 'PAP_IDADE') {
            $expr = $this->idadeNormalizadaSql("{$tableAlias}.PAP_IDADE");
            $selectFields[] = DB::raw("{$expr} as PAP_IDADE");
            $groupByFields[] = DB::raw($expr);
        } elseif (str_starts_with($field, 'APA_')) {
            $selectFields[] = "apa.{$field}";
            $groupByFields[] = "apa.{$field}";
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
        } else {
            $fields = array_merge($fields, $this->getSusPaulistaMatrixNumericFields($field, $tableAlias));
        }

        return $fields;
    }

    protected function getGroupKeyPart($item, $field)
    {
        if ($field === 'PAP_UID') {
            return ($item->prestador_codigo ?? '').'|'.($item->prestador_nome ?? '');
        } elseif ($field === 'PAP_PA') {
            return ($item->procedimento_codigo ?? '').'|'.($item->procedimento_nome ?? '');
        } elseif ($field === 'PAP_CBO') {
            return ($item->cbo_codigo ?? '').'|'.($item->cbo_nome ?? '');
        } elseif ($field === 'cismetro_descricao') {
            return ($item->cismetro_codigo ?? '').'|'.($item->cismetro_descricao ?? '');
        } elseif (in_array($field, $this->getFaixaEtariaFieldIds(), true)) {
            return $item->{$field} ?? '';
        } elseif (in_array($field, $this->getFormaFieldIds(), true)) {
            return $item->{$field} ?? '';
        } elseif ($field === 'tipo_relatorio') {
            return $item->tipo_relatorio ?? '';
        } elseif ($field === 'PAP_IDADE') {
            return $this->idadeAgrupamentoKey($item->PAP_IDADE ?? null);
        } elseif (str_starts_with($field, 'APA_')) {
            return $item->{$field} ?? '';
        }

        return $item->{$field} ?? '';
    }

    protected function getNumericValue($item, $field)
    {
        switch ($field) {
            case 'PAP_QT_P':
                return (float) ($item->total_quantidade ?? 0);
            case 'PAP_VALOR':
                return (float) ($item->valor_total ?? 0);
            case 'cismetro_total':
                return (float) ($item->cismetro_total ?? 0);
            case 'cismetro_valor':
                return (float) ($item->cismetro_valor ?? 0);
            default:
                $susValue = $this->getSusPaulistaNumericValue($item, $field);

                return $susValue ?? (float) ($item->{$field} ?? 0);
        }
    }

    protected function getDefaultNumericField(): ?string
    {
        return 'PAP_QT_P';
    }
}
