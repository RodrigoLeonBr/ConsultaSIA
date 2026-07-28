# Módulo e-SUS (produção SIGTAP via API) — mapa para AI

> **Leia este arquivo ANTES de tocar em qualquer coisa de e-SUS.** Resume tabelas,
> arquivos, funções e regras. Só abra o código-fonte se precisar de detalhe além
> do resumo aqui.
> Última atualização: 2026-07-29

---

## 1. O que é

Importa **produção ambulatorial já tratada e agregada** do sistema externo
**simpa** (e-SUS), por unidade (CNES) + procedimento SIGTAP, para o banco
`producao` (MariaDB). Dados vêm via **API JSON** (não arquivo), autenticada por
**JWT**. Escopo entregue: **importação + CRUD + relatório dinâmico** (nos moldes
do `/relatorios`, sem valores financeiros).

Diferente do SIHD/AIH (arquivo texto) e do `s_prd` (produção bruta): aqui os
dados já vêm **agregados e só com quantidade** (sem valores R$).

**Não há mais de-para de unidades** (`esus_unidade` foi descontinuado): o CNES
vem no próprio payload. Quais unidades entram nas consultas/relatórios é
controlado pela flag **`prestador.esus_ativo`** (ver §5).

---

## 2. API externa (simpa)

- **Base**: `http://localhost:8080` (config `services.esus.base_url`)
- **Origem real**: postgres do simpa (`E:\xampp\htdocs\simpa`), serviço
  `simpa-backend/src/services/producaoSigtapService.js` → `exportProducao()`.
  O `cnes` vem de `estabelecimentos.codigo_externo`.

| Método | Rota | Retorno |
|---|---|---|
| POST | `/auth/login` | body `{username, senha}` → `{token, user}`; 400 se faltar; 401 inválido |
| GET | `/api/cadastros/procedimentos-sigtap/competencias` | `["2026-07", ...]` (YYYY-MM desc) |
| GET | `/api/cadastros/procedimentos-sigtap/producao?competencia=YYYY-MM` | array de linhas; **400** se competência inválida |

`/api/*` exige header `Authorization: Bearer <token>`.

**Linha de produção** (JSON):
```
{ competencia, cnes, unidade, tipo_relatorio, bloco,
  descricao_esus, codigo_sigtap, descricao_sigtap, quantidade }
```
- `cnes` = `estabelecimentos.codigo_externo` (pode vir **vazio** — a linha é gravada mesmo assim, `cnes` null).
- `codigo_sigtap` pode vir **vazio** (blocos CDS). Gravado mesmo assim.
- Zeros descartados na origem (`HAVING quantidade > 0`).
- **Grão da origem** (GROUP BY): `competencia + cnes + unidade + tipo_relatorio + bloco + descricao_esus + codigo_sigtap + descricao_sigtap`.

**Credenciais** (`.env`): `ESUS_API_URL`, `ESUS_API_USER`, `ESUS_API_PASSWORD`.
Sem elas, login lança exceção (tela abre com aviso amarelo, sem listar competências).

**Consultar o postgres simpa** (debug, checar CNES de unidade): container docker
`simpa-postgres-1` porta `5433`, db `simpa`, user/pass `postgres/postgres`.
`psql` não está no host → usar:
```
docker exec -e PGPASSWORD=postgres simpa-postgres-1 psql -U postgres -d simpa -tA -F';' \
  -c "SELECT nome, codigo_externo FROM estabelecimentos;"
```

---

## 3. Tabelas

### `s_esus` — produção (auxiliar, migration OK)
`id`, `competencia` VARCHAR(7) **YYYY-MM** (formato nativo e-SUS, difere do
`YYYYMM` das outras tabelas!), `cnes` VARCHAR(7) **null**, `unidade` VARCHAR(180),
`tipo_relatorio` VARCHAR(60), `bloco` VARCHAR(120), `descricao_esus` VARCHAR(180),
`codigo_sigtap` VARCHAR(10), `descricao_sigtap` VARCHAR(180), `quantidade` INT,
timestamps.

- **uk_esus** = (`competencia`, `unidade`, `tipo_relatorio`, `bloco`, `codigo_sigtap`, `descricao_esus`) — espelha o grão da origem. **Precisa de `tipo_relatorio` + `bloco`**: mesmo SIGTAP aparece em tipos diferentes (ex: `0301010030` em `procedimentos_individualizados` E `atendimento_odontologico`) → sem eles dá `Duplicate entry`.
- Índices: `idx_esus_cmp`, `idx_esus_cnes`, `idx_esus_cnes_cmp`, `idx_esus_sigtap`.
- `codigo_sigtap` liga a `procedimento.codigo`; agrupar por forma = `LEFT(codigo_sigtap,6)` → tabela `forma`.

