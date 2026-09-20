# Spec — Relatório de Produção Quadrimestral (SIA + SIH + e-SUS)

**Data:** 2026-09-20
**Branch alvo:** feature nova (a partir de `main`)
**Autor:** brainstorming ConsultaProd

---

## 1. Objetivo

Novo relatório que consolida **produção de procedimentos** de três fontes —
SIA (`s_prd`), SIH (`s_aih_pa`) e e-SUS (`s_esus`) — em um único relatório
**quadrimestral** (4 meses + total), com **drill-down** e agrupamento por
**tipo de relatório**.

A única métrica é **quantidade de procedimentos** (sem valores R$).

---

## 2. Decisões travadas (brainstorming)

| Tema | Decisão |
|---|---|
| Combinar fontes | **Somar** SIA + SIH + e-SUS. e-SUS entra **apenas** para prestadores com `prestador.esus_ativo = 1` (evita dupla contagem com SIA). |
| Quantidade SIA | **Aprovada** — `PRD_QT_A`. |
| Quantidade SIH | `s_aih_pa.QUANTIDADE`. |
| Quantidade e-SUS | `s_esus.quantidade`. |
| Quadrimestre | Fixo do ano civil: **Q1 Jan–Abr / Q2 Mai–Ago / Q3 Set–Dez**. Usuário escolhe **ano + quadrimestre**. |
| Agrupamento topo | **Seção por tipo de relatório** (`prestador.relatorio`) — uma tabela drill por tipo. |
| Níveis do drill | `subgrupo → forma → procedimento → prestador`. |
| Componente tabela | Portar `tabela-avancada.js` do `cria-app` (drill + filtro por coluna + sort + export CSV client). |
| Arquitetura de dados | **3 queries separadas + merge em PHP** (não UNION de derived tables — ver §7). |

---

## 3. Fontes e mapeamento de colunas

Grão de saída de cada query (idêntico nas 3):
`tipo_relatorio, subgrupo_cod, subgrupo_desc, forma_cod, forma_desc,
proc_cod, proc_desc, cnes, prestador_nome, competencia (YYYYMM), qtd`.

| Fonte | Tabela (alias) | Proc | Qtd (SUM) | CNES | Competência | Collation join |
|---|---|---|---|---|---|---|
| SIA | `s_prd` (sp) | `prd_pa` | `SUM(CAST(sp.PRD_QT_A AS UNSIGNED))` | `prd_uid` | `prd_cmp` (YYYYMM) | general_ci (nativo) |
| SIH | `s_aih_pa` (ap) | `PROC_DETALHADO` | `SUM(ap.QUANTIDADE)` | `CNES` | `COMPETENCIA` (YYYYMM) | forçar `COLLATE utf8mb4_general_ci` |
| e-SUS | `s_esus` (es) | `codigo_sigtap` | `SUM(es.quantidade)` | `cnes` | `competencia` (**YYYY-MM**) | forçar `COLLATE utf8mb4_general_ci` |

### Dimensões (iguais para as 3), via LEFT JOIN
- `prestador pr` on `<cnes> = pr.re_cunid` → `pr.relatorio` (tipo), `pr.re_cnome`.
- `forma fs` (subgrupo): `SUBSTRING(<proc>,1,4) = fs.subgrupo AND fs.forma = CONCAT(SUBSTRING(<proc>,1,4),'00')` → `fs.descricao`.
- `forma ff` (forma): `SUBSTRING(<proc>,1,6) = ff.forma` → `ff.descricao`.
- `procedimento pc` on `<proc> = pc.codigo` → `pc.procedimento`.

### Regras de descarte (não cabem na árvore)
- Linha sem CNES (e-SUS `cnes` null) → descartada (INNER JOIN em `prestador` na fonte e-SUS já faz isso).
- Linha com `proc` inválido (`LENGTH < 6`, ex.: e-SUS blocos CDS sem `codigo_sigtap`) → descartada via `WHERE LENGTH(<proc>) >= 6`.
- `pr.relatorio` null/vazio → agrupado na seção **"Sem tipo"** (mantido, não descartado).

### Filtro e-SUS
Fonte e-SUS usa **INNER JOIN prestador** com `pr.esus_ativo = 1`.
SIA e SIH usam LEFT JOIN (mantêm a linha mesmo sem prestador cadastrado; tipo cai em "Sem tipo").

### Competências do quadrimestre
Resolver (ano, quadrimestre) → 4 competências:
- Q1 → `{ano}01..{ano}04`; Q2 → `..05..08`; Q3 → `..09..12`.
- SIA/SIH filtram `IN (4× YYYYMM)`.
- e-SUS filtra `IN (4× 'YYYY-MM')` (formato nativo, usa índice `idx_esus_cmp`), e a saída normaliza `REPLACE(competencia,'-','')` para YYYYMM.

