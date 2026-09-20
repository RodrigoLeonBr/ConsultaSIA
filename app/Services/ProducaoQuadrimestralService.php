<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProducaoQuadrimestralService
{
    private const MESES = [
        '01' => 'Jan', '02' => 'Fev', '03' => 'Mar', '04' => 'Abr',
        '05' => 'Mai', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
        '09' => 'Set', '10' => 'Out', '11' => 'Nov', '12' => 'Dez',
    ];

    /** @return array<int,string> 4 competências YYYYMM */
    public function competencias(int $ano, int $quadrimestre): array
    {
        $inicio = ($quadrimestre - 1) * 4 + 1;
        $out = [];
        for ($m = $inicio; $m < $inicio + 4; $m++) {
            $out[] = sprintf('%04d%02d', $ano, $m);
        }

        return $out;
    }

    /**
     * @param  array<int,string>  $competencias  YYYYMM
     * @return array<int,string> rótulos "Mmm/AAAA"
     */
    public function rotulosMeses(array $competencias): array
    {
        return array_map(
            fn (string $c): string => self::MESES[substr($c, 4, 2)].'/'.substr($c, 0, 4),
            $competencias
        );
    }

    /**
     * @param  iterable<int,object>  $linhas  linhas cruas (ver convenção do plano)
     * @param  array<string,int>  $colMap  competência YYYYMM => índice da coluna (0..N-1)
     * @return array<int,array{tipo:string,linhas:array<int,array<string,mixed>>,total_valores:array<int,int>,total:int}>
     */
    public function montarSecoes(iterable $linhas, array $colMap): array
    {
        $numCols = $colMap ? max($colMap) + 1 : 0;
        $tipos = [];

        foreach ($linhas as $l) {
            if (! isset($colMap[$l->competencia])) {
                continue;
            }
            $i = $colMap[$l->competencia];
            $q = (int) $l->qtd;
            $tipo = $l->tipo_relatorio;
            $tipos[$tipo] ??= ['tipo' => $tipo, 'nodes' => [], 'total_valores' => array_fill(0, $numCols, 0), 'total' => 0];

            $niveis = [
                [0, 'sg:'.$l->subgrupo_cod, '', $l->subgrupo_cod, $l->subgrupo_desc],
                [1, 'fo:'.$l->forma_cod, 'sg:'.$l->subgrupo_cod, $l->forma_cod, $l->forma_desc],
                [2, 'pc:'.$l->proc_cod, 'fo:'.$l->forma_cod, $l->proc_cod, $l->proc_desc],
                [3, 'pe:'.$l->proc_cod.':'.$l->cnes, 'pc:'.$l->proc_cod, $l->cnes, $l->prestador_nome],
            ];

            foreach ($niveis as [$level, $id, $parent, $cod, $desc]) {
                if (! isset($tipos[$tipo]['nodes'][$id])) {
                    $tipos[$tipo]['nodes'][$id] = [
                        'node_id' => $id, 'parent_id' => $parent, 'level' => $level,
                        'cod' => (string) $cod, 'desc' => (string) $desc, 'has_children' => $level < 3,
                        'valores' => array_fill(0, $numCols, 0), 'total' => 0,
                    ];
                }
                $tipos[$tipo]['nodes'][$id]['valores'][$i] += $q;
                $tipos[$tipo]['nodes'][$id]['total'] += $q;
            }

            $tipos[$tipo]['total_valores'][$i] += $q;
            $tipos[$tipo]['total'] += $q;
        }

        ksort($tipos);

        $secoes = [];
        foreach ($tipos as $t) {
            $secoes[] = [
                'tipo' => $t['tipo'],
                'linhas' => $this->linearizar($t['nodes']),
                'total_valores' => $t['total_valores'],
                'total' => $t['total'],
            ];
        }

        return $secoes;
    }

    /**
     * DFS ordenado por código: pai antes das filhas.
     *
     * @param  array<string,array<string,mixed>>  $nodes
     * @return array<int,array<string,mixed>>
     */
    private function linearizar(array $nodes): array
    {
        $filhos = [];
        foreach ($nodes as $n) {
            $filhos[$n['parent_id']][] = $n['node_id'];
        }
        foreach ($filhos as &$ids) {
            usort($ids, fn (string $a, string $b): int => strcmp($nodes[$a]['cod'], $nodes[$b]['cod']));
        }
        unset($ids);

        $out = [];
        $visit = function (string $pid) use (&$visit, &$out, $nodes, $filhos): void {
            foreach ($filhos[$pid] ?? [] as $id) {
                $out[] = $nodes[$id];
                $visit($id);
            }
        };
        $visit('');

        return $out;
    }

    /**
     * @param  string  $modo  'meses' (4 meses do quadrimestre + total) ou 'anos' (mesmo quadrimestre em 4 anos, ano-3..ano)
     * @return array{secoes:array<int,mixed>,colunas:array<int,string>,mostra_total:bool,modo:string,competencias:array<int,string>,ano:int,quadrimestre:int}
     */
    public function gerar(int $ano, int $quadrimestre, string $modo = 'meses'): array
    {
        if ($modo === 'anos') {
            $comps = [];
            $colMap = [];
            foreach (range($ano - 3, $ano) as $idx => $a) { // antigo → recente
                foreach ($this->competencias($a, $quadrimestre) as $c) {
                    $comps[] = $c;
                    $colMap[$c] = $idx;
                }
            }
            $colunas = array_map('strval', range($ano - 3, $ano));
            $mostraTotal = false;
        } else {
            $comps = $this->competencias($ano, $quadrimestre);
            $colMap = array_flip($comps);
            $colunas = $this->rotulosMeses($comps);
            $mostraTotal = true;
        }

        $linhas = array_merge(
            $this->querySia($comps)->all(),
            $this->querySih($comps)->all(),
            $this->queryEsus($comps)->all(),
        );

        return [
            'secoes' => $this->montarSecoes($linhas, $colMap),
            'colunas' => $colunas,
            'mostra_total' => $mostraTotal,
            'modo' => $modo,
            'competencias' => $comps,
            'ano' => $ano,
            'quadrimestre' => $quadrimestre,
        ];
    }

    /** @return array<int,int> anos com produção (para o filtro) */
    public function anosDisponiveis(): array
    {
        // ponytail: cache 6h — a lista só muda quando importam nova competência;
        // evita o full-scan de DISTINCT no s_prd (5.9M) a cada request. Limpa com `php artisan cache:clear`.
        return Cache::remember('quad_anos_disponiveis', now()->addHours(6), function (): array {
            $sia = DB::table('s_prd')->selectRaw('DISTINCT LEFT(prd_cmp,4) as ano')->pluck('ano');
            $sih = DB::table('s_aih_pa')->selectRaw('DISTINCT LEFT(COMPETENCIA,4) as ano')->pluck('ano');
            $esus = DB::table('s_esus')->selectRaw('DISTINCT LEFT(competencia,4) as ano')->pluck('ano');

            return collect([$sia, $sih, $esus])->flatten()
                ->map(fn ($a) => (int) $a)
                ->filter(fn (int $a) => $a >= 1900 && $a <= 2199)
                ->unique()->sortDesc()->values()->all();
        });
    }

    /** @param array<int,string> $comps */
    private function querySia(array $comps): \Illuminate\Support\Collection
    {
        return DB::table('s_prd as sp')
            ->leftJoin('prestador as pr', 'sp.prd_uid', '=', 'pr.re_cunid')
            ->leftJoin('forma as fs', function ($j) {
                $j->on(DB::raw('SUBSTRING(sp.prd_pa,1,4)'), '=', 'fs.subgrupo')
                    ->where('fs.forma', '=', DB::raw("CONCAT(SUBSTRING(sp.prd_pa,1,4),'00')"));
            })
            ->leftJoin('forma as ff', DB::raw('SUBSTRING(sp.prd_pa,1,6)'), '=', 'ff.forma')
            ->leftJoin('procedimento as pc', 'sp.prd_pa', '=', 'pc.codigo')
            ->whereIn('sp.prd_cmp', $comps)
            ->whereRaw('LENGTH(sp.prd_pa) >= 6')
            ->groupBy('pr.relatorio', 'sp.prd_pa', 'fs.descricao', 'ff.descricao', 'pc.procedimento', 'sp.prd_uid', 'pr.re_cnome', 'sp.prd_cmp')
            ->select($this->colunasComuns(
                proc: 'sp.prd_pa', cnes: 'sp.prd_uid', cmp: 'sp.prd_cmp',
                qtd: 'SUM(CAST(sp.PRD_QT_A AS UNSIGNED))',
            ))
            ->get();
    }

    /** @param array<int,string> $comps */
    private function querySih(array $comps): \Illuminate\Support\Collection
    {
        $c = 'COLLATE utf8mb4_general_ci';

        return DB::table('s_aih_pa as ap')
            ->leftJoin('prestador as pr', DB::raw("ap.CNES $c"), '=', 'pr.re_cunid')
            ->leftJoin('forma as fs', function ($j) use ($c) {
                $j->on(DB::raw("SUBSTRING(ap.PROC_DETALHADO,1,4) $c"), '=', 'fs.subgrupo')
                    ->where('fs.forma', '=', DB::raw("CONCAT(SUBSTRING(ap.PROC_DETALHADO,1,4),'00') $c"));
            })
            ->leftJoin('forma as ff', DB::raw("SUBSTRING(ap.PROC_DETALHADO,1,6) $c"), '=', 'ff.forma')
            ->leftJoin('procedimento as pc', DB::raw("ap.PROC_DETALHADO $c"), '=', 'pc.codigo')
            ->whereIn('ap.COMPETENCIA', $comps)
            ->whereRaw('LENGTH(ap.PROC_DETALHADO) >= 6')
            ->groupBy('pr.relatorio', 'ap.PROC_DETALHADO', 'fs.descricao', 'ff.descricao', 'pc.procedimento', 'ap.CNES', 'pr.re_cnome', 'ap.COMPETENCIA')
            ->select($this->colunasComuns(
                proc: 'ap.PROC_DETALHADO', cnes: 'ap.CNES', cmp: 'ap.COMPETENCIA',
                qtd: 'SUM(ap.QUANTIDADE)',
            ))
            ->get();
    }

    /** @param array<int,string> $comps */
    private function queryEsus(array $comps): \Illuminate\Support\Collection
    {
        $c = 'COLLATE utf8mb4_general_ci';
        $compsEsus = array_map(fn (string $x) => substr($x, 0, 4).'-'.substr($x, 4, 2), $comps);

        return DB::table('s_esus as es')
            ->join('prestador as pr', function ($j) use ($c) {
                $j->on(DB::raw("es.cnes $c"), '=', 'pr.re_cunid')->where('pr.esus_ativo', '=', 1);
            })
            ->leftJoin('forma as fs', function ($j) use ($c) {
                $j->on(DB::raw("SUBSTRING(es.codigo_sigtap,1,4) $c"), '=', 'fs.subgrupo')
                    ->where('fs.forma', '=', DB::raw("CONCAT(SUBSTRING(es.codigo_sigtap,1,4),'00') $c"));
            })
            ->leftJoin('forma as ff', DB::raw("SUBSTRING(es.codigo_sigtap,1,6) $c"), '=', 'ff.forma')
            ->leftJoin('procedimento as pc', DB::raw("es.codigo_sigtap $c"), '=', 'pc.codigo')
            ->whereIn('es.competencia', $compsEsus)
            ->whereRaw('LENGTH(es.codigo_sigtap) >= 6')
            ->groupBy('pr.relatorio', 'es.codigo_sigtap', 'fs.descricao', 'ff.descricao', 'pc.procedimento', 'es.cnes', 'pr.re_cnome', 'es.competencia')
            ->select($this->colunasComuns(
                proc: 'es.codigo_sigtap', cnes: 'es.cnes',
                cmp: "REPLACE(es.competencia,'-','')", qtd: 'SUM(es.quantidade)',
            ))
            ->get();
    }

    /**
     * Colunas do grão comum às 3 fontes.
     *
     * @return array<int,\Illuminate\Database\Query\Expression>
     */
    private function colunasComuns(string $proc, string $cnes, string $cmp, string $qtd): array
    {
        return [
            DB::raw("COALESCE(NULLIF(pr.relatorio,''),'Sem tipo') as tipo_relatorio"),
            DB::raw("SUBSTRING($proc,1,4) as subgrupo_cod"),
            DB::raw("COALESCE(fs.descricao,'') as subgrupo_desc"),
            DB::raw("SUBSTRING($proc,1,6) as forma_cod"),
            DB::raw("COALESCE(ff.descricao,'') as forma_desc"),
            DB::raw("$proc as proc_cod"),
            DB::raw("COALESCE(pc.procedimento,'') as proc_desc"),
            DB::raw("$cnes as cnes"),
            DB::raw("COALESCE(pr.re_cnome,'') as prestador_nome"),
            DB::raw("$cmp as competencia"),
            DB::raw("$qtd as qtd"),
        ];
    }
}
