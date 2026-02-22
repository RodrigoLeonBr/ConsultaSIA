---
title: "MVP UI React DataGrid (React SPA + Grid Server-side + Filters)"
status: pending
phases:
  - P
  - R
  - E
  - V
  - C
agents:
  - frontend-specialist
---

# MVP UI React DataGrid

## Objetivos
- Estabelecer a aplicação Frontend Desktop-first em React SPA.
- Criar a base visual e de componentes (DataGrid com paginação server-side obrigatória e filtros persistentes).

## Tarefas
- [ ] Inicializar o projeto frontend React.
- [ ] Implementar componente de DataGrid.
- [ ] Adicionar suporte à paginação remota.
- [ ] Criar barra de filtros que persista estado, enviando restrições formatadas ao backend Node.

## Dependências
- Existência da estrutura frontend (ou sua criação simultânea).
- Endpoints páginados do Node.js.

## Critérios de Aceite
- UI carrega sem delays maiores, pois busca somente as linhas referentes à página atual.
- Filtros preservam a coerência e resetam corretamente.

## Riscos e Mitigação
- **Risco**: Alta complexidade de filtros.
  **Mitigação**: Padronizar as chaves dos filtros RESTful e traduzir adequadamente no Prisma/ORM e tabelas base.
