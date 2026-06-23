<?php

namespace App\Services;

use App\Models\Procedimento;
use Illuminate\Support\Facades\DB;

class ProcedimentoTuImportService
{
    private const BATCH_SIZE = 100;

    /** @var array<string, string> */
    private const IMPORTABLE_FIELDS = [
        'procedimento' => 'Descrição',
        'vl_sp'        => 'Valor SP',
        'vl_sh'        => 'Valor SH',
    ];

    /**
     * @return array{
     *     total_tu: int,
     *     created: list<array{codigo: string, procedimento: string}>,
     *     changed: list<array{codigo: string, procedimento: string, diffs: list<array{field: string, label: string, mysql: mixed, tu: mixed}>, tu_data: array<string, mixed>}>,
     *     unchanged: int,
     *     only_mysql: list<array{codigo: string, procedimento: string}>,
     *     skipped: list<array{codigo: string, reason: string}>,
     * }
     */
    public function import(string $tuPath): array
    {
        $rows = $this->parseFileStreaming($tuPath);

        $result = [
            'total_tu'   => count($rows),
            'created'    => [],
            'changed'    => [],
            'unchanged'  => 0,
            'only_mysql' => [],
            'skipped'    => [],
        ];

        $seenCodigos = [];

        foreach (array_chunk($rows, self::BATCH_SIZE) as $batch) {
            set_time_limit(60);
            $this->processBatch($batch, $result, $seenCodigos);
        }

        $this->appendOnlyMysql($seenCodigos, $result);

        return $result;
    }

    /**
     * @param  list<string>  $codigoList
     * @param  list<array{codigo: string, tu_data: array<string, mixed>}>  $changedItems
     */
    public function applyChanges(array $codigoList, array $changedItems): int
    {
        $byCodigo = collect($changedItems)->keyBy('codigo');
        $applied = 0;

        foreach (array_chunk($codigoList, self::BATCH_SIZE) as $batch) {
            set_time_limit(60);

            foreach ($batch as $codigo) {
                $item = $byCodigo->get($codigo);
                if ($item === null || empty($item['tu_data'])) {
                    continue;
                }

                $procedimento = Procedimento::find($codigo);
                if ($procedimento === null) {
                    continue;
                }

                $updateData = array_intersect_key(
                    $item['tu_data'],
                    array_flip(array_keys(self::IMPORTABLE_FIELDS))
                );

                if ($updateData === []) {
                    continue;
                }

                $procedimento->update($updateData);
                $applied++;
            }
        }

        return $applied;
    }

    /**
     * @param  list<array{codigo: string, procedimento: string, vl_sp: float, vl_sh: float}>  $batch
     * @param  array<string, true>  $seenCodigos
     */
    private function processBatch(array $batch, array &$result, array &$seenCodigos): void
    {
        $codigos = array_column($batch, 'codigo');

        $existing = Procedimento::query()
            ->whereIn('codigo', $codigos)
            ->get(['codigo', 'procedimento', 'vl_sp', 'vl_sh'])
            ->keyBy('codigo');

        $toInsert = [];

        foreach ($batch as $row) {
            $codigo = $row['codigo'];
            $seenCodigos[$codigo] = true;
            $current = $existing->get($codigo);

            if ($current === null) {
                $toInsert[] = $this->mapInsertRow($row);
                $result['created'][] = [
                    'codigo'       => $codigo,
                    'procedimento' => $row['procedimento'],
                ];
                continue;
            }

            $diffs = $this->diffRow($current, $row);

            if ($diffs === []) {
                $result['unchanged']++;
                continue;
            }

            $result['changed'][] = [
                'codigo'       => $codigo,
                'procedimento' => $row['procedimento'],
                'diffs'        => $diffs,
                'tu_data'      => $row,
            ];
        }

        foreach (array_chunk($toInsert, self::BATCH_SIZE) as $insertChunk) {
            if ($insertChunk !== []) {
                DB::table('procedimento')->insert($insertChunk);
            }
        }
    }

