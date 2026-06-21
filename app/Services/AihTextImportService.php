<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AihTextImportService
{
    private const AIH_COLUMNS = [
        'AIH', 'CNES', 'COMPETENCIA', 'DT_NASC', 'IDADE', 'SEXO_PACIENTE',
        'DT_INT', 'DT_SAIDA', 'ESPECIALIDADE', 'PROC_PRINCIPAL',
        'DIAG_PRINCIPAL', 'COMPLEXIDADE', 'FINANCIAMENTO', 'ENFERMARIA',
        'MOTIVO_SAIDA', 'DIARIAS', 'DIARIAS_UTI', 'VALOR_TOTAL_AIH',
    ];

    private const HPA_COLUMNS = [
        'AIH', 'CNES', 'COMPETENCIA', 'PROC_DETALHADO',
        'QUANTIDADE', 'VALOR_ITEM', 'FINANCIAMENTO_DETALHE', 'CBO_PROFISSIONAL',
    ];

    /**
     * Parse resumo AIH file (semicolon-delimited, no header, 18 columns).
     * Column order: AIH, CNES, COMPETENCIA, DT_NASC, IDADE, SEXO_PACIENTE,
     *               DT_INT, DT_SAIDA, ESPECIALIDADE, PROC_PRINCIPAL,
     *               DIAG_PRINCIPAL, COMPLEXIDADE, FINANCIAMENTO, ENFERMARIA,
     *               MOTIVO_SAIDA, DIARIAS, DIARIAS_UTI, VALOR_TOTAL_AIH
     */
    public function parseAihFile(string $path): array
    {
        $records = [];
        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new \RuntimeException("Não foi possível abrir o arquivo: {$path}");
        }

        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }

            $parts = explode(';', $line);

            if (count($parts) < 18) {
                continue;
            }

            $valorRaw = str_replace(',', '.', trim($parts[17]));

            $records[] = [
                'AIH'             => trim($parts[0]),
                'CNES'            => trim($parts[1]),
                'COMPETENCIA'     => trim($parts[2]),
                'DT_NASC'         => trim($parts[3]),
                'IDADE'           => (int) trim($parts[4]),
                'SEXO_PACIENTE'   => trim($parts[5]),
                'DT_INT'          => trim($parts[6]),
                'DT_SAIDA'        => trim($parts[7]),
                'ESPECIALIDADE'   => trim($parts[8]),
                'PROC_PRINCIPAL'  => trim($parts[9]),
                'DIAG_PRINCIPAL'  => trim($parts[10]),
                'COMPLEXIDADE'    => trim($parts[11]),
                'FINANCIAMENTO'   => trim($parts[12]),
                'ENFERMARIA'      => trim($parts[13]),
                'MOTIVO_SAIDA'    => trim($parts[14]),
                'DIARIAS'         => (int) trim($parts[15]),
                'DIARIAS_UTI'     => (int) trim($parts[16]),
                'VALOR_TOTAL_AIH' => (float) $valorRaw,
            ];
        }

        fclose($handle);

        return $records;
    }

    /**
     * Parse procedimentos AIH file (semicolon-delimited, no header, 8 columns).
     * VALOR_ITEM uses BR decimal (comma as separator).
     */
    public function parseHpaFile(string $path): array
    {
        $records = [];
        $handle = fopen($path, 'r');

        if (!$handle) {
            throw new \RuntimeException("Não foi possível abrir o arquivo: {$path}");
        }

        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }

            $parts = explode(';', $line);

            if (count($parts) < 8) {
                continue;
            }

            $valorRaw = str_replace(',', '.', trim($parts[5]));

            $records[] = [
                'AIH'                  => trim($parts[0]),
                'CNES'                 => trim($parts[1]),
                'COMPETENCIA'          => trim($parts[2]),
                'PROC_DETALHADO'       => trim($parts[3]),
                'QUANTIDADE'           => (int) trim($parts[4]),
                'VALOR_ITEM'           => (float) $valorRaw,
                'FINANCIAMENTO_DETALHE'=> trim($parts[6]),
                'CBO_PROFISSIONAL'     => trim($parts[7]),
            ];
        }

        fclose($handle);

        return $records;
    }

    /**
     * Returns unique [CNES, COMPETENCIA] pairs found in AIH records.
     */
    public function detectCompetencias(array $aihRecords): array
    {
        $pairs = [];

        foreach ($aihRecords as $rec) {
            $key = $rec['CNES'] . '|' . $rec['COMPETENCIA'];
            if (!isset($pairs[$key])) {
                $pairs[$key] = [
                    'CNES'        => $rec['CNES'],
                    'COMPETENCIA' => $rec['COMPETENCIA'],
                    'count_aih'   => 0,
                    'count_hpa'   => 0,
                    'exists_db'   => false,
                    'count_db'    => 0,
                ];
            }
            $pairs[$key]['count_aih']++;
        }

        return array_values($pairs);
    }

    /**
     * Check which [CNES, COMPETENCIA] pairs already exist in s_aih.
     * Returns the same $competencias array with 'exists_db' and 'count_db' filled.
     */
    public function checkExisting(array $competencias): array
    {
        foreach ($competencias as &$pair) {
            $count = DB::table('s_aih')
                ->where('CNES', $pair['CNES'])
                ->where('COMPETENCIA', $pair['COMPETENCIA'])
                ->count();

            $pair['exists_db'] = $count > 0;
            $pair['count_db']  = $count;
        }
        unset($pair);

        return $competencias;
    }

    /**
     * Cross-reference HPA records into the competencias summary.
     */
    public function enrichWithHpa(array $competencias, array $hpaRecords): array
    {
        $hpaCounts = [];

        foreach ($hpaRecords as $rec) {
            $key = $rec['CNES'] . '|' . $rec['COMPETENCIA'];
            $hpaCounts[$key] = ($hpaCounts[$key] ?? 0) + 1;
        }

        foreach ($competencias as &$pair) {
            $key = $pair['CNES'] . '|' . $pair['COMPETENCIA'];
            $pair['count_hpa'] = $hpaCounts[$key] ?? 0;
        }
        unset($pair);

        return $competencias;
    }

    /**
     * Apply import: insert or replace records per CNES+COMPETENCIA.
     *
     * @param  array  $aihRecords   Parsed AIH header records
     * @param  array  $hpaRecords   Parsed HPA records
     * @param  bool   $replace      If true, deletes existing before inserting
     * @return array  ['inserted_aih' => N, 'inserted_hpa' => N, 'replaced' => [...], 'skipped' => [...]]
     */
    public function applyImport(array $aihRecords, array $hpaRecords, bool $replace): array
    {
        $competencias = $this->detectCompetencias($aihRecords);
        $competencias = $this->checkExisting($competencias);

        $toInsert  = [];
        $replaced  = [];
        $skipped   = [];

        foreach ($competencias as $pair) {
            if ($pair['exists_db']) {
                if ($replace) {
                    DB::table('s_aih')
                        ->where('CNES', $pair['CNES'])
                        ->where('COMPETENCIA', $pair['COMPETENCIA'])
                        ->delete();

                    DB::table('s_aih_pa')
                        ->where('CNES', $pair['CNES'])
                        ->where('COMPETENCIA', $pair['COMPETENCIA'])
                        ->delete();

                    $replaced[] = $pair['CNES'] . '/' . $pair['COMPETENCIA'];
                    $toInsert[] = $pair['CNES'] . '|' . $pair['COMPETENCIA'];
                } else {
                    $skipped[] = $pair['CNES'] . '/' . $pair['COMPETENCIA'];
                }
            } else {
                $toInsert[] = $pair['CNES'] . '|' . $pair['COMPETENCIA'];
            }
        }

        // Bulk insert AIH
        $aihToInsert = array_filter(
            $aihRecords,
            fn ($r) => in_array($r['CNES'] . '|' . $r['COMPETENCIA'], $toInsert, true)
        );

        $hpaToInsert = array_filter(
            $hpaRecords,
            fn ($r) => in_array($r['CNES'] . '|' . $r['COMPETENCIA'], $toInsert, true)
        );

        $insertedAih = 0;
        $insertedHpa = 0;

        foreach (array_chunk(array_values($aihToInsert), 500) as $chunk) {
            DB::table('s_aih')->insert($chunk);
            $insertedAih += count($chunk);
        }

        foreach (array_chunk(array_values($hpaToInsert), 500) as $chunk) {
            DB::table('s_aih_pa')->insert($chunk);
            $insertedHpa += count($chunk);
        }

        return [
            'inserted_aih' => $insertedAih,
            'inserted_hpa' => $insertedHpa,
            'replaced'     => $replaced,
            'skipped'      => $skipped,
        ];
    }
}