---

## 4. Camadas e arquivos

### 4.1 Service — `app/Services/ProducaoQuadrimestralService.php`
Responsável por dados. Sem HTTP.

- `competenciasDoQuadrimestre(int $ano, int $quad): array` — retorna os 4 YYYYMM.
- `anosDisponiveis(): array` — distinct de anos das 3 fontes (para o select).
- `linhas(int $ano, int $quad): array` — roda as **3 queries**, concatena.
  Cada query: `querySia() / querySih() / queryEsus()` (métodos privados),
  já com GROUP BY no grão de §3 e filtro de competência.
- `montarSecoes(array $linhas, array $competencias): array` — agrupa por
  `tipo_relatorio`; dentro de cada tipo, **lineariza** a árvore
  (subgrupo→forma→procedimento→prestador) espelhando
  `cria-app/.../core/arvore.py::linearizar`:
  - cada nó: `node_id, parent_id, level (0..3), has_children, label_cod,
    label_desc, meses[4], total`.
  - acumula `meses[i]` e `total` em **todos os níveis ancestrais** (padrão
    `FaturamentoPrestadorController::processarDadosHierarquicos`).
  - ordena por código em cada nível.
  Retorna: `[ ['tipo' => ..., 'linhas' => [<nós lineares>], 'total_meses' => [...], 'total' => ...], ... ]`.

**node_id (único por tabela/seção):**
`sg:<subgrupo>` · `fo:<forma>` · `pc:<proc>` · `pe:<proc>:<cnes>`.
`parent_id` encadeia; raízes (subgrupo) têm `parent_id = ''`.

### 4.2 Controller — `app/Http/Controllers/RelatorioQuadrimestralController.php`
- `index()` — view de filtros (anos disponíveis, quadrimestres). Sem gerar.
- `gerar(Request $r)` — valida `ano` (required int), `quadrimestre` (required in:1,2,3);
  chama service; passa `secoes` + rótulos dos 4 meses para a view.
- Export XLSX/PDF → **fase 2** (ver §8).

### 4.3 View — `resources/views/relatorios/quadrimestral/index.blade.php`
- `@extends('layouts.modern')` (usa `<x-sidebar/>`).
- Filtros com botão **"Aplicar"** (nunca onChange) — regra CLAUDE.md §4.3.
- Uma `<section>` por tipo de relatório, cada uma com
  `<table data-tree="true" data-tree-depth="2">`:
  - `<thead>`: coluna 1 `data-col-key="dim"` (Subgrupo/Forma/Proc/Prestador),
    depois 4 colunas mês (`data-col-key`, `class="numeric"`, `data-tipo="numero"`)
    + coluna Total.
  - `<tbody>`: uma `<tr data-node-id data-parent-id data-level>` por nó linear;
    célula 1 = tree-cell com botão `data-tree-toggle` quando `has_children`
    (padrão `balancete_arvore.html` / `arvore_macros.html`).
- Inicializa via `TabelaAvancada.init(wrap, {prefKey})` por seção.

### 4.4 Assets portados
- `public/js/tabela-avancada.js` — porte do `cria-app`. **Ajustes de porte (lazy):**
  - CSRF: `document.querySelector('meta[name="csrf-token"]').content` (Laravel)
    no lugar de `input[name="csrf_token"]`; header `X-CSRF-TOKEN`.
  - Persistência de preferências (`/api/preferencia/*`): **removida** — `preferences()`
    vira no-op resolvido (drill/filtro/sort funcionam sem persistir). `// ponytail: sem persistência; add /api/preferencia se pedirem`.
  - Export: manter **CSV client-side**; XLSX (`/exportar/tabela.xlsx`) → botão oculto na fase 1.
  - Mantém: drill (expand/collapse/níveis), filtro por coluna, ordenação, paginação por ramo, mostrar/ocultar colunas.
- `public/css/tabela-avancada.css` — extrair as classes usadas: `ta-*`, `tab-menu-*`,
  `tree-cell`, `tree-toggle`, `tree-spacer`, `tree-level-*`, `oc-pag*`, `section-head`.
  Origem: `cria-app/assets/template/static/css/{componentes,balancete,layout}.css`.
  Ícones: o JS usa classes Phosphor (`ph ph-*`). Se o projeto não tiver Phosphor,
  trocar por ícones já usados no projeto **ou** incluir a fonte Phosphor. **Decidir no plano.**