    /**
     * @param  array<string, true>  $seenCodigos
     */
    private function appendOnlyMysql(array $seenCodigos, array &$result): void
    {
        Procedimento::query()
            ->select(['codigo', 'procedimento'])
            ->orderBy('codigo')
            ->chunk(self::BATCH_SIZE, function ($procedimentos) use ($seenCodigos, &$result) {
                foreach ($procedimentos as $proc) {
                    if (! isset($seenCodigos[$proc->codigo])) {
                        $result['only_mysql'][] = [
                            'codigo'       => $proc->codigo,
                            'procedimento' => $proc->procedimento,
                        ];
                    }
                }
            });
    }

    /**
     * @return list<array{codigo: string, procedimento: string, vl_sp: float, vl_sh: float}>
     */
    private function parseFileStreaming(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new \RuntimeException("Não foi possível abrir o arquivo: {$path}");
        }

        $byCodigo = [];

        try {
            while (($line = fgets($handle)) !== false) {
                $line = rtrim($line, "\r\n");

                if ($line === '') {
                    continue;
                }

                $line = mb_convert_encoding($line, 'UTF-8', 'CP1252');
                $parsed = $this->parseLine($line);

                if ($parsed !== null) {
                    $byCodigo[$parsed['codigo']] = $parsed;
                }
            }
        } finally {
            fclose($handle);
        }

        return array_values($byCodigo);
    }

    /**
     * @return array{codigo: string, procedimento: string, vl_sp: float, vl_sh: float}|null
     */
    private function parseLine(string $line): ?array
    {
        $fields = explode(';', $line);

        if (count($fields) < 11) {
            return null;
        }

        $codigo = trim($fields[0]);

        if (strlen($codigo) !== 10 || ! ctype_digit($codigo)) {
            return null;
        }

        return [
            'codigo'       => $codigo,
            'procedimento' => mb_substr(trim($fields[1]), 0, 255),
            'vl_sp'        => $this->parseDecimal($fields[9] ?? '0'),
            'vl_sh'        => $this->parseDecimal($fields[10] ?? '0'),
        ];
    }

    /**
     * @param  array{codigo: string, procedimento: string, vl_sp: float, vl_sh: float}  $row
     * @return array<string, mixed>
     */
    private function mapInsertRow(array $row): array
    {
        return [
            'codigo'       => $row['codigo'],
            'procedimento' => $row['procedimento'],
            'PA_TOTAL'     => 0,
            'PA_ID'        => mb_substr($row['codigo'], 0, 9),
            'VL_SP'        => $row['vl_sp'],
            'VL_SH'        => $row['vl_sh'],
            'RUB_TOTAL'    => '',
            'RUB_DC'       => '',
            'PA_RUB'       => '',
            'FINANCIAMENTO'=> null,
        ];
    }

    private function parseDecimal(string $value): float
    {
        return (float) str_replace(',', '.', trim($value));
    }

    /**
     * @return list<array{field: string, label: string, mysql: mixed, tu: mixed}>
     */
    private function diffRow(Procedimento $current, array $tuRow): array
    {
        $diffs = [];

        foreach (self::IMPORTABLE_FIELDS as $field => $label) {
            $mysqlVal = $current->{$field};
            $tuVal = $tuRow[$field] ?? null;

            if (in_array($field, ['vl_sp', 'vl_sh'], true)) {
                $mysqlVal = number_format((float) $mysqlVal, 2, '.', '');
                $tuVal    = number_format((float) $tuVal, 2, '.', '');
            }

            if ($field === 'procedimento') {
                $mysqlVal = trim((string) $mysqlVal);
                $tuVal    = trim((string) $tuVal);
            }

            if ((string) $mysqlVal !== (string) $tuVal) {
                $diffs[] = [
                    'field' => $field,
                    'label' => $label,
                    'mysql' => $mysqlVal,
                    'tu'    => $tuVal,
                ];
            }
        }

        return $diffs;
    }
}
