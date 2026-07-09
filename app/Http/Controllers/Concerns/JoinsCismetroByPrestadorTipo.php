<?php

namespace App\Http\Controllers\Concerns;

use App\Support\CismetroReportJoin;
use Illuminate\Support\Facades\DB;

trait JoinsCismetroByPrestadorTipo
{
    protected function usesCollatedCismetroJoins(): bool
    {
        return false;
    }

    protected function ensurePrestadorJoinForCismetro($query, array &$joins, string $tableAlias): void
    {
        if (in_array('prestador', $joins, true)) {
            return;
        }

        $prestadorField = $this->getPrestadorField();

        if ($this->usesCollatedCismetroJoins()) {
            $query->leftJoin('prestador as pr', function ($join) use ($tableAlias, $prestadorField) {
                $join->on(
                    DB::raw("{$tableAlias}.{$prestadorField} COLLATE utf8mb4_unicode_ci"),
                    '=',
                    DB::raw('pr.re_cunid COLLATE utf8mb4_unicode_ci')
                );
            });
        } else {
            $query->leftJoin('prestador as pr', "{$tableAlias}.{$prestadorField}", '=', 'pr.re_cunid');
        }

        $joins[] = 'prestador';
    }

    protected function joinCismetroByPrestadorTipo($query, string $tableAlias, string $procedimentoField, array &$joins): void
    {
        if (in_array('cismetro', $joins, true)) {
            return;
        }

        $this->ensurePrestadorJoinForCismetro($query, $joins, $tableAlias);

        $query->leftJoin('cismetro as cs', function ($join) use ($tableAlias, $procedimentoField) {
            $codigoColumn = "{$tableAlias}.{$procedimentoField}";

            if ($this->usesCollatedCismetroJoins()) {
                $join->on(
                    DB::raw("{$codigoColumn} COLLATE utf8mb4_unicode_ci"),
                    '=',
                    DB::raw('cs.codigo COLLATE utf8mb4_unicode_ci')
                );
            } else {
                $join->on($codigoColumn, '=', 'cs.codigo');
            }

            CismetroReportJoin::applyTipoValorCondition($join, $codigoColumn);
        });
        $joins[] = 'cismetro';
    }
}
