# exporta.md — Guia de Importação de Produção SIA para o Sistema de Custos

> **Para LLMs (Claude Code et al.):** Leia este arquivo antes de implementar qualquer
> funcionalidade de importação de produção. Ele descreve o contrato real do banco,
> as queries corretas e como mapear para as entidades do módulo de custos.

---

## 1. Contexto e Objetivo

O **Sistema de Gestão de Custos em Saúde** (pasta raiz do projeto) precisa importar
dados de produção assistencial a partir do banco `producao` (MySQL/MariaDB local XAMPP).
A fonte primária é a tabela `s_prd` — tabela bruta do DATASUS com registros mensais
de produção SIA (Sistema de Informações Ambulatoriais).

O objetivo é popular os conceitos do módulo de custos:
- `ProductionType` → cada procedimento distinto (`prd_pa` + nome da tabela `procedimento`)
- `MonthlyProduction` → produção mensal por unidade (prestador) e tipo de procedimento

**Banco:** `producao` | **Host:** `127.0.0.1` | **Engine:** MySQL/MariaDB via XAMPP

---

## 2. Tabelas Envolvidas

### 2.1 `s_prd` — Produção Mensal (tabela principal)

Contém um registro por linha de produção: combinação prestador + competência + procedimento.

| Coluna física   | Tipo MySQL              | Significado                          | Observações críticas |
|-----------------|-------------------------|--------------------------------------|----------------------|
| `prd_uid`       | `varchar(7)`            | CNES do prestador                    | FK lógica → `prestador.re_cunid` |
| `prd_cmp`       | `varchar(6)`            | Competência `YYYYMM`                 | Ex: `202501` = jan/2025 |
| `prd_pa`        | `varchar(10)`           | Código do procedimento               | FK lógica → `procedimento.codigo` |
| `prd_cbo`       | `varchar(8)`, nullable  | Código CBO (ocupação)                | — |
| `PRD_QT_P`      | `int`, nullable         | Quantidade apresentada               | — |
| `PRD_QT_A`      | `int`, nullable         | Quantidade aprovada                  | Usar para produção efetiva |
| `PRD_VL_P`      | `decimal(15,2)`         | Valor apresentado                    | TypeORM retorna como `string` |
| `PRD_VL_A`      | `decimal(15,2)`         | Valor aprovado                       | TypeORM retorna como `string` |
| `prd_rub`       | `varchar(6)`, nullable  | Rubrica / tipo de financiamento      | FK lógica → `s_rub.RUB_ID` |
| `grupo`         | `varchar(2)`            | **STORED GENERATED** — 2 primeiros chars de `prd_pa` | Somente leitura |
| `subgrupo`      | `varchar(4)`            | **STORED GENERATED** — 4 primeiros chars de `prd_pa` | Somente leitura |
| `forma`         | `varchar(6)`            | **STORED GENERATED** — 6 primeiros chars de `prd_pa` | Somente leitura |
| `PRD_CNPJ`      | `varchar(14)`, nullable | CNPJ do prestador                    | — |

**Atenção:** `s_prd` não tem Primary Key formal. Não usar ORM para INSERT/UPDATE nessa tabela.
Engine: InnoDB.

---

### 2.2 `prestador` — Unidades Prestadoras

Representa as unidades organizacionais no contexto SIA. Equivale ao conceito de
`OrganizationalUnit` no módulo de custos.

| Coluna física | Tipo MySQL      | Significado               |
|---------------|-----------------|---------------------------|
| `re_cunid`    | `varchar(7)`    | CNES — PK lógica          |
| `re_cnome`    | `varchar(...)`  | Nome do prestador/unidade |
| `ativo`       | `tinyint`/`int` | 1 = ativo, 0 = inativo    |

**Atenção:** Engine **MyISAM** — sem transações. Não usar para writes do sistema de custos.
Apenas leitura para lookup de nomes.

---

### 2.3 `procedimento` — Tabela de Procedimentos

Nomenclatura dos códigos de procedimento (equivalente a `ProductionType` do módulo custos).

| Coluna física | Tipo MySQL      | Significado                  | Observações críticas          |
|---------------|-----------------|------------------------------|-------------------------------|
| `codigo`      | `varchar(...)`  | Código do procedimento       | FK alvo de `s_prd.prd_pa`     |
| `procedimento`| `varchar(...)`  | Nome/descrição do procedimento | —                            |
| `PA_TOTAL`    | `varchar(...)`  | Valor unitário               | **VARCHAR — CAST obrigatório** antes de operações matemáticas |

---

### 2.4 `cismetro` — Procedimentos com Valor Local (alternativa)

Tabela local de procedimentos com valores atualizados. Alternativa ao `procedimento`
quando se quer valor unitário mais recente.

