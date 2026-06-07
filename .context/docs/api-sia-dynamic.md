# Contrato de API — SIA Dinâmico

**Data:** 2026-02-22
**Base URL:** `http://localhost:3000`
**Auth:** nenhuma (MVP local)

---

## GET /reports/sia/metadata

Retorna o catálogo de campos disponíveis para o relatório dinâmico e os limites do endpoint.

### Response 200

```json
{
  "producao": {
    "description": "Relatório dinâmico de Produção SIA (s_prd). Filtro de competência obrigatório.",
    "fields": [
      {
        "id": "prd_cmp",
        "label": "Competência",
        "type": "date",
        "allowedOperators": ["=", ">=", "<=", "between"],
        "sortable": true,
        "groupable": true,
        "filterOnly": false,
        "displayOnly": false
      },
      {
        "id": "prd_uid",
        "label": "Prestador",
        "type": "lookup",
        "allowedOperators": ["=", "in"],
        "sortable": true,
        "groupable": true,
        "filterOnly": false,
        "displayOnly": false
      },
      {
        "id": "PRD_QT_P",
        "label": "Quantidade Apresentada",
        "type": "number",
        "allowedOperators": ["=", ">", "<", ">=", "<=", "between"],
        "sortable": true,
        "groupable": false,
        "filterOnly": false,
        "displayOnly": false
      },
      {
        "id": "procedimento_descricao",
        "label": "Descrição do Procedimento",
        "type": "text",
        "allowedOperators": ["=", "like", "starts_with", "ends_with"],
        "sortable": false,
        "groupable": false,
        "filterOnly": true,
        "displayOnly": false
      }
      // ... demais campos
    ]
  },
  "faturamentoPrestador": {
    "description": "Campos do relatório hierárquico de Faturamento por Prestador (colunas fixas).",
    "fields": [ /* ... */ ]
  },
  "limits": {
    "maxSelect": 20,
    "maxFilters": 20,
    "maxPageSize": 500
  }
}
```

---

## POST /reports/sia/production

Relatório dinâmico de Produção SIA com seleção de colunas, filtros compostos e paginação server-side.

### Request Body

```json
{
  "competence": "202301",
  "select": ["prd_uid", "prd_pa", "PRD_QT_P", "PRD_VL_P"],
  "filters": [
    { "fieldId": "prd_uid",  "operator": "=",    "value": "2058790" },
    { "fieldId": "PRD_QT_P", "operator": ">=",   "value": "10" }
  ],
  "page": 1,
  "pageSize": 50,
  "sort": { "fieldId": "PRD_QT_P", "direction": "DESC" }
}
```

#### Campos do body

| Campo | Tipo | Obrig. | Regras |
|-------|------|--------|--------|
| `competence` | string | ✅ | Exatamente 6 chars, formato AAAAMM |
| `select` | string[] | ✅ | 1–20 IDs do catálogo; campos `filterOnly` não permitidos |
| `filters` | FilterItem[] | ❌ | Máximo 20; fieldId + operator + value |
| `page` | number | ❌ | Default 1, mínimo 1 |
| `pageSize` | number | ❌ | Default 50, máximo 500 |
| `sort` | SortItem | ❌ | fieldId deve ter `sortable=true` no catálogo |

#### FilterItem

```typescript
{
  fieldId: string;    // ID do catálogo (ex: "prd_uid", "PRD_QT_P")
  operator: string;   // Operador válido para o tipo do campo
  value: string | string[];  // string simples OU array (para "between"/"in")
}
```

#### Operadores por tipo

| Tipo de campo | Operadores aceitos |
|---------------|--------------------|
| `date` | `=`, `>=`, `<=`, `between` |
| `lookup` | `=`, `in` (+ `like` para `prd_pa`) |
| `number` / `currency` | `=`, `>`, `<`, `>=`, `<=`, `between` |
| `text` | `=`, `like`, `starts_with`, `ends_with` |

#### Regras especiais de `value`

| Operador | value esperado |
|----------|---------------|
| `between` | array com **exatamente 2** elementos: `["202301", "202312"]` |
| `in` | array não-vazio: `["2058790", "2028578"]` |
| demais | string simples: `"2058790"` |

