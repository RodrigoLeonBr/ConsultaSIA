# ConsultaProd v3 — Regras do Projeto (CLAUDE.md)

## Objetivo
Novo projeto Node.js + React SPA em paralelo ao Laravel legado, mantendo banco MySQL/MariaDB existente (XAMPP) como contrato.

## Restrições obrigatórias
- Laravel existente permanece em produção durante a migração (Strangler Fig).
- Banco `producao` e arquivo `producao.sql` são contrato imutável:
  - PROIBIDO alterar DDL das tabelas core.
  - PROIBIDO criar migrations automáticas que toquem tabelas core.
  - Em produção, migrations automáticas desabilitadas.
- Sem Redis.
- Jobs e resultados persistidos em tabelas auxiliares MySQL (fora do core).
- Frontend: React SPA desktop-first (sem SSR no MVP).
- UI: DataGrid sempre server-side (paginação/ordenação/filtros no backend).
- Filtros com botão “Aplicar” (evitar requests em onChange).
- Não logar payload grande (proteger RAM/overlay do Docker).

## Onde está a verdade do projeto
- PRD: `.context/docs/prd.md`
- Contrato de dados: `.context/docs/data-contract.md`
- ADRs: `.context/docs/adr/`
- Planos: `.context/plans/` (especialmente `mvp-slice-1.md`)

## Slice atual (MVP Slice 1)
- Backend: endpoint `GET /reports/sia` em cima da tabela `s_apa` com paginação obrigatória e filtro de competência obrigatório.
- Frontend: página `SiaReportsPage` consumindo endpoint via `DataGrid` (server-side).
- Observabilidade: interceptor global medindo tempo; sem logs gigantes.

## Orientação de trabalho
- Sempre confirmar colunas/índices no `producao.sql` e/ou `data-contract.md` antes de escrever query.
- Implementar em passos pequenos (1-2 arquivos por vez).
- Sempre explicar o que vai mudar antes de mudar.