| Coluna    | Significado           |
|-----------|-----------------------|
| `codigo`  | Código do procedimento (mesmo domínio de `prd_pa`) |
| `descricao` | Descrição             |
| `valor`   | Valor unitário (numérico) |

---

## 3. Relacionamentos (sem FK formal)

```
s_prd.prd_uid  ──── prestador.re_cunid    (LEFT JOIN: nem todo prd_uid tem match)
s_prd.prd_pa   ──── procedimento.codigo   (LEFT JOIN: nem todo prd_pa tem match)
s_prd.prd_pa   ──── cismetro.codigo       (LEFT JOIN alternativo para valor/descrição)
s_prd.prd_rub  ──── s_rub.RUB_ID         (LEFT JOIN para descrição da rubrica)
```

Usar sempre **LEFT JOIN**, nunca INNER JOIN — dados DATASUS têm registros órfãos.

---

## 4. Queries SQL de Extração

### 4.1 Listar competências disponíveis

```sql
SELECT DISTINCT
    prd_cmp                                       AS competencia,
    CONCAT(SUBSTRING(prd_cmp,1,4), '-', SUBSTRING(prd_cmp,5,2)) AS competencia_display,
    COUNT(*)                                       AS total_registros
FROM producao.s_prd
GROUP BY prd_cmp
ORDER BY prd_cmp DESC;
```

### 4.2 Listar prestadores ativos com produção (unidades disponíveis)

```sql
SELECT DISTINCT
    p.re_cunid   AS cnes,
    p.re_cnome   AS nome,
    p.ativo
FROM producao.s_prd sp
LEFT JOIN producao.prestador p ON p.re_cunid = sp.prd_uid
WHERE p.ativo = 1
ORDER BY p.re_cnome;
```

### 4.3 Extrair tipos de produção distintos (→ `ProductionType`)

```sql
SELECT DISTINCT
    sp.prd_pa                        AS codigo,
    COALESCE(proc.procedimento, cs.descricao, sp.prd_pa) AS nome,
    CAST(proc.PA_TOTAL AS DECIMAL(15,2))                 AS valor_unitario,
    sp.grupo,
    sp.subgrupo,
    sp.forma
FROM producao.s_prd sp
LEFT JOIN producao.procedimento proc ON proc.codigo = sp.prd_pa
LEFT JOIN producao.cismetro cs       ON cs.codigo   = sp.prd_pa
ORDER BY sp.prd_pa;
```

**Mapeamento para `ProductionType`:**
```
codigo      → ProductionType.code
nome        → ProductionType.name
grupo/subgrupo/forma → ProductionType.category (concatenar ou usar subgrupo)
'procedimento' (literal) → ProductionType.unit
```

### 4.4 Extrair produção mensal por prestador e procedimento (→ `MonthlyProduction`)

```sql
SELECT
    sp.prd_uid                                    AS prestador_cnes,
    p.re_cnome                                    AS prestador_nome,
    sp.prd_cmp                                    AS competencia,
    SUBSTRING(sp.prd_cmp, 1, 4)                   AS ano,
    SUBSTRING(sp.prd_cmp, 5, 2)                   AS mes,
    sp.prd_pa                                     AS procedimento_codigo,
    COALESCE(proc.procedimento, cs.descricao, sp.prd_pa) AS procedimento_nome,
    sp.grupo,
    sp.subgrupo,
    sp.forma,
    SUM(CAST(sp.PRD_QT_A AS UNSIGNED))            AS quantidade_aprovada,
    SUM(CAST(sp.PRD_QT_P AS UNSIGNED))            AS quantidade_apresentada,
    SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2)))       AS valor_aprovado,
    SUM(CAST(sp.PRD_VL_P AS DECIMAL(15,2)))       AS valor_apresentado,
    SUM(CAST(sp.PRD_QT_P AS UNSIGNED)
        * CAST(COALESCE(cs.valor, proc.PA_TOTAL, 0) AS DECIMAL(15,2))) AS valor_calculado
FROM producao.s_prd sp
LEFT JOIN producao.prestador   p    ON p.re_cunid  = sp.prd_uid
LEFT JOIN producao.procedimento proc ON proc.codigo = sp.prd_pa
LEFT JOIN producao.cismetro    cs   ON cs.codigo   = sp.prd_pa
WHERE sp.prd_cmp = :competencia        -- parâmetro obrigatório ex: '202501'
  AND p.ativo = 1                      -- remover se quiser incluir inativos
GROUP BY
    sp.prd_uid, p.re_cnome,
    sp.prd_cmp,
    sp.prd_pa, proc.procedimento, cs.descricao,
    sp.grupo, sp.subgrupo, sp.forma
ORDER BY p.re_cnome, sp.prd_pa;
```

