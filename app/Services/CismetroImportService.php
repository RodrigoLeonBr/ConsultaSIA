<?php

namespace App\Services;

use App\Models\Cismetro;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\Process\ExecutableFinder;

/**
 * Importa a tabela de valores CISMETRO (XLSX ou PDF) com vigência.
 *
 * Dataset é pequeno (~1600 linhas), então processa em passe único —
 * sem o streaming em chunks do SusPaulistaXlsxImportService.
 */
class CismetroImportService
{
    /** @var array<string, string> Campos mutáveis comparados no diff. */
    private const IMPORTABLE_FIELDS = [
        'valor' => 'Valor',
        'grupo' => 'Grupo',
    ];

    /**
     * Extrai linhas normalizadas do arquivo.
     *
     * @return array{rows: list<array{codigo: string, descricao: string, grupo: ?string, valor: string}>, skipped: list<array{ref: string, reason: string}>}
     */
    public function parseFile(string $path, string $extension): array
    {
        return match (strtolower($extension)) {
            'xlsx' => $this->parseXlsx($path),
            'pdf' => $this->parsePdf($path),
            default => throw new \InvalidArgumentException('Formato não suportado: '.$extension),
        };
    }

    /**
     * Compara as linhas com a versão vigente e classifica em novos/alterados/inalterados.
     *
     * @param  list<array{codigo: string, descricao: string, grupo: ?string, valor: string}>  $rows
     * @param  list<array{ref: string, reason: string}>  $skipped
     * @return array<string, mixed>
     */
    public function buildResult(array $rows, array $skipped, string $competenciaInicial, string $competenciaFinal, string $credenciamento): array
    {
        $existing = Cismetro::query()
            ->where('competencia_final', '999999')
            ->get(['id', 'codigo', 'descricao', 'grupo', 'valor', 'competencia_inicial'])
            ->keyBy(fn (Cismetro $c) => $this->rowKey($c->codigo, $c->descricao));

        $created = [];
        $changed = [];
        $unchanged = 0;
        $seen = [];

        foreach ($rows as $row) {
            $key = $this->rowKey($row['codigo'], $row['descricao']);

            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;

            $current = $existing->get($key);

            if ($current === null) {
                $created[] = $row + ['key' => $key];

                continue;
            }

            $diffs = $this->diff($current, $row);

            if ($diffs === []) {
                $unchanged++;

                continue;
            }

            $changed[] = $row + ['key' => $key, 'diffs' => $diffs];
        }

        return [
            'competencia_inicial' => $competenciaInicial,
            'competencia_final' => $competenciaFinal,
            'credenciamento' => $credenciamento,
            'created' => $created,
            'changed' => $changed,
            'unchanged' => $unchanged,
            'skipped' => $skipped,
            'total_parsed' => count($seen),
        ];
    }

    /**
     * Insere os novos e aplica as alterações selecionadas, fechando a vigência anterior.
     *
     * @param  list<string>  $selectedKeys  Chaves de linhas alteradas a aplicar.
     * @param  array<string, mixed>  $result
     * @return array{created: int, updated: int}
     */
    public function applyImport(array $selectedKeys, array $result): array
    {
        $competenciaInicial = $result['competencia_inicial'];
        $competenciaFinal = $result['competencia_final'];
        $credenciamento = $result['credenciamento'];
        $now = now()->toDateTimeString();

        $createdCount = 0;
        $updatedCount = 0;

        DB::transaction(function () use ($result, $selectedKeys, $competenciaInicial, $competenciaFinal, $credenciamento, $now, &$createdCount, &$updatedCount): void {
            $inserts = [];

            foreach ($result['created'] as $row) {
                $inserts[] = $this->newRowPayload($row, $competenciaInicial, $competenciaFinal, $credenciamento, $now);
                $createdCount++;
            }

            $selected = array_flip($selectedKeys);

            foreach ($result['changed'] as $row) {
                if (! isset($selected[$row['key']])) {
                    continue;
                }

                $active = Cismetro::query()
                    ->where('codigo', $row['codigo'])
                    ->where('descricao', $row['descricao'])
                    ->where('competencia_final', '999999')
                    ->first();

                // Correção da mesma versão: atualiza em lugar.
                if ($active !== null && $active->competencia_inicial === $competenciaInicial) {
                    $active->update([
                        'valor' => $row['valor'],
                        'grupo' => $row['grupo'] ?? $active->grupo,
                    ]);
                    $updatedCount++;

                    continue;
                }

                // Nova versão: fecha a anterior e insere.
                if ($active !== null) {
                    $active->update(['competencia_final' => $this->previousCompetencia($competenciaInicial)]);
                }

                $inserts[] = $this->newRowPayload($row, $competenciaInicial, $competenciaFinal, $credenciamento, $now);
                $updatedCount++;
            }

            foreach (array_chunk($inserts, 500) as $chunk) {
                DB::table('cismetro')->insert($chunk);
            }
        });

        return ['created' => $createdCount, 'updated' => $updatedCount];
    }

