<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Http\Controllers\RelatorioController;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MatrixReportTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new RelatorioController();
    }

    /**
     * Test matrix request validation
     */
    public function test_matrix_validation_requires_competencia(): void
    {
        $response = $this->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_uid', 'PRD_QT_P'], // Missing prd_cmp
            'filters' => []
        ]);

        $response->assertStatus(400)
                ->assertJsonFragment(['error' => 'Campo "Data Competência" é obrigatório para visualização matriz']);
    }

    /**
     * Test matrix validation requires at least one grouping field
     */
    public function test_matrix_validation_requires_grouping_fields(): void
    {
        $response = $this->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp'], // Only competencia
            'filters' => []
        ]);

        $response->assertStatus(400)
                ->assertJsonFragment(['error' => 'Pelo menos um campo além de "Data Competência" deve ser selecionado']);
    }

    /**
     * Test pivot data transformation with mock data
     */
    public function test_pivot_data_transformation(): void
    {
        // Create mock data
        $inputData = collect([
            (object)[
                'competencia' => '202401',
                'prestador_codigo' => 'HOSP001',
                'prestador_nome' => 'Hospital A',
                'total_quantidade' => 100
            ],
            (object)[
                'competencia' => '202402',
                'prestador_codigo' => 'HOSP001',
                'prestador_nome' => 'Hospital A',
                'total_quantidade' => 150
            ],
            (object)[
                'competencia' => '202401',
                'prestador_codigo' => 'HOSP002',
                'prestador_nome' => 'Hospital B',
                'total_quantidade' => 200
            ],
        ]);

        $selectedFields = ['prd_cmp', 'prd_uid', 'PRD_QT_P'];

        // Use reflection to access private method
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $inputData, $selectedFields);

        // Verify structure
        $this->assertArrayHasKey('competencias', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertArrayHasKey('totals', $result);
        $this->assertArrayHasKey('grand_totals', $result);

        // Verify competencias
        $this->assertCount(2, $result['competencias']); // 202401, 202402
        $this->assertEquals('01/2024', $result['competencias'][0]['label']);
        $this->assertEquals('02/2024', $result['competencias'][1]['label']);

        // Verify rows
        $this->assertCount(2, $result['rows']); // Hospital A, Hospital B

        // Verify totals calculation
        $this->assertEquals(300, $result['totals']['202401']['PRD_QT_P']); // 100 + 200
        $this->assertEquals(150, $result['totals']['202402']['PRD_QT_P']); // 150 + 0

        // Verify grand total
        $this->assertEquals(450, $result['grand_totals']['PRD_QT_P']); // 300 + 150
    }

    /**
     * Test competencia formatting
     */
    public function test_competencia_formatting(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatCompetencia');
        $method->setAccessible(true);

        $this->assertEquals('01/2024', $method->invoke($this->controller, '202401'));
        $this->assertEquals('12/2023', $method->invoke($this->controller, '202312'));
        $this->assertEquals('invalid', $method->invoke($this->controller, 'invalid'));
    }

    /**
     * Test row category formatting
     */
    public function test_row_category_formatting(): void
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('formatRowCategory');
        $method->setAccessible(true);

        $groupKey = 'HOSP001|Hospital ABC||PROC001|Consulta Médica';
        $groupFields = ['prd_uid', 'prd_pa'];

        $result = $method->invoke($this->controller, $groupKey, $groupFields);
        $this->assertEquals('Hospital ABC - Consulta Médica', $result);
    }

    /**
     * Test numeric fields identification
     */
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

    /**
     * Test matrix generation with valid data
     */
    public function test_matrix_generation_success(): void
    {
        // This test would require database setup with actual data
        // For now, we'll test the endpoint exists and validates properly
        
        $response = $this->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => [
                [
                    'field' => 'prd_cmp',
                    'operator' => '=',
                    'value' => '202401'
                ]
            ],
            'format' => 'html'
        ]);

        // Should not return validation error
        $this->assertNotEquals(400, $response->getStatusCode());
    }

    /**
     * Test matrix export formats
     */
    public function test_matrix_export_formats(): void
    {
        $validFormats = ['html', 'excel', 'pdf', 'csv'];

        foreach ($validFormats as $format) {
            $response = $this->postJson('/relatorios/generate-matrix', [
                'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
                'filters' => [
                    [
                        'field' => 'prd_cmp',
                        'operator' => '=',
                        'value' => '202401'
                    ]
                ],
                'format' => $format
            ]);

            // Should accept the format (not return 400 for validation)
            $this->assertNotEquals(400, $response->getStatusCode(), "Format {$format} should be accepted");
        }
    }

    /**
     * Test empty data handling
     */
    public function test_empty_data_handling(): void
    {
        $inputData = collect([]);
        $selectedFields = ['prd_cmp', 'prd_uid', 'PRD_QT_P'];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $inputData, $selectedFields);

        $this->assertArrayHasKey('competencias', $result);
        $this->assertArrayHasKey('rows', $result);
        $this->assertCount(0, $result['competencias']);
        $this->assertCount(0, $result['rows']);
    }

    /**
     * Test matrix with multiple numeric fields
     */
    public function test_matrix_with_multiple_numeric_fields(): void
    {
        $inputData = collect([
            (object)[
                'competencia' => '202401',
                'prestador_codigo' => 'HOSP001',
                'prestador_nome' => 'Hospital A',
                'total_quantidade' => 100,
                'total_valor' => 1500.50
            ],
        ]);

        $selectedFields = ['prd_cmp', 'prd_uid', 'PRD_QT_P', 'PRD_VL_P'];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $inputData, $selectedFields);

        // Check that both numeric fields are handled
        $firstRow = $result['rows'][0];
        $this->assertArrayHasKey('PRD_QT_P', $firstRow['totals']);
        $this->assertArrayHasKey('PRD_VL_P', $firstRow['totals']);
        $this->assertEquals(100, $firstRow['totals']['PRD_QT_P']);
        $this->assertEquals(1500.50, $firstRow['totals']['PRD_VL_P']);
    }
}
