---
description: Escopo mínimo executável (MVP Slice 1) focado na consulta síncrona de SIA, com UI React paginada e proteção de performance.
status: pending
---

# MVP Slice 1: Consulta Síncrona SIA paginada (Server-Side)

## Contexto e Escopo
Como primeira fase funcional do MVP (estimativa: 1 a 2 dias de engenharia), vamos consolidar o encanamento "Legado -> Node -> React" implementando a consulta e listagem dos Autorizações de Procedimentos Ambulatoriais (SIA - tabela `s_apa`). Não haverá rotinas de background (Workers) neste momento, apenas endpoints síncronos com paginadores agressivos.

## Funcionalidades a Entregar

### 1. Backend (NestJS API)
- Criar a Entidade `SiaApa` contendo um subconjunto seguro e útil de colunas mapeadas da tabela `s_apa` de `producao.sql` (Ex: `cmp` (competência), `cgc` (CNPJ/prestador), `ide`, `vlt` (valor total)).
- Construir o Controller `GET /reports/sia` responsável estritamente por queries paginadas (aceitando params: `page`, `limit`, `competence` e `provider_id`).
- Embutir "Observabilidade Leve":
  - Logging no interceptor marcando o tempo de duração da requisição (Logging interceptor: `Execution time: {ms}ms`).
  - Sem espelhar o payload de resposta no log de terminal (conforme `production-checklist.md`).

### 2. Frontend (React SPA)
- Uma Rota/Página `<SiaReportsPage>` acoplada a um Componente `<DataGrid>`.
- O `<DataGrid>` precisa ser *Server-Side* estrito, consumindo diretamente o endpoint `GET /reports/sia`.
- O Header da página conterá dois filtros básicos: Competência e Código do Prestador, com um botão explícito de "Aplicar Filtros".

## Critérios de Aceite (Gates/Validações)
1. **Paginação blindada**: Backend rejeita requests sem paginação ou com `limit > 500`.
2. **Filtros Funcionais**: Interface de usuário consegue filtrar por competência (Mês/Ano) e prestador com trigger apenas via botão manual (Sem Debounce abusivo em keypress).
3. **Database Health**: Queries geradas pelo TypeORM não devem causar Table Scans integrais travando operações de INSERT do legado (Utilizar índices adequados ou impor o param de filtragem mandatório de competência).
4. **Alvo de SLA**: Resposta *p95* do endpoint de consulta paginada ≤ 800ms em um dataset realista de desenvolvimento (medido via log do Nest).

## Arquivos Relacionados (A serem Tocados/Criados)

**Backend:**
- `v3-backend/src/sia/sia.module.ts`
- `v3-backend/src/sia/sia.controller.ts`
- `v3-backend/src/sia/sia.service.ts`
- `v3-backend/src/sia/entities/s-apa.entity.ts`
- `v3-backend/src/common/interceptors/logging.interceptor.ts` (Para p95)

**Frontend:**
- `v3-frontend/src/routes.tsx`
- `v3-frontend/src/pages/SiaReportsPage.tsx`
- `v3-frontend/src/components/DataGrid.tsx`
- `v3-frontend/src/services/api.ts`
