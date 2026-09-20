# Relatório de Produção Quadrimestral — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Novo relatório que consolida produção de procedimentos de SIA (`s_prd`), SIH (`s_aih_pa`) e e-SUS (`s_esus`) em uma visão quadrimestral (4 meses + total), com drill-down (subgrupo → forma → procedimento → prestador) e seção por tipo de relatório.

**Architecture:** 3 queries SQL independentes (uma por fonte), cada uma já pré-agregada e filtrada por competência (usa índice próprio), concatenadas e mescladas em PHP em uma árvore hierárquica. e-SUS entra só para `prestador.esus_ativo = 1` (evita dupla contagem com SIA). Frontend usa o componente `tabela-avancada.js` portado do `cria-app`.

**Tech Stack:** Laravel 12, MariaDB 10.4, Blade + Tailwind (layout `layouts.modern`), PHPUnit, `tabela-avancada.js` (vanilla), Phosphor icons (CDN).

**Spec:** `docs/superpowers/specs/2026-09-20-relatorio-quadrimestral-design.md`

---

## File Structure

| Arquivo | Responsabilidade |
|---|---|
| `app/Services/ProducaoQuadrimestralService.php` | Dados: competências do quadrimestre, 3 queries, merge + linearização da árvore |
| `app/Http/Controllers/RelatorioQuadrimestralController.php` | `index` (filtros) + `gerar` (POST) |
| `routes/web.php` | 2 rotas no grupo auth |
| `resources/views/relatorios/quadrimestral/index.blade.php` | Filtros + seções com tabelas drill |
| `app/View/Components/Sidebar.php` | Item de menu |
| `public/js/tabela-avancada.js` | Componente drill/filtro/sort (porte) |
| `public/css/tabela-avancada.css` | Estilos do componente |
| `tests/Feature/RelatorioQuadrimestralTest.php` | Testes de feature |

**Convenção de dados (usada em todas as tasks):**

Cada linha crua (saída das 3 queries) é um `stdClass` com:
`tipo_relatorio, subgrupo_cod, subgrupo_desc, forma_cod, forma_desc, proc_cod, proc_desc, cnes, prestador_nome, competencia (YYYYMM), qtd`.

Cada **nó** da árvore (após merge) é um array:
```
['node_id'=>string,'parent_id'=>string,'level'=>int(0..3),
 'cod'=>string,'desc'=>string,'has_children'=>bool,
 'meses'=>[int,int,int,int],'total'=>int]
```
node_id: `sg:<subgrupo>` · `fo:<forma>` · `pc:<proc>` · `pe:<proc>:<cnes>`. Raiz (subgrupo) tem `parent_id=''`.

---

## Task 1: Service — competências + linearização (lógica pura)

Começa pela lógica sem SQL (fácil de testar), depois adiciona as queries na Task 2.

**Files:**
- Create: `app/Services/ProducaoQuadrimestralService.php`
- Test: `tests/Feature/RelatorioQuadrimestralTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Services\ProducaoQuadrimestralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesReportTestUser;
use Tests\TestCase;

class RelatorioQuadrimestralTest extends TestCase
{
    use CreatesReportTestUser;
    use RefreshDatabase;

    private function service(): ProducaoQuadrimestralService
    {
        return new ProducaoQuadrimestralService();
    }

    public function test_competencias_do_quadrimestre(): void
    {
        $s = $this->service();
        $this->assertSame(['202601', '202602', '202603', '202604'], $s->competencias(2026, 1));
        $this->assertSame(['202605', '202606', '202607', '202608'], $s->competencias(2026, 2));
        $this->assertSame(['202609', '202610', '202611', '202612'], $s->competencias(2026, 3));
    }

    public function test_rotulos_meses(): void
    {
        $s = $this->service();
        $this->assertSame(
            ['Jan/2026', 'Fev/2026', 'Mar/2026', 'Abr/2026'],
            $s->rotulosMeses(['202601', '202602', '202603', '202604'])
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/RelatorioQuadrimestralTest.php --filter="competencias_do_quadrimestre|rotulos_meses"`
Expected: FAIL — `Class "App\Services\ProducaoQuadrimestralService" not found`.

- [ ] **Step 3: Write minimal implementation**

Create `app/Services/ProducaoQuadrimestralService.php`:

