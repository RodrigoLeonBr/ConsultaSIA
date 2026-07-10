<?php

namespace App\Support;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

class AihEpisodeJoin
{
    /**
     * Liga s_aih_pa ao cabeçalho s_aih quando a mesma AIH aparece mais de uma vez na competência
     * (fechamento/reabertura de UTI). ponytail: QUANTIDADE=DIARIAS amarra o item ao episódio;
     * itens sem par usam o cabeçalho com DT_SAIDA mais recente (dados demográficos iguais).
     */
    public static function applyPaToHeaderJoin(
        JoinClause $join,
        string $paAlias = 'sp',
        string $aihAlias = 'aih',
    ): void {
        $join->on(
            DB::raw("{$paAlias}.AIH COLLATE utf8mb4_unicode_ci"),
            '=',
            DB::raw("{$aihAlias}.AIH COLLATE utf8mb4_unicode_ci")
        )->on(
            DB::raw("{$paAlias}.CNES COLLATE utf8mb4_unicode_ci"),
            '=',
            DB::raw("{$aihAlias}.CNES COLLATE utf8mb4_unicode_ci")
        )->on(
            DB::raw("{$paAlias}.COMPETENCIA COLLATE utf8mb4_unicode_ci"),
            '=',
            DB::raw("{$aihAlias}.COMPETENCIA COLLATE utf8mb4_unicode_ci")
        );

        $episodeMatchExists = "EXISTS (
            SELECT 1 FROM s_aih ax
            WHERE ax.AIH COLLATE utf8mb4_unicode_ci = {$paAlias}.AIH COLLATE utf8mb4_unicode_ci
              AND ax.CNES COLLATE utf8mb4_unicode_ci = {$paAlias}.CNES COLLATE utf8mb4_unicode_ci
              AND ax.COMPETENCIA COLLATE utf8mb4_unicode_ci = {$paAlias}.COMPETENCIA COLLATE utf8mb4_unicode_ci
              AND ax.DIARIAS = {$paAlias}.QUANTIDADE
        )";

        $latestDtSaida = "(
            SELECT MAX(ay.DT_SAIDA) FROM s_aih ay
            WHERE ay.AIH COLLATE utf8mb4_unicode_ci = {$paAlias}.AIH COLLATE utf8mb4_unicode_ci
              AND ay.CNES COLLATE utf8mb4_unicode_ci = {$paAlias}.CNES COLLATE utf8mb4_unicode_ci
              AND ay.COMPETENCIA COLLATE utf8mb4_unicode_ci = {$paAlias}.COMPETENCIA COLLATE utf8mb4_unicode_ci
        )";

        $join->where(function ($query) use ($paAlias, $aihAlias, $episodeMatchExists, $latestDtSaida) {
            $query->whereColumn("{$paAlias}.QUANTIDADE", "{$aihAlias}.DIARIAS")
                ->orWhere(function ($fallback) use ($aihAlias, $episodeMatchExists, $latestDtSaida) {
                    $fallback->whereRaw("NOT {$episodeMatchExists}")
                        ->whereRaw("{$aihAlias}.DT_SAIDA = {$latestDtSaida}");
                });
        });
    }
}
