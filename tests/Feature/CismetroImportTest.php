<?php

namespace Tests\Feature;

use App\Models\Cismetro;
use App\Services\CismetroImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class CismetroImportTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    private CismetroImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CismetroImportService;
    }

    public function test_normalize_codigo_strips_dots_and_pads(): void
    {
        $this->assertSame('0101010028', CismetroImportService::normalizeCodigo('01.01.01.002-8'));
        $this->assertSame('0040313301', CismetroImportService::normalizeCodigo('40313301'));
        $this->assertSame('0202031039', CismetroImportService::normalizeCodigo('0202031039'));
        $this->assertSame('', CismetroImportService::normalizeCodigo(''));
    }

    public function test_previous_competencia_rolls_year(): void
    {
        $this->assertSame('202407', $this->service->previousCompetencia('202408'));
        $this->assertSame('202312', $this->service->previousCompetencia('202401'));
    }

    public function test_build_result_classifies_created_changed_unchanged(): void
    {
        Cismetro::factory()->create(['codigo' => '0202031039', 'descricao' => 'Exame A', 'valor' => 10.00, 'grupo' => 'EXAMES']);
        Cismetro::factory()->create(['codigo' => '0202031040', 'descricao' => 'Exame B', 'valor' => 20.00, 'grupo' => 'EXAMES']);

        $rows = [
            ['codigo' => '0202031039', 'descricao' => 'Exame A', 'grupo' => 'EXAMES', 'valor' => '15.00'], // changed (valor)
            ['codigo' => '0202031040', 'descricao' => 'Exame B', 'grupo' => 'EXAMES', 'valor' => '20.00'], // unchanged
            ['codigo' => '0202031041', 'descricao' => 'Exame C', 'grupo' => 'EXAMES', 'valor' => '30.00'], // created
        ];

        $result = $this->service->buildResult($rows, [], '202409', '999999', 'Credenciamento 2024');

        $this->assertCount(1, $result['created']);
        $this->assertCount(1, $result['changed']);
        $this->assertSame(1, $result['unchanged']);
        $this->assertSame('0202031041', $result['created'][0]['codigo']);
    }

    public function test_apply_closes_previous_vigencia_on_change(): void
    {
        $old = Cismetro::factory()->create([
            'codigo' => '0202031039', 'descricao' => 'Exame A', 'valor' => 10.00,
            'competencia_inicial' => '202301', 'competencia_final' => '999999',
        ]);

        $rows = [['codigo' => '0202031039', 'descricao' => 'Exame A', 'grupo' => 'EXAMES', 'valor' => '15.00']];
        $result = $this->service->buildResult($rows, [], '202408', '999999', 'Credenciamento 2024');

        $applied = $this->service->applyImport([$result['changed'][0]['key']], $result);

        $this->assertSame(1, $applied['updated']);
        // Versão antiga fechada em 202407.
        $this->assertSame('202407', $old->fresh()->competencia_final);
        // Nova versão vigente com o valor novo.
        $nova = Cismetro::where('codigo', '0202031039')->where('competencia_final', '999999')->first();
        $this->assertSame('202408', $nova->competencia_inicial);
        $this->assertEquals('15.00', $nova->valor);
    }

    public function test_apply_inserts_created_rows(): void
    {
        $rows = [['codigo' => '0202031041', 'descricao' => 'Exame C', 'grupo' => 'EXAMES', 'valor' => '30.00']];
        $result = $this->service->buildResult($rows, [], '202408', '999999', 'Credenciamento 2024');

        $applied = $this->service->applyImport([], $result);

        $this->assertSame(1, $applied['created']);
        $this->assertDatabaseHas('cismetro', ['codigo' => '0202031041', 'competencia_inicial' => '202408', 'valor' => 30.00]);
    }

    public function test_parse_xlsx_extracts_rows(): void
    {
        $path = $this->makeXlsx([
            ['CÓDIGO SUS', 'PROCEDIMENTO', 'VALOR', 'GRUPO'],
            ['0202031039', 'Exame A', 'R$ 1.234,56', 'EXAMES'],
            ['01.01.01.002-8', 'Yoga', '55,00', 'ACOES'],
            ['', '', '', ''],
        ]);

        $parsed = $this->service->parseFile($path, 'xlsx');
        unlink($path);

        $this->assertCount(2, $parsed['rows']);
        $this->assertSame('0202031039', $parsed['rows'][0]['codigo']);
        $this->assertSame('1234.56', $parsed['rows'][0]['valor']);
        $this->assertSame('0101010028', $parsed['rows'][1]['codigo']);
    }

    public function test_parse_pdf_best_effort_when_available(): void
    {
        $pdf = base_path('TabeladeValores01.04.2026.pdf');

        if (! is_file($pdf)) {
            $this->markTestSkipped('PDF de exemplo ausente.');
        }

        try {
            $parsed = $this->service->parseFile($pdf, 'pdf');
        } catch (\RuntimeException $e) {
            $this->markTestSkipped('pdftotext indisponível: '.$e->getMessage());
        }

        $this->assertNotEmpty($parsed['rows']);
        foreach ($parsed['rows'] as $row) {
            $this->assertSame(10, strlen($row['codigo']));
            $this->assertMatchesRegularExpression('/^\d+\.\d{2}$/', $row['valor']);
        }
    }

    public function test_full_http_flow_upload_preview_apply(): void
    {
        $user = $this->createReportTestUser();
        $path = $this->makeXlsx([
            ['CÓDIGO SUS', 'PROCEDIMENTO', 'VALOR', 'GRUPO'],
            ['0202031039', 'Exame A', '61,00', 'EXAMES'],
        ]);

        $upload = new UploadedFile($path, 'TabeladeValores01.08.2024.xlsx', null, null, true);

        $this->actingAs($user)->get(route('cismetro.import'))->assertOk();

        $store = $this->actingAs($user)->post(route('cismetro.import.store'), [
            'arquivo' => $upload,
            'competencia_inicial' => '202408',
            'credenciamento' => 'Credenciamento 2024',
        ]);
        $store->assertRedirect(route('cismetro.import.preview'));

        $this->actingAs($user)->get(route('cismetro.import.preview'))->assertOk()->assertSee('Exame A');

        $apply = $this->actingAs($user)->post(route('cismetro.import.apply'), ['selected' => []]);
        $apply->assertRedirect(route('cismetro.index'));

        $this->assertDatabaseHas('cismetro', [
            'codigo' => '0202031039',
            'competencia_inicial' => '202408',
            'valor' => 61.00,
        ]);
    }

    private function makeXlsx(array $data): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($data, null, 'A1');
        $path = tempnam(sys_get_temp_dir(), 'cis').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }
}
