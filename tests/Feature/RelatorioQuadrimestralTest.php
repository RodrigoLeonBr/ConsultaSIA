<?php

namespace Tests\Feature;

use App\Services\ProducaoQuadrimestralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class RelatorioQuadrimestralTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    private function service(): ProducaoQuadrimestralService
    {
        return new ProducaoQuadrimestralService;
    }

    public function test_competencias_do_quadrimestre(): void
    {
        $s = $this->service();
        $this->assertSame(['202601', '202602', '202603', '202604'], $s->competencias(2026, 1));
        $this->assertSame(['202605', '202606', '202607', '202608'], $s->competencias(2026, 2));
        $this->assertSame(['202609', '202610', '202611', '202612'], $s->competencias(2026, 3));
    }

    public function test_rotulos_meses(): void
    {
        $s = $this->service();
        $this->assertSame(
            ['Jan/2026', 'Fev/2026', 'Mar/2026', 'Abr/2026'],
            $s->rotulosMeses(['202601', '202602', '202603', '202604'])
        );
    }
}
