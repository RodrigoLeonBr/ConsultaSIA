<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class EsusRelatorioTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('procedimento')->insert([
            'codigo' => '0301100209',
            'procedimento' => 'ADMINISTRACAO DE MEDICAMENTOS POR VIA INTRAMUSCULAR',
            'pa_id' => '0',
        ]);

        DB::table('prestador')->insert([
            ['re_cunid' => '2048205', 're_cnome' => 'NUCLEO', 're_tipo' => 'U', 'area' => 1, 'tipouni' => 'M', 'ativo' => 1, 'esus_ativo' => 1],
            ['re_cunid' => '9999999', 're_cnome' => 'FORA', 're_tipo' => 'U', 'area' => 1, 'tipouni' => 'M', 'ativo' => 1, 'esus_ativo' => 0],
        ]);

        DB::table('s_esus')->insert([
            ['competencia' => '2026-01', 'cnes' => '2048205', 'unidade' => 'A', 'tipo_relatorio' => 'procedimentos_individualizados', 'bloco' => 'B1', 'descricao_esus' => 'X', 'codigo_sigtap' => '0301100209', 'descricao_sigtap' => 'ADM IM', 'quantidade' => 10],
            ['competencia' => '2026-01', 'cnes' => '2048205', 'unidade' => 'A', 'tipo_relatorio' => 'procedimentos_individualizados', 'bloco' => 'B2', 'descricao_esus' => 'Y', 'codigo_sigtap' => '0301100209', 'descricao_sigtap' => 'ADM IM', 'quantidade' => 5],
            ['competencia' => '2026-01', 'cnes' => '9999999', 'unidade' => 'B', 'tipo_relatorio' => 'procedimentos_individualizados', 'bloco' => 'B1', 'descricao_esus' => 'X', 'codigo_sigtap' => '0301100209', 'descricao_sigtap' => 'ADM IM', 'quantidade' => 99],
        ]);
    }

    public function test_fields_endpoint_lists_esus_fields(): void
    {
        $fields = $this->actingAs($this->createReportTestUser())
            ->getJson(route('relatorios.esus.fields'))
            ->assertOk()
            ->json('fields');

        $this->assertArrayHasKey('codigo_sigtap', $fields);
        $this->assertArrayHasKey('quantidade', $fields);
        $this->assertArrayNotHasKey('PRD_VL_P', $fields); // sem valores financeiros
    }

    public function test_generate_groups_and_sums_quantidade_with_sigtap_join(): void
    {
        $data = $this->actingAs($this->createReportTestUser())
            ->postJson(route('relatorios.esus.generate'), [
                'fields' => ['competencia', 'codigo_sigtap', 'quantidade'],
                'filters' => [],
                'format' => 'html',
            ])
            ->assertOk()
            ->json();

        $this->assertCount(1, $data['data']);
        $row = $data['data'][0];
        $this->assertSame('0301100209', $row['Código SIGTAP']);
        $this->assertStringContainsString('INTRAMUSCULAR', $row['Procedimento']);
        $this->assertSame('114', $row['Quantidade']); // 10 + 5 + 99
        $this->assertSame('114', $data['totals']['Quantidade Total']);
    }

    public function test_filter_esus_ativo_excludes_units(): void
    {
        $data = $this->actingAs($this->createReportTestUser())
            ->postJson(route('relatorios.esus.generate'), [
                'fields' => ['codigo_sigtap', 'quantidade'],
                'filters' => [['field' => 'esus_ativo', 'operator' => '=', 'value' => '1']],
                'format' => 'html',
            ])
            ->assertOk()
            ->json();

        $this->assertSame('15', $data['data'][0]['Quantidade']); // exclui CNES 9999999 (esus_ativo=0)
    }

    public function test_checkbox_filter_esus_ativo_only_excludes_units(): void
    {
        $data = $this->actingAs($this->createReportTestUser())
            ->postJson(route('relatorios.esus.generate'), [
                'fields' => ['codigo_sigtap', 'quantidade'],
                'filters' => [['field' => 'filter_esus_ativo', 'operator' => '=', 'value' => true]],
                'format' => 'html',
            ])
            ->assertOk()
            ->json();

        $this->assertSame('15', $data['data'][0]['Quantidade']); // só CNES considerado (10+5)
    }

    public function test_matrix_by_competencia(): void
    {
        $data = $this->actingAs($this->createReportTestUser())
            ->postJson(route('relatorios.esus.generate-matrix'), [
                'fields' => ['competencia', 'unidade', 'quantidade'],
                'filters' => [],
                'format' => 'html',
            ])
            ->assertOk()
            ->json();

        $this->assertSame('matrix', $data['type']);
        $this->assertNotEmpty($data['data']['competencias']);
    }
}