**Mapeamento para `MonthlyProduction`:**
```
prestador_cnes    → MonthlyProduction.organizationalUnitId
prestador_nome    → MonthlyProduction.organizationalUnitName
CAST(mes AS INT)  → MonthlyProduction.competenceMonth
CAST(ano AS INT)  → MonthlyProduction.competenceYear
procedimento_codigo → MonthlyProduction.productionTypeId
quantidade_aprovada → MonthlyProduction.quantity   (preferir aprovada)
isImported = true
isValidated = false (requer validação manual antes de fechar)
```

### 4.5 Resumo agregado por prestador (para dashboard)

```sql
SELECT
    sp.prd_uid                             AS prestador_cnes,
    p.re_cnome                             AS prestador_nome,
    sp.prd_cmp                             AS competencia,
    COUNT(DISTINCT sp.prd_pa)              AS tipos_procedimento,
    SUM(CAST(sp.PRD_QT_A AS UNSIGNED))    AS total_quantidade_aprovada,
    SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2))) AS total_valor_aprovado
FROM producao.s_prd sp
LEFT JOIN producao.prestador p ON p.re_cunid = sp.prd_uid
WHERE sp.prd_cmp = :competencia
  AND p.ativo = 1
GROUP BY sp.prd_uid, p.re_cnome, sp.prd_cmp
ORDER BY total_valor_aprovado DESC;
```

---

## 5. Armadilhas e Regras Obrigatórias

### 5.1 CAST obrigatório

| Campo         | Problema           | Correção                              |
|---------------|--------------------|---------------------------------------|
| `PRD_QT_P`    | int nullable       | `CAST(sp.PRD_QT_P AS UNSIGNED)`       |
| `PRD_QT_A`    | int nullable       | `CAST(sp.PRD_QT_A AS UNSIGNED)`       |
| `PRD_VL_P`    | decimal via TypeORM retorna string | `CAST(... AS DECIMAL(15,2))` |
| `PRD_VL_A`    | decimal via TypeORM retorna string | `CAST(... AS DECIMAL(15,2))` |
| `proc.PA_TOTAL` | **varchar** no schema | `CAST(proc.PA_TOTAL AS DECIMAL(15,2))` — nunca somar direto |
| `re_cunid`    | varchar(7)         | Comparar como string, nunca cast numérico |

### 5.2 Colunas STORED GENERATED — nunca inserir

`grupo`, `subgrupo` e `forma` em `s_prd` são geradas automaticamente pelo banco
a partir de `prd_pa`. **Não incluir em INSERT/UPDATE.** Apenas leitura.

### 5.3 Sem Primary Key em `s_prd`

Não usar `findOne`, `save` ou qualquer operação ORM que precise de PK na tabela `s_prd`.
Usar apenas `getRawMany()` / `manager.query()` para leituras.

### 5.4 LEFT JOIN sempre — nunca INNER JOIN

Dados DATASUS têm `prd_uid` sem match em `prestador` e `prd_pa` sem match em
`procedimento`. INNER JOIN silencia registros válidos.

### 5.5 Competência obrigatória

Toda query em `s_prd` deve filtrar `prd_cmp`. Sem esse filtro a tabela inteira
é varrida — potencialmente milhões de linhas.

### 5.6 DDL proibido

**PROIBIDO** `ALTER TABLE`, `CREATE INDEX`, `DROP`, ou qualquer DDL nas tabelas core
(`s_prd`, `prestador`, `procedimento`, `cismetro`, `s_rub`, `cbo`).
Ver ADR-0001 em `.context/docs/adr/ADR-0001-db-immutable.md`.

---

## 6. Como Implementar no Projeto v3-backend (NestJS/TypeORM)

O backend NestJS já possui:
- Entity: `v3-backend/src/sia/entities/s-prd.entity.ts`
- Service com joins prontos: `v3-backend/src/sia/sia.service.ts` (método `getBillingProvider`)
- Field catalog com expressões SQL seguras: `v3-backend/src/sia/field-catalog.ts`

### 6.1 Reutilizar joins existentes

O `sia.service.ts` já tem os LEFT JOINs corretos para `prestador` e `procedimento`.
Para o módulo de custos, criar um novo service ou endpoint que reutilize o mesmo
`SPrd` repository e aplique a query 4.4 acima.

### 6.2 Estrutura sugerida para o módulo de custos

```
v3-backend/src/custos/
├── custos.module.ts
├── dto/
│   └── import-production.dto.ts     # { competence: string; prestadorCnes?: string }
├── entities/
│   ├── monthly-production.entity.ts # tabela auxiliar (fora do core)
│   └── production-type.entity.ts    # tabela auxiliar (fora do core)
└── custos.service.ts                # usa SPrd repository + queries 4.3 e 4.4
```

