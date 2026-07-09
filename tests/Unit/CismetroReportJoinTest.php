<?php

namespace Tests\Unit;

use App\Models\Cismetro;
use App\Support\CismetroReportJoin;
use Tests\TestCase;

class CismetroReportJoinTest extends TestCase
{
    public function test_resolved_tipo_valor_falls_back_to_municipio_when_no_prestador_row(): void
    {
        $sql = CismetroReportJoin::resolvedTipoValorExpression('sp.prd_pa', 'pr');

        $this->assertStringContainsString("pr.re_tipo = 'P'", $sql);
        $this->assertStringContainsString('cfx.tipo_valor = '.Cismetro::TIPO_PRESTADOR, $sql);
        $this->assertStringContainsString((string) Cismetro::TIPO_MUNICIPIO, $sql);
    }
}
