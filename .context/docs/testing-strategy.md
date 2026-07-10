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

## Estado atual

Suite **PHPUnit** em `tests/`. Validação manual de totais SQL contra competência de referência após mudanças em relatórios.

### Critério de aprovação

Para alterações em queries de relatório, **delta = 0** nos totais:

- `COUNT(*)` de registros
- `SUM(QT_A)`, `SUM(VL_A)` — quantidade e valor aprovados
- `SUM(QT_P)`, `SUM(VL_P)` — quantidade e valor apresentados

### Query de validação de referência

```sql
SELECT
  COUNT(*)                                        AS cnt,
  SUM(CAST(PRD_QT_A AS UNSIGNED))                 AS qt_a,
  SUM(CAST(PRD_VL_A AS DECIMAL(15,2)))            AS vl_a,
  SUM(CAST(PRD_QT_P AS UNSIGNED))                 AS qt_p,
  SUM(CAST(PRD_VL_P AS DECIMAL(15,2)))            AS vl_p
FROM s_prd
WHERE prd_cmp = '202301';

-- Esperado: cnt=31765, qt_a=214675, vl_a=1606620.01, qt_p=214866, vl_p=1673942.13
```

## Regra de ouro

Delta ≠ 0 é **bloqueante** para mudanças em agregações de relatório.

## Executar testes

```bash
php artisan test                                    # suite completa (MariaDB: producao_test)
php artisan test tests/Feature/RelatorioTest.php   # arquivo específico
php artisan test --filter=testNome                 # filtro por método
```

### Banco de testes

PHPUnit usa **MariaDB/MySQL** (mesmo driver de produção), nunca SQLite. O banco padrão é `producao_test`, definido em `phpunit.xml` e opcionalmente em `.env.testing` (copie de `.env.testing.example`).

`RefreshDatabase` roda migrations nesse banco isolado. Testes **falham** se `DB_DATABASE=producao` (guarda em `tests/TestCase.php`).

Criar o banco uma vez:

```sql
CREATE DATABASE IF NOT EXISTS producao_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL ON producao_test.* TO 'hospital'@'localhost';
```

## Prioridade de cobertura

| Área | O que testar |
|------|--------------|
| `RelatorioController` | GROUP BY, CAST, JOINs dinâmicos, operadores `between`/`in` |
| `RelatorioApacController` / `RelatorioBpiController` | Totais e filtros de competência |
| `FaturamentoPrestadorController` | Hierarquia e agregações |
| Exports (`app/Exports/`) | Formatação BR e colunas esperadas |
| Auth / roles | Middleware `CheckRole`, rotas `/admin` |