    public function previousCompetencia(string $competencia): string
    {
        $year = (int) substr($competencia, 0, 4);
        $month = (int) substr($competencia, 4, 2);
        $month--;

        if ($month < 1) {
            $month = 12;
            $year--;
        }

        return sprintf('%04d%02d', $year, $month);
    }

    public static function normalizeCodigo(mixed $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value) ?? '';

        if ($digits === '') {
            return '';
        }

        return str_pad($digits, 10, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array{codigo: string, descricao: string, grupo: ?string, valor: string}  $row
     * @return array<string, mixed>
     */
    private function newRowPayload(array $row, string $competenciaInicial, string $competenciaFinal, string $credenciamento, string $now): array
    {
        return [
            'codigo' => $row['codigo'],
            'credenciamento' => $credenciamento,
            'grupo' => $row['grupo'] ?? '',
            'descricao' => $row['descricao'],
            'valor' => $row['valor'],
            'tipo_valor' => Cismetro::TIPO_MUNICIPIO,
            'competencia_inicial' => $competenciaInicial,
            'competencia_final' => $competenciaFinal,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    private function rowKey(string $codigo, string $descricao): string
    {
        return $codigo.'|'.mb_strtolower(trim($descricao));
    }

    /**
     * @param  array{grupo: ?string, valor: string}  $row
     * @return list<array{field: string, label: string, atual: mixed, novo: mixed}>
     */
    private function diff(Cismetro $current, array $row): array
    {
        $diffs = [];

        $comparisons = [
            'valor' => [number_format((float) $current->valor, 2, '.', ''), number_format((float) $row['valor'], 2, '.', '')],
            'grupo' => [(string) $current->grupo, (string) ($row['grupo'] ?? $current->grupo)],
        ];

        foreach ($comparisons as $field => [$atual, $novo]) {
            if ($atual !== $novo) {
                $diffs[] = [
                    'field' => $field,
                    'label' => self::IMPORTABLE_FIELDS[$field],
                    'atual' => $atual,
                    'novo' => $novo,
                ];
            }
        }

        return $diffs;
    }

    /**
     * @return array{rows: list<array{codigo: string, descricao: string, grupo: ?string, valor: string}>, skipped: list<array{ref: string, reason: string}>}
     */
    private function parseXlsx(string $path): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();

        $map = $this->mapXlsxColumns($sheet);

        if ($map['codigo'] === null || $map['valor'] === null) {
            $spreadsheet->disconnectWorksheets();

            throw new \InvalidArgumentException('Colunas obrigatórias não encontradas no XLSX. Esperado: código e valor.');
        }

        $rows = [];
        $skipped = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($r = 2; $r <= $highestRow; $r++) {
            $codigo = self::normalizeCodigo($sheet->getCell($map['codigo'].$r)->getValue());
            $valor = $this->decimalValue($sheet->getCell($map['valor'].$r)->getValue());
            $descricao = $map['descricao'] !== null
                ? mb_substr(trim((string) $sheet->getCell($map['descricao'].$r)->getValue()), 0, 180)
                : '';

            if ($codigo === '' || $valor === null) {
                if ($codigo !== '' || $descricao !== '') {
                    $skipped[] = ['ref' => 'linha '.$r, 'reason' => 'Código ou valor ausente/inválido'];
                }

                continue;
            }

            $rows[] = [
                'codigo' => $codigo,
                'descricao' => $descricao,
                'grupo' => $map['grupo'] !== null ? mb_substr(trim((string) $sheet->getCell($map['grupo'].$r)->getValue()), 0, 40) : null,
                'valor' => $valor,
            ];
        }

        $spreadsheet->disconnectWorksheets();

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    /**
     * PDF é multi-coluna renderizada — parse é best-effort: só linhas onde
     * código+descrição+valor aparecem juntos. O resto vai para "skipped".
     *
     * @return array{rows: list<array{codigo: string, descricao: string, grupo: ?string, valor: string}>, skipped: list<array{ref: string, reason: string}>}
     */
    private function parsePdf(string $path): array
    {
        $text = $this->pdfToText($path);

        $rows = [];
        $skipped = [];

        // codigo (pontilhado ou 6-11 dígitos) + descrição + valor (R$ x.xxx,xx) + grupo opcional
        $pattern = '/^\s*((?:\d{2}\.){3}\d{3}-\d|\d{6,11})\s+(.+?)\s+(?:R\$\s*)?(\d{1,3}(?:\.\d{3})*,\d{2})\s*(.*)$/u';

        foreach (preg_split('/\r?\n/', $text) as $line) {
            $line = trim($line);

            if ($line === '' || preg_match('/^(CISMETRO|C.DIGO|CODIGO|Total de|Grupo de|Credencia|Avenida|Sitcon|PROCEDIMENTO|VALOR|GRUPO)/iu', $line)) {
                continue;
            }

            if (! preg_match($pattern, $line, $m)) {
                if (preg_match('/^(?:(?:\d{2}\.){3}\d{3}-\d|\d{6,11})\s+\D/u', $line)) {
                    $skipped[] = ['ref' => mb_substr($line, 0, 60), 'reason' => 'Valor não localizado na mesma linha (revisar no PDF)'];
                }

                continue;
            }

            $codigo = self::normalizeCodigo($m[1]);
            $descricao = mb_substr(trim($m[2]), 0, 180);
            $valor = $this->decimalValue($m[3]);
            $grupo = trim($m[4]);

            if ($codigo === '' || $valor === null || $descricao === '') {
                continue;
            }

            $rows[] = [
                'codigo' => $codigo,
                'descricao' => $descricao,
                'grupo' => $grupo !== '' ? mb_substr($grupo, 0, 40) : null,
                'valor' => $valor,
            ];
        }

        return ['rows' => $rows, 'skipped' => $skipped];
    }

    private function pdfToText(string $path): string
    {
        $binary = (new ExecutableFinder)->find('pdftotext');

        if ($binary === null) {
            throw new \RuntimeException('pdftotext (poppler) não encontrado no servidor. Use a importação por XLSX.');
        }

        $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        // Args em array: sem shell, sem injeção.
        $process = proc_open([$binary, '-layout', '-enc', 'UTF-8', $path, '-'], $descriptor, $pipes);

        if (! is_resource($process)) {
            throw new \RuntimeException('Falha ao executar pdftotext.');
        }

        $text = (string) stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return $text;
    }

    /**
     * @return array{codigo: ?string, descricao: ?string, valor: ?string, grupo: ?string}
     */
    private function mapXlsxColumns(Worksheet $sheet): array
    {
        $map = ['codigo' => null, 'descricao' => null, 'valor' => null, 'grupo' => null];
        $highest = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($i = 1; $i <= $highest; $i++) {
            $col = Coordinate::stringFromColumnIndex($i);
            $header = $this->normalizeHeader((string) $sheet->getCell($col.'1')->getValue());

            if ($header === '') {
                continue;
            }

            if ($map['codigo'] === null && str_contains($header, 'cod')) {
                $map['codigo'] = $col;
            } elseif ($map['descricao'] === null && (str_contains($header, 'proced') || str_contains($header, 'descri'))) {
                $map['descricao'] = $col;
            } elseif ($map['valor'] === null && str_contains($header, 'valor')) {
                $map['valor'] = $col;
            } elseif ($map['grupo'] === null && str_contains($header, 'grupo')) {
                $map['grupo'] = $col;
            }
        }

        return $map;
    }

    private function decimalValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(['R$', ' '], '', $value);
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        if (! is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = str_replace(
            ['á', 'à', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'],
            ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'],
            $header
        );

        return preg_replace('/\s+/', ' ', $header) ?? $header;
    }
}