### 6.3 Tabelas auxiliares (permitidas — fora do core)

O sistema de custos pode criar tabelas próprias para persistir a produção importada:

```sql
-- Tipos de produção (cache local dos procedimentos)
CREATE TABLE IF NOT EXISTS custos_production_type (
    id          CHAR(36) PRIMARY KEY,
    code        VARCHAR(10) NOT NULL UNIQUE,
    name        VARCHAR(255) NOT NULL,
    category    VARCHAR(100),
    unit        VARCHAR(50) DEFAULT 'procedimento',
    is_active   TINYINT(1) DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Produção mensal importada
CREATE TABLE IF NOT EXISTS custos_monthly_production (
    id                      CHAR(36) PRIMARY KEY,
    organizational_unit_id  VARCHAR(7) NOT NULL,    -- CNES
    organizational_unit_name VARCHAR(255),
    production_type_id      VARCHAR(10) NOT NULL,   -- código procedimento
    competence_month        TINYINT UNSIGNED NOT NULL,
    competence_year         SMALLINT UNSIGNED NOT NULL,
    quantity                INT UNSIGNED NOT NULL DEFAULT 0,
    is_validated            TINYINT(1) DEFAULT 0,
    is_imported             TINYINT(1) DEFAULT 1,
    import_competence       VARCHAR(6) NOT NULL,    -- prd_cmp original
    created_at              DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_by              VARCHAR(100),
    INDEX idx_competencia   (competence_year, competence_month),
    INDEX idx_unidade       (organizational_unit_id),
    INDEX idx_procedimento  (production_type_id)
) ENGINE=InnoDB;
```

### 6.4 Fluxo de importação

```
1. Receber POST /custos/import-production { competence: "202501" }
2. Executar query 4.3 → upsert em custos_production_type (novos procedimentos)
3. Executar query 4.4 → INSERT em custos_monthly_production
4. Retornar { imported: N, competence, prestadores: [...] }
```

---

## 7. Referência Cruzada com Módulo Custos

| Conceito (custos.md)        | Coluna em `s_prd`     | Tabela auxiliar              |
|-----------------------------|----------------------|------------------------------|
| `MonthlyProduction.organizationalUnitId` | `prd_uid` | `custos_monthly_production.organizational_unit_id` |
| `MonthlyProduction.productionTypeId`     | `prd_pa`  | `custos_monthly_production.production_type_id` |
| `MonthlyProduction.quantity`             | `PRD_QT_A`| SUM após agrupamento         |
| `MonthlyProduction.competenceMonth`      | `SUBSTRING(prd_cmp,5,2)` CAST INT | — |
| `MonthlyProduction.competenceYear`       | `SUBSTRING(prd_cmp,1,4)` CAST INT | — |
| `ProductionType.code`                    | `prd_pa`  | `custos_production_type.code` |
| `ProductionType.name`                    | `proc.procedimento` / `cs.descricao` | `custos_production_type.name` |
| `ProductionType.category`                | `subgrupo` (4 chars)  | `custos_production_type.category` |

---

## 8. Verificação Rápida de Sanidade

Antes de implementar, confirmar com estas queries no banco vivo:

```sql
-- Quantas competências existem?
SELECT COUNT(DISTINCT prd_cmp) FROM producao.s_prd;

-- Estrutura real da tabela
DESCRIBE producao.s_prd;

-- Procedimentos sem match na tabela de nomes
SELECT COUNT(*) FROM producao.s_prd sp
WHERE NOT EXISTS (SELECT 1 FROM producao.procedimento p WHERE p.codigo = sp.prd_pa)
  AND NOT EXISTS (SELECT 1 FROM producao.cismetro c WHERE c.codigo = sp.prd_pa);

-- Prestadores sem match
SELECT COUNT(DISTINCT sp.prd_uid) FROM producao.s_prd sp
WHERE NOT EXISTS (SELECT 1 FROM producao.prestador p WHERE p.re_cunid = sp.prd_uid);
```

---

## 9. O Que NÃO Fazer

- ❌ `ALTER TABLE s_prd` — proibido por ADR-0001
- ❌ INNER JOIN com `prestador` ou `procedimento` — perde dados
- ❌ Query sem filtro `prd_cmp` — full scan destrutivo
- ❌ Inserir em `s_prd` diretamente — dados DATASUS são somente leitura
- ❌ Comparar `re_cunid` como número — é varchar, ordenação lexicográfica
- ❌ Somar `proc.PA_TOTAL` sem CAST — campo varchar no schema real
- ❌ Usar `save()` do TypeORM em `s_prd` — sem PK formal
