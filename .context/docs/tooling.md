---
type: doc
name: tooling
description: Tech stack, scripts, SQL utilities, IDE setup, and developer productivity tools
category: tooling
generated: 2026-02-22
status: filled
scaffoldVersion: "2.0.0"
---

# Tooling

## Stack Técnica

| Camada | Tecnologia | Versão |
|--------|-----------|--------|
| Backend runtime | Node.js | 18+ LTS |
| Backend framework | NestJS | ^10 |
| ORM | TypeORM | ^0.3 |
| DB driver | mysql2 | ^3 |
| Validação | class-validator + class-transformer | ^0.14 |
| Export XLSX | ExcelJS | ^4 |
| Export PDF | PDFKit | ^0.14 |
| Frontend bundler | Vite | ^5 |
| Frontend framework | React | ^18 |
| HTTP client | Axios | ^1 |
| Linguagem | TypeScript | ^5 |

## Comandos SQL Úteis

### Inspecionar jobs presos em `running`

```sql
SELECT id, type, status, started_at,
       TIMESTAMPDIFF(MINUTE, started_at, NOW()) AS min_running
FROM report_job
WHERE status = 'running'
  AND started_at < NOW() - INTERVAL 10 MINUTE;
```

### Reprocessar job preso

```sql
UPDATE report_job
SET status = 'queued', started_at = NULL
WHERE id = '<job_id>';
```

### Verificar resultados expirados

```sql
SELECT id, job_id, ttl, total_rows_fetched
FROM report_result_header
WHERE ttl < NOW();
```

### Validação de referência (delta=0)

```sql
SELECT
  COUNT(*)                                        AS cnt,
  SUM(CAST(PRD_QT_A AS UNSIGNED))                 AS qt_a,
  SUM(CAST(PRD_VL_A AS DECIMAL(15,2)))            AS vl_a,
  SUM(CAST(PRD_QT_P AS UNSIGNED))                 AS qt_p,
  SUM(CAST(PRD_VL_P AS DECIMAL(15,2)))            AS vl_p
FROM s_prd
WHERE prd_cmp = '202301';

-- Esperado: cnt=31765, qt_a=214675, vl_a=1606620.01, qt_p=214866, vl_p=1673942.13
```

## Observabilidade

- `LoggingInterceptor` mede tempo de cada request sem logar body
- Saída exemplo: `[SiaController] POST /reports/sia/production — 46ms`
- Sem Datadog/Sentry no MVP; logs via stdout (redirecionar para arquivo em produção)

## IDE Recomendado

**VSCode** com extensões:

- ESLint
- Prettier — Code formatter
- TypeScript + JavaScript
- REST Client (arquivo `.http` para testar endpoints)
- Database Client (conexão MySQL direto no editor)

## Exemplos de Request (REST Client)

Criar `v3-backend/test/api.http`:

```http
@base = http://localhost:3000

### Health check
GET {{base}}/health

### Metadata do catálogo de campos
GET {{base}}/reports/sia/metadata

### Query dinâmica síncrona
POST {{base}}/reports/sia/production
Content-Type: application/json

{
  "competence": "202301",
  "select": ["prd_uid", "prd_pa", "PRD_QT_A", "PRD_VL_A"],
  "filters": [],
  "page": 1,
  "pageSize": 50,
  "sort": { "fieldId": "PRD_VL_A", "direction": "DESC" }
}

### Criar job assíncrono
POST {{base}}/reports/jobs
Content-Type: application/json

{
  "type": "sia-dynamic-production",
  "parameters": {
    "competence": "202301",
    "select": ["prd_uid", "PRD_QT_A", "PRD_VL_A"],
    "filters": [],
    "page": 1,
    "pageSize": 500
  }
}

### Status do job
GET {{base}}/reports/jobs/<job_id>

### Resultados paginados
GET {{base}}/reports/jobs/<job_id>/results?page=1&limit=200
```

## Variáveis de Ambiente Resumidas

| Variável | Onde | Descrição |
|----------|------|-----------|
| `DB_HOST` | backend | Host do MySQL (default: `localhost`) |
| `DB_PORT` | backend | Porta do MySQL (default: `3306`) |
| `DB_USER` | backend | Usuário MySQL (default: `root`) |
| `DB_PASSWORD` | backend | Senha MySQL (default: vazio) |
| `DB_NAME` | backend | Nome do banco (default: `producao`) |
| `PORT` | backend | Porta do servidor HTTP (default: `3000`) |
| `CORS_ORIGIN` | backend | Origem CORS permitida (default: `http://localhost:5173`) |
| `RUN_WORKER` | backend | `true` = modo worker, sem HTTP server |
| `VITE_API_URL` | frontend | URL base da API (default: `http://localhost:3000`) |