```php
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
     * @return array<int,string>  rótulos "Mmm/AAAA"
     */
    public function rotulosMeses(array $competencias): array
    {
        return array_map(
            fn (string $c): string => self::MESES[substr($c, 4, 2)].'/'.substr($c, 0, 4),
            $competencias
        );
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/RelatorioQuadrimestralTest.php --filter="competencias_do_quadrimestre|rotulos_meses"`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/ProducaoQuadrimestralService.php tests/Feature/RelatorioQuadrimestralTest.php
git commit -m "feat(quadrimestral): service com competências e rótulos de mês"
```

---

## Task 2: Service — linearização da árvore (merge das linhas)

**Files:**
- Modify: `app/Services/ProducaoQuadrimestralService.php`
- Test: `tests/Feature/RelatorioQuadrimestralTest.php`

- [ ] **Step 1: Write the failing test**

Adicionar ao test class:

```php
    public function test_monta_secoes_soma_e_lineariza(): void
    {
        $comps = ['202601', '202602', '202603', '202604'];
        $linhas = [
            // mesmo proc+cnes em 2 meses → soma nos meses certos e no total
            (object) ['tipo_relatorio' => 'AB', 'subgrupo_cod' => '0301', 'subgrupo_desc' => 'SG', 'forma_cod' => '030110', 'forma_desc' => 'FO', 'proc_cod' => '0301100209', 'proc_desc' => 'PROC', 'cnes' => '2048205', 'prestador_nome' => 'UNI', 'competencia' => '202601', 'qtd' => 10],
            (object) ['tipo_relatorio' => 'AB', 'subgrupo_cod' => '0301', 'subgrupo_desc' => 'SG', 'forma_cod' => '030110', 'forma_desc' => 'FO', 'proc_cod' => '0301100209', 'proc_desc' => 'PROC', 'cnes' => '2048205', 'prestador_nome' => 'UNI', 'competencia' => '202603', 'qtd' => 5],
        ];

        $secoes = $this->service()->montarSecoes($linhas, $comps);

        $this->assertCount(1, $secoes);
        $this->assertSame('AB', $secoes[0]['tipo']);
        $this->assertSame([10, 0, 5, 0], $secoes[0]['total_meses']);
        $this->assertSame(15, $secoes[0]['total']);

        $linear = $secoes[0]['linhas'];
        // 4 níveis: subgrupo, forma, procedimento, prestador
        $this->assertSame(['sg:0301', 'fo:030110', 'pc:0301100209', 'pe:0301100209:2048205'], array_column($linear, 'node_id'));
        $this->assertSame([0, 1, 2, 3], array_column($linear, 'level'));
        // acumulação em todos os níveis
        foreach ($linear as $no) {
            $this->assertSame([10, 0, 5, 0], $no['meses']);
            $this->assertSame(15, $no['total']);
        }
        $this->assertTrue($linear[0]['has_children']);
        $this->assertFalse($linear[3]['has_children']);
    }

    public function test_secoes_separadas_por_tipo_e_ordenadas(): void
    {
        $comps = ['202601', '202602', '202603', '202604'];
        $linhas = [
            (object) ['tipo_relatorio' => 'MAC', 'subgrupo_cod' => '0801', 'subgrupo_desc' => '', 'forma_cod' => '080101', 'forma_desc' => '', 'proc_cod' => '0801010010', 'proc_desc' => '', 'cnes' => '1', 'prestador_nome' => '', 'competencia' => '202602', 'qtd' => 3],
            (object) ['tipo_relatorio' => 'AB', 'subgrupo_cod' => '0301', 'subgrupo_desc' => '', 'forma_cod' => '030110', 'forma_desc' => '', 'proc_cod' => '0301100209', 'proc_desc' => '', 'cnes' => '2', 'prestador_nome' => '', 'competencia' => '202601', 'qtd' => 7],
        ];

        $secoes = $this->service()->montarSecoes($linhas, $comps);

        $this->assertSame(['AB', 'MAC'], array_column($secoes, 'tipo')); // ordem alfabética
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/RelatorioQuadrimestralTest.php --filter="monta_secoes|secoes_separadas"`
Expected: FAIL — `Call to undefined method ...::montarSecoes()`.

- [ ] **Step 3: Write minimal implementation**

Adicionar ao service:

```php
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
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/RelatorioQuadrimestralTest.php --filter="monta_secoes|secoes_separadas"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/ProducaoQuadrimestralService.php tests/Feature/RelatorioQuadrimestralTest.php
git commit -m "feat(quadrimestral): merge e linearização da árvore de produção"
```

---

## Task 3: Service — 3 queries SQL (SIA, SIH, e-SUS) + `gerar()`

**Files:**
- Modify: `app/Services/ProducaoQuadrimestralService.php`
- Test: `tests/Feature/RelatorioQuadrimestralTest.php`

- [ ] **Step 1: Write the failing test**

Adicionar ao test class (semeia as 3 fontes; valida soma cross-fonte e exclusão e-SUS):

```php
    private function seedTresFontes(): void
    {
        \DB::table('procedimento')->insert([
            'codigo' => '0301100209', 'procedimento' => 'ADM MEDICAMENTO IM', 'pa_id' => '0',
        ]);
        \DB::table('forma')->insert([
            ['id_registro' => 1, 'grupo' => '03', 'subgrupo' => '0301', 'forma' => '030100', 'descricao' => 'SUBGRUPO 0301'],
            ['id_registro' => 2, 'grupo' => '03', 'subgrupo' => '0301', 'forma' => '030110', 'descricao' => 'FORMA 030110'],
        ]);
        \DB::table('prestador')->insert([
            ['re_cunid' => '2048205', 're_cnome' => 'UNIDADE A', 're_tipo' => 'U', 'area' => 1, 'tipouni' => 'M', 'ativo' => 1, 'esus_ativo' => 1, 'relatorio' => 'ATENCAO BASICA'],
            ['re_cunid' => '9999999', 're_cnome' => 'UNIDADE B', 're_tipo' => 'U', 'area' => 1, 'tipouni' => 'M', 'ativo' => 1, 'esus_ativo' => 0, 'relatorio' => 'ATENCAO BASICA'],
        ]);
        // SIA: unidade A, jan, aprovada 20 (PRD_QT_A); apresentada diferente pra garantir que usamos A
        \DB::table('s_prd')->insert([
            'prd_cmp' => '202601', 'prd_uid' => '2048205', 'prd_pa' => '0301100209',
            'PRD_QT_P' => '999', 'PRD_QT_A' => '20', 'PRD_VL_P' => '0', 'PRD_VL_A' => '0', 'prd_rub' => '01',
        ]);
        // SIH: unidade A, fev, 3
        \DB::table('s_aih_pa')->insert([
            'AIH' => '0000000000001', 'CNES' => '2048205', 'COMPETENCIA' => '202602',
            'PROC_DETALHADO' => '0301100209', 'QUANTIDADE' => 3, 'VALOR_ITEM' => 0,
            'FINANCIAMENTO_DETALHE' => '01', 'CBO_PROFISSIONAL' => '000000',
        ]);
        // e-SUS: unidade A (esus_ativo=1) jan 10 → soma com SIA no mesmo leaf; unidade B (esus_ativo=0) jan 99 → excluída
        \DB::table('s_esus')->insert([
            ['competencia' => '2026-01', 'cnes' => '2048205', 'unidade' => 'A', 'tipo_relatorio' => 'x', 'bloco' => 'b', 'descricao_esus' => 'd', 'codigo_sigtap' => '0301100209', 'descricao_sigtap' => 's', 'quantidade' => 10],
            ['competencia' => '2026-01', 'cnes' => '9999999', 'unidade' => 'B', 'tipo_relatorio' => 'x', 'bloco' => 'b', 'descricao_esus' => 'd', 'codigo_sigtap' => '0301100209', 'descricao_sigtap' => 's', 'quantidade' => 99],
            // e-SUS bloco CDS sem SIGTAP → descartado
            ['competencia' => '2026-01', 'cnes' => '2048205', 'unidade' => 'A', 'tipo_relatorio' => 'cds', 'bloco' => 'b', 'descricao_esus' => 'd', 'codigo_sigtap' => '', 'descricao_sigtap' => '', 'quantidade' => 7],
        ]);
    }

    public function test_gerar_soma_tres_fontes_e_exclui_esus_inativo(): void
    {
        $this->seedTresFontes();

        $r = $this->service()->gerar(2026, 1); // Q1 = jan..abr

        $this->assertSame(['Jan/2026', 'Fev/2026', 'Mar/2026', 'Abr/2026'], $r['meses']);
        $this->assertCount(1, $r['secoes']);
        $secao = $r['secoes'][0];
        $this->assertSame('ATENCAO BASICA', $secao['tipo']);

        // leaf do prestador A: jan = SIA 20 + eSUS 10 = 30 ; fev = SIH 3
        $leaf = collect($secao['linhas'])->firstWhere('node_id', 'pe:0301100209:2048205');
        $this->assertNotNull($leaf);
        $this->assertSame([30, 3, 0, 0], $leaf['meses']);
        $this->assertSame(33, $leaf['total']);

        // unidade B (esus_ativo=0, 99) NÃO entra
        $this->assertNull(collect($secao['linhas'])->firstWhere('node_id', 'pe:0301100209:9999999'));

        // total da seção = 33 (CDS de 7 descartado)
        $this->assertSame(33, $secao['total']);
    }
```

> Nota: se `s_prd`/`s_aih_pa`/`s_esus` não existirem em `producao_test`, criar as migrations/rodar o setup de teste já usado por `EsusImportTest`/AIH (o banco de teste é montado com RefreshDatabase). Confirmar que as tabelas existem antes de rodar; se faltar `s_aih_pa`, aplicar a migration correspondente com `php artisan migrate --path=...`.

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/RelatorioQuadrimestralTest.php --filter="gerar_soma_tres_fontes"`
Expected: FAIL — `Call to undefined method ...::gerar()`.

- [ ] **Step 3: Write minimal implementation**

Adicionar `use Illuminate\Support\Facades\DB;` no topo do service e os métodos:

```php
    /**
     * @return array{secoes:array<int,mixed>,meses:array<int,string>,competencias:array<int,string>,ano:int,quadrimestre:int}
     */
    public function gerar(int $ano, int $quadrimestre): array
    {
        $comps = $this->competencias($ano, $quadrimestre);
        $linhas = array_merge(
            $this->querySia($comps)->all(),
            $this->querySih($comps)->all(),
            $this->queryEsus($comps)->all(),
        );

        return [
            'secoes' => $this->montarSecoes($linhas, $comps),
            'meses' => $this->rotulosMeses($comps),
            'competencias' => $comps,
            'ano' => $ano,
            'quadrimestre' => $quadrimestre,
        ];
    }

    /** @return array<int,int> anos com produção (para o filtro) */
    public function anosDisponiveis(): array
    {
        // ponytail: distinct por LEFT(cmp,4); mesmo custo aceito no FaturamentoPrestadorController.
        $sia = DB::table('s_prd')->selectRaw('DISTINCT LEFT(prd_cmp,4) as ano')
            ->whereRaw('prd_cmp REGEXP "^[0-9]{6}$"')->pluck('ano');
        $sih = DB::table('s_aih_pa')->selectRaw('DISTINCT LEFT(COMPETENCIA,4) as ano')->pluck('ano');
        $esus = DB::table('s_esus')->selectRaw('DISTINCT LEFT(competencia,4) as ano')->pluck('ano');

        return collect([$sia, $sih, $esus])->flatten()
            ->filter()->map(fn ($a) => (int) $a)->unique()->sortDesc()->values()->all();
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
            ->groupBy('tipo_relatorio', 'subgrupo_cod', 'subgrupo_desc', 'forma_cod', 'forma_desc', 'proc_cod', 'proc_desc', 'cnes', 'prestador_nome', 'competencia')
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
                    ->where('fs.forma', '=', DB::raw("CONCAT(SUBSTRING(ap.PROC_DETALHADO,1,4),'00')"));
            })
            ->leftJoin('forma as ff', DB::raw("SUBSTRING(ap.PROC_DETALHADO,1,6) $c"), '=', 'ff.forma')
            ->leftJoin('procedimento as pc', DB::raw("ap.PROC_DETALHADO $c"), '=', 'pc.codigo')
            ->whereIn('ap.COMPETENCIA', $comps)
            ->whereRaw('LENGTH(ap.PROC_DETALHADO) >= 6')
            ->groupBy('tipo_relatorio', 'subgrupo_cod', 'subgrupo_desc', 'forma_cod', 'forma_desc', 'proc_cod', 'proc_desc', 'cnes', 'prestador_nome', 'competencia')
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
                    ->where('fs.forma', '=', DB::raw("CONCAT(SUBSTRING(es.codigo_sigtap,1,4),'00')"));
            })
            ->leftJoin('forma as ff', DB::raw("SUBSTRING(es.codigo_sigtap,1,6) $c"), '=', 'ff.forma')
            ->leftJoin('procedimento as pc', DB::raw("es.codigo_sigtap $c"), '=', 'pc.codigo')
            ->whereIn('es.competencia', $compsEsus)
            ->whereRaw('LENGTH(es.codigo_sigtap) >= 6')
            ->groupBy('tipo_relatorio', 'subgrupo_cod', 'subgrupo_desc', 'forma_cod', 'forma_desc', 'proc_cod', 'proc_desc', 'cnes', 'prestador_nome', 'competencia')
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
```

> Se `groupBy` por alias falhar no MariaDB em modo estrito, trocar os aliases do `groupBy()` pelas mesmas expressões cruas usadas no `select`. Testar primeiro com alias (MariaDB aceita por padrão).

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/RelatorioQuadrimestralTest.php --filter="gerar_soma_tres_fontes"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Services/ProducaoQuadrimestralService.php tests/Feature/RelatorioQuadrimestralTest.php
git commit -m "feat(quadrimestral): 3 queries por fonte + gerar()"
```

---

## Task 4: Controller + rotas

**Files:**
- Create: `app/Http/Controllers/RelatorioQuadrimestralController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/RelatorioQuadrimestralTest.php`

- [ ] **Step 1: Write the failing test**

Adicionar ao test class:

```php
    public function test_rota_index_exige_auth(): void
    {
        $this->get(route('relatorios.quadrimestral.index'))->assertRedirect(route('login'));
    }

    public function test_gerar_renderiza_secoes(): void
    {
        $this->seedTresFontes();

        $this->actingAs($this->createReportTestUser())
            ->post(route('relatorios.quadrimestral.gerar'), ['ano' => 2026, 'quadrimestre' => 1])
            ->assertOk()
            ->assertSee('ATENCAO BASICA')
            ->assertSee('data-node-id="pe:0301100209:2048205"', false);
    }

    public function test_gerar_valida_quadrimestre(): void
    {
        $this->actingAs($this->createReportTestUser())
            ->post(route('relatorios.quadrimestral.gerar'), ['ano' => 2026, 'quadrimestre' => 9])
            ->assertSessionHasErrors('quadrimestre');
    }
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/RelatorioQuadrimestralTest.php --filter="rota_index_exige_auth|gerar_renderiza|gerar_valida"`
Expected: FAIL — rota `relatorios.quadrimestral.index` não definida (`RouteNotFoundException`).

- [ ] **Step 3: Write minimal implementation**

Create `app/Http/Controllers/RelatorioQuadrimestralController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\ProducaoQuadrimestralService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class RelatorioQuadrimestralController extends Controller
{
    public function __construct(private ProducaoQuadrimestralService $service) {}

    public function index(): View
    {
        return view('relatorios.quadrimestral.index', [
            'anos' => $this->service->anosDisponiveis(),
            'quadrimestres' => [
                1 => '1º Quadrimestre (Jan–Abr)',
                2 => '2º Quadrimestre (Mai–Ago)',
                3 => '3º Quadrimestre (Set–Dez)',
            ],
            'resultado' => null,
        ]);
    }

    public function gerar(Request $request): View
    {
        $dados = $request->validate([
            'ano' => 'required|integer|min:1900|max:2199',
            'quadrimestre' => 'required|integer|in:1,2,3',
        ]);

        return view('relatorios.quadrimestral.index', [
            'anos' => $this->service->anosDisponiveis(),
            'quadrimestres' => [
                1 => '1º Quadrimestre (Jan–Abr)',
                2 => '2º Quadrimestre (Mai–Ago)',
                3 => '3º Quadrimestre (Set–Dez)',
            ],
            'resultado' => $this->service->gerar((int) $dados['ano'], (int) $dados['quadrimestre']),
        ]);
    }
}
```

Em `routes/web.php`, dentro do grupo com middleware `auth` (junto das outras rotas `relatorios.*`), adicionar:

```php
use App\Http\Controllers\RelatorioQuadrimestralController;

Route::get('relatorios/quadrimestral', [RelatorioQuadrimestralController::class, 'index'])->name('relatorios.quadrimestral.index');
Route::post('relatorios/quadrimestral/gerar', [RelatorioQuadrimestralController::class, 'gerar'])->name('relatorios.quadrimestral.gerar');
```

Criar o esqueleto mínimo da view para o teste passar (será completada na Task 6) — `resources/views/relatorios/quadrimestral/index.blade.php`:

```blade
@extends('layouts.modern')
@section('title', 'Produção Quadrimestral')
@section('content')
  <form method="POST" action="{{ route('relatorios.quadrimestral.gerar') }}">
    @csrf
    <select name="ano">@foreach($anos as $a)<option value="{{ $a }}">{{ $a }}</option>@endforeach</select>
    <select name="quadrimestre">@foreach($quadrimestres as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach</select>
    <button type="submit">Aplicar</button>
  </form>
  @isset($resultado)
    @foreach($resultado['secoes'] as $secao)
      <h2>{{ $secao['tipo'] }}</h2>
      <table>
        @foreach($secao['linhas'] as $no)
          <tr data-node-id="{{ $no['node_id'] }}" data-parent-id="{{ $no['parent_id'] }}" data-level="{{ $no['level'] }}">
            <td>{{ $no['cod'] }} · {{ $no['desc'] }}</td>
          </tr>
        @endforeach
      </table>
    @endforeach
  @endisset
@endsection
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/RelatorioQuadrimestralTest.php --filter="rota_index_exige_auth|gerar_renderiza|gerar_valida"`
Expected: PASS. (Rode `php artisan optimize:clear` antes se a rota não for encontrada por cache.)

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/RelatorioQuadrimestralController.php routes/web.php resources/views/relatorios/quadrimestral/index.blade.php
git commit -m "feat(quadrimestral): controller, rotas e view mínima"
```

---

## Task 5: Assets — porte de `tabela-avancada.js` + CSS

Sem teste automatizado (JS de UI); validação manual no fim. Porte deliberadamente lazy: mantém drill/filtro/sort/export-CSV, remove persistência.

**Files:**
- Create: `public/js/tabela-avancada.js`
- Create: `public/css/tabela-avancada.css`

- [ ] **Step 1: Copiar o componente**

Copiar `cria-app/assets/template/static/js/tabela-avancada.js` para `public/js/tabela-avancada.js`.

- [ ] **Step 2: Aplicar os ajustes de porte (Laravel)**

No `public/js/tabela-avancada.js`:

1. **CSRF** — trocar a função `csrf`:
```js
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';
```

2. **Persistência (preferências)** — substituir a função `preferences` por no-op resolvido (mantém a API que o resto do código chama):
```js
// ponytail: sem persistência de preferências; adicionar rota /api/preferencia se pedirem.
async function preferences(_key, _value) { return {}; }
```

3. **Export XLSX server** — no `exportExcel`, trocar o endpoint por CSV client (já existe `exportCsv`). Substituir o corpo de `exportExcel` por:
```js
async function exportExcel(allAccounts=false) { exportCsv(allAccounts); }
```
`// ponytail: XLSX server é fase 2; usa CSV por enquanto.`

Nada mais muda — drill, filtro por coluna, ordenação, paginação por ramo e mostrar/ocultar colunas seguem iguais.

- [ ] **Step 3: Criar o CSS**

Create `public/css/tabela-avancada.css` (estilos mínimos das classes que o JS usa):

```css
.table-card { background:#fff; border:1px solid #e5e7eb; border-radius:.75rem; margin-bottom:1.5rem; overflow:hidden; }
.section-head, .tabela-cab { display:flex; align-items:center; justify-content:space-between; padding:.75rem 1rem; border-bottom:1px solid #e5e7eb; }
.table-scroll { overflow-x:auto; }
table[data-tree] { width:100%; border-collapse:collapse; font-size:.875rem; }
table[data-tree] th, table[data-tree] td { padding:.5rem .75rem; border-bottom:1px solid #f1f5f9; text-align:left; }
table[data-tree] th { background:#f8fafc; font-weight:600; cursor:pointer; }
table[data-tree] td.numeric, table[data-tree] th.numeric { text-align:right; font-variant-numeric:tabular-nums; }
.tree-cell { display:flex; align-items:center; gap:.25rem; }
.tree-level-1 { padding-left:1.25rem; } .tree-level-2 { padding-left:2.5rem; } .tree-level-3 { padding-left:3.75rem; }
.tree-toggle { border:0; background:none; cursor:pointer; padding:0 .25rem; color:#64748b; }
.tree-spacer { display:inline-block; width:1.25rem; }
.row-subtotal { font-weight:600; background:#fcfcfd; }
.ta-filter-row th { background:#fff; }
.ta-filter-row input { width:100%; border:1px solid #cbd5e1; border-radius:.375rem; padding:.25rem .5rem; font-weight:400; }
.tab-menu-wrap { position:relative; display:inline-block; }
.btn-tab-menu { border:1px solid #cbd5e1; background:#fff; border-radius:.5rem; padding:.375rem .5rem; cursor:pointer; }
.tab-menu, .ta-popup { position:absolute; right:0; top:100%; z-index:20; background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; box-shadow:0 10px 30px rgba(0,0,0,.12); min-width:14rem; padding:.25rem; }
.tab-menu[hidden], .ta-popup[hidden] { display:none; }
.tab-menu-item, .ta-option { display:flex; align-items:center; gap:.5rem; width:100%; border:0; background:none; text-align:left; padding:.5rem .625rem; border-radius:.375rem; cursor:pointer; }
.tab-menu-item:hover, .ta-option:hover { background:#f1f5f9; }
.tab-menu-sep { height:1px; background:#e5e7eb; margin:.25rem 0; }
.ta-popup.abre-acima { top:auto; bottom:100%; }
.oc-pag { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:.75rem 1rem; }
.oc-pag[hidden] { display:none; }
.oc-pag-ctrls { display:flex; gap:.25rem; }
.tab-status { padding:0 1rem .5rem; color:#64748b; font-size:.8125rem; }
.btn { border:1px solid #cbd5e1; background:#fff; border-radius:.375rem; padding:.25rem .5rem; cursor:pointer; }
.btn:disabled { opacity:.5; cursor:default; }
```

- [ ] **Step 4: Commit**

```bash
git add public/js/tabela-avancada.js public/css/tabela-avancada.css
git commit -m "feat(quadrimestral): porta tabela-avancada.js + css para Laravel"
```

---

## Task 6: View completa (tabela drill) + menu + validação

**Files:**
- Modify: `resources/views/relatorios/quadrimestral/index.blade.php`
- Modify: `app/View/Components/Sidebar.php`
- Test: `tests/Feature/RelatorioQuadrimestralTest.php` (reusa os testes das Tasks 4)

- [ ] **Step 1: Substituir a view pela versão completa**

`resources/views/relatorios/quadrimestral/index.blade.php`:

```blade
@extends('layouts.modern')
@section('title', 'Produção Quadrimestral')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tabela-avancada.css') }}">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/regular/style.css">
<link rel="stylesheet" href="https://unpkg.com/@phosphor-icons/web@2.1.1/src/bold/style.css">
@endpush

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Produção Quadrimestral</h1>
    <p class="text-gray-500">SIA + SIH + e-SUS · 4 meses e total, por tipo de relatório.</p>
</div>

<form method="POST" action="{{ route('relatorios.quadrimestral.gerar') }}"
      class="bg-white border border-gray-200 rounded-xl p-4 mb-6 flex flex-wrap items-end gap-4">
    @csrf
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Ano</label>
        <select name="ano" class="border border-gray-300 rounded-lg px-3 py-2">
            @foreach($anos as $a)
                <option value="{{ $a }}" @selected(($resultado['ano'] ?? null) === $a)>{{ $a }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Quadrimestre</label>
        <select name="quadrimestre" class="border border-gray-300 rounded-lg px-3 py-2">
            @foreach($quadrimestres as $v => $l)
                <option value="{{ $v }}" @selected(($resultado['quadrimestre'] ?? null) === $v)>{{ $l }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 font-medium hover:bg-blue-700">Aplicar</button>
</form>

@isset($resultado)
    @forelse($resultado['secoes'] as $s => $secao)
        <section class="table-card statement" data-quad-secao>
            <header class="section-head"><h2 class="font-semibold text-gray-900">{{ $secao['tipo'] }}</h2></header>
            <div class="table-scroll">
                <table data-tree="true" data-tree-depth="2">
                    <thead>
                        <tr>
                            <th data-col-key="dim">Subgrupo / Forma / Procedimento / Prestador</th>
                            @foreach($resultado['meses'] as $m)
                                <th class="numeric" data-col-key="m{{ $loop->index }}" data-tipo="numero">{{ $m }}</th>
                            @endforeach
                            <th class="numeric" data-col-key="total" data-tipo="numero">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($secao['linhas'] as $no)
                            <tr data-node-id="{{ $no['node_id'] }}" data-parent-id="{{ $no['parent_id'] }}"
                                data-level="{{ $no['level'] }}" class="{{ $no['has_children'] ? 'row-subtotal' : '' }}">
                                <td>
                                    <div class="tree-cell tree-level-{{ $no['level'] }}">
                                        @if($no['has_children'])
                                            <button type="button" class="tree-toggle" data-tree-toggle
                                                    aria-expanded="true" aria-label="Recolher {{ $no['desc'] }}">
                                                <i class="ph ph-caret-down" aria-hidden="true"></i>
                                            </button>
                                        @else
                                            <span class="tree-spacer" aria-hidden="true"></span>
                                        @endif
                                        <span>{{ $no['cod'] }} · {{ $no['desc'] }}</span>
                                    </div>
                                </td>
                                @foreach($no['meses'] as $q)
                                    <td class="numeric">{{ number_format($q, 0, ',', '.') }}</td>
                                @endforeach
                                <td class="numeric">{{ number_format($no['total'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="row-subtotal">
                            <td>Total do tipo</td>
                            @foreach($secao['total_meses'] as $q)
                                <td class="numeric">{{ number_format($q, 0, ',', '.') }}</td>
                            @endforeach
                            <td class="numeric">{{ number_format($secao['total'], 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    @empty
        <p class="text-gray-500">Nenhuma produção encontrada para o quadrimestre selecionado.</p>
    @endforelse
@endisset
@endsection

@push('scripts')
<script src="{{ asset('js/tabela-avancada.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-quad-secao]').forEach((wrap, i) => {
        window.TabelaAvancada.init(wrap, { prefKey: 'quad-' + i, titulo: 'Produção' });
    });
});
</script>
@endpush
```

- [ ] **Step 2: Adicionar item de menu**

Em `app/View/Components/Sidebar.php`, no array da seção `relatorios` (`menuSections()`), adicionar após a linha `faturamento`:

```php
                    ['id' => 'quadrimestral', 'label' => 'Produção Quadrimestral', 'route' => 'relatorios.quadrimestral.index', 'icon' => 'relatorios'],
```

E em `resolveActiveRoute()`, antes do bloco genérico `relatorios.*`, adicionar:

```php
        if (request()->routeIs('relatorios.quadrimestral.*')) {
            return 'quadrimestral';
        }
```

- [ ] **Step 3: Rodar todos os testes do arquivo**

Run: `php artisan optimize:clear && php artisan test tests/Feature/RelatorioQuadrimestralTest.php`
Expected: PASS (todos).

- [ ] **Step 4: Pint**

Run: `vendor\bin\pint --dirty`
Expected: sem erros; arquivos formatados.

- [ ] **Step 5: Validação manual**

Run: `php artisan serve`
Abrir `http://localhost:8000/relatorios/quadrimestral`, escolher ano/quadrimestre, Aplicar. Conferir:
- seções por tipo de relatório;
- drill expande/colapsa (caret);
- filtro por coluna e ordenação no menu de ferramentas;
- 4 colunas de mês + total batem com o total do tipo.

- [ ] **Step 6: Commit**

```bash
git add resources/views/relatorios/quadrimestral/index.blade.php app/View/Components/Sidebar.php
git commit -m "feat(quadrimestral): view drill completa + item de menu"
```

---

## Task 7: Fechamento

- [ ] **Step 1: Suite completa**

Run: `php artisan test`
Expected: sem regressões. Se algum teste de outro módulo quebrar por cache de rota, rodar `php artisan optimize:clear` antes.

- [ ] **Step 2: Atualizar docs de navegação**

Adicionar linha do novo relatório em `.context/docs/routes-map.md` (módulo Relatórios) e uma nota em `.context/docs/current-work.md`. Registrar o índice `s_prd.prd_cmp` como requisito de produção em `.context/docs/performance-playbook.md` se ainda não constar.

- [ ] **Step 3: Commit**

```bash
git add .context/docs/
git commit -m "docs(quadrimestral): registra relatório no mapa de rotas e current-work"
```

---

## Notas de execução

- **Índice `s_prd.prd_cmp`**: as 3 queries dependem do filtro de competência. Confirmar que existe índice em `s_prd.prd_cmp` em produção antes de liberar (é aditivo, não altera schema DATASUS). `s_aih_pa` e `s_esus` já têm índice de competência.
- **Tabelas core imutáveis**: nenhuma DDL em `s_prd`/`forma`/`procedimento`/`prestador` (só leitura). Ver CLAUDE.md §4.1.
- **Fallback de timeout** (só se a query SIA isolada estourar em produção): materializar cada fonte numa `CREATE TEMPORARY TABLE ... ENGINE=MEMORY` pré-agregada e ler as temps. Não implementar agora (ver spec §7).
- **Migrations de teste**: `s_prd`/`s_aih_pa`/`s_esus` precisam existir em `producao_test`. Se faltarem, rodar as migrations correspondentes 1 a 1 (`php artisan migrate --path=...`) — CLAUDE.md §8.
```
