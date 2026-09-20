<?php

namespace App\Services;

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
     * @param  array<int,string>  $competencias  YYYYMM na ordem das colunas
     * @return array<int,array{tipo:string,linhas:array<int,array<string,mixed>>,total_meses:array<int,int>,total:int}>
     */
    public function montarSecoes(iterable $linhas, array $competencias): array
    {
        $col = array_flip(array_values($competencias));
        $tipos = [];

        foreach ($linhas as $l) {
            if (! isset($col[$l->competencia])) {
                continue;
            }
            $i = $col[$l->competencia];
            $q = (int) $l->qtd;
            $tipo = $l->tipo_relatorio;
            $tipos[$tipo] ??= ['tipo' => $tipo, 'nodes' => [], 'total_meses' => [0, 0, 0, 0], 'total' => 0];

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
                        'meses' => [0, 0, 0, 0], 'total' => 0,
                    ];
                }
                $tipos[$tipo]['nodes'][$id]['meses'][$i] += $q;
                $tipos[$tipo]['nodes'][$id]['total'] += $q;
            }

            $tipos[$tipo]['total_meses'][$i] += $q;
            $tipos[$tipo]['total'] += $q;
        }

        ksort($tipos);

        $secoes = [];
        foreach ($tipos as $t) {
            $secoes[] = [
                'tipo' => $t['tipo'],
                'linhas' => $this->linearizar($t['nodes']),
                'total_meses' => $t['total_meses'],
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
}
