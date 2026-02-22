# ADR 0002: Modelo de Execução de Relatórios

## Status
Aceito

## Contexto
A interface (SPA) precisará carregar listagens de dados volumosas, mas também emitir relatórios demorados. Consultas síncronas para dados pesados bloqueiam a UI, enquanto a listagem rotineira precisa ser rápida.

## Decisão
Consultas em tela que alimentam tabelas e grids deverão ser obrigatoriamente paginadas (server-side). Requisições de geração de relatórios pesados ou processamento complexo seguirão um modelo assíncrono (Job). O cliente emite o pedido, o servidor devolve um Job ID, executa a requisição em background e persiste o resultado final numa tabela auxiliar do MySQL, que o cliente consultará posteriormente (polling).

## Consequências
- Mantém a interface responsiva (SPA rápida).
- Permite o cancelamento e gerenciamento das filas de trabalho pesado.
- Exige criação de tabelas de filas e workers no servidor.
