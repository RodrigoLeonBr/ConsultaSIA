<?php

namespace Tests\Feature;

use App\Http\Controllers\RelatorioAihController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class RelatorioAihFieldsTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createReportTestUser();
    }

    public function test_aih_fields_endpoint_exposes_new_sih_columns(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('relatorios.aih.fields'));

        $response->assertOk();
        $fields = $response->json('fields');

        $this->assertArrayHasKey('MUN_RESIDENCIA', $fields);
        $this->assertArrayHasKey('CARATER_INTERNACAO', $fields);
        $this->assertArrayHasKey('CID_OBITO', $fields);

        $this->assertSame('Município de Residência', $fields['MUN_RESIDENCIA']['label']);
        $this->assertSame('Caráter da Internação', $fields['CARATER_INTERNACAO']['label']);
        $this->assertSame('lookup', $fields['CARATER_INTERNACAO']['type']);
        $this->assertArrayHasKey('carater_internacao_resumo', $fields);
        $this->assertSame('CID do Óbito', $fields['CID_OBITO']['label']);

        $this->assertNotEmpty($fields['MUN_RESIDENCIA']['operators']);
        $this->assertNotEmpty($fields['CARATER_INTERNACAO']['operators']);
        $this->assertNotEmpty($fields['CID_OBITO']['operators']);
    }

    public function test_build_query_selects_and_filters_new_sih_columns(): void
    {
        DB::table('s_aih')->insert([
            'AIH' => '3525129193971',
            'IDENT_AIH' => '01',
            'CNES' => '2082179',
            'COMPETENCIA' => '202601',
            'MUN_RESIDENCIA' => '350160',
            'CARATER_INTERNACAO' => '01',
            'CID_OBITO' => '0000',
            'DIARIAS' => 1,
            'DIARIAS_UTI' => 0,
            'VALOR_TOTAL_AIH' => 637.97,
        ]);

        DB::table('s_aih')->insert([
            'AIH' => '3525129193993',
            'IDENT_AIH' => '01',
            'CNES' => '2082179',
            'COMPETENCIA' => '202601',
            'MUN_RESIDENCIA' => '352840',
            'CARATER_INTERNACAO' => '02',
            'CID_OBITO' => '',
            'DIARIAS' => 0,
            'DIARIAS_UTI' => 15,
            'VALOR_TOTAL_AIH' => 11810.65,
        ]);

        $controller = new RelatorioAihController;
        $method = new \ReflectionMethod($controller, 'buildQuery');
        $method->setAccessible(true);

        $query = $method->invoke(
            $controller,
            ['MUN_RESIDENCIA', 'CARATER_INTERNACAO', 'CID_OBITO', 'qtd_aih'],
            [
                ['field' => 'COMPETENCIA', 'operator' => '=', 'value' => '202601'],
                ['field' => 'CARATER_INTERNACAO', 'operator' => '=', 'value' => '01'],
                ['field' => 'MUN_RESIDENCIA', 'operator' => '=', 'value' => '350160'],
            ]
        );

        $rows = $query->get();

        $this->assertCount(1, $rows);
        $this->assertSame('350160', $rows[0]->MUN_RESIDENCIA);
        $this->assertSame('01', $rows[0]->CARATER_INTERNACAO);
        $this->assertSame('Eletivo', $rows[0]->CARATER_INTERNACAO_display);
        $this->assertSame('0000', $rows[0]->CID_OBITO);
        $this->assertSame(1, (int) $rows[0]->qtd_aih);
    }

    public function test_build_query_groups_carater_by_dashboard_resumo(): void
    {
        DB::table('s_aih')->insert([
            ['AIH' => '1', 'CNES' => '2082179', 'COMPETENCIA' => '202601', 'CARATER_INTERNACAO' => '03', 'VALOR_TOTAL_AIH' => 10],
            ['AIH' => '2', 'CNES' => '2082179', 'COMPETENCIA' => '202601', 'CARATER_INTERNACAO' => '05', 'VALOR_TOTAL_AIH' => 20],
            ['AIH' => '3', 'CNES' => '2082179', 'COMPETENCIA' => '202601', 'CARATER_INTERNACAO' => '01', 'VALOR_TOTAL_AIH' => 30],
        ]);

        $controller = new RelatorioAihController;
        $method = new \ReflectionMethod($controller, 'buildQuery');
        $method->setAccessible(true);

        $query = $method->invoke(
            $controller,
            ['carater_internacao_resumo', 'qtd_aih'],
            [['field' => 'COMPETENCIA', 'operator' => '=', 'value' => '202601']],
        );

        $rows = $query->get()->keyBy('carater_internacao_resumo');

        $this->assertSame(2, (int) $rows['Urgência (Acidente)']->qtd_aih);
        $this->assertSame(1, (int) $rows['Eletivo']->qtd_aih);
    }

    public function test_carater_lookup_endpoint_returns_sihd_options(): void
    {
        $response = $this->actingAs($this->user)->getJson(
            route('relatorios.aih.lookup', ['field' => 'CARATER_INTERNACAO', 'search' => 'urg'])
        );

        $response->assertOk();
        $response->assertJsonFragment(['value' => '02', 'label' => '02 — Urgência']);
    }
}
