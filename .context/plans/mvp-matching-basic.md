---
title: "MVP Matching Basic (Matching 1:1 and Divergencies)"
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

# MVP Matching Basic

## Objetivos
- Implementar o algoritmo básico de conciliação (1:1) de dados seguindo o PRD.
- Identificar e pontuar divergências de maneira controlada com base na estrutura de dados de `producao.sql`.

## Tarefas
- [ ] Obter a lista de entidades primárias e secundárias do `producao.sql` a serem cruzadas.
- [ ] Escrever lógica de negócio pura (Service Layer) que compara conjuntos de linhas seguindo chaves estritas.
- [ ] Persistir "matches" ou pendências em tabelas à parte, sem alterar tabelas originais.

## Dependências
- `mvp-foundation.md`
- `mvp-db-contract.md`

## Critérios de Aceite
- Motor indica sucesso para combinações 100% literais.
- Motor levanta a tag de "Divergência" quando as comparações batem apenas parcialmente ou têm erros esperados.

## Riscos e Mitigação
- **Risco**: Lentidão severa por quantidade excessiva de linhas (Full Table Scan).
  **Mitigação**: Uso de processamento de batelada, cursores paginados durante os hooks, e garantias de que haverão índices nas chaves pesquisadas.
