<?php

namespace App\Services;

use App\Models\Prestador;
use XBase\TableReader;

class PrestadorDbfImportService
{
    /** @var array<string, string> Campos importados do DBF (Tipo, Área e Relatório são manuais). */
    private const IMPORTABLE_FIELDS = [
        're_cnome' => 'Nome',
        'cnpj' => 'CNPJ/CPF',
        'tipouni' => 'Natureza',
        'ativo' => 'Ativo',
    ];

    /** @var array<string, mixed> Valores padrão para campos manuais ao criar novo prestador. */
    private const MANUAL_DEFAULTS = [
        're_tipo' => 'U',
        'area' => 0,
        'relatorio' => null,
    ];

    /**
     * @return array{
     *     total_dbf: int,
     *     created: list<array{re_cunid: string, re_cnome: string}>,
     *     changed: list<array{re_cunid: string, re_cnome: string, diffs: list<array{field: string, label: string, mysql: mixed, dbf: mixed}>}>,
     *     unchanged: int,
     *     only_mysql: list<array{re_cunid: string, re_cnome: string}>,
     *     skipped: list<array{re_cunid: string, reason: string}>,
     * }
     */
    public function import(string $dbfPath, bool $autoCreate = true): array
    {
        $dbfRows = $this->readDbf($dbfPath);
        $existing = Prestador::query()
            ->get(['re_cunid', 're_cnome', 're_tipo', 'cnpj', 'area', 'tipouni', 'relatorio', 'ativo'])
            ->keyBy('re_cunid');

        $result = [
            'total_dbf' => count($dbfRows),
            'created' => [],
            'changed' => [],
            'unchanged' => 0,
            'only_mysql' => [],
            'skipped' => [],
        ];

        $seenCnes = [];

        foreach ($dbfRows as $row) {
            $cnes = $row['re_cunid'];

            if ($cnes === '') {
                $result['skipped'][] = ['re_cunid' => '(vazio)', 'reason' => 'CNES ausente no DBF'];
                continue;
            }

            $seenCnes[$cnes] = true;
            $current = $existing->get($cnes);

            if ($current === null) {
                if ($autoCreate) {
                    Prestador::create(array_merge(self::MANUAL_DEFAULTS, $row));
                }
                $result['created'][] = [
                    're_cunid' => $cnes,
                    're_cnome' => $row['re_cnome'],
                    'needs_manual' => true,
                ];
                continue;
            }

            $diffs = $this->diffRow($current, $row);

            if ($diffs === []) {
                $result['unchanged']++;
                continue;
            }

            $result['changed'][] = [
                're_cunid' => $cnes,
                're_cnome' => $row['re_cnome'],
                'diffs' => $diffs,
                'dbf_data' => $row,
            ];
        }

        foreach ($existing as $cnes => $prestador) {
            if (! isset($seenCnes[$cnes])) {
                $result['only_mysql'][] = [
                    're_cunid' => $cnes,
                    're_cnome' => $prestador->re_cnome,
                ];
            }
        }

        return $result;
    }

    /**
     * @param  list<string>  $cnesList
     */
    public function applyChanges(array $cnesList, array $changedItems): int
    {
        $byCnes = collect($changedItems)->keyBy('re_cunid');
        $applied = 0;

        foreach ($cnesList as $cnes) {
            $item = $byCnes->get($cnes);
            if ($item === null || empty($item['dbf_data'])) {
                continue;
            }

            $prestador = Prestador::find($cnes);
            if ($prestador === null) {
                continue;
            }

            $updateData = array_intersect_key(
                $item['dbf_data'],
                array_flip(array_keys(self::IMPORTABLE_FIELDS))
            );

            $prestador->update($updateData);
            $applied++;
        }

        return $applied;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function readDbf(string $dbfPath): array
    {
        $table = new TableReader($dbfPath);
        $rows = [];

        while ($record = $table->nextRecord()) {
            if ($record->isDeleted()) {
                continue;
            }

            $mapped = $this->mapRecord($record->getData());
            if ($mapped !== null) {
                $rows[] = $mapped;
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>|null
     */
    private function mapRecord(array $data): ?array
    {
        $cnes = $this->trim($data['ups_cnes'] ?? '') ?: $this->trim($data['ups_id'] ?? '');

        if ($cnes === '') {
            return null;
        }

        $nome = $this->trim($data['ups_nmfn'] ?? '') ?: $this->trim($data['ups_rzsc'] ?? '');
        $nome = mb_substr($nome, 0, 35);

        $inMn = strtoupper($this->trim($data['ups_in_mn'] ?? ''));
        $tipouni = match ($inMn) {
            'M' => 'M',
            'U' => 'P',
            default => 'M',
        };

        $cnpj = preg_replace('/\D/', '', $this->trim($data['ups_cgccpf'] ?? '')) ?: '0';
        $cnpj = substr($cnpj, 0, 14);

        $ativoFlag = strtoupper($this->trim($data['ups_in_atv'] ?? 'A'));

        return [
            're_cunid' => $cnes,
            're_cnome' => $nome !== '' ? $nome : $cnes,
            'cnpj' => $cnpj,
            'tipouni' => $tipouni,
            'ativo' => $ativoFlag !== 'I',
        ];
    }

    /**
     * @return list<array{field: string, label: string, mysql: mixed, dbf: mixed}>
     */
    private function diffRow(Prestador $current, array $dbfRow): array
    {
        $diffs = [];

        foreach (self::IMPORTABLE_FIELDS as $field => $label) {
            $mysqlVal = $current->{$field};
            $dbfVal = $dbfRow[$field] ?? null;

            if ($field === 'ativo') {
                $mysqlVal = (bool) $mysqlVal;
                $dbfVal = (bool) $dbfVal;
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