### `prestador.esus_ativo` — flag de consideração (BOOL, default 1)
Coluna adicionada a `prestador` (que já tem colunas de app `ativo`/`relatorio`;
**não** é uma coluna DATASUS). Marca se a produção e-SUS da unidade é considerada
nas **consultas/relatórios futuros**. Padrão **sim (1)**. Desmarcar quando a
produção vier de outra fonte (ex.: Núcleo de Especialidades → usar SIA).
**Não afeta a importação** (ver §5).

### `esus_unidade` — REMOVIDA
De-para nome→CNES descontinuado. Migration `2026_07_29_...` dropa a tabela.

### DDL
- Migrations: `2026_07_27_000000_create_esus_tables.php` (cria `s_esus`), `2026_07_28_000000_fix_esus_unique_key.php` (uk final), `2026_07_29_000000_add_esus_ativo_to_prestador_and_drop_esus_unidade.php` (flag + drop de-para).
- **Produção (SQL manual, migrations desabilitadas em prod)**: `database/sql/atualizar_producao_2026_07_esus.sql` (cria `s_esus`, `ALTER prestador ADD esus_ativo`, `DROP esus_unidade`).
- Migrations aqui **não são rastreadas** (banco montado por SQL). Rodar 1 por vez: `php artisan migrate --path=database/migrations/<arquivo>.php`. Testes usam `producao_test` com RefreshDatabase.

---

## 4. Arquivos e funções (resumo — não precisa abrir o código)

### `app/Services/EsusApiService.php` — cliente HTTP JWT
Facade `Http` (sem lib cURL). Token lazy, cacheado na instância.
- `fromConfig(): self` — monta de `config('services.esus.*')`.
- `getCompetencias(): array` / `getProducao(string $competencia): array` (trata 400).
- `private login()` / `private authorized(): PendingRequest`.
- Bind: `AppServiceProvider::register` → `singleton(EsusApiService::class, fromConfig())` (construtor tem `string $baseUrl`, não auto-resolve — **não remover o bind**).

### `app/Services/EsusImportService.php` — regra de importação
Construtor recebe `EsusApiService`; `make(): self` via `fromConfig`.
- `getCompetencias(): array`.
- `preview(string $competencia): array` — **não grava**. Retorna `{competencia, total_linhas, sem_cnes, unidades[{unidade,cnes,linhas}], existentes}`. `sem_cnes` = só informativo (unidades sem CNES no payload). `existentes` = linhas já em `s_esus`.
- `apply(string $competencia): array` — **grava tudo**. Transacional: `DELETE` da competência + insert em chunks de 500. Retorna `{inserted, replaced}`. **Substitui a competência inteira**. **Dedupe** por grão (=uk) somando `quantidade`. **Nenhuma linha é descartada**; `cnes` vazio → null.
- `history(): array` — resumo por competência.
- `private cnes(row): ?string` — CNES do payload ou null.

### `app/Http/Controllers/EsusImportController.php` — fluxo
Preview em sessão (`const SESSION_KEY` público).
- `create(service)` — form: competências da API (try/catch → `apiError`) + histórico. View `esus.import`.
- `store(request, service)` — valida `competencia` (`^\d{4}-\d{2}$`), `preview`, sessão, redirect.
- `preview()` — view `esus.import-preview`.
- `apply(request, service)` — **se `existentes > 0` e `confirm_replace` não marcado → bloqueia** pedindo confirmação. Senão grava, limpa sessão, flash.

### `app/Http/Controllers/EsusController.php` — CRUD produção
`index` (filtros `competencia`/`search`, paginação 30), `edit`, `update`, `destroy`. Sem create. Param `{esu}`.

### `app/Http/Controllers/EsusRelatorioController.php` — relatório dinâmico
Estende `BaseRelatorioController` + trait `HasMatrixReport` (mesmo motor do `/relatorios`, AIH). Fonte `s_esus` (alias `es`). **Sem valores financeiros** — única métrica é `quantidade` (SUM). Campos: `competencia` (pivô da matriz), `cnes` (lookup prestador), `unidade`, `esus_ativo` (lookup estático Sim/Não via prestador — filtre `=1` para excluir unidades que usam SIA), `tipo_relatorio`, `bloco`, `descricao_esus`, `codigo_sigtap` (lookup procedimento), `descricao_sigtap`, grupo/subgrupo/forma (+desc) via `LEFT(codigo_sigtap,n)` na tabela `forma`, `quantidade`. **Collation**: `s_esus` é unicode_ci e prestador/procedimento/forma são general_ci → todos os joins forçam `COLLATE utf8mb4_general_ci`. Views `relatorios/esus/{index,pdf}.blade.php` (index = cópia do AIH com rotas `relatorios.esus.*`, `COMPETENCIA_FIELD='competencia'`, sem bloco SUS Paulista).

