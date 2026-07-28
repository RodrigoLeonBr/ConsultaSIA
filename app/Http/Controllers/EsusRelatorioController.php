<?php

namespace App\Http\Controllers;

use App\Exports\MatrixReportExport;
use App\Exports\RelatorioExport;
use App\Http\Controllers\Concerns\HasMatrixReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Relatório dinâmico da produção e-SUS (SIGTAP), nos moldes do /relatorios,
 * porém sem valores financeiros. Fonte: tabela s_esus.
 * Collation: s_esus é utf8mb4_unicode_ci; prestador/procedimento/forma são
 * utf8mb4_general_ci → joins forçam COLLATE utf8mb4_general_ci.
 */
class EsusRelatorioController extends BaseRelatorioController
{
    use HasMatrixReport;

    private const COLL = 'COLLATE utf8mb4_general_ci';

    protected function getAllFieldConfigs(): array
    {
        return [
            'competencia' => [
                'label' => 'Competência',
                'type' => 'date',
                'operators' => ['=', '>=', '<=', 'between'],
            ],
            'cnes' => [
                'label' => 'Prestador (CNES)',
                'type' => 'lookup',
                'lookup_table' => 'prestador',
                'lookup_key' => 're_cunid',
                'lookup_display' => 're_cnome',
                'operators' => ['=', 'in'],
            ],
            'unidade' => [
                'label' => 'Unidade (e-SUS)',
                'type' => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'esus_ativo' => [
                'label' => 'Considerar e-SUS',
                'type' => 'lookup',
                'lookup_static' => 'esus_ativo',
                'operators' => ['='],
            ],
            'tipo_relatorio' => [
                'label' => 'Tipo de Relatório (e-SUS)',
                'type' => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'bloco' => [
                'label' => 'Bloco',
                'type' => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'descricao_esus' => [
                'label' => 'Descrição e-SUS',
                'type' => 'text',
                'operators' => ['=', 'like', 'starts_with', 'ends_with'],
            ],
            'codigo_sigtap' => [
                'label' => 'Procedimento (SIGTAP)',
                'type' => 'lookup',
                'lookup_table' => 'procedimento',
                'lookup_key' => 'codigo',
                'lookup_display' => 'procedimento',
                'operators' => ['=', 'in', 'like'],
            ],
            'descricao_sigtap' => [
                'label' => 'Descrição SIGTAP',
                'type' => 'text',
                'operators' => ['=', 'like', 'starts_with', 'ends_with'],
            ],
            'grupo' => [
                'label' => 'Grupo',
                'type' => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'descgrupo' => [
                'label' => 'Descrição do Grupo',
                'type' => 'text',
                'operators' => [],
            ],
            'subgrupo' => [
                'label' => 'Subgrupo',
                'type' => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'descsubgrupo' => [
                'label' => 'Descrição do Subgrupo',
                'type' => 'text',
                'operators' => [],
            ],
            'forma' => [
                'label' => 'Forma de Organização',
                'type' => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'descforma' => [
                'label' => 'Descrição da Forma',
                'type' => 'text',
                'operators' => ['=', 'like', 'starts_with'],
            ],
            'quantidade' => [
                'label' => 'Quantidade',
                'type' => 'number',
                'operators' => ['=', '>', '<', '>=', '<='],
            ],
        ];
    }

    // ── Controller interface ─────────────────────────────────────────────────

    public function index()
    {
        return view('relatorios.esus.index');
    }

    public function getFields()
    {
        return response()->json(['fields' => $this->getAllFieldConfigs()]);
    }

    public function getLookupData(Request $request)
    {
        if ($request->get('field') === 'esus_ativo') {
            $search = mb_strtolower((string) $request->get('search', ''));
            $options = [
                ['value' => '1', 'label' => 'Sim'],
                ['value' => '0', 'label' => 'Não'],
            ];

            return response()->json(array_values(array_filter(
                $options,
                fn ($o) => $search === '' || str_contains(mb_strtolower($o['label']), $search)
            )));
        }

        return parent::getLookupData($request);
    }

    protected function getFieldConfig($field)
    {
        return $this->getAllFieldConfigs()[$field] ?? null;
    }

    protected function getTableName(): string
    {
        return 's_esus';
    }

    protected function getTableAlias(): string
    {
        return 'es';
    }

    protected function getCompetenciaField(): string
    {
        return 'competencia';
    }

    protected function getExportClass(): string
    {
        return RelatorioExport::class;
    }

    protected function getMatrixExportClass(): string
    {
        return MatrixReportExport::class;
    }

    protected function getPdfView(): string
    {
        return 'relatorios.esus.pdf';
    }

    protected function getReportTitle(): string
    {
        return 'Relatório de Produção e-SUS (SIGTAP)';
    }

    protected function getExportFilename($extension)
    {
        return 'relatorio_esus.'.$extension;
    }

    protected function getMatrixExportFilename()
    {
        return 'relatorio_esus_matriz.xlsx';
    }

    protected function getPrestadorField(): string
    {
        return 'cnes';
    }

    protected function getCboField(): string
    {
        return '';
    }

    protected function getProcedimentoFieldForCismetro(): string
    {
        return 'codigo_sigtap';
    }

    protected function getDefaultNumericField(): ?string
    {
        return 'quantidade';
    }

    protected function getMatrixSplitCandidates(): array
    {
        return ['cnes', 'tipo_relatorio'];
    }

    // ── Joins ─────────────────────────────────────────────────────────────────

    private function needsPrestador(array $all): bool
    {
        return (bool) array_intersect(['cnes', 'esus_ativo', 'filter_esus_ativo'], $all);
    }

    private function needsFormaJoins(array $all): bool
    {
        return (bool) array_intersect(['grupo', 'descgrupo', 'subgrupo', 'descsubgrupo', 'forma', 'descforma'], $all);
    }

    private function addPrestadorJoin($query): void
    {
        $query->leftJoin('prestador as pr', function ($join) {
            $join->on(DB::raw('es.cnes '.self::COLL), '=', DB::raw('pr.re_cunid '.self::COLL));
        });
    }

    private function addProcedimentoJoin($query): void
    {
        $query->leftJoin('procedimento as pc', function ($join) {
            $join->on(DB::raw('es.codigo_sigtap '.self::COLL), '=', DB::raw('pc.codigo '.self::COLL));
        });
    }

    private function addFormaJoins($query): void
    {
        $c = self::COLL;
        $query->leftJoin('forma as fg', function ($join) use ($c) {
            $join->on(DB::raw("SUBSTRING(es.codigo_sigtap, 1, 2) {$c}"), '=', DB::raw("fg.grupo {$c}"))
                ->where(DB::raw("fg.subgrupo {$c}"), '=', DB::raw("CONCAT(SUBSTRING(es.codigo_sigtap, 1, 2), '00') {$c}"))
                ->where(DB::raw("fg.forma {$c}"), '=', DB::raw("CONCAT(SUBSTRING(es.codigo_sigtap, 1, 2), '0000') {$c}"));
        });
        $query->leftJoin('forma as fs', function ($join) use ($c) {
            $join->on(DB::raw("SUBSTRING(es.codigo_sigtap, 1, 4) {$c}"), '=', DB::raw("fs.subgrupo {$c}"))
                ->where(DB::raw("fs.forma {$c}"), '=', DB::raw("CONCAT(SUBSTRING(es.codigo_sigtap, 1, 4), '00') {$c}"));
        });
        $query->leftJoin('forma as ff', function ($join) use ($c) {
            $join->on(DB::raw("SUBSTRING(es.codigo_sigtap, 1, 6) {$c}"), '=', DB::raw("ff.forma {$c}"));
        });
    }

    protected function addReportJoins($query, array $selectedFields, array $filters, string $tableAlias, array &$joins): void
    {
        $all = array_merge($selectedFields, array_column($filters, 'field'));

        if ($this->needsPrestador($all) && ! in_array('prestador', $joins, true)) {
            $this->addPrestadorJoin($query);
            $joins[] = 'prestador';
        }
        if (in_array('codigo_sigtap', $all, true) && ! in_array('procedimento', $joins, true)) {
            $this->addProcedimentoJoin($query);
            $joins[] = 'procedimento';
        }
        if ($this->needsFormaJoins($all) && ! in_array('forma', $joins, true)) {
            $this->addFormaJoins($query);
            $joins[] = 'forma';
        }
    }

    // ── Query builder (list) ──────────────────────────────────────────────────

    protected function buildQuery($selectedFields, $filters, $groupBy = true)
    {
        $query = DB::table('s_esus as es');
        $joins = [];
        $all = array_merge($selectedFields, array_column($filters, 'field'));

        $this->addReportJoins($query, $selectedFields, $filters, 'es', $joins);

        $selectFields = [];
        $groupByFields = [];

        foreach ($selectedFields as $field) {
            if (! $this->getFieldConfig($field)) {
                continue;
            }

            switch ($field) {
                case 'competencia':
                    $selectFields[] = 'es.competencia as competencia';
                    $groupByFields[] = 'es.competencia';
                    break;
                case 'cnes':
                    $selectFields[] = 'es.cnes';
                    $selectFields[] = 'pr.re_cnome as cnes_display';
                    $groupByFields[] = 'es.cnes';
                    $groupByFields[] = 'pr.re_cnome';
                    break;
                case 'esus_ativo':
                    $selectFields[] = 'pr.esus_ativo as esus_ativo';
                    $groupByFields[] = 'pr.esus_ativo';
                    break;
                case 'codigo_sigtap':
                    $selectFields[] = 'es.codigo_sigtap';
                    $selectFields[] = 'pc.procedimento as codigo_sigtap_display';
                    $groupByFields[] = 'es.codigo_sigtap';
                    $groupByFields[] = 'pc.procedimento';
                    break;
                case 'grupo':
                    $selectFields[] = DB::raw('SUBSTRING(es.codigo_sigtap, 1, 2) as grupo');
                    $groupByFields[] = DB::raw('SUBSTRING(es.codigo_sigtap, 1, 2)');
                    break;
                case 'descgrupo':
                    $selectFields[] = 'fg.descricao as descgrupo';
                    $groupByFields[] = 'fg.descricao';
                    break;
                case 'subgrupo':
                    $selectFields[] = DB::raw('SUBSTRING(es.codigo_sigtap, 1, 4) as subgrupo');
                    $groupByFields[] = DB::raw('SUBSTRING(es.codigo_sigtap, 1, 4)');
                    break;
                case 'descsubgrupo':
                    $selectFields[] = 'fs.descricao as descsubgrupo';
                    $groupByFields[] = 'fs.descricao';
                    break;
                case 'forma':
                    $selectFields[] = DB::raw('SUBSTRING(es.codigo_sigtap, 1, 6) as forma');
                    $groupByFields[] = DB::raw('SUBSTRING(es.codigo_sigtap, 1, 6)');
                    break;
                case 'descforma':
                    $selectFields[] = 'ff.descricao as descforma';
                    $groupByFields[] = 'ff.descricao';
                    break;
                case 'quantidade':
                    $selectFields[] = DB::raw('SUM(es.quantidade) as quantidade');
                    break;
                default:
                    $selectFields[] = "es.{$field}";
                    $groupByFields[] = "es.{$field}";
            }
        }

        $query->select($selectFields);

        foreach ($filters as $filter) {
            $this->applyFilter($query, $filter);
        }

        if ($groupBy && ! empty($groupByFields)) {
            $query->groupBy($groupByFields);
            $query->orderBy($groupByFields[0]);
        }

        return $query;
    }

    // ── Filters ────────────────────────────────────────────────────────────────

    protected function applyFilter($query, $filter)
    {
        $field = $filter['field'];
        $operator = $filter['operator'];
        $value = $filter['value'];

        if ($field === 'filter_esus_ativo') {
            $query->where('pr.esus_ativo', '=', 1);

            return;
        }

        if ($field === 'esus_ativo') {
            $query->where('pr.esus_ativo', '=', (int) $value);

            return;
        }

        if (in_array($field, ['grupo', 'subgrupo', 'forma'], true)) {
            $len = ['grupo' => 2, 'subgrupo' => 4, 'forma' => 6][$field];
            $this->applyTextFilter($query, DB::raw("SUBSTRING(es.codigo_sigtap, 1, {$len})"), $operator, $value);

            return;
        }
        if ($field === 'descgrupo') {
            $this->applyTextFilter($query, 'fg.descricao', $operator, $value);

            return;
        }
        if ($field === 'descsubgrupo') {
            $this->applyTextFilter($query, 'fs.descricao', $operator, $value);

            return;
        }
        if ($field === 'descforma') {
            $this->applyTextFilter($query, 'ff.descricao', $operator, $value);

            return;
        }

        parent::applyFilter($query, $filter);
    }

    private function applyTextFilter($query, $column, string $operator, $value): void
    {
        match ($operator) {
            'like' => $query->where($column, 'like', "%{$value}%"),
            'starts_with' => $query->where($column, 'like', "{$value}%"),
            'ends_with' => $query->where($column, 'like', "%{$value}"),
            default => $query->where($column, '=', $value),
        };
    }

    // ── Matrix hooks ────────────────────────────────────────────────────────────

    protected function getMatrixLookupFields($field, $tableAlias): array
    {
        return match ($field) {
            'cnes' => ['select' => ['es.cnes', 'pr.re_cnome as cnes_display'], 'groupBy' => ['es.cnes', 'pr.re_cnome']],
            'esus_ativo' => ['select' => ['pr.esus_ativo as esus_ativo'], 'groupBy' => ['pr.esus_ativo']],
            'codigo_sigtap' => ['select' => ['es.codigo_sigtap', 'pc.procedimento as codigo_sigtap_display'], 'groupBy' => ['es.codigo_sigtap', 'pc.procedimento']],
            'grupo' => ['select' => [DB::raw('SUBSTRING(es.codigo_sigtap, 1, 2) as grupo')], 'groupBy' => [DB::raw('SUBSTRING(es.codigo_sigtap, 1, 2)')]],
            'descgrupo' => ['select' => ['fg.descricao as descgrupo'], 'groupBy' => ['fg.descricao']],
            'subgrupo' => ['select' => [DB::raw('SUBSTRING(es.codigo_sigtap, 1, 4) as subgrupo')], 'groupBy' => [DB::raw('SUBSTRING(es.codigo_sigtap, 1, 4)')]],
            'descsubgrupo' => ['select' => ['fs.descricao as descsubgrupo'], 'groupBy' => ['fs.descricao']],
            'forma' => ['select' => [DB::raw('SUBSTRING(es.codigo_sigtap, 1, 6) as forma')], 'groupBy' => [DB::raw('SUBSTRING(es.codigo_sigtap, 1, 6)')]],
            'descforma' => ['select' => ['ff.descricao as descforma'], 'groupBy' => ['ff.descricao']],
            default => ['select' => [], 'groupBy' => []],
        };
    }

    protected function getMatrixNumericFields($field, $tableAlias): array
    {
        return $field === 'quantidade'
            ? [DB::raw('SUM(es.quantidade) as quantidade')]
            : [];
    }

    protected function getNumericValue($item, $field)
    {
        return (float) ($item->{$field} ?? 0);
    }

    protected function getGroupKeyPart($item, $field)
    {
        return match ($field) {
            'cnes' => ($item->cnes ?? '').'|'.($item->cnes_display ?? ''),
            'codigo_sigtap' => ($item->codigo_sigtap ?? '').'|'.($item->codigo_sigtap_display ?? ''),
            'esus_ativo' => ($item->esus_ativo ?? '') ? 'Sim' : 'Não',
            default => $item->{$field} ?? '',
        };
    }

    // ── Totals & formatting ─────────────────────────────────────────────────────

    protected function calculateTotals($data, $selectedFields)
    {
        $totals = [];

        if (in_array('quantidade', $selectedFields, true)) {
            $totals['Quantidade Total'] = number_format($data->sum(fn ($r) => $r->quantidade ?? 0), 0, ',', '.');
        }

        return $totals;
    }

    protected function formatFieldValue($row, $field, $fieldConfig)
    {
        return match ($field) {
            'competencia' => ['Competência' => $row->competencia ?? ''],
            'cnes' => ['CNES' => $row->cnes ?? '', 'Prestador' => $row->cnes_display ?? ''],
            'codigo_sigtap' => ['Código SIGTAP' => $row->codigo_sigtap ?? '', 'Procedimento' => $row->codigo_sigtap_display ?? ''],
            'esus_ativo' => ['Considerar e-SUS' => ($row->esus_ativo ?? '') ? 'Sim' : 'Não'],
            'quantidade' => ['Quantidade' => number_format((float) ($row->quantidade ?? 0), 0, ',', '.')],
            'grupo', 'descgrupo', 'subgrupo', 'descsubgrupo', 'forma', 'descforma' => [$fieldConfig['label'] => $row->{$field} ?? ''],
            default => parent::formatFieldValue($row, $field, $fieldConfig),
        };
    }
}
