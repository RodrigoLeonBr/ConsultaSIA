status: completed
---

# Documentation Index

Welcome to the repository knowledge base. Start with the project overview, then dive into specific guides as needed.

## Core Guides
- [Project Overview](./project-overview.md)
- [Architecture Notes](./architecture.md)
- [Development Workflow](./development-workflow.md)
- [Testing Strategy](./testing-strategy.md)
- [Glossary & Domain Concepts](./glossary.md)
- [Data Flow & Integrations](./data-flow.md)
- [Security & Compliance Notes](./security.md)
- [Tooling & Productivity Guide](./tooling.md)

## Repository Snapshot
- `_ide_helper_models.php/`
- `_ide_helper.php/`
- `AGENTS.md/`
- `app/`
- `artisan/`
- `bootstrap/`
- `CHANGELOG.md/`
- `composer.json/`
- `composer.lock/`
- `config/`
- `create_missing_tables.php/`
- `create_temp_tables.php/`
- `CRUD-CISMETRO-IMPLEMENTADO.md/`
- `database/`
- `discard_tablespaces.php/`
- `ERRO-SINTAXE-JAVASCRIPT-CORRIGIDO.md/`
- `fix_database.php/`
- `fix_tablespace.php/`
- `index.php/`
- `LARAPEX-CHARTS-GUIDE.md/`
- `MODERN-INTERFACE-README.md/`
- `package-lock.json/`
- `package.json/`
- `PARSE-ERROR-CORRIGIDO.md/`
- `phpunit.xml/`
- `postcss.config.js/`
- `PROBLEMA-RESOLVIDO-STANDALONE.md/`
- `public/`
- `README-CONSULTAPROD.md/`
- `README.md/`
- `recreate_tables.php/`
- `RELACAO-PROCEDIMENTO-CISMETRO.md/`
- `RELATORIOS-STANDALONE-SOLUTION.md/`
- `resources/`
- `routes/`
- `SIDEBAR-PROBLEMAS-CORRIGIDOS.md/`
- `SOLUCAO-CSS-INLINE-FINAL.md/`
- `storage/`
- `tailwind.config.js/`
- `TECHNICAL-DOCS.md/`
- `tests/` — Automated tests and fixtures.
- `vendor/`
- `vite.config.js/`

## Document Map
| Guide | File | Primary Inputs |
| -<!-- documentation-index -->
- **[Project Overview](./project-overview.md)**: High-level overview, goals, and business context.
- **[PRD](./prd.md)**: Product Requirements Document for V3.
- **[Architecture](./architecture.md)**: System design, layers, and patterns for the hybrid Laravel + Node system.
- **ADRs (Architectural Decision Records)**:
  - [ADR-0001: DB Imutável](./adr/ADR-0001-db-immutable.md)
  - [ADR-0002: Report Execution Model](./adr/ADR-0002-report-execution-model.md)
  - [ADR-0003: No Redis](./adr/ADR-0003-no-redis.md)
  - [ADR-0004: Frontend SPA React](./adr/ADR-0004-frontend-spa-react.md)
- **[Data Contract](./data-contract.md)**: Mapeamento de anomalias e schemas das tabelas core.
- **[Job Engine Model](./job-model.md)**: Descrição das tabelas auxiliares para fila assíncrona.
- **[Data Flow](./data-flow.md)**: How data moves through the system.
- **[Development Workflow](./development-workflow.md)**: Setup, standards, and git branching.
- **[Validação e Testes](./validation-plan.md)**: Estratégias e playbooks para validação V3 vs Legado ([Template](./validation-report-template.md)).
- **[Ops Runbook](./ops-runbook.md)**: Operação do motor assíncrono e do ambiente híbrido.
- **[Performance Playbook](./performance-playbook.md)**: Checklist de mitigação de full scans e anomalias de tipo.
<!-- /documentation-index -->guide |
| Testing Strategy | `testing-strategy.md` | Test configs, CI gates, known flaky suites |
| Glossary & Domain Concepts | `glossary.md` | Business terminology, user personas, domain rules |
| Data Flow & Integrations | `data-flow.md` | System diagrams, integration specs, queue topics |
| Security & Compliance Notes | `security.md` | Auth model, secrets management, compliance requirements |
| Tooling & Productivity Guide | `tooling.md` | CLI scripts, IDE configs, automation workflows |
