# Checklist de Produção: Servidores com Recursos Limitados

Diretrizes de operação do ConsultaProd (Laravel/PHP) em ambientes restritos (VPS ou servidor compartilhando RAM com MySQL/XAMPP).

## 1. Limites de PHP e memória

*   **`memory_limit`**: mínimo 256M; relatórios pesados podem precisar 512M temporariamente.
*   **`max_execution_time`**: exportações síncronas (Excel/PDF) podem exigir 120–300s — evitar timeout em produção.
*   **PHP-FPM / Apache**: limitar `pm.max_children` para não competir com o MySQL pela RAM.

## 2. Paginação e exportação

*   **DataGrid**: server-side, padrão 50 linhas por request. Rejeitar `limit > 500`.
*   **Exportação (Excel/PDF)**: não exportar milhões de linhas via HTTP síncrono. Usar filtros de competência obrigatórios e paginação nos relatórios em tela.

## 3. Política de retenção (TTL) e limpeza

Resultados em `report_result_header` / `report_result_rows` pesam no banco.

*   **Janela de retenção**: limpar resultados com mais de **48 horas**.
*   **Cron no host**: `DELETE FROM report_job WHERE created_at < NOW() - INTERVAL 2 DAY;` (CASCADE remove filhos).

## 4. Logging

*   **Não logar payloads de dados**: filtros completos, SQL com bindings ou respostas de relatório no log de produção.
*   Logar apenas status HTTP, IDs de job e mensagens de erro resumidas.
