<?php

namespace Tests\Feature;

use App\Http\Controllers\RelatorioController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class RelatorioFieldsTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createReportTestUser();
    }

    public function test_fields_endpoint_exposes_ano_competencia(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('relatorios.fields'));

        $response->assertOk();
        $fields = $response->json('fields');

        $this->assertArrayHasKey('ano_competencia', $fields);
        $this->assertSame('Ano Competência', $fields['ano_competencia']['label']);
        $this->assertContains('=', $fields['ano_competencia']['operators']);
        $this->assertContains('between', $fields['ano_competencia']['operators']);
    }

    public function test_build_query_groups_by_year_when_competencia_not_selected(): void
    {
        $base = [
            'prd_uid' => '1234567',
            'prd_flh' => '001',
            'prd_cbo' => '225125',
            'PRD_QT_P' => 1,
            'PRD_VL_P' => 10.00,
        ];

        DB::table('s_prd')->insert([
            array_merge($base, ['prd_cmp' => '202401', 'prd_seq' => '01', 'prd_pa' => '0301010010']),
            array_merge($base, ['prd_cmp' => '202402', 'prd_seq' => '02', 'prd_pa' => '0301010028']),
            array_merge($base, ['prd_cmp' => '202501', 'prd_seq' => '03', 'prd_pa' => '0301010036']),
        ]);

        $controller = new RelatorioController;
        $method = new \ReflectionMethod($controller, 'buildQuery');
        $method->setAccessible(true);

        $query = $method->invoke(
            $controller,
            ['ano_competencia', 'PRD_QT_P'],
            [
                ['field' => 'prd_cmp', 'operator' => 'between', 'value' => ['202401', '202512']],
            ]
        );

        $rows = $query->get()->keyBy('ano_competencia');

        $this->assertCount(2, $rows);
        $this->assertSame(2, (int) $rows['2024']->total_quantidade);
        $this->assertSame(1, (int) $rows['2025']->total_quantidade);
    }

    public function test_build_query_groups_by_month_when_competencia_also_selected(): void
    {
        $base = [
            'prd_uid' => '1234567',
            'prd_flh' => '001',
            'prd_cbo' => '225125',
            'PRD_QT_P' => 1,
            'PRD_VL_P' => 10.00,
        ];

        DB::table('s_prd')->insert([
            array_merge($base, ['prd_cmp' => '202401', 'prd_seq' => '01', 'prd_pa' => '0301010010']),
            array_merge($base, ['prd_cmp' => '202402', 'prd_seq' => '02', 'prd_pa' => '0301010028']),
        ]);

        $controller = new RelatorioController;
        $method = new \ReflectionMethod($controller, 'buildQuery');
        $method->setAccessible(true);

        $query = $method->invoke(
            $controller,
            ['ano_competencia', 'prd_cmp', 'PRD_QT_P'],
            [
                ['field' => 'prd_cmp', 'operator' => 'between', 'value' => ['202401', '202402']],
            ]
        );

        $rows = $query->get();

        $this->assertCount(2, $rows);
        $this->assertSame('2024', $rows[0]->ano_competencia);
        $this->assertSame('2024', $rows[1]->ano_competencia);
        $this->assertNotSame($rows[0]->competencia, $rows[1]->competencia);
    }

    public function test_build_query_filters_by_ano_competencia(): void
    {
        $base = [
            'prd_uid' => '1234567',
            'prd_flh' => '001',
            'prd_cbo' => '225125',
            'PRD_QT_P' => 1,
            'PRD_VL_P' => 10.00,
        ];

        DB::table('s_prd')->insert([
            array_merge($base, ['prd_cmp' => '202401', 'prd_seq' => '01', 'prd_pa' => '0301010010']),
            array_merge($base, ['prd_cmp' => '202501', 'prd_seq' => '02', 'prd_pa' => '0301010028']),
        ]);

        $controller = new RelatorioController;
        $method = new \ReflectionMethod($controller, 'buildQuery');
        $method->setAccessible(true);

        $query = $method->invoke(
            $controller,
            ['ano_competencia', 'PRD_QT_P'],
            [
                ['field' => 'ano_competencia', 'operator' => '=', 'value' => '2024'],
            ]
        );

        $rows = $query->get();

        $this->assertCount(1, $rows);
        $this->assertSame('2024', $rows[0]->ano_competencia);
        $this->assertSame(1, (int) $rows[0]->total_quantidade);
    }

    public function test_fields_endpoint_exposes_prd_org(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('relatorios.fields'));

        $response->assertOk();
        $fields = $response->json('fields');

        $this->assertArrayHasKey('PRD_ORG', $fields);
        $this->assertSame('Origem', $fields['PRD_ORG']['label']);
        $this->assertSame('text', $fields['PRD_ORG']['type']);
        $this->assertContains('=', $fields['PRD_ORG']['operators']);
        $this->assertContains('in', $fields['PRD_ORG']['operators']);
    }

    public function test_build_query_selects_and_filters_prd_org(): void
    {
        $base = [
            'prd_uid' => '1234567',
            'prd_cmp' => '202401',
            'prd_flh' => '001',
            'prd_cbo' => '225125',
            'PRD_QT_P' => 1,
            'PRD_VL_P' => 10.00,
        ];

        DB::table('s_prd')->insert([
            array_merge($base, ['prd_seq' => '01', 'prd_pa' => '0301010010', 'prd_org' => 'BPA']),
            array_merge($base, ['prd_seq' => '02', 'prd_pa' => '0301010028', 'prd_org' => 'EXT']),
        ]);

        $controller = new RelatorioController;
        $method = new \ReflectionMethod($controller, 'buildQuery');
        $method->setAccessible(true);

        $query = $method->invoke(
            $controller,
            ['PRD_ORG', 'PRD_QT_P'],
            [
                ['field' => 'prd_cmp', 'operator' => '=', 'value' => '202401'],
                ['field' => 'PRD_ORG', 'operator' => '=', 'value' => 'BPA'],
            ]
        );

        $rows = $query->get();

        $this->assertCount(1, $rows);
        $this->assertSame('BPA', $rows[0]->PRD_ORG);
        $this->assertSame(1, (int) $rows[0]->total_quantidade);
    }
}
