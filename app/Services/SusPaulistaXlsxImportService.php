<?php

namespace App\Services;

use App\Models\SusPaulista;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SusPaulistaXlsxImportService
{
    public const CHUNK_ROW_SIZE = 5000;

    public static function resolveChunkRowSize(int $totalRows): int
    {
        if ($totalRows > 100_000) {
            return 10_000;
        }

        if ($totalRows > 20_000) {
            return 5_000;
        }

        return 2_000;
    }

    private const IMPORTABLE_FIELDS = [
        'descricao' => 'Descrição',
        'tab_paulista' => 'Tabela SUS Paulista',
        'complementacao_tsp' => 'Complementação TSP',
    ];

    private const DEFAULT_COMPETENCIA_INICIAL = '202602';

    private const DEFAULT_COMPETENCIA_FINAL = '999999';

    private const PREVIEW_LIMIT = 200;

    /**
     * @return array{
     *     column_map: array<string, ?string>,
     *     total_rows: int,
     * }
     */
    public function prepareImport(string $xlsxPath): array
    {
        $columnMap = $this->resolveColumnMap($xlsxPath);

        if ($columnMap['codigo'] === null || $columnMap['tab_paulista'] === null) {
            throw new \InvalidArgumentException(
                'Colunas obrigatórias não encontradas. Esperado: código e Tabela SUS Paulista/Tab Paulista.'
            );
        }

        return [
            'column_map' => $columnMap,
            'total_rows' => $this->countDataRows($xlsxPath),
        ];
    }

    /**
     * @param  array<string, mixed>  $job
     * @return array{
     *     done: bool,
     *     progress: array{next_row: int, total_rows: int, percent: float},
     *     chunk: array{rows_scanned: int, rows_valid: int, created: int, changed: int, unchanged: int, skipped: int},
     *     result?: array<string, mixed>,
     * }
     */
    public function processChunk(array &$job, bool $autoCreate = true): array
    {
        $startRow = (int) $job['next_row'];
        $totalRows = (int) $job['total_rows'];
        $chunkSize = (int) ($job['chunk_row_size'] ?? self::resolveChunkRowSize($totalRows));

        if ($startRow > $totalRows) {
            $result = $this->finalizeJob($job);

            return [
                'done' => true,
                'progress' => $this->buildProgress($totalRows + 1, $totalRows),
                'chunk' => $this->emptyChunkStats(),
                'result' => $result,
            ];
        }

        $endRow = min($startRow + $chunkSize - 1, $totalRows);
        $rows = $this->readXlsxChunk(
            $job['file_path'],
            $job['column_map'],
            $startRow,
            $endRow
        );

        $chunkStats = $this->emptyChunkStats();
        $chunkStats['rows_scanned'] = $endRow - $startRow + 1;

        $pendingInsert = [];
        $now = now()->toDateTimeString();

        foreach ($rows as $row) {
            $chunkStats['rows_valid']++;
            $codigo = $row['codigo'];

            if ($codigo === '') {
                $chunkStats['skipped']++;
                $job['result']['skipped_count']++;
                $this->pushLimited($job['result']['skipped'], [
                    'codigo' => '(vazio)',
                    'reason' => 'Código ausente no XLSX',
                ]);
                continue;
            }

            if (isset($job['seen_codigos'][$codigo])) {
                continue;
            }

            $job['seen_codigos'][$codigo] = true;
            $current = $job['existing'][$codigo] ?? null;

            if ($current === null) {
                $chunkStats['created']++;
                $job['result']['created_count']++;

                if ($autoCreate) {
                    $pendingInsert[] = array_merge($row, [
                        'modalidade' => $job['modalidade'],
                        'competencia_inicial' => $job['competencia_inicial'],
                        'competencia_final' => $job['competencia_final'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $this->pushLimited($job['result']['created'], [
                    'codigo' => $codigo,
                    'descricao' => $row['descricao'] ?? '',
                ]);

                continue;
            }

            $diffs = $this->diffSnapshot($current, $row);

            if ($diffs === []) {
                $chunkStats['unchanged']++;
                $job['result']['unchanged']++;
                continue;
            }

            $chunkStats['changed']++;
            $job['result']['changed'][] = [
                'codigo' => $codigo,
                'descricao' => $row['descricao'] ?? ($current['descricao'] ?? ''),
                'diffs' => $diffs,
                'xlsx_data' => $row,
            ];
        }

        if ($autoCreate && $pendingInsert !== []) {
            foreach (array_chunk($pendingInsert, 500) as $chunk) {
                DB::table('sus_paulista')->insert($chunk);
            }
        }

        $job['next_row'] = $endRow + 1;
        $job['result']['total_xlsx'] = count($job['seen_codigos']);

        $done = $job['next_row'] > $totalRows;

        if ($done) {
            $result = $this->finalizeJob($job);

            return [
                'done' => true,
                'progress' => $this->buildProgress($totalRows + 1, $totalRows),
                'chunk' => $chunkStats,
                'result' => $result,
            ];
        }

        return [
            'done' => false,
            'progress' => $this->buildProgress($job['next_row'], $totalRows),
            'chunk' => $chunkStats,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createEmptyJob(
        string $filePath,
        string $modalidade,
        array $columnMap,
        int $totalRows,
        string $competenciaInicial = self::DEFAULT_COMPETENCIA_INICIAL,
        string $competenciaFinal = self::DEFAULT_COMPETENCIA_FINAL,
    ): array {
        $modalidade = strtolower($modalidade);

        $existing = SusPaulista::query()
            ->where('modalidade', $modalidade)
            ->active()
            ->get(['codigo', 'descricao', 'tab_paulista', 'complementacao_tsp', 'competencia_inicial'])
            ->keyBy('codigo')
            ->map(fn (SusPaulista $record) => [
                'codigo' => $record->codigo,
                'descricao' => $record->descricao,
                'tab_paulista' => number_format((float) $record->tab_paulista, 2, '.', ''),
                'complementacao_tsp' => number_format((float) $record->complementacao_tsp, 2, '.', ''),
                'competencia_inicial' => $record->competencia_inicial,
            ])
            ->all();

        return [
            'file_path' => $filePath,
            'modalidade' => $modalidade,
            'competencia_inicial' => $competenciaInicial,
            'competencia_final' => $competenciaFinal,
            'column_map' => $columnMap,
            'total_rows' => $totalRows,
            'chunk_row_size' => self::resolveChunkRowSize($totalRows),
            'next_row' => 2,
            'seen_codigos' => [],
            'existing' => $existing,
            'result' => $this->createEmptyResult($modalidade, $competenciaInicial, $competenciaFinal),
        ];
    }

    /**
     * @param  list<string>  $codigos
     */
    public function applyChanges(array $codigos, array $changedItems, array $context): int
    {
        $byCodigo = collect($changedItems)->keyBy('codigo');
        $modalidade = $context['modalidade'];
        $competenciaInicial = $context['competencia_inicial'];
        $competenciaFinal = $context['competencia_final'];
        $applied = 0;

        foreach ($codigos as $codigo) {
            $item = $byCodigo->get($codigo);

            if ($item === null || empty($item['xlsx_data'])) {
                continue;
            }

            $active = SusPaulista::query()
                ->where('modalidade', $modalidade)
                ->where('codigo', $codigo)
                ->active()
                ->first();

            $payload = array_intersect_key(
                $item['xlsx_data'],
                array_flip(array_merge(['codigo'], array_keys(self::IMPORTABLE_FIELDS)))
            );

            if ($active !== null && $active->competencia_inicial === $competenciaInicial) {
                $active->update($payload);
                $applied++;
                continue;
            }

            if ($active !== null) {
                $active->update([
                    'competencia_final' => $this->previousCompetencia($competenciaInicial),
                ]);
            }

            SusPaulista::create(array_merge($payload, [
                'modalidade' => $modalidade,
                'competencia_inicial' => $competenciaInicial,
                'competencia_final' => $competenciaFinal,
            ]));

            $applied++;
        }

        return $applied;
    }

    public static function normalizeCodigo(mixed $value): string
    {
        $digits = preg_replace('/\D/', '', (string) $value);

        if ($digits === '') {
            return '';
        }

        return str_pad($digits, 10, '0', STR_PAD_LEFT);
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

    /**
     * @return array{codigo: ?string, descricao: ?string, tab_sus_ms: ?string, tab_paulista: ?string, complementacao_tsp: ?string}
     */
    private function resolveColumnMap(string $xlsxPath): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row === 1;
            }
        });

        $spreadsheet = $reader->load($xlsxPath);
        $columnMap = $this->mapColumns($spreadsheet->getActiveSheet());
        $spreadsheet->disconnectWorksheets();

        return $columnMap;
    }

    private function countDataRows(string $xlsxPath): int
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class implements IReadFilter {
            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $columnAddress === 'A';
            }
        });

        $spreadsheet = $reader->load($xlsxPath);
        $totalRows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        $spreadsheet->disconnectWorksheets();

        return max(1, $totalRows);
    }

    /**
     * @param  array<string, ?string>  $columnMap
     * @return list<array<string, mixed>>
     */
    private function readXlsxChunk(string $xlsxPath, array $columnMap, int $startRow, int $endRow): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $reader->setReadFilter(new class($startRow, $endRow) implements IReadFilter {
            public function __construct(private int $startRow, private int $endRow) {}

            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row >= $this->startRow && $row <= $this->endRow;
            }
        });

        $spreadsheet = $reader->load($xlsxPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        for ($row = $startRow; $row <= $endRow; $row++) {
            $mapped = $this->mapRow($sheet, $row, $columnMap);

            if ($mapped !== null) {
                $rows[] = $mapped;
            }
        }

        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    private function finalizeJob(array $job): array
    {
        $result = $job['result'];
        $onlyMysqlCount = 0;

        foreach ($job['existing'] as $codigo => $record) {
            if (! isset($job['seen_codigos'][$codigo])) {
                $onlyMysqlCount++;
                $this->pushLimited($result['only_mysql'], [
                    'codigo' => $codigo,
                    'descricao' => $record['descricao'] ?? '',
                ]);
            }
        }

        $result['only_mysql_count'] = $onlyMysqlCount;
        $result['total_xlsx'] = count($job['seen_codigos']);

        return $result;
    }

    /**
     * @return array{next_row: int, total_rows: int, percent: float}
     */
    private function buildProgress(int $nextRow, int $totalRows): array
    {
        $denominator = max(1, $totalRows - 1);

        return [
            'next_row' => $nextRow,
            'total_rows' => $totalRows,
            'percent' => round(min(100, (($nextRow - 2) / $denominator) * 100), 1),
        ];
    }

    /**
     * @return array{rows_scanned: int, rows_valid: int, created: int, changed: int, unchanged: int, skipped: int}
     */
    private function emptyChunkStats(): array
    {
        return [
            'rows_scanned' => 0,
            'rows_valid' => 0,
            'created' => 0,
            'changed' => 0,
            'unchanged' => 0,
            'skipped' => 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createEmptyResult(string $modalidade, string $competenciaInicial, string $competenciaFinal): array
    {
        return [
            'modalidade' => $modalidade,
            'competencia_inicial' => $competenciaInicial,
            'competencia_final' => $competenciaFinal,
            'total_xlsx' => 0,
            'created' => [],
            'created_count' => 0,
            'changed' => [],
            'unchanged' => 0,
            'only_mysql' => [],
            'only_mysql_count' => 0,
            'skipped' => [],
            'skipped_count' => 0,
        ];
    }

    /**
     * @return array{codigo: ?string, descricao: ?string, tab_sus_ms: ?string, tab_paulista: ?string, complementacao_tsp: ?string}
     */
    private function mapColumns(Worksheet $sheet): array
    {
        $map = [
            'codigo' => null,
            'descricao' => null,
            'tab_sus_ms' => null,
            'tab_paulista' => null,
            'complementacao_tsp' => null,
        ];

        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        for ($index = 1; $index <= $highestColumn; $index++) {
            $column = Coordinate::stringFromColumnIndex($index);
            $header = $this->normalizeHeader((string) $sheet->getCell($column . '1')->getValue());

            if ($header === '') {
                continue;
            }

            if ($map['codigo'] === null && str_contains($header, 'cod') && str_contains($header, 'proced')) {
                $map['codigo'] = $column;
            } elseif ($map['descricao'] === null && str_contains($header, 'proced') && ! str_contains($header, 'cod')) {
                $map['descricao'] = $column;
            } elseif ($map['tab_sus_ms'] === null && str_contains($header, 'tab sus ms')) {
                $map['tab_sus_ms'] = $column;
            } elseif ($map['complementacao_tsp'] === null && str_contains($header, 'complementacao') && str_contains($header, 'tsp')) {
                $map['complementacao_tsp'] = $column;
            } elseif ($map['tab_paulista'] === null && (
                str_contains($header, 'tabela sus paulista')
                || $header === 'tab paulista'
                || (str_contains($header, 'tab paulista') && ! str_contains($header, 'vl_'))
            )) {
                $map['tab_paulista'] = $column;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, ?string>  $columnMap
     * @return array<string, mixed>|null
     */
    private function mapRow(Worksheet $sheet, int $row, array $columnMap): ?array
    {
        $codigo = self::normalizeCodigo($sheet->getCell($columnMap['codigo'] . $row)->getValue());

        if ($codigo === '') {
            return null;
        }

        $descricaoCol = $columnMap['descricao'] ?? 'B';
        $descricao = mb_substr(trim((string) $sheet->getCell($descricaoCol . $row)->getValue()), 0, 180);
        $tabPaulista = $this->decimalValue($sheet->getCell($columnMap['tab_paulista'] . $row)->getValue());

        if ($tabPaulista === null) {
            return null;
        }

        $complementacao = null;

        if ($columnMap['complementacao_tsp'] !== null) {
            $complementacao = $this->decimalValue($sheet->getCell($columnMap['complementacao_tsp'] . $row)->getValue());
        }

        if ($complementacao === null && $columnMap['tab_sus_ms'] !== null) {
            $tabSusMs = $this->decimalValue($sheet->getCell($columnMap['tab_sus_ms'] . $row)->getValue());

            if ($tabSusMs !== null) {
                $complementacao = number_format((float) $tabPaulista - (float) $tabSusMs, 2, '.', '');
            }
        }

        if ($complementacao === null) {
            return null;
        }

        return [
            'codigo' => $codigo,
            'descricao' => $descricao !== '' ? $descricao : null,
            'tab_paulista' => $tabPaulista,
            'complementacao_tsp' => $complementacao,
        ];
    }

    private function decimalValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            if (str_starts_with(trim($value), '=')) {
                return null;
            }

            $value = str_replace(['.', ' '], ['', ''], $value);
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
        $header = preg_replace('/\s+/', ' ', $header) ?? $header;

        return $header;
    }

    /**
     * @param  list<array<string, mixed>>  $bucket
     */
    private function pushLimited(array &$bucket, array $item): void
    {
        if (count($bucket) < self::PREVIEW_LIMIT) {
            $bucket[] = $item;
        }
    }

    /**
     * @param  array<string, mixed>  $current
     * @return list<array{field: string, label: string, mysql: mixed, xlsx: mixed}>
     */
    private function diffSnapshot(array $current, array $xlsxRow): array
    {
        $diffs = [];

        foreach (self::IMPORTABLE_FIELDS as $field => $label) {
            $mysqlVal = $current[$field] ?? null;
            $xlsxVal = $xlsxRow[$field] ?? null;

            if (in_array($field, ['tab_paulista', 'complementacao_tsp'], true)) {
                $mysqlVal = number_format((float) $mysqlVal, 2, '.', '');
                $xlsxVal = number_format((float) $xlsxVal, 2, '.', '');
            }

            if ((string) ($mysqlVal ?? '') !== (string) ($xlsxVal ?? '')) {
                $diffs[] = [
                    'field' => $field,
                    'label' => $label,
                    'mysql' => $mysqlVal,
                    'xlsx' => $xlsxVal,
                ];
            }
        }

        return $diffs;
    }
}
