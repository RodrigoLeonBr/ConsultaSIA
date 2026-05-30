---
type: doc
name: development-workflow
description: Day-to-day engineering processes, environment setup, scripts, and contribution guidelines
category: workflow
generated: 2026-02-22
status: filled
scaffoldVersion: "2.0.0"
---

# Development Workflow

## Pré-requisitos

- Node.js 18+ (LTS)
- npm 9+
- XAMPP rodando com MySQL na porta 3306
- Banco `producao` importado via `producao.sql`

## Setup Inicial

```bash
# Backend
cd v3-backend
npm install
cp .env.example .env   # editar com credenciais do banco local

# Frontend
cd v3-frontend
npm install
cp .env.example .env   # editar VITE_API_URL
```

## Variáveis de Ambiente

### Backend (`v3-backend/.env`)

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=producao
PORT=3000
CORS_ORIGIN=http://localhost:5173
RUN_WORKER=false      # true = modo worker (sem servidor HTTP)
```

### Frontend (`v3-frontend/.env`)

```env
VITE_API_URL=http://localhost:3000
```

## Rodando em Desenvolvimento

Três terminais simultâneos:

```bash
# Terminal 1 — API
cd v3-backend && npm run start:dev

# Terminal 2 — Worker (job processor)
cd v3-backend && cross-env RUN_WORKER=true npm run start:dev

# Terminal 3 — Frontend
cd v3-frontend && npm run dev
# Acesse: http://localhost:5173
```

O Worker e a API compartilham o mesmo código NestJS. A variável `RUN_WORKER=true` muda o bootstrap: sem servidor HTTP, só o polling loop.

## Scripts Backend (`v3-backend/package.json`)

| Comando | Ação |
|---------|------|
| `npm run start:dev` | Dev com hot-reload (ts-node) |
| `npm run build` | Compila TypeScript → `dist/` |
| `npm run start:prod` | Roda `dist/main.js` |
| `npm run lint` | ESLint (TypeScript) |
| `npm run format` | Prettier |

## Scripts Frontend (`v3-frontend/package.json`)

| Comando | Ação |
|---------|------|
| `npm run dev` | Vite dev server (porta 5173, HMR) |
| `npm run build` | Build produção → `dist/` |
| `npm run preview` | Preview do build de produção |

## Regras de Desenvolvimento

- **NUNCA** alterar DDL das tabelas core (`s_prd`, `s_pap`, `s_apa`, `prestador`, etc.)
- Verificar colunas em `producao.sql` ou `.context/docs/data-contract.md` antes de escrever qualquer query
- Novos campos de query dinâmica: adicionar a `v3-backend/src/sia/field-catalog.ts` com `id`, `label`, `type`, `allowedOperators`, `sortable`, `groupable` completos
- Mudanças em 1–2 arquivos por vez; commits granulares
- Filtros com botão "Aplicar" — sem requests em `onChange`
- UI: DataGrid sempre server-side (paginação/ordenação/filtros no backend)

## Estrutura de Commits

```
feat(slice-N): descrição curta
fix(worker): descrição curta
docs: atualizar data-contract
refactor(sia): extrair helper de CAST
```

## Branch Principal

`main`. Nenhuma branch strategy adicional definida para MVP. PRs para `main` com revisão manual.

## Verificação Antes de Subir

1. `npm run build` no backend sem erros TypeScript
2. `npm run build` no frontend sem erros
3. Rodar validação manual: ver `.context/docs/validation-plan.md`
4. Delta = 0 vs Laravel para competência de referência (202301)
