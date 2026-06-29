<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;

trait HasSusPaulistaReport
{
    protected function getSusPaulistaModalidade(): string
    {
        return 'sia';
    }

    abstract protected function getSusPaulistaQuantityField(): string;

    protected function getSusPaulistaQuantityCastType(): string
    {
        return 'UNSIGNED';
    }

    protected function getSusPaulistaQuantityCastSql(string $tableAlias): string
    {
        $field = "{$tableAlias}.{$this->getSusPaulistaQuantityField()}";
        $cast = $this->getSusPaulistaQuantityCastType();

        return "CAST({$field} AS {$cast})";
    }

    protected function getSusPaulistaFieldConfigs(): array
    {
        return [
            'sus_paulista_tab' => [
                'label' => 'Tab Paulista - Valor Unitário',
                'type' => 'currency',
                'table' => 'sus_paulista',
                'operators' => ['=', '>', '<', '>=', '<=', 'between'],
            ],
            'sus_paulista_tab_total' => [
                'label' => 'Tab Paulista - Valor Total',
                'type' => 'currency',
                'table' => 'calculated',
                'operators' => ['=', '>', '<', '>=', '<=', 'between'],
            ],
            'sus_paulista_tsp' => [
                'label' => 'Compl. TSP - Valor Unitário',
                'type' => 'currency',
                'table' => 'sus_paulista',
                'operators' => ['=', '>', '<', '>=', '<=', 'between'],
            ],
            'sus_paulista_tsp_total' => [
                'label' => 'Compl. TSP - Valor Total',
                'type' => 'currency',
                'table' => 'calculated',
                'operators' => ['=', '>', '<', '>=', '<=', 'between'],
            ],
        ];
    }

    protected function needsSusPaulista(array $selectedFields, array $filters): bool
    {
        return collect($selectedFields)->contains(fn ($field) => str_starts_with($field, 'sus_paulista_'))
            || collect($filters)->contains(fn ($f) => str_starts_with($f['field'] ?? '', 'sus_paulista_'))
            || $this->hasSusPaulistaOnlyFilter($filters);
    }

    protected function hasSusPaulistaOnlyFilter(array $filters): bool
    {
        $filter = collect($filters)->firstWhere('field', 'filter_sus_paulista');

        if ($filter === null) {
            return false;
        }

        return filter_var($filter['value'], FILTER_VALIDATE_BOOLEAN);
    }

    protected function addSusPaulistaJoin($query, array $filters, string $tableAlias): void
    {
        $procedimentoField = $this->getProcedimentoFieldForCismetro();
        $competenciaField = $this->getCompetenciaField();
        $modalidade = $this->getSusPaulistaModalidade();

        $callback = function ($join) use ($tableAlias, $procedimentoField, $competenciaField, $modalidade) {
            $join->on(
                DB::raw("{$tableAlias}.{$procedimentoField} COLLATE utf8mb4_unicode_ci"),
                '=',
                DB::raw('spaul.codigo COLLATE utf8mb4_unicode_ci')
            )
                ->where('spaul.modalidade', '=', $modalidade)
                ->whereRaw("spaul.competencia_inicial COLLATE utf8mb4_unicode_ci <= {$tableAlias}.{$competenciaField} COLLATE utf8mb4_unicode_ci")
                ->whereRaw("spaul.competencia_final COLLATE utf8mb4_unicode_ci >= {$tableAlias}.{$competenciaField} COLLATE utf8mb4_unicode_ci");
        };

        if ($this->hasSusPaulistaOnlyFilter($filters)) {
            $query->join('sus_paulista as spaul', $callback);
        } else {
            $query->leftJoin('sus_paulista as spaul', $callback);
        }
    }

    protected function getMatrixCustomFieldSelect(string $field, string $tableAlias): array
    {
        return match ($field) {
            'sus_paulista_tab' => [
                'select' => ['spaul.tab_paulista as sus_paulista_tab'],
                'groupBy' => ['spaul.tab_paulista'],
            ],
            'sus_paulista_tsp' => [
                'select' => ['spaul.complementacao_tsp as sus_paulista_tsp'],
                'groupBy' => ['spaul.complementacao_tsp'],
            ],
            default => ['select' => [], 'groupBy' => []],
        };
    }

