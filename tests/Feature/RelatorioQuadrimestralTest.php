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

    public function test_monta_secoes_soma_e_lineariza(): void
    {
        $comps = ['202601', '202602', '202603', '202604'];
        $linhas = [
            // mesmo proc+cnes em 2 meses → soma nos meses certos e no total
            (object) ['tipo_relatorio' => 'AB', 'subgrupo_cod' => '0301', 'subgrupo_desc' => 'SG', 'forma_cod' => '030110', 'forma_desc' => 'FO', 'proc_cod' => '0301100209', 'proc_desc' => 'PROC', 'cnes' => '2048205', 'prestador_nome' => 'UNI', 'competencia' => '202601', 'qtd' => 10],
            (object) ['tipo_relatorio' => 'AB', 'subgrupo_cod' => '0301', 'subgrupo_desc' => 'SG', 'forma_cod' => '030110', 'forma_desc' => 'FO', 'proc_cod' => '0301100209', 'proc_desc' => 'PROC', 'cnes' => '2048205', 'prestador_nome' => 'UNI', 'competencia' => '202603', 'qtd' => 5],
        ];

        $secoes = $this->service()->montarSecoes($linhas, $comps);

        $this->assertCount(1, $secoes);
        $this->assertSame('AB', $secoes[0]['tipo']);
        $this->assertSame([10, 0, 5, 0], $secoes[0]['total_meses']);
        $this->assertSame(15, $secoes[0]['total']);

        $linear = $secoes[0]['linhas'];
        // 4 níveis: subgrupo, forma, procedimento, prestador
        $this->assertSame(['sg:0301', 'fo:030110', 'pc:0301100209', 'pe:0301100209:2048205'], array_column($linear, 'node_id'));
        $this->assertSame([0, 1, 2, 3], array_column($linear, 'level'));
        // acumulação em todos os níveis
        foreach ($linear as $no) {
            $this->assertSame([10, 0, 5, 0], $no['meses']);
            $this->assertSame(15, $no['total']);
        }
        $this->assertTrue($linear[0]['has_children']);
        $this->assertFalse($linear[3]['has_children']);
    }

    public function test_secoes_separadas_por_tipo_e_ordenadas(): void
    {
        $comps = ['202601', '202602', '202603', '202604'];
        $linhas = [
            (object) ['tipo_relatorio' => 'MAC', 'subgrupo_cod' => '0801', 'subgrupo_desc' => '', 'forma_cod' => '080101', 'forma_desc' => '', 'proc_cod' => '0801010010', 'proc_desc' => '', 'cnes' => '1', 'prestador_nome' => '', 'competencia' => '202602', 'qtd' => 3],
            (object) ['tipo_relatorio' => 'AB', 'subgrupo_cod' => '0301', 'subgrupo_desc' => '', 'forma_cod' => '030110', 'forma_desc' => '', 'proc_cod' => '0301100209', 'proc_desc' => '', 'cnes' => '2', 'prestador_nome' => '', 'competencia' => '202601', 'qtd' => 7],
        ];

        $secoes = $this->service()->montarSecoes($linhas, $comps);

        $this->assertSame(['AB', 'MAC'], array_column($secoes, 'tipo')); // ordem alfabética
    }
}
