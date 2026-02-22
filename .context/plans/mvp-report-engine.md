---
title: "MVP Report Engine (Sync vs Job Model + Auxiliary Tables)"
status: pending
phases:
  - P
  - R
  - E
  - V
  - C
agents:
  - backend-specialist
  - database-specialist
---

# MVP Report Engine

## Objetivos
- Construir a infraestrutura para processamento de relatórios pesados sem Redis, persistindo o estado de jobs (scheduling/running/done/failed) no MySQL de forma paralela ao banco legado.
- Implementar o disparo de jobs background.

## Tarefas
- [ ] Criar tabelas auxiliares (fora do core) para sistema de filas (ex: `jobs`, `failed_jobs`, `report_results`). Essas não ferem as tabelas core do `producao.sql`.
- [ ] Desenvolver serviço em Node.js (worker) responsável por pescar e processar tarefas da tabela `jobs`.
- [ ] Disponibilizar endpoints para "enfileirar solicitação de relatório" (assíncrono) e "checar status/obter resultado" (polling).

## Dependências
- `mvp-db-contract.md` (Conexão e abstração do DB resolvidos).

## Critérios de Aceite
- Requisições pesadas retornam `202 Accepted` de status e um jobId.
- O resultado é salvo numa tabela auxiliar após o término.
- Interface consegue obter o resultado do processamento posteriomente consultando a tabela auxiliar.

## Riscos e Mitigação
- **Risco**: Concorrência ao pegar trabalhos no DB de filas (`select for update` locks, transações).
  **Mitigação**: Uso de isolamentos de transação corretos ou travas na aplicação durante o worker pool.
