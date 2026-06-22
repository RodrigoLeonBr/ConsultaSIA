<?php

namespace App\Http\Controllers;

use App\Exports\RelatorioAihExport;
use App\Exports\MatrixReportExport;
use App\Http\Controllers\Concerns\HasMatrixReport;
use Illuminate\Support\Facades\DB;

class RelatorioAihController extends BaseRelatorioController
{
    use HasMatrixReport;

    // ── Field IDs helpers ────────────────────────────────────────────────────

    protected function getFaixaEtariaFieldIds(): array
    {
        return ['faixa_etaria_1', 'faixa_etaria_2'];
    }

    protected function getFormaFieldIds(): array
    {
        return ['grupo', 'descgrupo', 'subgrupo', 'descsubgrupo', 'forma', 'descforma'];
    }

    // ── Faixa etária expressions (based on sa.IDADE) ─────────────────────────

    private function faixaEtaria1Expression(): string
    {
        return "CASE
            WHEN CAST(sa.IDADE AS SIGNED) > 150 THEN 'Ignorado'
            WHEN CAST(sa.IDADE AS SIGNED) = 0 THEN 'Menor que 1 ano'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 1 AND 4 THEN '1 a 4 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 5 AND 9 THEN '5 a 9 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 10 AND 14 THEN '10 a 14 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 15 AND 19 THEN '15 a 19 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 20 AND 24 THEN '20 a 24 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 25 AND 29 THEN '25 a 29 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 30 AND 34 THEN '30 a 34 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 35 AND 39 THEN '35 a 39 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 40 AND 44 THEN '40 a 44 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 45 AND 49 THEN '45 a 49 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 50 AND 54 THEN '50 a 54 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 55 AND 59 THEN '55 a 59 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 60 AND 64 THEN '60 a 64 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 65 AND 69 THEN '65 a 69 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 70 AND 74 THEN '70 a 74 anos'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 75 AND 79 THEN '75 a 79 anos'
            WHEN CAST(sa.IDADE AS SIGNED) >= 80 THEN '80 anos ou mais'
            ELSE 'Ignorado'
        END";
    }

    private function faixaEtaria2Expression(): string
    {
        return "CASE
            WHEN CAST(sa.IDADE AS SIGNED) > 150 THEN 'Ignorado'
            WHEN CAST(sa.IDADE AS SIGNED) <= 9 THEN 'Criança'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 10 AND 17 THEN 'Infantil'
            WHEN CAST(sa.IDADE AS SIGNED) BETWEEN 18 AND 59 THEN 'Adulto'
            WHEN CAST(sa.IDADE AS SIGNED) >= 60 THEN 'Idoso'
            ELSE 'Ignorado'
        END";
    }

    private function faixaEtaria1OrderExpression(): string
    {
        $expr = $this->faixaEtaria1Expression();
        return "FIELD(({$expr}), 'Menor que 1 ano','1 a 4 anos','5 a 9 anos','10 a 14 anos','15 a 19 anos','20 a 24 anos','25 a 29 anos','30 a 34 anos','35 a 39 anos','40 a 44 anos','45 a 49 anos','50 a 54 anos','55 a 59 anos','60 a 64 anos','65 a 69 anos','70 a 74 anos','75 a 79 anos','80 anos ou mais','Ignorado')";
    }

    private function faixaEtaria2OrderExpression(): string
    {
        $expr = $this->faixaEtaria2Expression();
        return "FIELD(({$expr}), 'Criança','Infantil','Adulto','Idoso','Ignorado')";
    }

    // ── Field definitions ────────────────────────────────────────────────────

