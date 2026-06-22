<?php

namespace App\Services;

use App\Models\Procedimento;

class ProcedimentoTuImportService
{
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
        $rows = $this->parseFile($tuPath);

        $existing = Procedimento::query()
            ->get(['codigo', 'procedimento', 'vl_sp', 'vl_sh'])
            ->keyBy('codigo');

        $result = [
            'total_tu'   => count($rows),
            'created'    => [],
            'changed'    => [],
            'unchanged'  => 0,
            'only_mysql' => [],
            'skipped'    => [],
        ];

        $seenCodigos = [];

        foreach ($rows as $row) {
            $codigo = $row['codigo'];

            $seenCodigos[$codigo] = true;
            $current = $existing->get($codigo);

            if ($current === null) {
                Procedimento::create([
                    'codigo'      => $codigo,
                    'procedimento'=> $row['procedimento'],
                    'pa_total'    => 0,
                    'pa_id'       => mb_substr($codigo, 0, 9),
                    'vl_sp'       => $row['vl_sp'],
                    'vl_sh'       => $row['vl_sh'],
                ]);
                $result['created'][] = [
                    'codigo'      => $codigo,
                    'procedimento'=> $row['procedimento'],
                ];
                continue;
            }

            $diffs = $this->diffRow($current, $row);

            if ($diffs === []) {
                $result['unchanged']++;
                continue;
            }

            $result['changed'][] = [
                'codigo'      => $codigo,
                'procedimento'=> $row['procedimento'],
                'diffs'       => $diffs,
                'tu_data'     => $row,
            ];
        }

        foreach ($existing as $codigo => $proc) {
            if (! isset($seenCodigos[$codigo])) {
                $result['only_mysql'][] = [
                    'codigo'      => $codigo,
                    'procedimento'=> $proc->procedimento,
                ];
            }
        }

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

        foreach ($codigoList as $codigo) {
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

        return $applied;
    }

    /**
     * @return list<array{codigo: string, procedimento: string, vl_sp: float, vl_sh: float}>
     */
    private function parseFile(string $path): array
    {
        $raw = file_get_contents($path);
        // TU file from SIHD uses CP1252 (Windows-1252) encoding
        $content = iconv('CP1252', 'UTF-8//IGNORE', $raw);
        $lines = explode("\n", str_replace("\r", '', $content));

        $byCodigo = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $fields = explode(';', $line);
            if (count($fields) < 11) {
                continue;
            }

            $codigo = trim($fields[0]);
            if (strlen($codigo) !== 10 || ! ctype_digit($codigo)) {
                continue;
            }

            $byCodigo[$codigo] = [
                'codigo'      => $codigo,
                'procedimento'=> mb_substr(trim($fields[1]), 0, 255),
                'vl_sp'       => $this->parseDecimal($fields[9] ?? '0'),
                'vl_sh'       => $this->parseDecimal($fields[10] ?? '0'),
            ];
        }

        return array_values($byCodigo);
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
