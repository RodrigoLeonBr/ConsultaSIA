<?php

namespace App\Http\Controllers;

use App\Exports\RelatorioAihPaExport;
use App\Exports\MatrixReportExport;
use App\Http\Controllers\Concerns\HasMatrixReport;
use Illuminate\Support\Facades\DB;

class RelatorioAihPaController extends BaseRelatorioController
{
    use HasMatrixReport;

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

    protected function getTableName(): string
    {
        return 's_aih_pa';
    }

    protected function getTableAlias(): string
    {
        return 'sp';
    }

    protected function getCompetenciaField(): string
    {
        return 'COMPETENCIA';
    }

    protected function getExportClass(): string
    {
        return RelatorioAihPaExport::class;
    }

    protected function getMatrixExportClass(): string
    {
        return MatrixReportExport::class;
    }

    protected function getPdfView(): string
    {
        return 'relatorios.aih-pa.pdf';
    }

    protected function getReportTitle(): string
    {
        return 'Relatório de Procedimentos AIH';
    }

    protected function getExportFilename($extension)
    {
        return 'relatorio_aih_pa.' . $extension;
    }

    protected function getMatrixExportFilename()
    {
        return 'relatorio_aih_pa_matriz.xlsx';
    }

    protected function getPrestadorField(): string
    {
        return 'CNES';
    }

    protected function getCboField(): string
    {
        return 'CBO_PROFISSIONAL';
    }

    protected function getRubField(): ?string
    {
        return 'FINANCIAMENTO_DETALHE';
    }

    protected function getProcedimentoFieldForCismetro(): string
    {
        return 'PROC_DETALHADO';
    }

    protected function getMatrixLookupFields($field, $tableAlias): array
    {
        return match ($field) {
            'CNES' => [
                'select'  => ['sp.CNES', 'pr.re_cnome as CNES_display'],
                'groupBy' => ['sp.CNES', 'pr.re_cnome'],
            ],
            'PROC_DETALHADO' => [
                'select'  => ['sp.PROC_DETALHADO', 'proc.procedimento as PROC_DETALHADO_display'],
                'groupBy' => ['sp.PROC_DETALHADO', 'proc.procedimento'],
            ],
            'CBO_PROFISSIONAL' => [
                'select'  => ['sp.CBO_PROFISSIONAL', 'cb.DS_CBO as CBO_PROFISSIONAL_display'],
                'groupBy' => ['sp.CBO_PROFISSIONAL', 'cb.DS_CBO'],
            ],
            'FINANCIAMENTO_DETALHE' => [
                'select'  => ['sp.FINANCIAMENTO_DETALHE', 'sr.RUB_DC as FINANCIAMENTO_DETALHE_display'],
                'groupBy' => ['sp.FINANCIAMENTO_DETALHE', 'sr.RUB_DC'],
            ],
            'AIH' => [
                'select'  => ['sp.AIH'],
                'groupBy' => ['sp.AIH'],
            ],
            default => ['select' => [], 'groupBy' => []],
        };
    }

    protected function getMatrixNumericFields($field, $tableAlias): array
    {
        return match ($field) {
            'QUANTIDADE'  => [DB::raw('SUM(CAST(sp.QUANTIDADE AS UNSIGNED)) as QUANTIDADE')],
            'VALOR_ITEM'  => [DB::raw('SUM(CAST(sp.VALOR_ITEM AS DECIMAL(12,2))) as VALOR_ITEM')],
            default       => [],
        };
    }

    protected function getNumericValue($item, $field)
    {
        return (float) ($item->{$field} ?? 0);
    }

    protected function getGroupKeyPart($item, $field)
    {
        return match ($field) {
            'CNES'                  => ($item->CNES ?? '') . '|' . ($item->CNES_display ?? ''),
            'PROC_DETALHADO'        => ($item->PROC_DETALHADO ?? '') . '|' . ($item->PROC_DETALHADO_display ?? ''),
            'CBO_PROFISSIONAL'      => ($item->CBO_PROFISSIONAL ?? '') . '|' . ($item->CBO_PROFISSIONAL_display ?? ''),
            'FINANCIAMENTO_DETALHE' => ($item->FINANCIAMENTO_DETALHE ?? '') . '|' . ($item->FINANCIAMENTO_DETALHE_display ?? ''),
            default                 => parent::getGroupKeyPart($item, $field),
        };
    }

    protected function getDefaultNumericField(): ?string
    {
        return 'QUANTIDADE';
    }

    protected function buildQuery($selectedFields, $filters, $groupBy = true)
    {
        $query = DB::table('s_aih_pa as sp');
        $joins = [];

        $allFields = array_merge(
            $selectedFields,
            array_column($filters, 'field')
        );

        foreach ($allFields as $field) {
            $cfg = $this->getFieldConfig($field);
            if ($cfg && $cfg['type'] === 'lookup' && !in_array($cfg['lookup_table'], $joins, true)) {
                $this->addLookupJoin($query, $field, $cfg);
                $joins[] = $cfg['lookup_table'];
            }
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

    private function addLookupJoin($query, string $field, array $cfg): void
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

    protected function calculateTotals($data, $selectedFields)
    {
        $totals = [];

        if (in_array('QUANTIDADE', $selectedFields, true)) {
            $totals['Total Quantidade'] = number_format(
                $data->sum(fn ($r) => $r->QUANTIDADE ?? 0), 0, ',', '.'
            );
        }

        if (in_array('VALOR_ITEM', $selectedFields, true)) {
            $totals['Total Valor'] = 'R$ ' . number_format(
                $data->sum(fn ($r) => $r->VALOR_ITEM ?? 0), 2, ',', '.'
            );
        }

        return $totals;
    }

    protected function formatFieldValue($row, $field, $fieldConfig)
    {
        if ($field === 'COMPETENCIA') {
            return ['Competência' => $row->COMPETENCIA ?? ''];
        }

        if ($field === 'CNES') {
            return [
                'CNES'      => $row->CNES ?? '',
                'Prestador' => $row->CNES_display ?? '',
            ];
        }

        if ($field === 'PROC_DETALHADO') {
            return [
                'Proc. Detalhado'  => $row->PROC_DETALHADO ?? '',
                'Desc. Procedimento' => $row->PROC_DETALHADO_display ?? '',
            ];
        }

        if ($field === 'CBO_PROFISSIONAL') {
            return [
                'CBO'      => $row->CBO_PROFISSIONAL ?? '',
                'Desc. CBO' => $row->CBO_PROFISSIONAL_display ?? '',
            ];
        }

        if ($field === 'FINANCIAMENTO_DETALHE') {
            return [
                'Financiamento'       => $row->FINANCIAMENTO_DETALHE ?? '',
                'Desc. Financiamento' => $row->FINANCIAMENTO_DETALHE_display ?? '',
            ];
        }

        return parent::formatFieldValue($row, $field, $fieldConfig);
    }
}
