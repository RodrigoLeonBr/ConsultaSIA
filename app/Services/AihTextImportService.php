<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AihTextImportService
{
    private const AIH_COLUMNS = [
        'AIH', 'IDENT_AIH', 'CNES', 'COMPETENCIA', 'MUN_RESIDENCIA',
        'DT_NASC', 'IDADE', 'SEXO_PACIENTE', 'DT_INT', 'DT_SAIDA',
        'CARATER_INTERNACAO', 'ESPECIALIDADE', 'PROC_PRINCIPAL',
        'DIAG_PRINCIPAL', 'DIAG_SECUNDARIO', 'COMPLEXIDADE', 'FINANCIAMENTO',
        'ENFERMARIA', 'MOTIVO_SAIDA', 'CID_OBITO', 'DIARIAS', 'DIARIAS_UTI',
        'VALOR_TOTAL_AIH',
    ];

    private const HPA_COLUMNS = [
        'AIH', 'CNES', 'COMPETENCIA', 'PROC_DETALHADO',
        'QUANTIDADE', 'VALOR_ITEM', 'FINANCIAMENTO_DETALHE', 'CBO_PROFISSIONAL',
    ];

    /**
     * Parse resumo AIH file (semicolon-delimited, no header).
     * 23 colunas com DIAG_SECUNDARIO; 22 quando o SIHD omite campo vazio.
     */
    public function parseAihFile(string $path): array
    {
        $records = [];
        $handle = fopen($path, 'r');

        if (! $handle) {
            throw new \RuntimeException("Não foi possível abrir o arquivo: {$path}");
        }

        while (($line = fgets($handle)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                continue;
            }

            $parts = explode(';', $line);
            $record = $this->mapAihParts($parts);

            if ($record !== null) {
                $records[] = $record;
            }
        }

        fclose($handle);

        return $records;
    }

    /**
     * @param  array<int, string>  $parts
     * @return array<string, mixed>|null
     */
    private function mapAihParts(array $parts): ?array
    {
        $columnCount = count($parts);

        if ($columnCount < 22) {
            return null;
        }

        $hasDiagSecundario = $columnCount >= 23;
        $diagSecundarioIndex = $hasDiagSecundario ? 14 : null;
        $tailOffset = $hasDiagSecundario ? 0 : -1;

        $valorRaw = str_replace(',', '.', trim($parts[22 + $tailOffset]));

        return [
            'AIH' => trim($parts[0]),
            'IDENT_AIH' => trim($parts[1]),
            'CNES' => trim($parts[2]),
            'COMPETENCIA' => trim($parts[3]),
            'MUN_RESIDENCIA' => trim($parts[4]),
            'DT_NASC' => trim($parts[5]),
            'IDADE' => (int) trim($parts[6]),
            'SEXO_PACIENTE' => trim($parts[7]),
            'DT_INT' => trim($parts[8]),
            'DT_SAIDA' => trim($parts[9]),
            'CARATER_INTERNACAO' => trim($parts[10]),
            'ESPECIALIDADE' => trim($parts[11]),
            'PROC_PRINCIPAL' => trim($parts[12]),
            'DIAG_PRINCIPAL' => trim($parts[13]),
            'DIAG_SECUNDARIO' => $diagSecundarioIndex !== null ? trim($parts[$diagSecundarioIndex]) : '',
            'COMPLEXIDADE' => trim($parts[15 + $tailOffset]),
            'FINANCIAMENTO' => trim($parts[16 + $tailOffset]),
            'ENFERMARIA' => trim($parts[17 + $tailOffset]),
            'MOTIVO_SAIDA' => trim($parts[18 + $tailOffset]),
            'CID_OBITO' => trim($parts[19 + $tailOffset]),
            'DIARIAS' => (int) trim($parts[20 + $tailOffset]),
            'DIARIAS_UTI' => (int) trim($parts[21 + $tailOffset]),
            'VALOR_TOTAL_AIH' => (float) $valorRaw,
        ];
    }

    /**
     * Parse procedimentos AIH file (semicolon-delimited, no header, 8 columns).
     * VALOR_ITEM uses BR decimal (comma as separator).
     */
    public function parseHpaFile(string $path): array
    {
        $records = [];
        $handle = fopen($path, 'r');

        if (! $handle) {
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
                'AIH' => trim($parts[0]),
                'CNES' => trim($parts[1]),
                'COMPETENCIA' => trim($parts[2]),
                'PROC_DETALHADO' => trim($parts[3]),
                'QUANTIDADE' => (int) trim($parts[4]),
                'VALOR_ITEM' => (float) $valorRaw,
                'FINANCIAMENTO_DETALHE' => trim($parts[6]),
                'CBO_PROFISSIONAL' => trim($parts[7]),
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
            $key = $rec['CNES'].'|'.$rec['COMPETENCIA'];
            if (! isset($pairs[$key])) {
                $pairs[$key] = [
                    'CNES' => $rec['CNES'],
                    'COMPETENCIA' => $rec['COMPETENCIA'],
                    'count_aih' => 0,
                    'count_hpa' => 0,
                    'exists_db' => false,
                    'count_db' => 0,
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
            $pair['count_db'] = $count;
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
            $key = $rec['CNES'].'|'.$rec['COMPETENCIA'];
            $hpaCounts[$key] = ($hpaCounts[$key] ?? 0) + 1;
        }

        foreach ($competencias as &$pair) {
            $key = $pair['CNES'].'|'.$pair['COMPETENCIA'];
            $pair['count_hpa'] = $hpaCounts[$key] ?? 0;
        }
        unset($pair);

        return $competencias;
    }

    /**
     * Apply import with selective replace.
     *
     * @param  array  $aihRecords  Parsed AIH header records
     * @param  array  $hpaRecords  Parsed HPA records
     * @param  array  $competenciasToReplace  Keys 'CNES|COMPETENCIA' that the user confirmed replacing
     * @return array ['inserted_aih' => N, 'inserted_hpa' => N, 'replaced' => [...], 'skipped' => [...]]
     */
    public function applyImport(array $aihRecords, array $hpaRecords, array $competenciasToReplace): array
    {
        $competencias = $this->detectCompetencias($aihRecords);
        $competencias = $this->checkExisting($competencias);

        $toInsert = [];
        $replaced = [];
        $skipped = [];

        foreach ($competencias as $pair) {
            $key = $pair['CNES'].'|'.$pair['COMPETENCIA'];

            if ($pair['exists_db']) {
                if (in_array($key, $competenciasToReplace, true)) {
                    DB::table('s_aih')
                        ->where('CNES', $pair['CNES'])
                        ->where('COMPETENCIA', $pair['COMPETENCIA'])
                        ->delete();

                    DB::table('s_aih_pa')
                        ->where('CNES', $pair['CNES'])
                        ->where('COMPETENCIA', $pair['COMPETENCIA'])
                        ->delete();

                    $replaced[] = $pair['CNES'].'/'.$pair['COMPETENCIA'];
                    $toInsert[] = $key;
                } else {
                    $skipped[] = $pair['CNES'].'/'.$pair['COMPETENCIA'];
                }
            } else {
                $toInsert[] = $key;
            }
        }

        $aihToInsert = array_filter(
            $aihRecords,
            fn ($r) => in_array($r['CNES'].'|'.$r['COMPETENCIA'], $toInsert, true)
        );

        $hpaToInsert = array_filter(
            $hpaRecords,
            fn ($r) => in_array($r['CNES'].'|'.$r['COMPETENCIA'], $toInsert, true)
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
            'replaced' => $replaced,
            'skipped' => $skipped,
        ];
    }

    /**
     * Returns history of imported CNES+COMPETENCIA from s_aih and s_aih_pa.
     */
    public function getImportHistory(): array
    {
        $aihHistory = DB::table('s_aih')
            ->select('CNES', 'COMPETENCIA', DB::raw('COUNT(*) as count_aih'))
            ->groupBy('CNES', 'COMPETENCIA')
            ->orderByDesc('COMPETENCIA')
            ->orderBy('CNES')
            ->get();

        if ($aihHistory->isEmpty()) {
            return [];
        }

        $hpaCounts = DB::table('s_aih_pa')
            ->select('CNES', 'COMPETENCIA', DB::raw('COUNT(*) as count_hpa'))
            ->groupBy('CNES', 'COMPETENCIA')
            ->get()
            ->keyBy(fn ($r) => $r->CNES.'|'.$r->COMPETENCIA);

        $prestadores = DB::table('prestador')
            ->whereIn('re_cunid', $aihHistory->pluck('CNES')->unique())
            ->pluck('re_cnome', 're_cunid');

        return $aihHistory->map(function ($row) use ($hpaCounts, $prestadores) {
            $key = $row->CNES.'|'.$row->COMPETENCIA;

            return [
                'CNES' => $row->CNES,
                'CNES_nome' => $prestadores[$row->CNES] ?? '',
                'COMPETENCIA' => $row->COMPETENCIA,
                'count_aih' => $row->count_aih,
                'count_hpa' => $hpaCounts[$key]->count_hpa ?? 0,
            ];
        })->all();
    }
}