### Response 200

```json
{
  "columns": [
    { "fieldId": "prd_uid",  "label": "Prestador",             "type": "lookup", "displayAlias": "prd_uid_display" },
    { "fieldId": "prd_pa",   "label": "Procedimento",          "type": "lookup", "displayAlias": "prd_pa_display" },
    { "fieldId": "PRD_QT_P", "label": "Quantidade Apresentada","type": "number" },
    { "fieldId": "PRD_VL_P", "label": "Valor Apresentado",     "type": "currency" }
  ],
  "rows": [
    {
      "prd_uid": "2058790",
      "prd_uid_display": "HOSPITAL REGIONAL XYZ",
      "prd_pa": "0301010196",
      "prd_pa_display": "CONSULTA MÉDICA EM ATENÇÃO BÁSICA",
      "PRD_QT_P": "214675",
      "PRD_VL_P": "1606620.01"
    }
  ],
  "meta": {
    "totalRows": 736,
    "page": 1,
    "pageSize": 50,
    "totalPages": 15,
    "queryTimeMs": 312,
    "hasAggregates": true
  }
}
```

#### Campos de `columns`

| Propriedade | Descrição |
|-------------|-----------|
| `fieldId` | ID do campo no catálogo |
| `label` | Label para exibição na UI |
| `type` | Tipo do campo (`date`, `text`, `number`, `currency`, `lookup`) |
| `displayAlias` | Presente somente em lookup: chave no objeto `rows` que contém o nome de exibição |

#### Campos de `meta`

| Propriedade | Descrição |
|-------------|-----------|
| `totalRows` | Total de linhas (ou grupos) sem paginação |
| `hasAggregates` | `true` se algum campo SUM foi selecionado (GROUP BY aplicado) |
| `warning` | Presente se query for potencialmente pesada (sugestão de job assíncrono) |

---

## Exemplos de Request

### 1. Agregado por Prestador + Procedimento

```bash
curl -s -X POST http://localhost:3000/reports/sia/production \
  -H "Content-Type: application/json" \
  -d '{
    "competence": "202301",
    "select": ["prd_uid", "prd_pa", "PRD_QT_A", "PRD_VL_A"],
    "page": 1,
    "pageSize": 25,
    "sort": { "fieldId": "PRD_VL_A", "direction": "DESC" }
  }'
```

### 2. Filtro por competência range + CBO específico

```bash
curl -s -X POST http://localhost:3000/reports/sia/production \
  -H "Content-Type: application/json" \
  -d '{
    "competence": "202301",
    "select": ["prd_uid", "prd_cbo", "PRD_QT_P"],
    "filters": [
      { "fieldId": "prd_cbo", "operator": "=", "value": "225125" }
    ],
    "pageSize": 100
  }'
```

### 3. Filtro por descrição de procedimento (subquery)

```bash
curl -s -X POST http://localhost:3000/reports/sia/production \
  -H "Content-Type: application/json" \
  -d '{
    "competence": "202301",
    "select": ["prd_pa", "PRD_QT_P", "PRD_VL_P"],
    "filters": [
      { "fieldId": "procedimento_descricao", "operator": "like", "value": "CONSULTA" }
    ]
  }'
```

### 4. Filtro entre competências + múltiplos prestadores

```bash
curl -s -X POST http://localhost:3000/reports/sia/production \
  -H "Content-Type: application/json" \
  -d '{
    "competence": "202301",
    "select": ["prd_cmp", "prd_uid", "PRD_QT_P"],
    "filters": [
      { "fieldId": "prd_cmp",  "operator": "between", "value": ["202301", "202306"] },
      { "fieldId": "prd_uid",  "operator": "in",      "value": ["2058790", "2028578"] }
    ]
  }'
```

### 5. Com cismetro (inclui JOIN dinâmico)

```bash
curl -s -X POST http://localhost:3000/reports/sia/production \
  -H "Content-Type: application/json" \
  -d '{
    "competence": "202301",
    "select": ["prd_pa", "cismetro_descricao", "cismetro_valor", "PRD_QT_P"],
    "filters": [
      { "fieldId": "cismetro_valor", "operator": ">=", "value": "10.00" }
    ]
  }'
```

