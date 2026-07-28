<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Importa produção e-SUS (SIGTAP) da API para s_esus.
 * O CNES vem no payload (est.codigo_externo); se vier vazio, a unidade é gravada
 * mesmo assim (cnes null). Nenhuma linha é descartada. A importação substitui a
 * competência inteira, pois a API retorna todas as unidades da competência.
 * O filtro por unidade considerada (prestador.esus_ativo) é aplicado nas
 * consultas/relatórios, não na importação.
 */
class EsusImportService
{
    public function __construct(private readonly EsusApiService $api) {}

    public static function make(): self
    {
        return new self(EsusApiService::fromConfig());
    }

    /**
     * @return array<int, string>
     */
    public function getCompetencias(): array
    {
        return $this->api->getCompetencias();
    }

    /**
     * Analisa a competência sem gravar.
     *
     * @return array{competencia:string,total_linhas:int,sem_cnes:int,unidades:array<int,array{unidade:string,cnes:?string,linhas:int}>,existentes:int}
     */
    public function preview(string $competencia): array
    {
        $rows = $this->api->getProducao($competencia);

        $porUnidade = [];
        foreach ($rows as $row) {
            $nome = (string) ($row['unidade'] ?? '');
            if (! isset($porUnidade[$nome])) {
                $porUnidade[$nome] = [
                    'unidade' => $nome,
                    'cnes' => $this->cnes($row),
                    'linhas' => 0,
                ];
            }
            $porUnidade[$nome]['linhas']++;
        }

        $unidades = array_values($porUnidade);
        $semCnes = count(array_filter($unidades, fn ($u) => empty($u['cnes'])));

        return [
            'competencia' => $competencia,
            'total_linhas' => count($rows),
            'sem_cnes' => $semCnes,
            'unidades' => $unidades,
            'existentes' => DB::table('s_esus')->where('competencia', $competencia)->count(),
        ];
    }

    /**
     * Grava a competência: substitui os dados existentes e insere todas as linhas.
     *
     * @return array{inserted:int,replaced:int}
     */
    public function apply(string $competencia): array
    {
        $rows = $this->api->getProducao($competencia);
        $now = now();

        $toInsert = [];

        foreach ($rows as $row) {
            $nome = (string) ($row['unidade'] ?? '');
            $tipo = $row['tipo_relatorio'] ?? null;
            $bloco = $row['bloco'] ?? null;
            $descEsus = $row['descricao_esus'] ?? null;
            $sigtap = (string) ($row['codigo_sigtap'] ?? '');

            // Grão idêntico ao uk_esus — soma quantidade se a origem repetir a linha.
            $key = implode('|', [$competencia, $nome, $tipo, $bloco, $sigtap, $descEsus]);

            if (isset($toInsert[$key])) {
                $toInsert[$key]['quantidade'] += (int) ($row['quantidade'] ?? 0);

                continue;
            }

            $toInsert[$key] = [
                'competencia' => $competencia,
                'cnes' => $this->cnes($row),
                'unidade' => $nome,
                'tipo_relatorio' => $tipo,
                'bloco' => $bloco,
                'descricao_esus' => $descEsus,
                'codigo_sigtap' => $sigtap,
                'descricao_sigtap' => $row['descricao_sigtap'] ?? null,
                'quantidade' => (int) ($row['quantidade'] ?? 0),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $toInsert = array_values($toInsert);
        $inserted = 0;
        $replaced = 0;

        DB::transaction(function () use ($competencia, $toInsert, &$inserted, &$replaced) {
            $replaced = DB::table('s_esus')->where('competencia', $competencia)->delete();

            foreach (array_chunk($toInsert, 500) as $chunk) {
                DB::table('s_esus')->insert($chunk);
                $inserted += count($chunk);
            }
        });

        return [
            'inserted' => $inserted,
            'replaced' => $replaced,
        ];
    }

    /**
     * @return array<int, array{competencia:string,linhas:int,quantidade:int,unidades:int}>
     */
    public function history(): array
    {
        return DB::table('s_esus')
            ->select('competencia', DB::raw('COUNT(*) as linhas'), DB::raw('SUM(quantidade) as quantidade'), DB::raw('COUNT(DISTINCT cnes) as unidades'))
            ->groupBy('competencia')
            ->orderByDesc('competencia')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();
    }

    /**
     * CNES do payload, ou null quando ausente.
     *
     * @param  array<string, mixed>  $row
     */
    private function cnes(array $row): ?string
    {
        $cnes = trim((string) ($row['cnes'] ?? ''));

        return $cnes !== '' ? $cnes : null;
    }
}
