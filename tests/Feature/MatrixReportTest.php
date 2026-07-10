<?php

namespace Tests\Feature;

use App\Http\Controllers\RelatorioController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class MatrixReportTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    protected RelatorioController $controller;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new RelatorioController;
        $this->user = $this->createReportTestUser();
    }

    public function test_matrix_validation_requires_competencia(): void
    {
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_uid', 'PRD_QT_P'],
            'filters' => [],
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'error' => 'Para visualização matriz, selecione "Data Competência" ou "Data Movimento" (apenas um deles).',
            ]);
    }

    public function test_matrix_validation_requires_grouping_fields(): void
    {
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp'],
            'filters' => [],
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'error' => 'Pelo menos um campo além de "Data Competência" deve ser selecionado',
            ]);
    }

    public function test_pivot_data_transformation(): void
    {
        $inputData = collect([
            (object) [
                'competencia' => '202401',
                'procedimento_codigo' => '0301010010',
                'procedimento_nome' => 'Consulta A',
                'total_quantidade' => 100,
            ],
            (object) [
                'competencia' => '202402',
                'procedimento_codigo' => '0301010010',
                'procedimento_nome' => 'Consulta A',
                'total_quantidade' => 150,
            ],
            (object) [
                'competencia' => '202401',
                'procedimento_codigo' => '0301010020',
                'procedimento_nome' => 'Consulta B',
                'total_quantidade' => 200,
            ],
        ]);

        $selectedFields = ['prd_cmp', 'prd_pa', 'PRD_QT_P'];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $inputData, $selectedFields);

        $this->assertArrayHasKey('competencias', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('grand_totals', $result);

        $this->assertCount(2, $result['competencias']);
        $this->assertEquals('01/2024', $result['competencias'][0]['label']);
        $this->assertEquals('02/2024', $result['competencias'][1]['label']);

        $this->assertCount(2, $result['rows']);
        $this->assertEquals(300, $result['totals']['202401']['PRD_QT_P']);
        $this->assertEquals(150, $result['totals']['202402']['PRD_QT_P']);
        $this->assertEquals(450, $result['grand_totals']['PRD_QT_P']);
    }

    public function test_pivot_data_splits_by_prestador_when_prd_uid_selected(): void
    {
        $inputData = collect([
            (object) [
                'competencia' => '202401',
                'prestador_codigo' => 'HOSP001',
                'prestador_nome' => 'Hospital A',
                'total_quantidade' => 100,
            ],
        ]);

        $selectedFields = ['prd_cmp', 'prd_uid', 'PRD_QT_P'];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $inputData, $selectedFields);

        $this->assertArrayHasKey('prestadores', $result);
        $this->assertArrayHasKey('split_field', $result);
        $this->assertEquals('prd_uid', $result['split_field']);
        $this->assertArrayHasKey('HOSP001', $result['prestadores']);
        $this->assertNotEmpty($result['prestadores']['HOSP001']['rows']);
    }

    public function test_competencia_formatting(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatCompetencia');
        $method->setAccessible(true);

        $this->assertEquals('01/2024', $method->invoke($this->controller, '202401'));
        $this->assertEquals('12/2023', $method->invoke($this->controller, '202312'));
        $this->assertEquals('invalid', $method->invoke($this->controller, 'invalid'));
    }

    public function test_row_category_formatting(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatRowCategory');
        $method->setAccessible(true);

        $groupKey = 'PROC001|Consulta Médica';
        $groupFields = ['prd_pa'];

        $result = $method->invoke($this->controller, $groupKey, $groupFields);
        $this->assertEquals('PROC001 - Consulta Médica', $result);
    }

    public function test_numeric_fields_identification(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('getNumericFields');
        $method->setAccessible(true);

        $selectedFields = ['prd_cmp', 'prd_uid', 'PRD_QT_P', 'PRD_VL_P', 'prd_cbo'];
        $result = $method->invoke($this->controller, $selectedFields);

        $this->assertContains('PRD_QT_P', $result);
        $this->assertContains('PRD_VL_P', $result);
        $this->assertNotContains('prd_cmp', $result);
        $this->assertNotContains('prd_uid', $result);
    }

    public function test_matrix_generation_success(): void
    {
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => [
                [
                    'field' => 'prd_cmp',
                    'operator' => '=',
                    'value' => '202401',
                ],
            ],
            'format' => 'html',
        ]);

        $this->assertNotEquals(400, $response->getStatusCode());
    }

    public function test_matrix_export_formats(): void
    {
        $validFormats = ['html', 'excel', 'pdf', 'csv'];

        foreach ($validFormats as $format) {
            $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
                'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
                'filters' => [
                    [
                        'field' => 'prd_cmp',
                        'operator' => '=',
                        'value' => '202401',
                    ],
                ],
                'format' => $format,
            ]);

            $this->assertNotEquals(400, $response->getStatusCode(), "Format {$format} should be accepted");
        }
    }

    public function test_empty_data_handling(): void
    {
        $inputData = collect([]);
        $selectedFields = ['prd_cmp', 'prd_pa', 'PRD_QT_P'];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $inputData, $selectedFields);

        $this->assertArrayHasKey('competencias', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertCount(0, $result['competencias']);
        $this->assertCount(0, $result['rows']);
    }

    public function test_matrix_with_multiple_numeric_fields(): void
    {
        $inputData = collect([
            (object) [
                'competencia' => '202401',
                'procedimento_codigo' => '0301010010',
                'procedimento_nome' => 'Consulta A',
                'total_quantidade' => 100,
                'total_valor' => 1500.50,
            ],
        ]);

        $selectedFields = ['prd_cmp', 'prd_pa', 'PRD_QT_P', 'PRD_VL_P'];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $inputData, $selectedFields);

        $firstRow = $result['rows'][0];
        $this->assertArrayHasKey('PRD_QT_P', $firstRow['totals']);
        $this->assertArrayHasKey('PRD_VL_P', $firstRow['totals']);
        $this->assertEquals(100, $firstRow['totals']['PRD_QT_P']);
        $this->assertEquals(1500.50, $firstRow['totals']['PRD_VL_P']);
    }
}
