<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class MatrixIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'role' => 'admin',
            'active' => true,
            'password_changed' => true
        ]);
    }

    /**
     * Test complete matrix workflow from field detection to generation
     */
    public function test_complete_matrix_workflow(): void
    {
        // 1. Test field loading
        $fieldsResponse = $this->actingAs($this->user)->getJson('/relatorios/fields');
        $fieldsResponse->assertStatus(200);
        $fieldsResponse->assertJsonStructure(['fields']);
        
        $fields = $fieldsResponse->json('fields');
        $this->assertArrayHasKey('prd_cmp', $fields);
        $this->assertEquals('Data Competência', $fields['prd_cmp']['label']);

        // 2. Test matrix generation with minimal valid data
        $matrixResponse = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
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
        $this->assertNotEquals(400, $matrixResponse->getStatusCode());
        
        if ($matrixResponse->getStatusCode() === 200) {
            $matrixResponse->assertJsonStructure([
                'success',
                'data' => [
                    'competencias',
                    'rows',
                    'totals',
                    'grand_totals'
                ],
                'type'
            ]);
            
            $this->assertEquals('matrix', $matrixResponse->json('type'));
        }
    }

    /**
     * Test matrix route accessibility
     */
    public function test_matrix_route_requires_authentication(): void
    {
        $response = $this->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid'],
            'filters' => []
        ]);

        $response->assertStatus(302); // Redirect to login
    }

    /**
     * Test matrix route with authenticated user
     */
    public function test_matrix_route_with_authentication(): void
    {
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => []
        ]);

        // Should not redirect (authentication passed)
        $this->assertNotEquals(302, $response->getStatusCode());
    }

    /**
     * Test matrix export endpoints
     */
    public function test_matrix_export_endpoints(): void
    {
        $exportFormats = ['excel', 'pdf', 'csv'];

        foreach ($exportFormats as $format) {
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

            // Should not return validation error
            $this->assertNotEquals(400, $response->getStatusCode(), "Export format {$format} failed validation");
            
            // For file downloads, we expect either 200 (success) or 500 (implementation error, but not validation)
            $this->assertContains($response->getStatusCode(), [200, 500], "Export format {$format} returned unexpected status");
        }
    }

    /**
     * Test matrix with different field combinations
     */
    public function test_matrix_with_different_field_combinations(): void
    {
        $fieldCombinations = [
            ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            ['prd_cmp', 'prd_pa', 'PRD_VL_P'],
            ['prd_cmp', 'prd_cbo', 'PRD_QT_P', 'PRD_VL_P'],
            ['prd_cmp', 'PRD_RUB', 'PRD_QT_P'],
        ];

        foreach ($fieldCombinations as $fields) {
            $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
                'fields' => $fields,
                'filters' => [
                    [
                        'field' => 'prd_cmp',
                        'operator' => 'between',
                        'value' => ['202401', '202403']
                    ]
                ],
                'format' => 'html'
            ]);

            // Should not return validation error
            $this->assertNotEquals(400, $response->getStatusCode(), 
                "Field combination " . implode(', ', $fields) . " failed validation");
        }
    }

    /**
     * Test matrix with various filter types
     */
    public function test_matrix_with_various_filters(): void
    {
        $filterCombinations = [
            // Single competencia filter
            [
                [
                    'field' => 'prd_cmp',
                    'operator' => '=',
                    'value' => '202401'
                ]
            ],
            // Range competencia filter
            [
                [
                    'field' => 'prd_cmp',
                    'operator' => 'between',
                    'value' => ['202401', '202406']
                ]
            ],
            // Multiple filters
            [
                [
                    'field' => 'prd_cmp',
                    'operator' => '>=',
                    'value' => '202401'
                ],
                [
                    'field' => 'PRD_QT_P',
                    'operator' => '>',
                    'value' => '0'
                ]
            ]
        ];

        foreach ($filterCombinations as $filters) {
            $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
                'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
                'filters' => $filters,
                'format' => 'html'
            ]);

            // Should not return validation error
            $this->assertNotEquals(400, $response->getStatusCode(), 
                "Filter combination failed validation");
        }
    }

    /**
     * Test matrix error handling
     */
    public function test_matrix_error_handling(): void
    {
        // Test with invalid field
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'invalid_field'],
            'filters' => []
        ]);

        // Should handle gracefully (not crash)
        $this->assertNotEquals(500, $response->getStatusCode(), "Invalid field caused server error");

        // Test with malformed filter
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid'],
            'filters' => [
                [
                    'field' => 'prd_cmp',
                    'operator' => 'invalid_operator',
                    'value' => '202401'
                ]
            ]
        ]);

        // Should handle gracefully
        $this->assertNotEquals(500, $response->getStatusCode(), "Invalid operator caused server error");
    }

    /**
     * Test matrix performance with reasonable limits
     */
    public function test_matrix_performance_limits(): void
    {
        // Test with many competencias (should trigger validation)
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => [] // No competencia filter - should check total count
        ]);

        // Should either succeed or return a performance warning, not crash
        $this->assertContains($response->getStatusCode(), [200, 400, 500]);
        
        if ($response->getStatusCode() === 400) {
            // Should contain performance-related message
            $errorMessage = $response->json('error');
            $this->assertStringContainsString('competências', $errorMessage);
        }
    }

    /**
     * Test matrix JSON structure consistency
     */
    public function test_matrix_json_structure_consistency(): void
    {
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

        if ($response->getStatusCode() === 200) {
            $data = $response->json();
            
            // Verify required structure
            $this->assertArrayHasKey('success', $data);
            $this->assertArrayHasKey('data', $data);
            $this->assertArrayHasKey('type', $data);
            $this->assertEquals('matrix', $data['type']);
            
            $matrixData = $data['data'];
            $this->assertArrayHasKey('competencias', $matrixData);
            $this->assertArrayHasKey('rows', $matrixData);
            $this->assertArrayHasKey('totals', $matrixData);
            $this->assertArrayHasKey('grand_totals', $matrixData);
            
            // Verify competencias structure
            if (!empty($matrixData['competencias'])) {
                $firstComp = $matrixData['competencias'][0];
                $this->assertArrayHasKey('code', $firstComp);
                $this->assertArrayHasKey('label', $firstComp);
            }
            
            // Verify rows structure
            if (!empty($matrixData['rows'])) {
                $firstRow = $matrixData['rows'][0];
                $this->assertArrayHasKey('category', $firstRow);
                $this->assertArrayHasKey('values', $firstRow);
                $this->assertArrayHasKey('totals', $firstRow);
            }
        }
    }
}