    /**
     * @return array{handled: bool, select?: array, groupBy?: array}
     */
    protected function buildSusPaulistaListSelect(string $field, string $tableAlias): array
    {
        $qtyCast = $this->getSusPaulistaQuantityCastSql($tableAlias);

        return match ($field) {
            'sus_paulista_tab' => [
                'handled' => true,
                'select' => ['spaul.tab_paulista as sus_paulista_tab'],
                'groupBy' => ['spaul.tab_paulista'],
            ],
            'sus_paulista_tsp' => [
                'handled' => true,
                'select' => ['spaul.complementacao_tsp as sus_paulista_tsp'],
                'groupBy' => ['spaul.complementacao_tsp'],
            ],
            'sus_paulista_tab_total' => [
                'handled' => true,
                'select' => [DB::raw("SUM({$qtyCast} * COALESCE(spaul.tab_paulista, 0)) as sus_paulista_tab_total")],
            ],
            'sus_paulista_tsp_total' => [
                'handled' => true,
                'select' => [DB::raw("SUM({$qtyCast} * COALESCE(spaul.complementacao_tsp, 0)) as sus_paulista_tsp_total")],
            ],
            default => ['handled' => false],
        };
    }

    /**
     * @return string|false|null string = coluna SQL; false = campo padrão da tabela; null = ignorar filtro
     */
    protected function resolveSusPaulistaFilterField(string $field): string|false|null
    {
        return match ($field) {
            'filter_sus_paulista' => null,
            'sus_paulista_tab' => 'spaul.tab_paulista',
            'sus_paulista_tsp' => 'spaul.complementacao_tsp',
            'sus_paulista_tab_total', 'sus_paulista_tsp_total' => null,
            default => false,
        };
    }

    /**
     * @return array<string, string>|null
     */
    protected function formatSusPaulistaField(string $field, object $row): ?array
    {
        return match ($field) {
            'sus_paulista_tab' => [
                'Tab Paulista - Valor Unitário' => $row->sus_paulista_tab
                    ? 'R$ ' . number_format((float) $row->sus_paulista_tab, 2, ',', '.')
                    : 'R$ 0,00',
            ],
            'sus_paulista_tab_total' => [
                'Tab Paulista - Valor Total' => $row->sus_paulista_tab_total
                    ? 'R$ ' . number_format((float) $row->sus_paulista_tab_total, 2, ',', '.')
                    : 'R$ 0,00',
            ],
            'sus_paulista_tsp' => [
                'Compl. TSP - Valor Unitário' => $row->sus_paulista_tsp
                    ? 'R$ ' . number_format((float) $row->sus_paulista_tsp, 2, ',', '.')
                    : 'R$ 0,00',
            ],
            'sus_paulista_tsp_total' => [
                'Compl. TSP - Valor Total' => $row->sus_paulista_tsp_total
                    ? 'R$ ' . number_format((float) $row->sus_paulista_tsp_total, 2, ',', '.')
                    : 'R$ 0,00',
            ],
            default => null,
        };
    }

    protected function appendSusPaulistaTotals(array $selectedFields, $data, array &$totals): void
    {
        if (in_array('sus_paulista_tab_total', $selectedFields, true)) {
            $total = $data->sum(fn ($item) => $item->sus_paulista_tab_total ?? 0);
            $totals['Tab Paulista - Valor Total'] = 'R$ ' . number_format((float) $total, 2, ',', '.');
        }

        if (in_array('sus_paulista_tsp_total', $selectedFields, true)) {
            $total = $data->sum(fn ($item) => $item->sus_paulista_tsp_total ?? 0);
            $totals['Compl. TSP - Valor Total'] = 'R$ ' . number_format((float) $total, 2, ',', '.');
        }
    }

    protected function getSusPaulistaMatrixNumericFields(string $field, string $tableAlias): array
    {
        $qtyCast = $this->getSusPaulistaQuantityCastSql($tableAlias);

        return match ($field) {
            'sus_paulista_tab_total' => [
                DB::raw("SUM({$qtyCast} * COALESCE(spaul.tab_paulista, 0)) as sus_paulista_tab_total"),
            ],
            'sus_paulista_tsp_total' => [
                DB::raw("SUM({$qtyCast} * COALESCE(spaul.complementacao_tsp, 0)) as sus_paulista_tsp_total"),
            ],
            default => [],
        };
    }

    protected function getSusPaulistaNumericValue(object $item, string $field): ?float
    {
        return match ($field) {
            'sus_paulista_tab_total' => (float) ($item->sus_paulista_tab_total ?? 0),
            'sus_paulista_tsp_total' => (float) ($item->sus_paulista_tsp_total ?? 0),
            'sus_paulista_tab' => (float) ($item->sus_paulista_tab ?? 0),
            'sus_paulista_tsp' => (float) ($item->sus_paulista_tsp ?? 0),
            default => null,
        };
    }

    protected function getSusPaulistaAggregateFieldIds(): array
    {
        return ['sus_paulista_tab_total', 'sus_paulista_tsp_total'];
    }
}
