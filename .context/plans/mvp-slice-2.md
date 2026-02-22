---
description: Pipeline Assíncrono para relatórios consolidados/pesados, usando MySQL como fila.
status: pending
---

# MVP Slice 2: Motor Assíncrono de Relatórios (SIA Consolidados)

## Contexto e Escopo
Relatórios agregados sobre milhões de linhas (ex: consolidação de produção financeira por CBO) não podem ser executados no _request loop_ da API síncrona, sob pena de *timeout*. O Slice 2 cria o motor de _Background Jobs_ usando tabelas MySQL (`report_job` e `report_result_*`) emulando o comportamento de filas sem depender de infraestruturas extras (Redis).

## Funcionalidades a Entregar

### 1. API Responsável pelo Enfileiramento e Recuperação (`v3-api`)
*   `POST /reports/jobs`: Aceitar parâmetros de filtros, tipo do relatório (ex: `sia-aggregated`) e persistir um `PENDING` na tabela `report_job`. Retorna o UUID/ID do job com *HTTP 202 Accepted*. A query pesada de agregação NÃO RODA AQUI.
*   `GET /reports/jobs/:id`: Um endpoint leve para a interface fazer _polling_. Retorna `PENDING`, `PROCESSING`, `COMPLETED` ou `FAILED`, além do _error_message_ se houver.
*   `GET /reports/results/:resultId`: A interface chamará esta rota quando o Job estiver `COMPLETED`. Ela recupera as linhas prontas e fatiadas da tabela auxiliar `report_result_rows` via paginação.

### 2. Worker Assíncrono (`v3-worker`)
*   Uma rotina em _loop_ que acorda apenas se a variável de ambiente `RUN_WORKER=true`.
*   O _Concurrency Limit_ deve ser `1` estrito (pega 1 Job `PENDING`, muda pra `PROCESSING` e trabalha seqüencialmente).
*   **A Execução**:
    1. Executa um _QueryBuilder_ de GROUP BY pesado na origem (`s_pap`). Ex: "Soma de `PAP_VL_FED` agrupada por `PAP_CBO`".
    2. Cria uma entrada principal no `report_result_header` e insere os resultados no `report_result_rows` em formato *stringified* JSON limpo ou _flat_.
    3. Marca o Job Original como `COMPLETED` (anexando o tempo gasto e `finished_at`).

## Critérios de Aceite (Gates/Validações)
1. Concorrência Unitária confirmada (sem atropelos no XAMPP).
2. Tabela de filas funcional: _Job Payload_ deve conter rastros lógicos (`{ competence: "202607", reportType: "sia_cbo_aggregation" }`).
3. Auditoria de execução registrada como metadados do Job.
4. Fluxos de teste *End-to-End* via postman ou front-end comprovando que o endpoint de disparo retorna em milisegundos enquanto a DB trabalha nos bastidores.