### 4.5 Menu — `app/View/Components/Sidebar.php`
Item novo em `menuSections` (grupo de relatórios): "Produção Quadrimestral" →
`relatorios.quadrimestral.index`.

### 4.6 Rotas — `routes/web.php` (grupo auth)
```
GET  relatorios/quadrimestral         relatorios.quadrimestral.index
POST relatorios/quadrimestral/gerar   relatorios.quadrimestral.gerar
```

---

## 5. Layout da tabela (por seção)

```
[Tipo de relatório: <nome>]                       Mês1  Mês2  Mês3  Mês4  Total
▸ 0401 · Subgrupo desc                              ...   ...   ...   ...   ...
  ▸ 040101 · Forma desc                             ...   ...   ...   ...   ...
    ▸ 0401010010 · Procedimento desc                ...   ...   ...   ...   ...
        2058790 · Prestador nome                    ...   ...   ...   ...   ...
[Total do tipo]                                     ...   ...   ...   ...   ...
```
Rótulos dos meses: nome PT-BR do mês (ex.: "Jan/2026"), do 1º ao 4º da janela.

---

## 6. Testes — `tests/Feature/RelatorioQuadrimestralTest.php`

Banco `producao_test` + `RefreshDatabase`. Semear linhas nas 3 fontes.
Casos:
1. **Soma cross-fonte**: mesmo `cnes+proc` em SIA e e-SUS (esus_ativo=1) → leaf soma as duas.
2. **e-SUS excluído**: prestador com `esus_ativo=0` → produção e-SUS **não** entra; SIA do mesmo entra.
3. **4 meses + total**: valores caem na coluna do mês certo; total = soma dos 4.
4. **Árvore**: níveis subgrupo/forma/proc/prestador com `node_id/parent_id/level` corretos; totais acumulam nos ancestrais.
5. **Seção por tipo**: prestadores com `relatorio` diferente → seções separadas; null → "Sem tipo".
6. **Descarte**: e-SUS com `codigo_sigtap` vazio (CDS) e linha sem CNES → fora do resultado.
7. **Quadrimestre**: Q2 traz Mai–Ago; competências fora da janela ignoradas.
8. **Auth**: rota exige login.

> Migrations não são rastreadas (banco por SQL); testes usam `producao_test`
> com RefreshDatabase (roda tudo do zero). Ver CLAUDE.md §8.

---

## 7. Performance / timeout (análise pedida)

**Descartado — UNION ALL de derived tables:** materializa o UNION inteiro e
re-agrupa em cima; força scan pesado do `s_prd` (5.9M) dentro de derived table
sem índice → alto risco de timeout.

**Escolhido — 3 queries separadas + merge em PHP:**
- Cada query filtra `competência IN (4)` batendo no índice próprio
  (`s_prd.prd_cmp` — **verificar índice**, faturamento já depende dele;
  `s_aih_pa.idx_aih_pa_cmp`; `s_esus.idx_esus_cmp`).
- Cada query já retorna **pré-agregada** no grão de §3 → volume de linhas
  retornado é modesto (procedimentos × unidades × 4 meses).
- Merge/árvore em PHP; colisões cross-fonte somam na acumulação.
- Sem DDL, sem temp table, sem derived table gigante.

**Fallback (só se uma fonte isolada estourar):**
Temporária por fonte — `CREATE TEMPORARY TABLE tmp_quad_sia (...) ENGINE=MEMORY`
com o grão pré-agregado, depois ler as temps. Escopo de conexão (1 request).
**Não implementar na fase 1**; documentar como plano B se a query SIA sozinha
passar do timeout em produção.

**Guardas:**
- Filtro de competência **obrigatório** (validação `required`) — nunca carregar sem janela.
- Se o índice `s_prd.prd_cmp` não existir em produção, criar antes (script
  `database/sql/` — **não** ALTER em tabela core que mude schema DATASUS; índice é aditivo, confirmar com dono).

---

## 8. Fora de escopo (fase 2)

- Export **XLSX server-side** da matriz (reusar Maatwebsite / `MatrixReportExport`).
- Export **PDF**.
- Persistência de preferências de tabela (`/api/preferencia`).
- Filtro adicional por prestador/procedimento específico.

---

## 9. Ordem de implementação (para o plano)

1. `ProducaoQuadrimestralService` (3 queries + linearização) + teste unitário do merge/árvore.
2. Controller + rotas + view de filtros.
3. Porte `tabela-avancada.js` + CSS (decidir ícones Phosphor vs existentes).
4. View da tabela com `data-*` + init do componente.
5. Menu.
6. Feature test completo (§6). Pint. Rodar testes.
