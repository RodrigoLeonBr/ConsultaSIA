# Documentação do Schema de Jobs e Resultados (Modelagem Auxiliar MySQL/XAMPP)

## Objetivo
O V3 implementa o processamento assíncrono (Job Worker) via tabelas no próprio banco (`producao`) para suplantar a ausência de um Redis. O schema permite isolar a UI principal e exibir DataGrids paginadas diretamente do banco, após o término do Job.

## Estrutura Criada (`v3-backend/sql/aux_jobs.sql`)
1. **`report_job`**: Tabela central para empilhamento das tarefas. Controla o estado, o requisitante e o percentual de finalização.
2. **`report_result_header`**: Cabeçalho de metadados do resultado do Job. É pai da linha de relatórios processados, indicando `report_type` e informações sobre quantidade de registros ou hashes de cache (`filters_hash`).
3. **`report_result_rows`**: Desdobramento final onde as linhas prontas (JSON) ficam alojadas contendo índice ordenante original.

## Índices e Justificativas de Paginação Server-side

### `report_job (status, created_at)`
- **Por que**: O Worker fará polling contínuo (ex.: `SELECT * FROM report_job WHERE status='queued' ORDER BY created_at LIMIT 1`).
- **Benefício**: Indexar de forma combinada reduz substancialmente o escrutínio sobre milhares de registros concluídos. A junção em "B-Tree" resolve o predicado "status" e a ordenação em um único passo, muito rápido em InnoDB.

### `report_result_rows (result_id, row_index)`
- **Por que**: Essa tabela é declarada com uma Chave Primária Composta via `PRIMARY KEY (result_id, row_index)`.
- **Benefício**: Ao buscar os dados na API para a grid, a constraint já estabelece a junção física no arquivo de índice (Clustered Index do InnoDB). Consultas como `WHERE result_id=X ORDER BY row_index LIMIT Y OFFSET Z` leem apenas do leaf node correto, gerando latência quase zero durante "paginação" em DataGrids, dispensando filesorts em disco, mesmo para relatórios de 100k+ linhas.

### Suporte Nativo a JSON
- Todas as três tabelas exploram uso do campo JSON. Isto é suportado pelo MariaDB/MySQL moderno empacotado no XAMPP, dispensando DDLs intermináveis para metadados variados ou dados finais de conciliação do V3.
