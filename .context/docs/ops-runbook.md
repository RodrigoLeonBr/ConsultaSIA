# Ops Runbook: Operação Híbrida sem Redis

Este documento descreve os procedimentos de infraestrutura e gestão para o funcionamento do motor de relatórios Node.js v3 em paralelo ao XAMPP/Laravel atual.

## Como rodar a API e Worker localmente/produção

**Obrigatório**: O pacote `cross-env` deve estar configurado no package.json ou usar sintaxe de injeção direta.

```bash
# Terminal 1 - Inicia a API REST (Síncrona)
npm run start:api

# Terminal 2 - Inicia o Worker Process (Polling MySQL)
npm run start:worker
```

*Nota:* O `start:worker` repassa a variável `RUN_WORKER=true`, instruindo o `WorkerService` a habilitar o loop contínuo sobre a tabela `report_job`.

## Inspecionando Jobs Travados

Sem ferramentas de gestão padrão (como BullMQ UI), a inspeção é feita diretamente no banco de dados.

**Para listar jobs empacados:**
```sql
SELECT id, type, created_at, started_at, TIMEDIFF(NOW(), started_at) as runtime_duration
FROM producao.report_job
WHERE status = 'running' 
AND started_at < NOW() - INTERVAL 1 HOUR;
```

**Para Inspecionar logs de Falha:**
```sql
SELECT id, error_message, finished_at FROM producao.report_job WHERE status = 'failed' ORDER BY finished_at DESC LIMIT 10;
```

## Como Reprocessar Job Falho

O Worker só pesca jobs no estado `queued`. Para enfileirar novamente um job que falhou (ou que travou como `running` por quebra do container):

```sql
UPDATE producao.report_job 
SET status = 'queued', started_at = NULL, finished_at = NULL, error_message = NULL, progress = 0
WHERE id = [JOB_ID];
```

## Política de Retenção e TTL de Resultados

Cada job que gera um *Resultado Pesado* alimenta as tabelas `report_result_header` e `report_result_rows`. Isso inchará rapidamente o banco de dados.
- O campo `ttl_expires_at` no cabeçalho será programado (geralmente `created_at + 24 horas`).
- Relatórios processados servirão como chaves de cache momentâneo, mas devem evaporar se irrelevantes.

### Como limpar resultados antigos com segurança (Cronjob ou Scheduler Interno)

Executar periodicamente (por evento diário de banco, crontab, ou rotina de higienização do NestJS node):

```sql
-- O DELETE CASCADE da foreign key vai limpar report_result_rows e atuar em ambas as tabelas
DELETE FROM producao.report_result_header 
WHERE ttl_expires_at < NOW();
```
