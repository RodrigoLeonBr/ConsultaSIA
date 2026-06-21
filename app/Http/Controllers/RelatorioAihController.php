<?php

namespace App\Http\Controllers;

use App\Exports\RelatorioAihExport;
use App\Exports\MatrixReportExport;
use App\Http\Controllers\Concerns\HasMatrixReport;
use Illuminate\Support\Facades\DB;

class RelatorioAihController extends BaseRelatorioController
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

    protected function getTableName(): string
    {
        return 's_aih';
    }

    protected function getTableAlias(): string
    {
        return 'sa';
    }

    protected function getCompetenciaField(): string
    {
        return 'COMPETENCIA';
    }

    protected function getExportClass(): string
    {
        return RelatorioAihExport::class;
    }

    protected function getMatrixExportClass(): string
    {
        return MatrixReportExport::class;
    }

    protected function getPdfView(): string
    {
        return 'relatorios.aih.pdf';
    }

    protected function getReportTitle(): string
    {
        return 'Relatório de Internações AIH';
    }

    protected function getExportFilename($extension)
    {
        return 'relatorio_aih.' . $extension;
    }

    protected function getMatrixExportFilename()
    {
        return 'relatorio_aih_matriz.xlsx';
    }

    protected function getPrestadorField(): string
    {
        return 'CNES';
    }

    protected function getCboField(): string
    {
        return '';
    }

    protected function getRubField(): ?string
    {
        return 'FINANCIAMENTO';
    }

    protected function getProcedimentoFieldForCismetro(): string
    {
        return 'PROC_PRINCIPAL';
    }

    protected function getMatrixLookupFields($field, $tableAlias): array
    {
        return match ($field) {
            'CNES' => [
                'select'  => ['sa.CNES', 'pr.re_cnome as CNES_display'],
                'groupBy' => ['sa.CNES', 'pr.re_cnome'],
            ],
            'PROC_PRINCIPAL' => [
                'select'  => ['sa.PROC_PRINCIPAL', 'proc.procedimento as PROC_PRINCIPAL_display'],
                'groupBy' => ['sa.PROC_PRINCIPAL', 'proc.procedimento'],
            ],
            'FINANCIAMENTO' => [
                'select'  => ['sa.FINANCIAMENTO', 'sr.RUB_DC as FINANCIAMENTO_display'],
                'groupBy' => ['sa.FINANCIAMENTO', 'sr.RUB_DC'],
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
        return match ($field) {
            'CNES'          => ($item->CNES ?? '') . '|' . ($item->CNES_display ?? ''),
            'PROC_PRINCIPAL'=> ($item->PROC_PRINCIPAL ?? '') . '|' . ($item->PROC_PRINCIPAL_display ?? ''),
            'FINANCIAMENTO' => ($item->FINANCIAMENTO ?? '') . '|' . ($item->FINANCIAMENTO_display ?? ''),
            default         => parent::getGroupKeyPart($item, $field),
        };
    }

    protected function getDefaultNumericField(): ?string
    {
        return 'qtd_aih';
    }

    protected function buildQuery($selectedFields, $filters, $groupBy = true)
    {
        $query = DB::table('s_aih as sa');
        $joins = [];

        // Determine joins needed from selected fields and filters
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

        return $query;
    }

    private function addLookupJoin($query, string $field, array $cfg): void
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

    protected function calculateTotals($data, $selectedFields)
    {
        $totals = [];

        if (in_array('qtd_aih', $selectedFields, true)) {
            $totals['Total AIH'] = number_format(
                $data->sum(fn ($r) => $r->qtd_aih ?? 0), 0, ',', '.'
            );
        }

        if (in_array('DIARIAS', $selectedFields, true)) {
            $totals['Total Diárias'] = number_format(
                $data->sum(fn ($r) => $r->DIARIAS ?? 0), 0, ',', '.'
            );
        }

        if (in_array('DIARIAS_UTI', $selectedFields, true)) {
            $totals['Total Diárias UTI'] = number_format(
                $data->sum(fn ($r) => $r->DIARIAS_UTI ?? 0), 0, ',', '.'
            );
        }

        if (in_array('VALOR_TOTAL_AIH', $selectedFields, true)) {
            $totals['Valor Total AIH'] = 'R$ ' . number_format(
                $data->sum(fn ($r) => $r->VALOR_TOTAL_AIH ?? 0), 2, ',', '.'
            );
        }

        return $totals;
    }

    protected function formatFieldValue($row, $field, $fieldConfig)
    {
        // Competência AAAAMM → formato legível YYYY-MM
        if ($field === 'COMPETENCIA') {
            return ['Competência' => $row->COMPETENCIA ?? ''];
        }

        // Lookup fields: show code + display
        if ($field === 'CNES') {
            return [
                'CNES'      => $row->CNES ?? '',
                'Prestador' => $row->CNES_display ?? '',
            ];
        }

        if ($field === 'PROC_PRINCIPAL') {
            return [
                'Proc. Principal'  => $row->PROC_PRINCIPAL ?? '',
                'Desc. Procedimento' => $row->PROC_PRINCIPAL_display ?? '',
            ];
        }

        if ($field === 'FINANCIAMENTO') {
            return [
                'Financiamento' => $row->FINANCIAMENTO ?? '',
                'Desc. Financiamento' => $row->FINANCIAMENTO_display ?? '',
            ];
        }

        return parent::formatFieldValue($row, $field, $fieldConfig);
    }
}
