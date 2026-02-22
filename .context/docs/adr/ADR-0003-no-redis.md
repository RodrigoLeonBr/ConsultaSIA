# ADR-0003: Arquitetura sem Redis (MySQL-Backed Jobs)

**Data**: 2026-02-21  
**Status**: Consolidado  

## Contexto
O MVP exige controle de tarefas assíncronas (relatórios demorados, processamento de +1M linhas) para evitar timeouts em conexões HTTP no XAMPP ou Node.js. 
Historicamente, o padrão ouro do ecossistema é o uso de Redis/Memcached atrelado ao BullMQ (Nest). No entanto, a infraestrutura legada atual não possui Redis configurado e sua introdução gera custos de setup agressivos (Deploy isolado, RAM dedicada).

## Decisão
**Reafirmar a remoção sumária do componente Redis da Stack V3**.
Em seu lugar, a comunicação entre o `v3-api` (Front-facing) e o `v3-worker` (Background jobs) acontecerá estritamente através do **Banco de Dados Relacional MySQL legada (`producao`) via tabelas auxiliares de fila/estado** (schema `report_job`). 

## Consequências
- **Positivos**: Não necessita manutenção ou RAM para containers extras de cache, unifica o backup dos estados de processamento junto com o DUMP de dados XAMPP original, arquitetura transparente e testável via queries SQL puras.
- **Negativos**: Taxa de polling constante do `v3-worker` pode injetar carga transacional desnecessária se não for adequadamente controlada (Delay + Wait); performance estritamente dependente de índices O(1) de bancos RDBMS (ao invés de In-Memory RTT do Redis).
