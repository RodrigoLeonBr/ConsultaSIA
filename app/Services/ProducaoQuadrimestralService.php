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

        // Agrega cada fonte SEM joins (coberto por índice de competência), depois
        // enriquece descrições/tipo em PHP — evita joins pesados e o mix de collations.
        $raw = array_merge($this->querySia($comps), $this->querySih($comps), $this->queryEsus($comps));
        $linhas = $this->enriquecer($raw);

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

    /**
     * SIA agregado sem joins (grão cnes+proc+competência).
     *
     * @param  array<int,string>  $comps
     * @return array<int,object>
     */
    private function querySia(array $comps): array
    {
        return DB::table('s_prd')
            ->whereIn('prd_cmp', $comps)
            ->whereRaw('LENGTH(prd_pa) >= 6')
            ->groupBy('prd_cmp', 'prd_uid', 'prd_pa')
            ->get([
                DB::raw('prd_cmp as competencia'),
                DB::raw('prd_uid as cnes'),
                DB::raw('prd_pa as proc'),
                DB::raw('SUM(CAST(PRD_QT_A AS UNSIGNED)) as qtd'),
            ])->all();
    }

    /**
     * SIH agregado sem joins.
     *
     * @param  array<int,string>  $comps
     * @return array<int,object>
     */
    private function querySih(array $comps): array
    {
        return DB::table('s_aih_pa')
            ->whereIn('COMPETENCIA', $comps)
            ->whereRaw('LENGTH(PROC_DETALHADO) >= 6')
            ->groupBy('COMPETENCIA', 'CNES', 'PROC_DETALHADO')
            ->get([
                DB::raw('COMPETENCIA as competencia'),
                DB::raw('CNES as cnes'),
                DB::raw('PROC_DETALHADO as proc'),
                DB::raw('SUM(QUANTIDADE) as qtd'),
            ])->all();
    }

    /**
     * e-SUS agregado sem joins; filtro esus_ativo=1 aplicado em PHP (sem mix de collation).
     *
     * @param  array<int,string>  $comps
     * @return array<int,object>
     */
    private function queryEsus(array $comps): array
    {
        $compsEsus = array_map(fn (string $x) => substr($x, 0, 4).'-'.substr($x, 4, 2), $comps);
        $ativos = array_flip(
            DB::table('prestador')->where('esus_ativo', 1)->pluck('re_cunid')
                ->map(fn ($c) => (string) $c)->all()
        );

        $rows = DB::table('s_esus')
            ->whereIn('competencia', $compsEsus)
            ->whereNotNull('cnes')
            ->whereRaw('LENGTH(codigo_sigtap) >= 6')
            ->groupBy('competencia', 'cnes', 'codigo_sigtap')
            ->get([
                DB::raw("REPLACE(competencia,'-','') as competencia"),
                DB::raw('cnes'),
                DB::raw('codigo_sigtap as proc'),
                DB::raw('SUM(quantidade) as qtd'),
            ])->all();

        return array_values(array_filter($rows, fn ($r) => isset($ativos[(string) $r->cnes])));
    }

    /**
     * Resolve tipo de relatório, subgrupo/forma e descrições em PHP via mapas
     * pequenos — mais barato que joins na agregação e imune a mix de collations.
     *
     * @param  array<int,object>  $raw  linhas cruas {competencia,cnes,proc,qtd}
     * @return array<int,object>  linhas no grão consumido por montarSecoes
     */
    private function enriquecer(array $raw): array
    {
        if (! $raw) {
            return [];
        }

        $cnes = array_values(array_unique(array_map(fn ($r) => (string) $r->cnes, $raw)));
        $procs = array_values(array_unique(array_map(fn ($r) => (string) $r->proc, $raw)));

        $prest = DB::table('prestador')->whereIn('re_cunid', $cnes)
            ->get(['re_cunid', 'relatorio', 're_cnome'])->keyBy('re_cunid');
        $procMap = DB::table('procedimento')->whereIn('codigo', $procs)->pluck('procedimento', 'codigo');
        $formaMap = DB::table('forma')->pluck('descricao', 'forma');

        $out = [];
        foreach ($raw as $r) {
            $proc = (string) $r->proc;
            $sub = substr($proc, 0, 4);
            $forma6 = substr($proc, 0, 6);
            $p = $prest[(string) $r->cnes] ?? null;
            $out[] = (object) [
                'tipo_relatorio' => ($p && $p->relatorio !== null && $p->relatorio !== '') ? $p->relatorio : 'Sem tipo',
                'subgrupo_cod' => $sub,
                'subgrupo_desc' => $formaMap[$sub.'00'] ?? '',
                'forma_cod' => $forma6,
                'forma_desc' => $formaMap[$forma6] ?? '',
                'proc_cod' => $proc,
                'proc_desc' => $procMap[$proc] ?? '',
                'cnes' => (string) $r->cnes,
                'prestador_nome' => $p->re_cnome ?? '',
                'competencia' => (string) $r->competencia,
                'qtd' => (int) $r->qtd,
            ];
        }

        return $out;
    }
}
