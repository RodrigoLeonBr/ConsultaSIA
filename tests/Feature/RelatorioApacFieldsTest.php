<?php

namespace Tests\Feature;

use App\Http\Controllers\RelatorioApacController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class RelatorioApacFieldsTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createReportTestUser();
    }

    public function test_ano_field_exposed_in_endpoint(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('relatorios.apac.fields'));

        $response->assertOk();
        $fields = $response->json('fields');

        $this->assertArrayHasKey('ano', $fields);
        $this->assertSame('Ano', $fields['ano']['label']);
        $this->assertSame(['=', '>=', '<=', 'between'], $fields['ano']['operators']);
    }

    public function test_build_query_selects_and_filters_ano(): void
    {
        DB::table('s_pap')->insert([
            ['PAP_UID' => '2082179', 'PAP_CMP' => '202511', 'PAP_NUM' => '1', 'PAP_PA' => '0301010010', 'PAP_QT_P' => 5],
            ['PAP_UID' => '2082179', 'PAP_CMP' => '202601', 'PAP_NUM' => '2', 'PAP_PA' => '0301010010', 'PAP_QT_P' => 3],
            ['PAP_UID' => '2082179', 'PAP_CMP' => '202603', 'PAP_NUM' => '3', 'PAP_PA' => '0301010010', 'PAP_QT_P' => 7],
        ]);

        $controller = new RelatorioApacController;
        $method = new \ReflectionMethod($controller, 'buildQuery');
        $method->setAccessible(true);

        $query = $method->invoke(
            $controller,
            ['ano', 'PAP_QT_P'],
            [['field' => 'ano', 'operator' => '=', 'value' => '2026']],
        );

        $rows = $query->get();

        $this->assertCount(1, $rows);
        $this->assertSame('2026', $rows[0]->ano);
        $this->assertSame(10.0, (float) $rows[0]->total_quantidade);
    }
}