### `app/Http/Controllers/PrestadorController.php` — cadastro prestador
CRUD padrão + `toggleStatus`. A flag `esus_ativo` entra pelo `PrestadorRequest`
(regra boolean + merge em `prepareForValidation`) e aparece nos forms create/edit
(checkbox "Considerar no e-SUS", marcado por padrão).

### Models
- `SEsus` (`s_esus`) — fillable todos; cast `quantidade`→int; `procedimento()`/`prestador()`; scope `forCompetencia`.
- `Prestador` — fillable inclui `esus_ativo`; cast boolean.

### Form Requests
- `SEsusRequest` — `quantidade` required int ≥0; `cnes` size:7.
- `PrestadorRequest` — inclui `esus_ativo` (boolean); `prepareForValidation` faz merge dos checkboxes `ativo`/`esus_ativo`.

### Views (`resources/views/esus/`)
- `import.blade.php` — seleciona competência, botão "Analisar competência", histórico.
- `import-preview.blade.php` — cards (linhas/unidades/sem CNES/já no banco), tabela por unidade, **checkbox `confirm_replace`** quando `existentes > 0`, botão aplicar.
- `index.blade.php` / `edit.blade.php` — CRUD produção.
- Layout `@extends('layouts.modern')` (usa `<x-sidebar/>`).
- Prestador: checkbox e-SUS em `resources/views/prestador/{create,edit}.blade.php`.

### Rotas (`routes/web.php`, grupo auth)
```
GET  esus-import            esus.import
POST esus-import            esus.import.store
GET  esus-import/preview    esus.import.preview
POST esus-import/apply      esus.import.apply
GET  esus                   esus.index
GET  esus/{esu}/edit        esus.edit
PUT  esus/{esu}             esus.update
DEL  esus/{esu}             esus.destroy

GET  relatorios/esus                 relatorios.esus.index
GET  relatorios/esus/fields          relatorios.esus.fields
GET  relatorios/esus/lookup          relatorios.esus.lookup
POST relatorios/esus/generate        relatorios.esus.generate
POST relatorios/esus/generate-matrix relatorios.esus.generate-matrix
```
(As rotas `esus-unidade.*` e `esus.import.sync-unidades` foram **removidas**.)

### Menu lateral
`app/View/Components/Sidebar.php` (**não** o `navigation.blade.php`). Seção `esus`
com itens `esus-import`/`esus` + resolução de rota ativa. Ícones reusam
`aih-import`/`relatorios`.

### Config
`config/services.php` bloco `esus` (base_url/username/password).

### Testes
- `tests/Feature/EsusImportTest.php` (`Http::fake`): grava todas as linhas incl. sem CNES; separa por tipo e soma grão idêntico; substitui competência; preview sem gravar; bloqueia reimport sem confirmação; index exige auth.
- `tests/Feature/PrestadorEsusFlagTest.php`: `esus_ativo` default true; update liga/desliga.

---

## 5. Regras de negócio (invioláveis)

1. **Importação grava TODAS as linhas** — nada é pulado. `cnes` vazio → null; `codigo_sigtap` vazio (CDS) → gravado.
2. A flag **`prestador.esus_ativo`** decide se a unidade entra nas **consultas/relatórios** (padrão sim). **NÃO** filtra a importação.
3. **Importar substitui a competência inteira** (delete+insert transacional).
4. **Reimportar competência existente exige `confirm_replace`** (checkbox). Sem confirmação, bloqueia.
5. **uk_esus precisa de `tipo_relatorio` + `bloco`** — senão colide.
6. **Dedupe somando `quantidade`** para grão idêntico repetido pela origem.
7. `competencia` é **YYYY-MM** (não YYYYMM).
8. Não remover o **bind** de `EsusApiService` no `AppServiceProvider`.

---

## 6. Como rodar / operar

- **Importar**: menu e-SUS → Importar → escolher competência → Analisar → conferir preview → (marcar confirmação se já existe) → Aplicar. Grava tudo.
- **Excluir unidade das consultas** (ex.: usar SIA): `/prestador` → editar a unidade → desmarcar **"Considerar no e-SUS"**. (Filtro aplicado quando o relatório for implementado.)
- **Testes**: `php artisan test tests/Feature/EsusImportTest.php tests/Feature/PrestadorEsusFlagTest.php`.
- **Migração dev**: `php artisan migrate --path=database/migrations/<arquivo>.php`.
- **Prod**: aplicar `database/sql/atualizar_producao_2026_07_esus.sql`.

---

## 7. Pendências / futuro

- Filtro `esus_ativo` no relatório é manual (usuário adiciona `Considerar e-SUS = Sim`). Se quiser **excluir automaticamente** as unidades com `esus_ativo=0`, aplicar um `where` fixo no `buildQuery`/`buildMatrixData` do `EsusRelatorioController`.
- Export PDF/CSV da **matriz** não implementado no motor base (`HasMatrixReport` retorna 501) — vale para todos os relatórios, não só e-SUS.
