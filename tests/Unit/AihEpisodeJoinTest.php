<?php

namespace Tests\Unit;

use App\Support\AihEpisodeJoin;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AihEpisodeJoinTest extends TestCase
{
    public function test_pa_to_header_join_matches_episode_by_diarias_or_falls_back_to_latest_dt_saida(): void
    {
        $query = DB::table('s_aih_pa as sp');
        $query->leftJoin('s_aih as aih', function (JoinClause $join) {
            AihEpisodeJoin::applyPaToHeaderJoin($join);
        });

        $sql = $query->toSql();

        $this->assertStringContainsString('sp.AIH COLLATE utf8mb4_unicode_ci', $sql);
        $this->assertStringContainsString('sp.QUANTIDADE', $sql);
        $this->assertStringContainsString('DIARIAS', $sql);
        $this->assertStringContainsString('MAX(ay.DT_SAIDA)', $sql);
        $this->assertStringContainsString('ax.DIARIAS = sp.QUANTIDADE', $sql);
    }
}
