<?php

namespace Tests\Feature;

use App\Http\Controllers\EsusImportController;
use App\Models\SEsus;
use App\Services\EsusImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class EsusImportTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.esus.base_url' => 'http://esus.test',
            'services.esus.username' => 'u',
            'services.esus.password' => 'p',
        ]);
    }

    private function fakeApi(array $producao): void
    {
        Http::fake([
            'http://esus.test/auth/login' => Http::response(['token' => 'jwt-abc']),
            'http://esus.test/api/cadastros/procedimentos-sigtap/competencias' => Http::response(['2026-01']),
            'http://esus.test/api/cadastros/procedimentos-sigtap/producao*' => Http::response($producao),
        ]);
    }

    private function row(string $unidade, string $sigtap, int $qtd, string $desc = 'X', string $cnes = ''): array
    {
        return [
            'competencia' => '2026-01',
            'cnes' => $cnes,
            'unidade' => $unidade,
            'tipo_relatorio' => 'procedimentos_individualizados',
            'bloco' => 'Bloco',
            'descricao_esus' => $desc,
            'codigo_sigtap' => $sigtap,
            'descricao_sigtap' => 'PROC '.$sigtap,
            'quantidade' => $qtd,
        ];
    }

    public function test_apply_imports_all_rows_including_without_cnes(): void
    {
        $this->fakeApi([
            $this->row('COM CNES', '0301100209', 10, 'X', '7169698'),
            $this->row('SEM CNES', '0214010058', 5, 'Y'),
        ]);

        $result = EsusImportService::make()->apply('2026-01');

        $this->assertSame(2, $result['inserted']);
        $this->assertSame(2, SEsus::count());
        $this->assertDatabaseHas('s_esus', ['unidade' => 'COM CNES', 'cnes' => '7169698']);
        $this->assertDatabaseHas('s_esus', ['unidade' => 'SEM CNES', 'cnes' => null]);
    }

    public function test_apply_separates_same_sigtap_by_tipo_and_sums_identical_grain(): void
    {
        $this->fakeApi([
            // Mesmo SIGTAP + descrição, tipos diferentes → 2 linhas distintas.
            array_merge($this->row('U', '0301010030', 297, 'CONSULTA', '111'), ['tipo_relatorio' => 'procedimentos_individualizados']),
            array_merge($this->row('U', '0301010030', 111, 'CONSULTA', '111'), ['tipo_relatorio' => 'atendimento_odontologico']),
            // Grão idêntico repetido → soma (10 + 5 = 15).
            $this->row('U', '0214010058', 10, 'HIV', '111'),
            $this->row('U', '0214010058', 5, 'HIV', '111'),
        ]);

        $result = EsusImportService::make()->apply('2026-01');

        $this->assertSame(3, $result['inserted']);
        $this->assertSame(3, SEsus::count());
        $this->assertDatabaseHas('s_esus', [
            'codigo_sigtap' => '0214010058', 'descricao_esus' => 'HIV', 'quantidade' => 15,
        ]);
    }

    public function test_apply_replaces_existing_competencia(): void
    {
        DB::table('s_esus')->insert([
            'competencia' => '2026-01', 'cnes' => '9999999', 'unidade' => 'ANTIGO',
            'codigo_sigtap' => '0000000000', 'quantidade' => 1,
        ]);

        $this->fakeApi([$this->row('U', '0301100209', 10, 'X', '1234567')]);

        $result = EsusImportService::make()->apply('2026-01');

        $this->assertSame(1, $result['replaced']);
        $this->assertSame(1, SEsus::count());
        $this->assertDatabaseMissing('s_esus', ['unidade' => 'ANTIGO']);
    }

    public function test_preview_counts_units_without_writing(): void
    {
        $this->fakeApi([
            $this->row('A', '0301100209', 10, 'X', '1234567'),
            $this->row('B', '0301100209', 99, 'X'),
        ]);

        $preview = EsusImportService::make()->preview('2026-01');

        $this->assertSame(2, $preview['total_linhas']);
        $this->assertSame(1, $preview['sem_cnes']);
        $this->assertCount(2, $preview['unidades']);
        $this->assertSame(0, SEsus::count());
    }

    public function test_apply_blocks_reimport_without_confirmation(): void
    {
        DB::table('s_esus')->insert([
            'competencia' => '2026-01', 'cnes' => '7169698', 'unidade' => 'U',
            'codigo_sigtap' => '0301100209', 'quantidade' => 1,
        ]);
        $this->fakeApi([$this->row('U', '0301100209', 10, 'X', '7169698')]);

        $user = $this->createReportTestUser();

        session([EsusImportController::SESSION_KEY => EsusImportService::make()->preview('2026-01')]);
        $this->actingAs($user)
            ->post(route('esus.import.apply'))
            ->assertRedirect();
        $this->assertDatabaseHas('s_esus', ['competencia' => '2026-01', 'quantidade' => 1]);

        session([EsusImportController::SESSION_KEY => EsusImportService::make()->preview('2026-01')]);
        $this->actingAs($user)
            ->post(route('esus.import.apply'), ['confirm_replace' => '1'])
            ->assertRedirect(route('esus.import'));
        $this->assertDatabaseMissing('s_esus', ['quantidade' => 1]);
        $this->assertDatabaseHas('s_esus', ['competencia' => '2026-01', 'quantidade' => 10]);
    }

    public function test_producao_index_requires_auth(): void
    {
        $this->get(route('esus.index'))->assertRedirect(route('login'));

        $this->actingAs($this->createReportTestUser())
            ->get(route('esus.index'))
            ->assertOk();
    }
}
