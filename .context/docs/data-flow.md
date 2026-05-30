---
type: doc
name: data-flow
description: How data moves through the system — sync queries, async jobs, and file exports
category: data-flow
generated: 2026-02-22
status: filled
scaffoldVersion: "2.0.0"
---

# Data Flow

## 1. Fluxo Síncrono (Relatórios Leves)

Usado por `SiaReportsPage`, `SiaBillingProviderPage`, `SiaDynamicPage`.

```
Usuário → React UI
  → GET /reports/sia             (lista paginada s_prd)
  → GET /reports/sia/faturamento-prestador  (GROUP BY hierárquico)
  → POST /reports/sia/production (dinâmico: select/filters/sort)
  → SiaService → TypeORM query builder → MySQL producao.s_prd + JOINs
  → { columns[], rows[], meta } → DataGrid
```

Tempo esperado: p95 < 800ms (validado: ~46ms em produção com índice `cmp`).

## 2. Fluxo Assíncrono (Jobs Pesados)

Usado por `AsyncReportsPage` + `JobResultsPage`.

```
Usuário → AsyncReportsPage
  → POST /reports/jobs { type, parameters }
  → ReportsService.createJob()
  → INSERT report_job (status='queued')
  → HTTP 202 { jobId }

Worker (processo separado: RUN_WORKER=true)
  → Poll: SELECT * FROM report_job WHERE status='queued' ORDER BY created_at LIMIT 1
  → UPDATE status='running', started_at=NOW()  (atômico — evita corrida)
  → Executa query do job type
  → INSERT report_result_header (columns, ttl=NOW()+7d, totalRowsFetched)
  → INSERT report_result_rows em chunks (JSON por linha)
  → UPDATE status='done', completed_at=NOW()

Usuário → useJobPolling (intervalo 2s)
  → GET /reports/jobs/:id → status='done'
  → Redirect → JobResultsPage
  → GET /reports/jobs/:id/results?page=1&limit=200
  → DataGrid com resultados paginados
```

Polling: 2 segundos. Estados: `queued → running → done | failed`.

## 3. Fluxo de Export (Jobs de Export)

Usado por `JobResultsPage` (botão "Exportar como XLSX/CSV/PDF").

```
Usuário (JobResultsPage)
  → Seleciona formato (xlsx | csv | pdf)
  → POST /reports/jobs { type: 'export', parameters: { resultId, format } }
  → HTTP 202 { jobId }

Worker
  → Lê report_result_rows do resultId (em chunks)
  → Limite: MAX_EXPORT_ROWS=100.000 (xlsx/csv), MAX_PDF_ROWS=5.000 (pdf)
  → Gera arquivo:
      xlsx → ExcelJS (cabeçalho bold, bg azul claro, auto-width, UTF-8 BOM)
      csv  → nativo (campos quoted, UTF-8 BOM para Excel)
      pdf  → PDFKit (tabela com bordas, A4 landscape)
  → Salva em /tmp/exports/<jobId>.<ext>
  → UPDATE report_result_header.sourceTablesVersionsJson com filePath
  → UPDATE report_job status='done'

Usuário → polling detecta done
  → GET /reports/jobs/:id/download
  → API faz stream do arquivo para o browser
```

## 4. TTL e Limpeza

| Tipo | TTL | Limpeza |
|------|-----|---------|
| Resultados de jobs | 7 dias | Manual (sem job de cleanup no MVP) |
| Arquivos de export | 2 dias | Manual |

Expiração é verificada ao acessar `GET /reports/jobs/:id/results`. Resultado expirado retorna erro informativo.

## 5. Tabelas Envolvidas

| Tabela | Papel | Volume |
|--------|-------|--------|
| `s_prd` | Dados de produção SIA | ~6.3M rows |
| `prestador` | Lookup CNES→nome prestador | ~80 rows |
| `cbo` | Lookup código→ocupação | ~500 rows |
| `procedimento` | Lookup PA→descrição | ~3.160 rows |
| `s_rub` | Lookup rubrica | pequeno |
| `cismetro` | Lookup código→metro | pequeno |
| `report_job` | Fila de jobs assíncronos | cresce com uso |
| `report_result_header` | Metadados do resultado (columns, ttl, filePath) | 1 por job |
| `report_result_rows` | Linhas do resultado em JSON paginado | N por job |

## 6. Parâmetros de URL (SiaDynamicPage → AsyncReportsPage)

`SiaDynamicPage` pode pré-carregar parâmetros em `AsyncReportsPage` via query string:

```
/async-reports?preload=<base64(JSON(params))>
```

O JSON codificado contém `{ competence, select[], filters[], sort }`. Decodificado na montagem do componente.
