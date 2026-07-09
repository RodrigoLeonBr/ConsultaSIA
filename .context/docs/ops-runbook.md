# Ops Runbook: Operação Laravel + MySQL

Procedimentos de infraestrutura e gestão do ConsultaProd em produção (XAMPP / `php artisan serve`).

## Servidor de aplicação

```bash
# Desenvolvimento
php artisan serve

# Produção (Apache/XAMPP): DocumentRoot em public/
# Após deploy:
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Inspecionando jobs travados

```sql
SELECT id, type, created_at, started_at, TIMEDIFF(NOW(), started_at) AS runtime_duration
FROM producao.report_job
WHERE status = 'running'
  AND started_at < NOW() - INTERVAL 1 HOUR;
```

**Logs de falha:**

```sql
SELECT id, error_message, finished_at
FROM producao.report_job
WHERE status = 'failed'
ORDER BY finished_at DESC
LIMIT 10;
```

## Reprocessar job falho

```sql
UPDATE producao.report_job
SET status = 'queued', started_at = NULL, finished_at = NULL,
    error_message = NULL, progress = 0
WHERE id = [JOB_ID];
```

## Política de retenção e TTL

Jobs pesados alimentam `report_result_header` e `report_result_rows`. Limpar periodicamente via cron:

```sql
DELETE FROM producao.report_result_header
WHERE ttl_expires_at < NOW();
```

## Cache e manutenção

```bash
php artisan optimize:clear   # após mudanças de config/rota/view
```
