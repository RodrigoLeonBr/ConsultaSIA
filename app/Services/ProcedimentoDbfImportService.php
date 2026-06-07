<?php

namespace App\Services;

use App\Models\Procedimento;
use App\Models\SRub;
use XBase\TableReader;

class ProcedimentoDbfImportService
{
    /** @var array<string, string> */
    private const SRUB_FIELDS = [
        'rub_dc' => 'Descrição (RUB_DC)',
        'rub_total' => 'RUB_TOTAL',
    ];

    /** @var array<string, string> */
    private const IMPORTABLE_FIELDS = [
        'procedimento' => 'Descrição',
        'pa_id' => 'PA_ID',
        'pa_rub' => 'Rubrica (PA_RUB)',
        'rub_total' => 'RUB_TOTAL',
        'rub_dc' => 'RUB_DC',
        'pa_total' => 'Valor (PA_TOTAL)',
    ];

    /**
     * @return array{
     *     total_dbf: int,
     *     total_unique: int,
     *     total_rub: int,
     *     competence: string,
     *     created: list<array{codigo: string, procedimento: string}>,
     *     changed: list<array{codigo: string, procedimento: string, diffs: list<array{field: string, label: string, mysql: mixed, dbf: mixed}>, dbf_data: array<string, mixed>}>,
     *     unchanged: int,
     *     only_mysql: list<array{codigo: string, procedimento: string}>,
     *     skipped: list<array{codigo: string, reason: string}>,
     * }
     */
    public function import(string $paDbfPath, string $rubDbfPath, bool $autoCreate = true): array
    {
        $rubLookup = $this->readRubLookup($rubDbfPath);
        $parsed = $this->readPaDbf($paDbfPath, $rubLookup);
        $dbfRows = $parsed['rows'];

        $existing = Procedimento::query()
            ->get(['codigo', 'procedimento', 'pa_total', 'pa_rub', 'pa_id', 'rub_total', 'rub_dc', 'financiamento'])
            ->keyBy('codigo');

        $result = [
            'total_dbf' => $parsed['raw_count'],
            'total_unique' => count($dbfRows),
            'total_rub' => count($rubLookup),
            'competence' => $parsed['competence'],
            's_rub' => $this->importSRubTable($rubLookup, $autoCreate),
            'created' => [],
            'changed' => [],
            'unchanged' => 0,
            'only_mysql' => [],
            'skipped' => [],
        ];

        $seenCodigos = [];

        foreach ($dbfRows as $row) {
            $codigo = $row['codigo'];

            if ($codigo === '') {
                $result['skipped'][] = ['codigo' => '(vazio)', 'reason' => 'Código ausente no DBF'];
                continue;
            }

            $seenCodigos[$codigo] = true;
            $current = $existing->get($codigo);

            if ($current === null) {
                if ($autoCreate) {
                    Procedimento::create(array_merge($row, ['financiamento' => null]));
                }
                $result['created'][] = [
                    'codigo' => $codigo,
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
                'codigo' => $codigo,
                'procedimento' => $row['procedimento'],
                'diffs' => $diffs,
                'dbf_data' => $row,
            ];
        }

        foreach ($existing as $codigo => $procedimento) {
            if (! isset($seenCodigos[$codigo])) {
                $result['only_mysql'][] = [
                    'codigo' => $codigo,
                    'procedimento' => $procedimento->procedimento,
                ];
            }
        }

        return $result;
    }

    /**
     * @param  array<string, array{rub_total: string, rub_dc: string}>  $rubLookup
     * @return array{
     *     created: list<array{rub_id: string, rub_dc: string}>,
     *     updated: list<array{rub_id: string, rub_dc: string, diffs: list<array{field: string, label: string, mysql: mixed, dbf: mixed}>}>,
     *     unchanged: int,
     *     only_mysql: list<array{rub_id: string, rub_dc: string}>,
     * }
     */
    private function importSRubTable(array $rubLookup, bool $autoSync): array
    {
        $existing = SRub::query()
            ->get(['rub_id', 'rub_dc', 'rub_total'])
            ->keyBy('rub_id');

        $result = [
            'created' => [],
            'updated' => [],
            'unchanged' => 0,
            'only_mysql' => [],
        ];

        foreach ($rubLookup as $rubId => $rubRow) {
            $dbfData = [
                'rub_id' => $rubId,
                'rub_dc' => $rubRow['rub_dc'],
                'rub_total' => mb_substr($rubRow['rub_total'], 0, 2),
            ];

            $current = $existing->get($rubId);

            if ($current === null) {
                if ($autoSync) {
                    SRub::create($dbfData);
                }
                $result['created'][] = [
                    'rub_id' => $rubId,
                    'rub_dc' => $dbfData['rub_dc'],
                ];
                continue;
            }

            $diffs = $this->diffSRubRow($current, $dbfData);

            if ($diffs === []) {
                $result['unchanged']++;
                continue;
            }

            if ($autoSync) {
                $current->update($dbfData);
            }

            $result['updated'][] = [
                'rub_id' => $rubId,
                'rub_dc' => $dbfData['rub_dc'],
                'diffs' => $diffs,
            ];
        }

        foreach ($existing as $rubId => $srub) {
            if (! isset($rubLookup[$rubId])) {
                $result['only_mysql'][] = [
                    'rub_id' => $rubId,
                    'rub_dc' => $srub->rub_dc,
                ];
            }
        }

        return $result;
    }

    /**
     * @return list<array{field: string, label: string, mysql: mixed, dbf: mixed}>
     */
    private function diffSRubRow(SRub $current, array $dbfRow): array
    {
        $diffs = [];

        foreach (self::SRUB_FIELDS as $field => $label) {
            $mysqlVal = $this->trim($current->{$field});
            $dbfVal = $this->trim($dbfRow[$field] ?? '');

            if ($mysqlVal !== $dbfVal) {
                $diffs[] = [
                    'field' => $field,
                    'label' => $label,
                    'mysql' => $mysqlVal,
                    'dbf' => $dbfVal,
                ];
            }
        }

        return $diffs;
    }

    /**
     * @param  list<string>  $codigoList
     */
    public function applyChanges(array $codigoList, array $changedItems): int
    {
        $byCodigo = collect($changedItems)->keyBy('codigo');
        $applied = 0;

        foreach ($codigoList as $codigo) {
            $item = $byCodigo->get($codigo);
            if ($item === null || empty($item['dbf_data'])) {
                continue;
            }

            $procedimento = Procedimento::find($codigo);
            if ($procedimento === null) {
                continue;
            }

            $updateData = array_intersect_key(
                $item['dbf_data'],
                array_flip(array_keys(self::IMPORTABLE_FIELDS))
            );

            if (isset($updateData['pa_total']) && (float) $updateData['pa_total'] == 0.0) {
                unset($updateData['pa_total']);
            }

            if ($updateData === []) {
                continue;
            }

            $procedimento->update($updateData);
            $applied++;
        }

        return $applied;
    }

    /**
     * @return array<string, array{rub_total: string, rub_dc: string}>
     */
    private function readRubLookup(string $rubDbfPath): array
    {
        $table = new TableReader($rubDbfPath);
        $lookup = [];

        while ($record = $table->nextRecord()) {
            if ($record->isDeleted()) {
                continue;
            }

            $data = $record->getData();
            $rubId = mb_substr($this->trim($data['rub_id'] ?? ''), 0, 4);

            if ($rubId === '') {
                continue;
            }

            $lookup[$rubId] = [
                'rub_total' => mb_substr($this->trim($data['rub_total'] ?? ''), 0, 4),
                'rub_dc' => mb_substr($this->trim($data['rub_dc'] ?? ''), 0, 40),
            ];
        }

        return $lookup;
    }

    /**
     * @param  array<string, array{rub_total: string, rub_dc: string}>  $rubLookup
     * @return array{rows: list<array<string, mixed>>, raw_count: int, competence: string}
     */
    private function readPaDbf(string $paDbfPath, array $rubLookup): array
    {
        $table = new TableReader($paDbfPath);
        $byCodigo = [];
        $rawCount = 0;
        $maxCompetence = '';

        while ($record = $table->nextRecord()) {
            if ($record->isDeleted()) {
                continue;
            }

            $rawCount++;
            $data = $record->getData();
            $cmp = $this->trim($data['pa_cmp'] ?? '');

            if ($cmp > $maxCompetence) {
                $maxCompetence = $cmp;
            }

            $mapped = $this->mapPaRecord($data, $rubLookup);
            if ($mapped === null) {
                continue;
            }

            $codigo = $mapped['codigo'];
            $existingCmp = $byCodigo[$codigo]['_cmp'] ?? '';

            if (! isset($byCodigo[$codigo]) || $cmp >= $existingCmp) {
                $mapped['_cmp'] = $cmp;
                $byCodigo[$codigo] = $mapped;
            }
        }

        $rows = [];
        foreach ($byCodigo as $row) {
            unset($row['_cmp']);
            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'raw_count' => $rawCount,
            'competence' => $maxCompetence,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, array{rub_total: string, rub_dc: string}>  $rubLookup
     * @return array<string, mixed>|null
     */
    private function mapPaRecord(array $data, array $rubLookup): ?array
    {
        $paId = $this->trim($data['pa_id'] ?? '');
        $paDv = $this->trim($data['pa_dv'] ?? '');

        if ($paId === '') {
            return null;
        }

        $codigo = $paId . $paDv;
        $descricao = mb_substr($this->trim($data['pa_dc'] ?? ''), 0, 63);
        $paRub = mb_substr($this->trim($data['pa_rub'] ?? ''), 0, 4);
        $rub = $rubLookup[$paRub] ?? ['rub_total' => '', 'rub_dc' => ''];

        return [
            'codigo' => $codigo,
            'procedimento' => $descricao !== '' ? $descricao : $codigo,
            'pa_total' => round((float) ($data['pa_total'] ?? 0), 2),
            'pa_rub' => $paRub,
            'pa_id' => mb_substr($paId, 0, 9),
            'rub_total' => $rub['rub_total'],
            'rub_dc' => $rub['rub_dc'],
        ];
    }

    /**
     * @return list<array{field: string, label: string, mysql: mixed, dbf: mixed}>
     */
    private function diffRow(Procedimento $current, array $dbfRow): array
    {
        $diffs = [];

        foreach (self::IMPORTABLE_FIELDS as $field => $label) {
            $mysqlVal = $current->{$field};
            $dbfVal = $dbfRow[$field] ?? null;

            if ($field === 'pa_total') {
                $dbfFloat = (float) $dbfVal;
                if ($dbfFloat == 0.0) {
                    continue;
                }
                $mysqlVal = number_format((float) $mysqlVal, 2, '.', '');
                $dbfVal = number_format($dbfFloat, 2, '.', '');
            }

            if (in_array($field, ['rub_dc', 'procedimento'], true)) {
                $mysqlVal = $this->trim($mysqlVal);
                $dbfVal = $this->trim($dbfVal);
            }

            if ((string) $mysqlVal !== (string) $dbfVal) {
                $diffs[] = [
                    'field' => $field,
                    'label' => $label,
                    'mysql' => $mysqlVal,
                    'dbf' => $dbfVal,
                ];
            }
        }

        return $diffs;
    }

    private function trim(mixed $value): string
    {
        return trim((string) $value);
    }
}
