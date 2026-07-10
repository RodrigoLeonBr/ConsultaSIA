<?php

namespace Tests\Feature;

use App\Http\Controllers\RelatorioController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class MatrixPerformanceTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    protected User $user;

    protected RelatorioController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createReportTestUser(['username' => 'perftest']);
        $this->controller = new RelatorioController;
    }

    public function test_matrix_performance_with_large_dataset(): void
    {
        $largeDataset = $this->createLargeDataset(1000);
        $selectedFields = ['prd_cmp', 'prd_uid', 'PRD_QT_P'];

        $startTime = microtime(true);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $largeDataset, $selectedFields);

        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(5.0, $executionTime, 'Pivot transformation should complete within 5 seconds for 1000 records');
        $this->assertNotEmpty($result['competencias']);
        $this->assertPivotHasBody($result);

        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024;
        $this->assertLessThan(128, $memoryUsage, 'Memory usage should be under 128MB');
    }

    public function test_matrix_api_performance(): void
    {
        $startTime = microtime(true);

        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => [
                [
                    'field' => 'prd_cmp',
                    'operator' => 'between',
                    'value' => ['202401', '202403'],
                ],
            ],
            'format' => 'html',
        ]);

        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(30.0, $executionTime, 'Matrix API should respond within 30 seconds');
        $this->assertNotEquals(500, $response->getStatusCode(), 'Should not return server error');
    }

    public function test_matrix_with_many_competencias(): void
    {
        $manyCompetenciasDataset = $this->createDatasetWithManyCompetencias(24);
        $selectedFields = ['prd_cmp', 'prd_uid', 'PRD_QT_P'];

        $startTime = microtime(true);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $manyCompetenciasDataset, $selectedFields);

        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(10.0, $executionTime, 'Should handle 24 competencias within 10 seconds');
        $this->assertCount(24, $result['competencias']);
    }

    public function test_matrix_validation_performance_limits(): void
    {
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => [],
        ]);

        $this->assertContains($response->getStatusCode(), [200, 400, 500]);
    }

    public function test_matrix_export_performance(): void
    {
        $formats = ['excel', 'csv'];

        foreach ($formats as $format) {
            $startTime = microtime(true);

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

            $executionTime = microtime(true) - $startTime;

            $this->assertLessThan(15.0, $executionTime, "Export to {$format} should complete within 15 seconds");
            $this->assertNotEquals(400, $response->getStatusCode());
        }
    }

    public function test_matrix_memory_usage(): void
    {
        $initialMemory = memory_get_usage(true);

        $dataset = $this->createLargeDataset(500);
        $selectedFields = ['prd_cmp', 'prd_uid', 'prd_pa', 'PRD_QT_P', 'PRD_VL_P'];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $dataset, $selectedFields);

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;

        $this->assertLessThan(50, $memoryIncrease, 'Memory increase should be under 50MB for 500 records');
        $this->assertPivotHasBody($result);

        unset($result, $dataset);
    }

    public function test_matrix_concurrent_requests_simulation(): void
    {
        $requests = [];
        $startTime = microtime(true);

        for ($i = 0; $i < 3; $i++) {
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

            $requests[] = $response->getStatusCode();
        }

        $totalTime = microtime(true) - $startTime;

        foreach ($requests as $statusCode) {
            $this->assertNotEquals(500, $statusCode, 'Concurrent requests should not cause server errors');
        }

        $this->assertLessThan(60.0, $totalTime, 'Multiple requests should complete within 60 seconds');
    }

    private function assertPivotHasBody(array $result): void
    {
        if (isset($result['prestadores'])) {
            $this->assertNotEmpty($result['prestadores']);

            return;
        }

        $this->assertNotEmpty($result['rows']);
    }

    private function createLargeDataset(int $size): Collection
    {
        $data = [];
        $competencias = ['202401', '202402', '202403', '202404', '202405', '202406'];
        $prestadores = ['HOSP001', 'HOSP002', 'HOSP003', 'HOSP004', 'HOSP005'];
        $procedimentos = ['PROC001', 'PROC002', 'PROC003', 'PROC004'];

        for ($i = 0; $i < $size; $i++) {
            $data[] = (object) [
                'competencia' => $competencias[$i % count($competencias)],
                'prestador_codigo' => $prestadores[$i % count($prestadores)],
                'prestador_nome' => 'Hospital '.chr(65 + ($i % count($prestadores))),
                'procedimento_codigo' => $procedimentos[$i % count($procedimentos)],
                'procedimento_nome' => 'Procedimento '.($i % count($procedimentos) + 1),
                'total_quantidade' => rand(10, 500),
                'total_valor' => rand(100, 5000) + (rand(0, 99) / 100),
            ];
        }

        return collect($data);
    }

    private function createDatasetWithManyCompetencias(int $months): Collection
    {
        $data = [];
        $prestadores = ['HOSP001', 'HOSP002'];

        $competencias = [];
        for ($i = 0; $i < $months; $i++) {
            $year = 2024;
            $month = ($i % 12) + 1;
            if ($i >= 12) {
                $year = 2025;
            }
            $competencias[] = sprintf('%04d%02d', $year, $month);
        }

        foreach ($competencias as $comp) {
            foreach ($prestadores as $prest) {
                $data[] = (object) [
                    'competencia' => $comp,
                    'prestador_codigo' => $prest,
                    'prestador_nome' => 'Hospital '.substr($prest, -1),
                    'total_quantidade' => rand(50, 200),
                ];
            }
        }

        return collect($data);
    }
}