    private function fieldDefs(): array
    {
        return [
            'COMPETENCIA' => [
                'label'     => 'Competência',
                'type'      => 'date',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'CNES' => [
                'label'          => 'Prestador (CNES)',
                'type'           => 'lookup',
                'lookup_table'   => 'prestador',
                'lookup_key'     => 're_cunid',
                'lookup_display' => 're_cnome',
                'operators'      => ['='],
            ],
            'DT_NASC' => [
                'label'     => 'Data de Nascimento',
                'type'      => 'text',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'IDADE' => [
                'label'     => 'Idade',
                'type'      => 'number',
                'operators' => ['=', '>', '<', '>=', '<=', 'between'],
            ],
            'faixa_etaria_1' => [
                'label'     => 'Faixa Etária (detalhada)',
                'type'      => 'calculated',
                'operators' => [],
            ],
            'faixa_etaria_2' => [
                'label'     => 'Faixa Etária (resumida)',
                'type'      => 'calculated',
                'operators' => [],
            ],
            'SEXO_PACIENTE' => [
                'label'     => 'Sexo do Paciente',
                'type'      => 'text',
                'operators' => ['='],
            ],
            'DT_INT' => [
                'label'     => 'Data de Internação',
                'type'      => 'text',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'DT_SAIDA' => [
                'label'     => 'Data de Saída',
                'type'      => 'text',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'ESPECIALIDADE' => [
                'label'     => 'Especialidade',
                'type'      => 'text',
                'operators' => ['=', 'like'],
            ],
            'PROC_PRINCIPAL' => [
                'label'          => 'Procedimento Principal',
                'type'           => 'lookup',
                'lookup_table'   => 'procedimento',
                'lookup_key'     => 'codigo',
                'lookup_display' => 'procedimento',
                'operators'      => ['='],
            ],
            'proc_principal_descricao' => [
                'label'     => 'Descrição Proc. Principal',
                'type'      => 'text',
                'operators' => ['=', 'like', 'starts_with', 'ends_with'],
            ],
            'grupo' => [
                'label'     => 'Grupo',
                'type'      => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'descgrupo' => [
                'label'     => 'Descrição do Grupo',
                'type'      => 'text',
                'operators' => [],
            ],
            'subgrupo' => [
                'label'     => 'Subgrupo',
                'type'      => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'descsubgrupo' => [
                'label'     => 'Descrição do Subgrupo',
                'type'      => 'text',
                'operators' => [],
            ],
            'forma' => [
                'label'     => 'Forma de Organização',
                'type'      => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'descforma' => [
                'label'     => 'Descrição da Forma',
                'type'      => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'DIAG_PRINCIPAL' => [
                'label'     => 'Diagnóstico Principal (CID)',
                'type'      => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'COMPLEXIDADE' => [
                'label'     => 'Complexidade',
                'type'      => 'text',
                'operators' => ['='],
            ],
            'FINANCIAMENTO' => [
                'label'          => 'Financiamento',
                'type'           => 'lookup',
                'lookup_table'   => 's_rub',
                'lookup_key'     => 'RUB_ID',
                'lookup_display' => 'RUB_DC',
                'operators'      => ['='],
            ],
            'MOTIVO_SAIDA' => [
                'label'     => 'Motivo de Saída',
                'type'      => 'text',
                'operators' => ['='],
            ],
            'DIARIAS' => [
                'label'     => 'Diárias (soma)',
                'type'      => 'number',
                'operators' => ['=', '>', '<', '>=', '<='],
            ],
            'DIARIAS_UTI' => [
                'label'     => 'Diárias UTI (soma)',
                'type'      => 'number',
                'operators' => ['=', '>', '<', '>=', '<='],
            ],
            'VALOR_TOTAL_AIH' => [
                'label'     => 'Valor Total AIH (soma)',
                'type'      => 'currency',
                'operators' => ['=', '>', '<', '>=', '<='],
            ],
            'qtd_aih' => [
                'label'     => 'Quantidade de AIH',
                'type'      => 'number',
                'operators' => [],
            ],
        ];
    }

    // ── Controller interface ─────────────────────────────────────────────────

    public function index()
    {
        return view('relatorios.aih.index');
    }

    public function getFields()
    {
        return response()->json(['fields' => $this->fieldDefs()]);
    }

    protected function getFieldConfig($field)
    {
        return $this->fieldDefs()[$field] ?? null;
    }

    protected function getTableName(): string { return 's_aih'; }
    protected function getTableAlias(): string { return 'sa'; }
    protected function getCompetenciaField(): string { return 'COMPETENCIA'; }
    protected function getExportClass(): string { return RelatorioAihExport::class; }
    protected function getMatrixExportClass(): string { return MatrixReportExport::class; }
    protected function getPdfView(): string { return 'relatorios.aih.pdf'; }
    protected function getReportTitle(): string { return 'Relatório de Internações AIH'; }
    protected function getExportFilename($extension) { return 'relatorio_aih.' . $extension; }
    protected function getMatrixExportFilename() { return 'relatorio_aih_matriz.xlsx'; }
    protected function getPrestadorField(): string { return 'CNES'; }
    protected function getCboField(): string { return ''; }
    protected function getRubField(): ?string { return 'FINANCIAMENTO'; }
    protected function getProcedimentoFieldForCismetro(): string { return 'PROC_PRINCIPAL'; }
    protected function getDefaultNumericField(): ?string { return 'qtd_aih'; }

    // ── Join helpers ─────────────────────────────────────────────────────────

    private function needsFormaJoins(array $selectedFields, array $filters): bool
    {
        $all = array_merge($selectedFields, array_column($filters, 'field'));
        return (bool) array_intersect($this->getFormaFieldIds(), $all);
    }

    private function addFormaJoins($query): void
    {
        // s_aih uses utf8mb4_unicode_ci; forma uses utf8mb4_general_ci → force collate
        $query->leftJoin('forma as fg', function ($join) {
            $join->on(DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 2) COLLATE utf8mb4_general_ci'), '=', DB::raw('fg.grupo COLLATE utf8mb4_general_ci'))
                 ->where(DB::raw('fg.subgrupo COLLATE utf8mb4_general_ci'), '=', DB::raw('CONCAT(SUBSTRING(sa.PROC_PRINCIPAL, 1, 2), "00") COLLATE utf8mb4_general_ci'))
                 ->where(DB::raw('fg.forma COLLATE utf8mb4_general_ci'),    '=', DB::raw('CONCAT(SUBSTRING(sa.PROC_PRINCIPAL, 1, 2), "0000") COLLATE utf8mb4_general_ci'));
        });
        $query->leftJoin('forma as fs', function ($join) {
            $join->on(DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 4) COLLATE utf8mb4_general_ci'), '=', DB::raw('fs.subgrupo COLLATE utf8mb4_general_ci'))
                 ->where(DB::raw('fs.forma COLLATE utf8mb4_general_ci'), '=', DB::raw('CONCAT(SUBSTRING(sa.PROC_PRINCIPAL, 1, 4), "00") COLLATE utf8mb4_general_ci'));
        });
        $query->leftJoin('forma as ff', function ($join) {
            $join->on(DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 6) COLLATE utf8mb4_general_ci'), '=', DB::raw('ff.forma COLLATE utf8mb4_general_ci'));
        });
    }

    private function addLookupJoin($query, string $field): void
    {
        switch ($field) {
            case 'CNES':
                $query->leftJoin('prestador as pr', function ($join) {
                    $join->on(DB::raw('sa.CNES COLLATE utf8mb4_unicode_ci'), '=', DB::raw('pr.re_cunid COLLATE utf8mb4_unicode_ci'));
                });
                break;
            case 'PROC_PRINCIPAL':
                $query->leftJoin('procedimento as proc', function ($join) {
                    $join->on(DB::raw('sa.PROC_PRINCIPAL COLLATE utf8mb4_unicode_ci'), '=', DB::raw('proc.codigo COLLATE utf8mb4_unicode_ci'));
                });
                break;
            case 'FINANCIAMENTO':
                $query->leftJoin('s_rub as sr', function ($join) {
                    $join->on(DB::raw('sa.FINANCIAMENTO COLLATE utf8mb4_unicode_ci'), '=', DB::raw('sr.RUB_ID COLLATE utf8mb4_unicode_ci'));
                });
                break;
        }
    }

    // ── Query builder ────────────────────────────────────────────────────────

    protected function buildQuery($selectedFields, $filters, $groupBy = true)
    {
        $query = DB::table('s_aih as sa');
        $joins = [];

        $allFields = array_merge($selectedFields, array_column($filters, 'field'));

        // Standard lookup joins
        foreach ($allFields as $field) {
            $cfg = $this->getFieldConfig($field);
            if ($cfg && $cfg['type'] === 'lookup' && !in_array($cfg['lookup_table'], $joins, true)) {
                $this->addLookupJoin($query, $field);
                $joins[] = $cfg['lookup_table'];
            }
        }

        // proc_principal_descricao needs procedimento join for filtering
        if (in_array('proc_principal_descricao', $allFields, true) && !in_array('procedimento', $joins, true)) {
            $this->addLookupJoin($query, 'PROC_PRINCIPAL');
            $joins[] = 'procedimento';
        }

        // Forma joins (grupo/subgrupo/forma)
        if ($this->needsFormaJoins($selectedFields, $filters) && !in_array('forma', $joins, true)) {
            $this->addFormaJoins($query);
            $joins[] = 'forma';
        }

        $selectFields  = [];
        $groupByFields = [];

        foreach ($selectedFields as $field) {
            $cfg = $this->getFieldConfig($field);
            if (!$cfg) {
                continue;
            }

            switch ($field) {
                case 'COMPETENCIA':
                    $selectFields[]  = DB::raw("CONCAT(SUBSTRING(sa.COMPETENCIA,1,4),'-',SUBSTRING(sa.COMPETENCIA,5,2)) as COMPETENCIA");
                    $groupByFields[] = 'sa.COMPETENCIA';
                    break;

                case 'CNES':
                    $selectFields[]  = 'sa.CNES';
                    $selectFields[]  = 'pr.re_cnome as CNES_display';
                    $groupByFields[] = 'sa.CNES';
                    $groupByFields[] = 'pr.re_cnome';
                    break;

                case 'PROC_PRINCIPAL':
                    $selectFields[]  = 'sa.PROC_PRINCIPAL';
                    $selectFields[]  = 'proc.procedimento as PROC_PRINCIPAL_display';
                    $groupByFields[] = 'sa.PROC_PRINCIPAL';
                    $groupByFields[] = 'proc.procedimento';
                    break;

                case 'FINANCIAMENTO':
                    $selectFields[]  = 'sa.FINANCIAMENTO';
                    $selectFields[]  = 'sr.RUB_DC as FINANCIAMENTO_display';
                    $groupByFields[] = 'sa.FINANCIAMENTO';
                    $groupByFields[] = 'sr.RUB_DC';
                    break;

                case 'faixa_etaria_1':
                    $expr = $this->faixaEtaria1Expression();
                    $selectFields[]  = DB::raw("({$expr}) as faixa_etaria_1");
                    $groupByFields[] = DB::raw("({$expr})");
                    break;

                case 'faixa_etaria_2':
                    $expr = $this->faixaEtaria2Expression();
                    $selectFields[]  = DB::raw("({$expr}) as faixa_etaria_2");
                    $groupByFields[] = DB::raw("({$expr})");
                    break;

                case 'grupo':
                    $selectFields[]  = DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 2) as grupo');
                    $groupByFields[] = DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 2)');
                    break;

                case 'descgrupo':
                    $selectFields[]  = 'fg.descricao as descgrupo';
                    $groupByFields[] = 'fg.descricao';
                    break;

                case 'subgrupo':
                    $selectFields[]  = DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 4) as subgrupo');
                    $groupByFields[] = DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 4)');
                    break;

                case 'descsubgrupo':
                    $selectFields[]  = 'fs.descricao as descsubgrupo';
                    $groupByFields[] = 'fs.descricao';
                    break;

                case 'forma':
                    $selectFields[]  = DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 6) as forma');
                    $groupByFields[] = DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 6)');
                    break;

                case 'descforma':
                    $selectFields[]  = 'ff.descricao as descforma';
                    $groupByFields[] = 'ff.descricao';
                    break;

                case 'proc_principal_descricao':
                    continue 2; // filter-only, no SELECT

                case 'DIARIAS':
                    $selectFields[] = DB::raw('SUM(CAST(sa.DIARIAS AS UNSIGNED)) as DIARIAS');
                    break;

                case 'DIARIAS_UTI':
                    $selectFields[] = DB::raw('SUM(CAST(sa.DIARIAS_UTI AS UNSIGNED)) as DIARIAS_UTI');
                    break;

                case 'VALOR_TOTAL_AIH':
                    $selectFields[] = DB::raw('SUM(CAST(sa.VALOR_TOTAL_AIH AS DECIMAL(12,2))) as VALOR_TOTAL_AIH');
                    break;

                case 'IDADE':
                    $selectFields[] = DB::raw('AVG(CAST(sa.IDADE AS UNSIGNED)) as IDADE');
                    break;

                case 'qtd_aih':
                    $selectFields[] = DB::raw('COUNT(DISTINCT sa.AIH) as qtd_aih');
                    break;

                default:
                    $selectFields[]  = "sa.{$field}";
                    $groupByFields[] = "sa.{$field}";
            }
        }

        $query->select($selectFields);

        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        if ($groupBy && !empty($groupByFields)) {
            $query->groupBy($groupByFields);
        }

        // Ordering
        $firstOrder = collect($selectedFields)->first(fn ($f) => !in_array($f, [
            'DIARIAS', 'DIARIAS_UTI', 'VALOR_TOTAL_AIH', 'IDADE', 'qtd_aih',
            'proc_principal_descricao',
        ], true));

        if ($firstOrder === 'faixa_etaria_1') {
            $query->orderBy(DB::raw($this->faixaEtaria1OrderExpression()));
        } elseif ($firstOrder === 'faixa_etaria_2') {
            $query->orderBy(DB::raw($this->faixaEtaria2OrderExpression()));
        }

        return $query;
    }

    // ── Filter override ───────────────────────────────────────────────────────

    protected function applyFilter($query, $filter)
    {
        $field    = $filter['field'];
        $operator = $filter['operator'];
        $value    = $filter['value'];

        // proc_principal_descricao → subquery on procedimento
        if ($field === 'proc_principal_descricao') {
            $sub = DB::table('procedimento')->select('codigo');
            match ($operator) {
                'like'        => $sub->where('procedimento', 'like', "%{$value}%"),
                'starts_with' => $sub->where('procedimento', 'like', "{$value}%"),
                'ends_with'   => $sub->where('procedimento', 'like', "%{$value}"),
                default       => $sub->where('procedimento', '=', $value),
            };
            $codes = $sub->pluck('codigo')->toArray();
            empty($codes)
                ? $query->whereRaw('1 = 0')
                : $query->whereIn('sa.PROC_PRINCIPAL', $codes);
            return;
        }

        // grupo / subgrupo / forma → SUBSTRING filter
        if (in_array($field, ['grupo', 'subgrupo', 'forma'], true)) {
            $len = match ($field) { 'grupo' => 2, 'subgrupo' => 4, 'forma' => 6 };
            $expr = DB::raw("SUBSTRING(sa.PROC_PRINCIPAL, 1, {$len})");
            $this->applySubstringFilter($query, $expr, $operator, $value);
            return;
        }

        // descgrupo / descsubgrupo / descforma → filter on forma aliases
        if ($field === 'descgrupo') {
            $this->applySubstringFilter($query, 'fg.descricao', $operator, $value);
            return;
        }
        if ($field === 'descsubgrupo') {
            $this->applySubstringFilter($query, 'fs.descricao', $operator, $value);
            return;
        }
        if ($field === 'descforma') {
            $this->applySubstringFilter($query, 'ff.descricao', $operator, $value);
            return;
        }

        // faixa etária → not filterable (operators=[])
        if (in_array($field, $this->getFaixaEtariaFieldIds(), true)) {
            return;
        }

        parent::applyFilter($query, $filter);
    }

    private function applySubstringFilter($query, $column, string $operator, $value): void
    {
        match ($operator) {
            'like'        => $query->where($column, 'like', "%{$value}%"),
            'starts_with' => $query->where($column, 'like', "{$value}%"),
            'ends_with'   => $query->where($column, 'like', "%{$value}"),
            'between'     => is_array($value) && count($value) === 2
                                ? $query->whereBetween($column, $value)
                                : null,
            default       => $query->where($column, '=', $value),
        };
    }

    // ── Matrix support ────────────────────────────────────────────────────────

    protected function addReportJoins($query, array $selectedFields, array $filters, string $tableAlias, array $joins): void
    {
        if ($this->needsFormaJoins($selectedFields, $filters) && !in_array('forma', $joins)) {
            $this->addFormaJoins($query);
        }
    }

    protected function getMatrixLookupFields($field, $tableAlias): array
    {
        return match (true) {
            $field === 'CNES' => [
                'select'  => ['sa.CNES', 'pr.re_cnome as CNES_display'],
                'groupBy' => ['sa.CNES', 'pr.re_cnome'],
            ],
            $field === 'PROC_PRINCIPAL' => [
                'select'  => ['sa.PROC_PRINCIPAL', 'proc.procedimento as PROC_PRINCIPAL_display'],
                'groupBy' => ['sa.PROC_PRINCIPAL', 'proc.procedimento'],
            ],
            $field === 'FINANCIAMENTO' => [
                'select'  => ['sa.FINANCIAMENTO', 'sr.RUB_DC as FINANCIAMENTO_display'],
                'groupBy' => ['sa.FINANCIAMENTO', 'sr.RUB_DC'],
            ],
            $field === 'faixa_etaria_1' => [
                'select'  => [DB::raw('(' . $this->faixaEtaria1Expression() . ') as faixa_etaria_1')],
                'groupBy' => [DB::raw('(' . $this->faixaEtaria1Expression() . ')')],
            ],
            $field === 'faixa_etaria_2' => [
                'select'  => [DB::raw('(' . $this->faixaEtaria2Expression() . ') as faixa_etaria_2')],
                'groupBy' => [DB::raw('(' . $this->faixaEtaria2Expression() . ')')],
            ],
            $field === 'grupo' => [
                'select'  => [DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 2) as grupo')],
                'groupBy' => [DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 2)')],
            ],
            $field === 'descgrupo' => [
                'select'  => ['fg.descricao as descgrupo'],
                'groupBy' => ['fg.descricao'],
            ],
            $field === 'subgrupo' => [
                'select'  => [DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 4) as subgrupo')],
                'groupBy' => [DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 4)')],
            ],
            $field === 'descsubgrupo' => [
                'select'  => ['fs.descricao as descsubgrupo'],
                'groupBy' => ['fs.descricao'],
            ],
            $field === 'forma' => [
                'select'  => [DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 6) as forma')],
                'groupBy' => [DB::raw('SUBSTRING(sa.PROC_PRINCIPAL, 1, 6)')],
            ],
            $field === 'descforma' => [
                'select'  => ['ff.descricao as descforma'],
                'groupBy' => ['ff.descricao'],
            ],
            default => ['select' => [], 'groupBy' => []],
        };
    }

    protected function getMatrixNumericFields($field, $tableAlias): array
    {
        return match ($field) {
            'DIARIAS'         => [DB::raw('SUM(CAST(sa.DIARIAS AS UNSIGNED)) as DIARIAS')],
            'DIARIAS_UTI'     => [DB::raw('SUM(CAST(sa.DIARIAS_UTI AS UNSIGNED)) as DIARIAS_UTI')],
            'VALOR_TOTAL_AIH' => [DB::raw('SUM(CAST(sa.VALOR_TOTAL_AIH AS DECIMAL(12,2))) as VALOR_TOTAL_AIH')],
            'IDADE'           => [DB::raw('AVG(CAST(sa.IDADE AS UNSIGNED)) as IDADE')],
            'qtd_aih'         => [DB::raw('COUNT(DISTINCT sa.AIH) as qtd_aih')],
            default           => [],
        };
    }

    protected function getNumericValue($item, $field)
    {
        return (float) ($item->{$field} ?? 0);
    }

    protected function getGroupKeyPart($item, $field)
    {
        if (in_array($field, $this->getFormaFieldIds(), true)) {
            return $item->{$field} ?? '';
        }

        if (in_array($field, $this->getFaixaEtariaFieldIds(), true)) {
            return $item->{$field} ?? '';
        }

        return match ($field) {
            'CNES'           => ($item->CNES ?? '') . '|' . ($item->CNES_display ?? ''),
            'PROC_PRINCIPAL' => ($item->PROC_PRINCIPAL ?? '') . '|' . ($item->PROC_PRINCIPAL_display ?? ''),
            'FINANCIAMENTO'  => ($item->FINANCIAMENTO ?? '') . '|' . ($item->FINANCIAMENTO_display ?? ''),
            default          => parent::getGroupKeyPart($item, $field),
        };
    }

    // ── Totals & formatting ───────────────────────────────────────────────────

    protected function calculateTotals($data, $selectedFields)
    {
        $totals = [];

        if (in_array('qtd_aih', $selectedFields, true)) {
            $totals['Total AIH'] = number_format($data->sum(fn ($r) => $r->qtd_aih ?? 0), 0, ',', '.');
        }
        if (in_array('DIARIAS', $selectedFields, true)) {
            $totals['Total Diárias'] = number_format($data->sum(fn ($r) => $r->DIARIAS ?? 0), 0, ',', '.');
        }
        if (in_array('DIARIAS_UTI', $selectedFields, true)) {
            $totals['Total Diárias UTI'] = number_format($data->sum(fn ($r) => $r->DIARIAS_UTI ?? 0), 0, ',', '.');
        }
        if (in_array('VALOR_TOTAL_AIH', $selectedFields, true)) {
            $totals['Valor Total AIH'] = 'R$ ' . number_format($data->sum(fn ($r) => $r->VALOR_TOTAL_AIH ?? 0), 2, ',', '.');
        }

        return $totals;
    }

    protected function formatFieldValue($row, $field, $fieldConfig)
    {
        if ($field === 'COMPETENCIA') {
            return ['Competência' => $row->COMPETENCIA ?? ''];
        }
        if ($field === 'CNES') {
            return ['CNES' => $row->CNES ?? '', 'Prestador' => $row->CNES_display ?? ''];
        }
        if ($field === 'PROC_PRINCIPAL') {
            return ['Proc. Principal' => $row->PROC_PRINCIPAL ?? '', 'Desc. Procedimento' => $row->PROC_PRINCIPAL_display ?? ''];
        }
        if ($field === 'FINANCIAMENTO') {
            return ['Financiamento' => $row->FINANCIAMENTO ?? '', 'Desc. Financiamento' => $row->FINANCIAMENTO_display ?? ''];
        }
        if (in_array($field, $this->getFaixaEtariaFieldIds(), true)) {
            return [$fieldConfig['label'] => $row->{$field} ?? ''];
        }
        if (in_array($field, $this->getFormaFieldIds(), true)) {
            return [$fieldConfig['label'] => $row->{$field} ?? ''];
        }
        if ($field === 'proc_principal_descricao') {
            return []; // filter-only
        }

        return parent::formatFieldValue($row, $field, $fieldConfig);
    }
}
