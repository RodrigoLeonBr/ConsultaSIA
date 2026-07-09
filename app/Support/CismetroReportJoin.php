<?php

namespace App\Support;

use App\Models\Cismetro;
use Illuminate\Database\Query\JoinClause;

class CismetroReportJoin
{
    /**
     * Tipo efetivo: P com registro prestador → 2; senão → 1 (fallback quando não há tipo 2).
     */
    public static function resolvedTipoValorExpression(string $codigoColumn, string $prestadorAlias = 'pr'): string
    {
        $hasPrestador = '(SELECT COUNT(*) FROM cismetro cfx WHERE cfx.codigo = '.$codigoColumn.' AND cfx.tipo_valor = '
            .Cismetro::TIPO_PRESTADOR.') > 0';

        return 'CASE WHEN '.$prestadorAlias.".re_tipo = 'P' AND {$hasPrestador} THEN "
            .Cismetro::TIPO_PRESTADOR.' ELSE '
            .Cismetro::TIPO_MUNICIPIO.' END';
    }

    public static function applyTipoValorCondition(
        JoinClause $join,
        string $codigoColumn,
        string $cismetroAlias = 'cs',
        string $prestadorAlias = 'pr',
    ): void {
        $tipoSql = self::resolvedTipoValorExpression($codigoColumn, $prestadorAlias);

        $join->whereRaw("{$cismetroAlias}.tipo_valor = {$tipoSql}");
        $join->whereRaw("{$cismetroAlias}.id = (
            SELECT MIN(c2.id) FROM cismetro c2
            WHERE c2.codigo = {$codigoColumn}
            AND c2.tipo_valor = {$tipoSql}
        )");
    }
}
