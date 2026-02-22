# Checklist de Produção: Servidores com Recursos Limitados

Este documento define as diretrizes de operação da stack técnica V3 (Node.js/React/Docker) em ambientes restritos (como VPS pequenas ou servidores que já dividem agressivamente RAM com o MySQL e XAMPP legado).

## 1. Limites de Recursos Docker (CPU/RAM)
O Docker compose deve impor *hard limits* para evitar que um pico de consumo do Node.js afete a disponibilidade do banco de dados legado hospedado na mesma máquina.

*   **v3-api**: Alocar memória moderada. (Recomendado: `mem_limit: 512m`, `cpus: 0.5`)
*   **v3-worker**: Pode exigir mais picos de RAM durante consolidação de arrays gigantes, mas deve ser contido. (Recomendado: `mem_limit: 1024m` (1GB), `cpus: 1.0`)
*   **v3-web**: O Nginx servindo estáticos é extremamente leve. (Recomendado: `mem_limit: 128m`, `cpus: 0.2`)

## 2. Concorrência do Worker
*   **Default = 1**: O `v3-worker` deve processar estritamente **um Job por vez**. 
*   **Justificativa**: Executar agregações SQL complexas em paralelo no banco de dados "producao" (que tem tabelas sem índices O(1) perfeitos) pode causar lentidão severa para os usuários do sistema Legado atual em horários de pico. 

## 3. Limites de Paginação e Exportação
*   **Paginação UI (DataGrid)**: Padrão de 50 linhas por request (`?limit=50`). O Motor do Backend deve invalidar com HTTP 400 requests que peçam `?limit>500`.
*   **Exportação (Excel/PDF)**: Exportações completas de milhões de linhas não devem ser realizadas via HTTP síncrono da API. Devem obrigatoriamente cair na fila do Worker (`report_job`) para geração assíncrona do arquivo (futuro), ou respeitar limites de paginação estritos no MVP.

## 4. Política de Retenção (Data TTL) e Limpeza
Resultados consolidados pesam muito no DB (coluna `payload_json` e `report_result_rows`).
*   **Janela de Retenção (TTL)**: Um resultado de relatório assíncrono espelhado no MySQL ganha obsolescência e deve ser limpo pelo menos a cada **48 horas**.
*   **Rotina Cron**: Configurar um CRON Job no Linux Host ou no próprio NestJS (`@nestjs/schedule`) que faça algo parecido com: `DELETE FROM report_job WHERE created_at < NOW() - INTERVAL 2 DAY;` (Lembrando que o `CASCADE` deleta os resultados filhos).

## 5. Cuidados com o Logging
*   **Jamais Logue Payloads de Dados**: O `Logger` do Nest não deve imprimir o conteúdo das propriedades `parameters`, `payload_json`, ou respostas do Axios/DataGrids completos no `stdout`.
*   **Alocação**: Logs excessivos entopem o OverlayFS do Docker rapidamente consumindo disco da raiz (`/var/lib/docker/containers/*`). Logue métricas de erro, status HTTP e UUID do job, **nunca seu volume**.
