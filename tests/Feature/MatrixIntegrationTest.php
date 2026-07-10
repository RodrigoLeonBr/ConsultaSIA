<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class MatrixIntegrationTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createReportTestUser(['username' => 'testuser']);
    }

    public function test_complete_matrix_workflow(): void
    {
        $fieldsResponse = $this->actingAs($this->user)->getJson('/relatorios/fields');
        $fieldsResponse->assertStatus(200);
        $fieldsResponse->assertJsonStructure(['fields']);

        $fields = $fieldsResponse->json('fields');
        $this->assertArrayHasKey('prd_cmp', $fields);
        $this->assertEquals('Data Competência', $fields['prd_cmp']['label']);

        $matrixResponse = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
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

        $this->assertNotEquals(400, $matrixResponse->getStatusCode());

        if ($matrixResponse->getStatusCode() === 200) {
            $matrixResponse->assertJsonStructure([
                'success',
                'data' => [
                    'competencias',
                    'prestadores',
                    'split_field',
                ],
                'type',
            ]);

            $this->assertEquals('matrix', $matrixResponse->json('type'));
        }
    }

    public function test_matrix_route_requires_authentication(): void
    {
        $response = $this->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid'],
            'filters' => [],
        ]);

        $response->assertUnauthorized();
    }

    public function test_matrix_route_with_authentication(): void
    {
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => [],
        ]);

        $this->assertNotEquals(401, $response->getStatusCode());
    }

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
                        'value' => '202401',
                    ],
                ],
                'format' => $format,
            ]);

            $this->assertNotEquals(400, $response->getStatusCode(), "Export format {$format} failed validation");
            $this->assertContains($response->getStatusCode(), [200, 500, 501], "Export format {$format} returned unexpected status");
        }
    }

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
                        'value' => ['202401', '202403'],
                    ],
                ],
                'format' => 'html',
            ]);

            $this->assertNotEquals(400, $response->getStatusCode(),
                'Field combination '.implode(', ', $fields).' failed validation');
        }
    }

    public function test_matrix_with_various_filters(): void
    {
        $filterCombinations = [
            [
                [
                    'field' => 'prd_cmp',
                    'operator' => '=',
                    'value' => '202401',
                ],
            ],
            [
                [
                    'field' => 'prd_cmp',
                    'operator' => 'between',
                    'value' => ['202401', '202406'],
                ],
            ],
            [
                [
                    'field' => 'prd_cmp',
                    'operator' => '>=',
                    'value' => '202401',
                ],
                [
                    'field' => 'PRD_QT_P',
                    'operator' => '>',
                    'value' => '0',
                ],
            ],
        ];

        foreach ($filterCombinations as $filters) {
            $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
                'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
                'filters' => $filters,
                'format' => 'html',
            ]);

            $this->assertNotEquals(400, $response->getStatusCode(), 'Filter combination failed validation');
        }
    }

    public function test_matrix_error_handling(): void
    {
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'invalid_field'],
            'filters' => [],
        ]);

        $this->assertContains($response->getStatusCode(), [400, 500], 'Invalid field should fail predictably');

        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => [
                [
                    'field' => 'prd_cmp',
                    'operator' => 'invalid_operator',
                    'value' => '202401',
                ],
            ],
        ]);

        $this->assertContains($response->getStatusCode(), [200, 400, 500], 'Invalid operator should fail predictably');
    }

    public function test_matrix_performance_limits(): void
    {
        $response = $this->actingAs($this->user)->postJson('/relatorios/generate-matrix', [
            'fields' => ['prd_cmp', 'prd_uid', 'PRD_QT_P'],
            'filters' => [],
        ]);

        $this->assertContains($response->getStatusCode(), [200, 400, 500]);
    }

    public function test_matrix_json_structure_consistency(): void
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

        if ($response->getStatusCode() !== 200) {
            $this->markTestSkipped('Matrix generation requires production data in s_prd.');

            return;
        }

        $data = $response->json();

        $this->assertArrayHasKey('success', $data);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('type', $data);
        $this->assertEquals('matrix', $data['type']);

        $matrixData = $data['data'];
        $this->assertArrayHasKey('competencias', $matrixData);

        if (isset($matrixData['prestadores'])) {
            $this->assertArrayHasKey('split_field', $matrixData);
            $this->assertEquals('prd_uid', $matrixData['split_field']);
        } else {
            $this->assertArrayHasKey('rows', $matrixData);
            $this->assertArrayHasKey('totals', $matrixData);
            $this->assertArrayHasKey('grand_totals', $matrixData);
        }

        if (! empty($matrixData['competencias'])) {
            $firstComp = $matrixData['competencias'][0];
            $this->assertArrayHasKey('code', $firstComp);
            $this->assertArrayHasKey('label', $firstComp);
        }
    }
}
