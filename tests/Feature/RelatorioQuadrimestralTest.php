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

    private function seedTresFontes(): void
    {
        \DB::table('procedimento')->insert([
            'codigo' => '0301100209', 'procedimento' => 'ADM MEDICAMENTO IM', 'pa_id' => '0',
        ]);
        \DB::table('forma')->insert([
            ['id_registro' => 1, 'grupo' => '03', 'subgrupo' => '0301', 'forma' => '030100', 'descricao' => 'SUBGRUPO 0301'],
            ['id_registro' => 2, 'grupo' => '03', 'subgrupo' => '0301', 'forma' => '030110', 'descricao' => 'FORMA 030110'],
        ]);
        \DB::table('prestador')->insert([
            ['re_cunid' => '2048205', 're_cnome' => 'UNIDADE A', 're_tipo' => 'U', 'area' => 1, 'tipouni' => 'M', 'ativo' => 1, 'esus_ativo' => 1, 'relatorio' => 'ATENCAO BASICA'],
            ['re_cunid' => '9999999', 're_cnome' => 'UNIDADE B', 're_tipo' => 'U', 'area' => 1, 'tipouni' => 'M', 'ativo' => 1, 'esus_ativo' => 0, 'relatorio' => 'ATENCAO BASICA'],
        ]);
        // SIA: unidade A, jan, aprovada 20 (PRD_QT_A); apresentada diferente pra garantir que usamos A
        \DB::table('s_prd')->insert([
            'prd_cmp' => '202601', 'prd_uid' => '2048205', 'prd_pa' => '0301100209',
            'prd_flh' => '001', 'prd_seq' => '01', 'prd_cbo' => '000000',
            'PRD_QT_P' => '999', 'PRD_QT_A' => '20', 'PRD_VL_P' => '0', 'PRD_VL_A' => '0', 'prd_rub' => '01',
        ]);
        // SIH: unidade A, fev, 3
        \DB::table('s_aih_pa')->insert([
            'AIH' => '0000000000001', 'CNES' => '2048205', 'COMPETENCIA' => '202602',
            'PROC_DETALHADO' => '0301100209', 'QUANTIDADE' => 3, 'VALOR_ITEM' => 0,
            'FINANCIAMENTO_DETALHE' => '01', 'CBO_PROFISSIONAL' => '000000',
        ]);
        // e-SUS: unidade A (esus_ativo=1) jan 10 → soma com SIA no mesmo leaf; unidade B (esus_ativo=0) jan 99 → excluída
        \DB::table('s_esus')->insert([
            ['competencia' => '2026-01', 'cnes' => '2048205', 'unidade' => 'A', 'tipo_relatorio' => 'x', 'bloco' => 'b', 'descricao_esus' => 'd', 'codigo_sigtap' => '0301100209', 'descricao_sigtap' => 's', 'quantidade' => 10],
            ['competencia' => '2026-01', 'cnes' => '9999999', 'unidade' => 'B', 'tipo_relatorio' => 'x', 'bloco' => 'b', 'descricao_esus' => 'd', 'codigo_sigtap' => '0301100209', 'descricao_sigtap' => 's', 'quantidade' => 99],
            // e-SUS bloco CDS sem SIGTAP → descartado
            ['competencia' => '2026-01', 'cnes' => '2048205', 'unidade' => 'A', 'tipo_relatorio' => 'cds', 'bloco' => 'b', 'descricao_esus' => 'd', 'codigo_sigtap' => '', 'descricao_sigtap' => '', 'quantidade' => 7],
        ]);
    }

    public function test_gerar_soma_tres_fontes_e_exclui_esus_inativo(): void
    {
        $this->seedTresFontes();

        $r = $this->service()->gerar(2026, 1); // Q1 = jan..abr

        $this->assertSame(['Jan/2026', 'Fev/2026', 'Mar/2026', 'Abr/2026'], $r['meses']);
        $this->assertCount(1, $r['secoes']);
        $secao = $r['secoes'][0];
        $this->assertSame('ATENCAO BASICA', $secao['tipo']);

        // leaf do prestador A: jan = SIA 20 + eSUS 10 = 30 ; fev = SIH 3
        $leaf = collect($secao['linhas'])->firstWhere('node_id', 'pe:0301100209:2048205');
        $this->assertNotNull($leaf);
        $this->assertSame([30, 3, 0, 0], $leaf['meses']);
        $this->assertSame(33, $leaf['total']);

        // unidade B (esus_ativo=0, 99) NÃO entra
        $this->assertNull(collect($secao['linhas'])->firstWhere('node_id', 'pe:0301100209:9999999'));

        // total da seção = 33 (CDS de 7 descartado)
        $this->assertSame(33, $secao['total']);
    }

    public function test_rota_index_exige_auth(): void
    {
        $this->get(route('relatorios.quadrimestral.index'))->assertRedirect(route('login'));
    }

    public function test_gerar_renderiza_secoes(): void
    {
        $this->seedTresFontes();

        $this->actingAs($this->createReportTestUser())
            ->post(route('relatorios.quadrimestral.gerar'), ['ano' => 2026, 'quadrimestre' => 1])
            ->assertOk()
            ->assertSee('ATENCAO BASICA')
            ->assertSee('data-node-id="pe:0301100209:2048205"', false);
    }

    public function test_gerar_valida_quadrimestre(): void
    {
        $this->actingAs($this->createReportTestUser())
            ->post(route('relatorios.quadrimestral.gerar'), ['ano' => 2026, 'quadrimestre' => 9])
            ->assertSessionHasErrors('quadrimestre');
    }
}
