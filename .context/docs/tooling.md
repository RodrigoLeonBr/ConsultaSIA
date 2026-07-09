---
type: doc
name: tooling
description: SQL utilities, IDE setup, and developer productivity tools
category: tooling
generated: 2026-02-22
status: filled
scaffoldVersion: "2.0.0"
---

# Tooling

## Stack em produção

| Camada | Tecnologia |
|--------|-----------|
| Backend | Laravel 12 + PHP 8.2 |
| Frontend | Blade + Tailwind + Alpine.js |
| Banco | MariaDB 10.4 (`producao`) |
| Exportações | Maatwebsite Excel + DomPDF |

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

## IDE Recomendado

**VSCode** ou **Cursor** com extensões:

- PHP Intelephense
- Laravel Extra Intellisense
- Database Client (conexão MySQL direto no editor)
