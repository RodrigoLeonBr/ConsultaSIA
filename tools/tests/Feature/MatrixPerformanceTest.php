<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Http\Controllers\RelatorioController;
use Illuminate\Support\Collection;

class MatrixPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'username' => 'perftest',
            'role' => 'admin',
            'active' => true,
            'password_changed' => true
        ]);

        $this->controller = new RelatorioController();
    }

    /**
     * Test matrix generation performance with large dataset simulation
     */
    public function test_matrix_performance_with_large_dataset(): void
    {
        // Create simulated large dataset
        $largeDataset = $this->createLargeDataset(1000); // 1000 records
        
        $selectedFields = ['prd_cmp', 'prd_uid', 'PRD_QT_P'];

        $startTime = microtime(true);
        
        // Use reflection to test pivot performance
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $largeDataset, $selectedFields);
        
        $executionTime = microtime(true) - $startTime;

        // Performance assertions
        $this->assertLessThan(5.0, $executionTime, 'Pivot transformation should complete within 5 seconds for 1000 records');
        $this->assertNotEmpty($result['competencias'], 'Should generate competencias');
        $this->assertNotEmpty($result['rows'], 'Should generate rows');
        
        // Memory usage check
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024; // MB
        $this->assertLessThan(128, $memoryUsage, 'Memory usage should be under 128MB');
    }

    /**
     * Test matrix API endpoint performance
     */
    public function test_matrix_api_performance(): void
    {
        $startTime = microtime(true);
        
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => [
                [
                    'field' => 'prd_cmp',
                    'operator' => 'between',
                    'value' => ['202401', '202403'] // Limited range for performance
                ]
            ],
            'format' => 'html'
        ]);
        
        $executionTime = microtime(true) - $startTime;
        
        // Should complete within reasonable time
        $this->assertLessThan(30.0, $executionTime, 'Matrix API should respond within 30 seconds');
        
        // Should not timeout or crash
        $this->assertNotEquals(500, $response->getStatusCode(), 'Should not return server error');
        $this->assertNotEquals(408, $response->getStatusCode(), 'Should not timeout');
    }

    /**
     * Test matrix with many competencias (stress test)
     */
    public function test_matrix_with_many_competencias(): void
    {
        // Create dataset with many competencias
        $manyCompetenciasDataset = $this->createDatasetWithManyCompetencias(24); // 2 years
        
        $selectedFields = ['prd_cmp', 'prd_uid', 'PRD_QT_P'];

        $startTime = microtime(true);
        
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $manyCompetenciasDataset, $selectedFields);
        
        $executionTime = microtime(true) - $startTime;

        // Should handle many columns efficiently
        $this->assertLessThan(10.0, $executionTime, 'Should handle 24 competencias within 10 seconds');
        $this->assertCount(24, $result['competencias'], 'Should generate all 24 competencias');
    }

    /**
     * Test matrix validation performance limits
     */
    public function test_matrix_validation_performance_limits(): void
    {
        // Test the validation that checks for too many competencias
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => [] // No filters - should trigger competencia count check
        ]);

        // The validation should complete quickly
        $this->assertNotEquals(408, $response->getStatusCode(), 'Validation should not timeout');
        
        // Should either succeed or return performance warning
        if ($response->getStatusCode() === 400) {
            $error = $response->json('error');
            $this->assertStringContainsString('competências', $error, 'Should mention competencias in performance warning');
        }
    }

    /**
     * Test export performance
     */
    public function test_matrix_export_performance(): void
    {
        $formats = ['excel', 'csv']; // Skip PDF for performance test
        
        foreach ($formats as $format) {
            $startTime = microtime(true);
            
            $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
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
            
            $executionTime = microtime(true) - $startTime;
            
            // Export should complete within reasonable time
            $this->assertLessThan(15.0, $executionTime, "Export to {$format} should complete within 15 seconds");
        }
    }

    /**
     * Test memory usage with large matrix
     */
    public function test_matrix_memory_usage(): void
    {
        $initialMemory = memory_get_usage(true);
        
        // Create moderately large dataset
        $dataset = $this->createLargeDataset(500);
        $selectedFields = ['prd_cmp', 'prd_uid', 'prd_pa', 'PRD_QT_P', 'PRD_VL_P'];

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('pivotData');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $dataset, $selectedFields);
        
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024; // MB
        
        // Memory increase should be reasonable
        $this->assertLessThan(50, $memoryIncrease, 'Memory increase should be under 50MB for 500 records');
        
        // Clean up
        unset($result, $dataset);
    }

    /**
     * Test concurrent matrix requests simulation
     */
    public function test_matrix_concurrent_requests_simulation(): void
    {
        $requests = [];
        $startTime = microtime(true);
        
        // Simulate multiple requests (sequential for testing)
        for ($i = 0; $i < 3; $i++) {
            $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
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
            
            $requests[] = $response->getStatusCode();
        }
        
        $totalTime = microtime(true) - $startTime;
        
        // All requests should complete
        foreach ($requests as $statusCode) {
            $this->assertNotEquals(500, $statusCode, 'Concurrent requests should not cause server errors');
        }
        
        // Total time should be reasonable
        $this->assertLessThan(60.0, $totalTime, 'Multiple requests should complete within 60 seconds');
    }

    /**
     * Create large dataset for testing
     */
    private function createLargeDataset(int $size): Collection
    {
        $data = [];
        $competencias = ['202401', '202402', '202403', '202404', '202405', '202406'];
        $prestadores = ['HOSP001', 'HOSP002', 'HOSP003', 'HOSP004', 'HOSP005'];
        $procedimentos = ['PROC001', 'PROC002', 'PROC003', 'PROC004'];
        
        for ($i = 0; $i < $size; $i++) {
            $data[] = (object)[
                'competencia' => $competencias[$i % count($competencias)],
                'prestador_codigo' => $prestadores[$i % count($prestadores)],
                'prestador_nome' => 'Hospital ' . chr(65 + ($i % count($prestadores))),
                'procedimento_codigo' => $procedimentos[$i % count($procedimentos)],
                'procedimento_nome' => 'Procedimento ' . ($i % count($procedimentos) + 1),
                'total_quantidade' => rand(10, 500),
                'total_valor' => rand(100, 5000) + (rand(0, 99) / 100)
            ];
        }
        
        return collect($data);
    }

    /**
     * Create dataset with many competencias
     */
    private function createDatasetWithManyCompetencias(int $months): Collection
    {
        $data = [];
        $prestadores = ['HOSP001', 'HOSP002'];
        
        // Generate competencias for specified months
        $competencias = [];
        for ($i = 0; $i < $months; $i++) {
            $year = 2024;
            $month = ($i % 12) + 1;
            if ($i >= 12) $year = 2025;
            $competencias[] = sprintf('%04d%02d', $year, $month);
        }
        
        foreach ($competencias as $comp) {
            foreach ($prestadores as $prest) {
                $data[] = (object)[
                    'competencia' => $comp,
                    'prestador_codigo' => $prest,
                    'prestador_nome' => 'Hospital ' . substr($prest, -1),
                    'total_quantidade' => rand(50, 200)
                ];
            }
        }
        
        return collect($data);
    }
}