---

## Erros

### HTTP 400 — Validação de DTO (class-validator)

```json
{
  "statusCode": 400,
  "message": [
    "competence must be longer than or equal to 6 characters",
    "each value in select must be one of the following values: prd_cmp, prd_uid, ..."
  ],
  "error": "Bad Request"
}
```

### HTTP 400 — Validação de negócio (service)

```json
{ "statusCode": 400, "message": "Campo \"procedimento_descricao\" é somente-filtro e não pode aparecer em \"select\"." }
{ "statusCode": 400, "message": "Operador \"like\" não é permitido para \"PRD_QT_P\". Válidos: =, >, <, >=, <=, between." }
{ "statusCode": 400, "message": "\"between\" em \"prd_cmp\" requer array com exatamente 2 elementos." }
{ "statusCode": 400, "message": "Operador \"=\" em \"prd_uid\" requer valor único, não array." }
```

---

## JOINs dinâmicos aplicados

| Campo selecionado | JOIN adicionado automaticamente |
|-------------------|---------------------------------|
| `prd_uid` | `LEFT JOIN prestador pr ON sp.prd_uid = pr.re_cunid` |
| `prd_cbo` | `LEFT JOIN cbo cb ON sp.prd_cbo = cb.cbo` |
| `prd_pa` | `LEFT JOIN procedimento pc ON sp.prd_pa = pc.codigo` |
| `PRD_RUB` | `LEFT JOIN s_rub sr ON sp.prd_rub = sr.RUB_ID` |
| `cismetro_*` | `LEFT JOIN cismetro cs ON sp.prd_pa = cs.codigo` |
| `procedimento_descricao` (filtro) | Subquery `IN (SELECT codigo FROM procedimento WHERE ...)` — **sem JOIN** |

---

## Lógica de GROUP BY

Se qualquer campo com `isAggregate=true` (SUM) estiver em `select`, a query aplica `GROUP BY` com todos os campos `groupable=true` da seleção.

**Exemplo:** `select = ["prd_uid", "prd_pa", "PRD_QT_P"]`
- `prd_uid` (groupable) + `prd_uid_display` → GROUP BY
- `prd_pa` (groupable) + `prd_pa_display` → GROUP BY
- `PRD_QT_P` (isAggregate) → `SUM(CAST(sp.PRD_QT_P AS UNSIGNED))`, não entra no GROUP BY

SQL gerado:
```sql
SELECT sp.prd_uid, pr.re_cnome AS prd_uid_display,
       sp.prd_pa, pc.procedimento AS prd_pa_display,
       SUM(CAST(sp.PRD_QT_P AS UNSIGNED)) AS PRD_QT_P
FROM s_prd sp
LEFT JOIN prestador pr ON sp.prd_uid = pr.re_cunid
LEFT JOIN procedimento pc ON sp.prd_pa = pc.codigo
WHERE sp.prd_cmp = '202301'
GROUP BY sp.prd_uid, pr.re_cnome, sp.prd_pa, pc.procedimento
ORDER BY SUM(CAST(sp.PRD_QT_P AS UNSIGNED)) DESC
LIMIT 50 OFFSET 0
```

---

## Warning de Query Pesada

O campo `meta.warning` é adicionado quando:
- Mais de 2 filtros com operadores de string (`like`, `starts_with`, `ends_with`), OU
- Campo `cismetro_total` está em `select`

```json
{
  "meta": {
    "warning": "Query potencialmente lenta. Para exportação completa, use POST /reports/jobs com type=\"sia-aggregated\"."
  }
}
```

---

## CAST Reference (campos VARCHAR numérico no DATASUS)

| Campo | CAST na sqlExpr |
|-------|----------------|
| `PRD_QT_P`, `PRD_QT_A` | `CAST(... AS UNSIGNED)` |
| `PRD_VL_P`, `PRD_VL_A` | `CAST(... AS DECIMAL(15,2))` |
| `proc.PA_TOTAL` | `CAST(... AS DECIMAL(15,2))` |
| `cs.valor` (cismetro) | sem CAST (float nativo) |
