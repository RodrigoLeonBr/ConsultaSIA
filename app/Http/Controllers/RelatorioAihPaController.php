<?php

namespace App\Http\Controllers;

use App\Exports\RelatorioAihPaExport;
use App\Exports\MatrixReportExport;
use App\Http\Controllers\Concerns\HasMatrixReport;
use Illuminate\Support\Facades\DB;

class RelatorioAihPaController extends BaseRelatorioController
{
    use HasMatrixReport;

    // ── Field ID helpers ──────────────────────────────────────────────────────

    protected function getFormaFieldIds(): array
    {
        return ['grupo', 'descgrupo', 'subgrupo', 'descsubgrupo', 'forma', 'descforma'];
    }

    // ── Field definitions ─────────────────────────────────────────────────────

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
            'AIH' => [
                'label'     => 'Número AIH',
                'type'      => 'text',
                'operators' => ['='],
            ],
            'PROC_DETALHADO' => [
                'label'          => 'Procedimento',
                'type'           => 'lookup',
                'lookup_table'   => 'procedimento',
                'lookup_key'     => 'codigo',
                'lookup_display' => 'procedimento',
                'operators'      => ['='],
            ],
            'proc_detalhado_descricao' => [
                'label'     => 'Descrição do Procedimento',
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
            'CBO_PROFISSIONAL' => [
                'label'          => 'CBO Profissional',
                'type'           => 'lookup',
                'lookup_table'   => 'cbo',
                'lookup_key'     => 'CBO',
                'lookup_display' => 'DS_CBO',
                'operators'      => ['='],
            ],
            'FINANCIAMENTO_DETALHE' => [
                'label'          => 'Financiamento',
                'type'           => 'lookup',
                'lookup_table'   => 's_rub',
                'lookup_key'     => 'RUB_ID',
                'lookup_display' => 'RUB_DC',
                'operators'      => ['='],
            ],
            'QUANTIDADE' => [
                'label'     => 'Quantidade (soma)',
                'type'      => 'number',
                'operators' => ['=', '>', '<', '>=', '<='],
            ],
            'VALOR_ITEM' => [
                'label'     => 'Valor (soma)',
                'type'      => 'currency',
                'operators' => ['=', '>', '<', '>=', '<='],
            ],
        ];
    }

    // ── Controller interface ──────────────────────────────────────────────────

    public function index()
    {
        return view('relatorios.aih-pa.index');
    }

    public function getFields()
    {
        return response()->json(['fields' => $this->fieldDefs()]);
    }

    protected function getFieldConfig($field)
    {
        return $this->fieldDefs()[$field] ?? null;
    }

    protected function getTableName(): string { return 's_aih_pa'; }
    protected function getTableAlias(): string { return 'sp'; }
    protected function getCompetenciaField(): string { return 'COMPETENCIA'; }
    protected function getExportClass(): string { return RelatorioAihPaExport::class; }
    protected function getMatrixExportClass(): string { return MatrixReportExport::class; }
    protected function getPdfView(): string { return 'relatorios.aih-pa.pdf'; }
    protected function getReportTitle(): string { return 'Relatório de Procedimentos AIH'; }
    protected function getExportFilename($extension) { return 'relatorio_aih_pa.' . $extension; }
    protected function getMatrixExportFilename() { return 'relatorio_aih_pa_matriz.xlsx'; }
    protected function getPrestadorField(): string { return 'CNES'; }
    protected function getCboField(): string { return 'CBO_PROFISSIONAL'; }
    protected function getRubField(): ?string { return 'FINANCIAMENTO_DETALHE'; }
    protected function getProcedimentoFieldForCismetro(): string { return 'PROC_DETALHADO'; }
    protected function getDefaultNumericField(): ?string { return 'QUANTIDADE'; }

    // ── Join helpers ──────────────────────────────────────────────────────────

    private function needsFormaJoins(array $selectedFields, array $filters): bool
    {
        $all = array_merge($selectedFields, array_column($filters, 'field'));
        return (bool) array_intersect($this->getFormaFieldIds(), $all);
    }

    private function addFormaJoins($query): void
    {
        // s_aih_pa uses utf8mb4_unicode_ci; forma uses utf8mb4_general_ci → force collate
        $query->leftJoin('forma as fg', function ($join) {
            $join->on(DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 2) COLLATE utf8mb4_general_ci'), '=', DB::raw('fg.grupo COLLATE utf8mb4_general_ci'))
                 ->where(DB::raw('fg.subgrupo COLLATE utf8mb4_general_ci'), '=', DB::raw('CONCAT(SUBSTRING(sp.PROC_DETALHADO, 1, 2), "00") COLLATE utf8mb4_general_ci'))
                 ->where(DB::raw('fg.forma COLLATE utf8mb4_general_ci'),    '=', DB::raw('CONCAT(SUBSTRING(sp.PROC_DETALHADO, 1, 2), "0000") COLLATE utf8mb4_general_ci'));
        });
        $query->leftJoin('forma as fs', function ($join) {
            $join->on(DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 4) COLLATE utf8mb4_general_ci'), '=', DB::raw('fs.subgrupo COLLATE utf8mb4_general_ci'))
                 ->where(DB::raw('fs.forma COLLATE utf8mb4_general_ci'), '=', DB::raw('CONCAT(SUBSTRING(sp.PROC_DETALHADO, 1, 4), "00") COLLATE utf8mb4_general_ci'));
        });
        $query->leftJoin('forma as ff', function ($join) {
            $join->on(DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 6) COLLATE utf8mb4_general_ci'), '=', DB::raw('ff.forma COLLATE utf8mb4_general_ci'));
        });
    }

    private function addLookupJoin($query, string $field): void
    {
        switch ($field) {
            case 'CNES':
                $query->leftJoin('prestador as pr', function ($join) {
                    $join->on(DB::raw('sp.CNES COLLATE utf8mb4_unicode_ci'), '=', DB::raw('pr.re_cunid COLLATE utf8mb4_unicode_ci'));
                });
                break;
            case 'PROC_DETALHADO':
                $query->leftJoin('procedimento as proc', function ($join) {
                    $join->on(DB::raw('sp.PROC_DETALHADO COLLATE utf8mb4_unicode_ci'), '=', DB::raw('proc.codigo COLLATE utf8mb4_unicode_ci'));
                });
                break;
            case 'CBO_PROFISSIONAL':
                $query->leftJoin('cbo as cb', function ($join) {
                    $join->on(DB::raw('sp.CBO_PROFISSIONAL COLLATE utf8mb4_unicode_ci'), '=', DB::raw('cb.CBO COLLATE utf8mb4_unicode_ci'));
                });
                break;
            case 'FINANCIAMENTO_DETALHE':
                $query->leftJoin('s_rub as sr', function ($join) {
                    $join->on(DB::raw('sp.FINANCIAMENTO_DETALHE COLLATE utf8mb4_unicode_ci'), '=', DB::raw('sr.RUB_ID COLLATE utf8mb4_unicode_ci'));
                });
                break;
        }
    }

    // ── Query builder ─────────────────────────────────────────────────────────

    protected function buildQuery($selectedFields, $filters, $groupBy = true)
    {
        $query = DB::table('s_aih_pa as sp');
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

        // proc_detalhado_descricao needs procedimento join
        if (in_array('proc_detalhado_descricao', $allFields, true) && !in_array('procedimento', $joins, true)) {
            $this->addLookupJoin($query, 'PROC_DETALHADO');
            $joins[] = 'procedimento';
        }

        // Forma joins
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
                    $selectFields[]  = DB::raw("CONCAT(SUBSTRING(sp.COMPETENCIA,1,4),'-',SUBSTRING(sp.COMPETENCIA,5,2)) as COMPETENCIA");
                    $groupByFields[] = 'sp.COMPETENCIA';
                    break;

                case 'CNES':
                    $selectFields[]  = 'sp.CNES';
                    $selectFields[]  = 'pr.re_cnome as CNES_display';
                    $groupByFields[] = 'sp.CNES';
                    $groupByFields[] = 'pr.re_cnome';
                    break;

                case 'AIH':
                    $selectFields[]  = 'sp.AIH';
                    $groupByFields[] = 'sp.AIH';
                    break;

                case 'PROC_DETALHADO':
                    $selectFields[]  = 'sp.PROC_DETALHADO';
                    $selectFields[]  = 'proc.procedimento as PROC_DETALHADO_display';
                    $groupByFields[] = 'sp.PROC_DETALHADO';
                    $groupByFields[] = 'proc.procedimento';
                    break;

                case 'proc_detalhado_descricao':
                    continue 2; // filter-only

                case 'grupo':
                    $selectFields[]  = DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 2) as grupo');
                    $groupByFields[] = DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 2)');
                    break;

                case 'descgrupo':
                    $selectFields[]  = 'fg.descricao as descgrupo';
                    $groupByFields[] = 'fg.descricao';
                    break;

                case 'subgrupo':
                    $selectFields[]  = DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 4) as subgrupo');
                    $groupByFields[] = DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 4)');
                    break;

                case 'descsubgrupo':
                    $selectFields[]  = 'fs.descricao as descsubgrupo';
                    $groupByFields[] = 'fs.descricao';
                    break;

                case 'forma':
                    $selectFields[]  = DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 6) as forma');
                    $groupByFields[] = DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 6)');
                    break;

                case 'descforma':
                    $selectFields[]  = 'ff.descricao as descforma';
                    $groupByFields[] = 'ff.descricao';
                    break;

                case 'CBO_PROFISSIONAL':
                    $selectFields[]  = 'sp.CBO_PROFISSIONAL';
                    $selectFields[]  = 'cb.DS_CBO as CBO_PROFISSIONAL_display';
                    $groupByFields[] = 'sp.CBO_PROFISSIONAL';
                    $groupByFields[] = 'cb.DS_CBO';
                    break;

                case 'FINANCIAMENTO_DETALHE':
                    $selectFields[]  = 'sp.FINANCIAMENTO_DETALHE';
                    $selectFields[]  = 'sr.RUB_DC as FINANCIAMENTO_DETALHE_display';
                    $groupByFields[] = 'sp.FINANCIAMENTO_DETALHE';
                    $groupByFields[] = 'sr.RUB_DC';
                    break;

                case 'QUANTIDADE':
                    $selectFields[] = DB::raw('SUM(CAST(sp.QUANTIDADE AS UNSIGNED)) as QUANTIDADE');
                    break;

                case 'VALOR_ITEM':
                    $selectFields[] = DB::raw('SUM(CAST(sp.VALOR_ITEM AS DECIMAL(12,2))) as VALOR_ITEM');
                    break;

                default:
                    $selectFields[]  = "sp.{$field}";
                    $groupByFields[] = "sp.{$field}";
            }
        }

        $query->select($selectFields);

        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        if ($groupBy && !empty($groupByFields)) {
            $query->groupBy($groupByFields);
        }

        return $query;
    }

    // ── Filter override ───────────────────────────────────────────────────────

    protected function applyFilter($query, $filter)
    {
        $field    = $filter['field'];
        $operator = $filter['operator'];
        $value    = $filter['value'];

        // proc_detalhado_descricao → subquery
        if ($field === 'proc_detalhado_descricao') {
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
                : $query->whereIn('sp.PROC_DETALHADO', $codes);
            return;
        }

        // grupo / subgrupo / forma → SUBSTRING filter
        if (in_array($field, ['grupo', 'subgrupo', 'forma'], true)) {
            $len  = match ($field) { 'grupo' => 2, 'subgrupo' => 4, 'forma' => 6 };
            $expr = DB::raw("SUBSTRING(sp.PROC_DETALHADO, 1, {$len})");
            $this->applySubstringFilter($query, $expr, $operator, $value);
            return;
        }

        if ($field === 'descgrupo') { $this->applySubstringFilter($query, 'fg.descricao', $operator, $value); return; }
        if ($field === 'descsubgrupo') { $this->applySubstringFilter($query, 'fs.descricao', $operator, $value); return; }
        if ($field === 'descforma') { $this->applySubstringFilter($query, 'ff.descricao', $operator, $value); return; }

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

    protected function getMatrixLookupFields($field, $tableAlias): array
    {
        return match (true) {
            $field === 'CNES' => [
                'select'  => ['sp.CNES', 'pr.re_cnome as CNES_display'],
                'groupBy' => ['sp.CNES', 'pr.re_cnome'],
            ],
            $field === 'PROC_DETALHADO' => [
                'select'  => ['sp.PROC_DETALHADO', 'proc.procedimento as PROC_DETALHADO_display'],
                'groupBy' => ['sp.PROC_DETALHADO', 'proc.procedimento'],
            ],
            $field === 'CBO_PROFISSIONAL' => [
                'select'  => ['sp.CBO_PROFISSIONAL', 'cb.DS_CBO as CBO_PROFISSIONAL_display'],
                'groupBy' => ['sp.CBO_PROFISSIONAL', 'cb.DS_CBO'],
            ],
            $field === 'FINANCIAMENTO_DETALHE' => [
                'select'  => ['sp.FINANCIAMENTO_DETALHE', 'sr.RUB_DC as FINANCIAMENTO_DETALHE_display'],
                'groupBy' => ['sp.FINANCIAMENTO_DETALHE', 'sr.RUB_DC'],
            ],
            $field === 'AIH' => [
                'select'  => ['sp.AIH'],
                'groupBy' => ['sp.AIH'],
            ],
            $field === 'grupo' => [
                'select'  => [DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 2) as grupo')],
                'groupBy' => [DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 2)')],
            ],
            $field === 'descgrupo' => [
                'select'  => ['fg.descricao as descgrupo'],
                'groupBy' => ['fg.descricao'],
            ],
            $field === 'subgrupo' => [
                'select'  => [DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 4) as subgrupo')],
                'groupBy' => [DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 4)')],
            ],
            $field === 'descsubgrupo' => [
                'select'  => ['fs.descricao as descsubgrupo'],
                'groupBy' => ['fs.descricao'],
            ],
            $field === 'forma' => [
                'select'  => [DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 6) as forma')],
                'groupBy' => [DB::raw('SUBSTRING(sp.PROC_DETALHADO, 1, 6)')],
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
            'QUANTIDADE' => [DB::raw('SUM(CAST(sp.QUANTIDADE AS UNSIGNED)) as QUANTIDADE')],
            'VALOR_ITEM' => [DB::raw('SUM(CAST(sp.VALOR_ITEM AS DECIMAL(12,2))) as VALOR_ITEM')],
            default      => [],
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

        return match ($field) {
            'CNES'                  => ($item->CNES ?? '') . '|' . ($item->CNES_display ?? ''),
            'PROC_DETALHADO'        => ($item->PROC_DETALHADO ?? '') . '|' . ($item->PROC_DETALHADO_display ?? ''),
            'CBO_PROFISSIONAL'      => ($item->CBO_PROFISSIONAL ?? '') . '|' . ($item->CBO_PROFISSIONAL_display ?? ''),
            'FINANCIAMENTO_DETALHE' => ($item->FINANCIAMENTO_DETALHE ?? '') . '|' . ($item->FINANCIAMENTO_DETALHE_display ?? ''),
            default                 => parent::getGroupKeyPart($item, $field),
        };
    }

    // ── Totals & formatting ───────────────────────────────────────────────────

    protected function calculateTotals($data, $selectedFields)
    {
        $totals = [];

        if (in_array('QUANTIDADE', $selectedFields, true)) {
            $totals['Total Quantidade'] = number_format($data->sum(fn ($r) => $r->QUANTIDADE ?? 0), 0, ',', '.');
        }
        if (in_array('VALOR_ITEM', $selectedFields, true)) {
            $totals['Total Valor'] = 'R$ ' . number_format($data->sum(fn ($r) => $r->VALOR_ITEM ?? 0), 2, ',', '.');
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
        if ($field === 'PROC_DETALHADO') {
            return ['Proc. Detalhado' => $row->PROC_DETALHADO ?? '', 'Desc. Procedimento' => $row->PROC_DETALHADO_display ?? ''];
        }
        if ($field === 'CBO_PROFISSIONAL') {
            return ['CBO' => $row->CBO_PROFISSIONAL ?? '', 'Desc. CBO' => $row->CBO_PROFISSIONAL_display ?? ''];
        }
        if ($field === 'FINANCIAMENTO_DETALHE') {
            return ['Financiamento' => $row->FINANCIAMENTO_DETALHE ?? '', 'Desc. Financiamento' => $row->FINANCIAMENTO_DETALHE_display ?? ''];
        }
        if (in_array($field, $this->getFormaFieldIds(), true)) {
            return [$fieldConfig['label'] => $row->{$field} ?? ''];
        }
        if ($field === 'proc_detalhado_descricao') {
            return []; // filter-only
        }

        return parent::formatFieldValue($row, $field, $fieldConfig);
    }
}
