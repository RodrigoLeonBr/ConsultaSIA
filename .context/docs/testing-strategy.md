---
type: doc
name: testing-strategy
description: Test frameworks, validation approach, coverage requirements, and quality gates
category: testing
generated: 2026-02-22
status: filled
scaffoldVersion: "2.0.0"
---

# Testing Strategy

## Estado Atual (MVP)

Sem suite automatizada. Validação feita manualmente comparando totais entre Node.js e o sistema Laravel legado.

### Critério de Aprovação MVP

**Delta = 0** entre Node.js e Laravel para:

- `COUNT(*)` de registros
- `SUM(QT_A)`, `SUM(VL_A)` — quantidade e valor aprovados
- `SUM(QT_P)`, `SUM(VL_P)` — quantidade e valor apresentados

### Casos Validados

| Caso | Competência | Resultado | Status |
|------|-------------|-----------|--------|
| SIA Production sync (`/reports/sia`) | 202301 | delta=0 em todos os totais | ✅ |
| Faturamento por Prestador (job) | 202301 | delta=0 em todos os totais | ✅ |
| p95 HTTP response | — | ~46ms < 800ms | ✅ |
| Job execution time | — | ~1s < 2min | ✅ |
| Query EXPLAIN (`s_prd`) | — | `type=ref` (usa índice) | ✅ |

Resultados completos: `.context/docs/validation-report.md`.

### Query de Validação de Referência

```sql
-- Rodar no banco producao após cada mudança relevante
SELECT
  COUNT(*)                                          AS cnt,
  SUM(CAST(PRD_QT_A AS UNSIGNED))                   AS qt_a,
  SUM(CAST(PRD_VL_A AS DECIMAL(15,2)))              AS vl_a,
  SUM(CAST(PRD_QT_P AS UNSIGNED))                   AS qt_p,
  SUM(CAST(PRD_VL_P AS DECIMAL(15,2)))              AS vl_p
FROM s_prd
WHERE prd_cmp = '202301';

-- Esperado: cnt=31765, qt_a=214675, vl_a=1606620.01, qt_p=214866, vl_p=1673942.13
```

## Regra de Ouro

Delta ≠ 0 é **bloqueante**. Nada vai para produção com divergência vs Laravel.

---

## Próximos Passos (Fase 2)

### Testes Unitários (Backend)

Framework: **Jest** (já em `devDependencies` do NestJS, apenas criar os arquivos `.spec.ts`).

Prioridade:

| Módulo | O que testar |
|--------|-------------|
| `SiaService.getDynamicProduction()` | GROUP BY logic, CAST, JOINs dinâmicos, operadores `between`/`in` |
| `WorkerService.executeJob()` | Cada job type isoladamente com banco de teste |
| DTOs class-validator | Constraints: competência 6 chars, pageSize ≤ 500, select whitelist |
| Field catalog | Operadores permitidos por tipo de campo |

### Testes de Integração

- Banco de teste separado (`producao_test`) com fixture de `s_prd`
- Fixture mínima: 100 linhas, competência `202301`, totais conhecidos
- Validar delta=0 contra os totais do `validation-report.md`
- Rodar com `DB_NAME=producao_test` via `.env.test`

### Testes E2E

Framework candidato: **Playwright**.

Cenários prioritários:

1. **Golden path SIA**: inserir competência → Aplicar → ver resultados na grid → segunda página
2. **Golden path Job**: criar job → ver "rodando" → ver resultados
3. **Erro competência inválida**: `12345` → mensagem de erro visível
4. **Cancelamento de request**: clicar Aplicar → cancelar → não travar UI

---

## Cobertura Mínima Alvo (Fase 2)

| Camada | Meta |
|--------|------|
| Service layer (backend) | 80% branches |
| DTOs/validators | 100% constraints |
| Worker job types | 1 teste por type |
| Frontend hooks | 70% |
