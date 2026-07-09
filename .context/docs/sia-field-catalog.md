# SIA Field Catalog

**Implementação:** `app/Http/Controllers/RelatorioController.php` (`getAllFieldConfigs()`; `getFields()` e `getFieldConfig()` delegam para ela)
**Data:** 2026-02-22

---

## Catálogo 1 — Produção SIA (`s_prd`)

Usado pelo relatório dinâmico com seleção de colunas e filtros múltiplos.
Tabela principal alias: `sp`

| ID | Label | Tipo | sqlExpr (SELECT) | filterExpr (WHERE) | Operadores |
|----|-------|------|------------------|--------------------|------------|
| `prd_cmp` | Competência | date | `sp.prd_cmp` | `sp.prd_cmp` | `=`, `>=`, `<=`, `between` |
| `prd_uid` | Prestador | lookup | `sp.prd_uid` | `sp.prd_uid` | `=`, `in` |
| `prd_cbo` | CBO | lookup | `sp.prd_cbo` | `sp.prd_cbo` | `=`, `in` |
| `prd_pa` | Procedimento | lookup | `sp.prd_pa` | `sp.prd_pa` | `=`, `in`, `like` |
| `procedimento_descricao` | Descrição do Procedimento | text | `pc.procedimento` | subquery ¹ | `=`, `like`, `starts_with`, `ends_with` |
| `PRD_QT_P` | Quantidade Apresentada | number | `SUM(CAST(sp.PRD_QT_P AS UNSIGNED))` | `CAST(sp.PRD_QT_P AS UNSIGNED)` | `=`, `>`, `<`, `>=`, `<=`, `between` |
| `PRD_VL_P` | Valor Apresentado | currency | `SUM(CAST(sp.PRD_VL_P AS DECIMAL(15,2)))` | `CAST(sp.PRD_VL_P AS DECIMAL(15,2))` | `=`, `>`, `<`, `>=`, `<=`, `between` |
| `PRD_QT_A` | Quantidade Aprovada | number | `SUM(CAST(sp.PRD_QT_A AS UNSIGNED))` | `CAST(sp.PRD_QT_A AS UNSIGNED)` | `=`, `>`, `<`, `>=`, `<=`, `between` |
| `PRD_VL_A` | Valor Aprovado | currency | `SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2)))` | `CAST(sp.PRD_VL_A AS DECIMAL(15,2))` | `=`, `>`, `<`, `>=`, `<=`, `between` |
| `PRD_RUB` | Rubrica / Tipo Financiamento | lookup | `sp.prd_rub` | `sp.prd_rub` | `=`, `in` |
| `PRD_CIDPRI` | CID Principal | text | `sp.PRD_CIDPRI` | `sp.PRD_CIDPRI` | `=`, `like`, `starts_with` |
| `cismetro_valor` | Cismetro — Valor Unitário | currency | `cs.valor` | `cs.valor` | `=`, `>`, `<`, `>=`, `<=`, `between` |
| `cismetro_total` | Cismetro — Valor Total | currency | `SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * COALESCE(cs.valor,0))` | — ² | — ² |
| `cismetro_descricao` | Cismetro — Descrição | lookup | `sp.prd_pa` | `sp.prd_pa` | `=`, `like` |

¹ `procedimento_descricao` é `filterOnly=true`: resolve para `SELECT codigo FROM procedimento WHERE procedimento LIKE ?` + `WHERE sp.prd_pa IN (...)`. Nunca aparece como coluna no resultado.

² `cismetro_total` é `displayOnly=true` e `isAggregate=true`: campo calculado, sem operadores de filtro (requereria HAVING).

### JOINs automáticos por campo

| Campo | JOIN adicionado |
|-------|----------------|
| `prd_uid` | `LEFT JOIN prestador pr ON sp.prd_uid = pr.re_cunid` |
| `prd_cbo` | `LEFT JOIN cbo cb ON sp.prd_cbo = cb.cbo` |
| `prd_pa`, `procedimento_descricao` | `LEFT JOIN procedimento pc ON sp.prd_pa = pc.codigo` |
| `PRD_RUB` | `LEFT JOIN s_rub sr ON sp.prd_rub = sr.RUB_ID` |
| `cismetro_valor`, `cismetro_total`, `cismetro_descricao` | `LEFT JOIN cismetro cs ON sp.prd_pa = cs.codigo` |

---

## Catálogo 2 — Faturamento por Prestador (`s_prd` hierárquico)

Campos fixos — sem seleção dinâmica de colunas.
Tabela principal alias: `sp` | prestador: `p` | procedimento: `proc`

| ID | Label | Tipo | sqlExpr | groupable | isAggregate |
|----|-------|------|---------|-----------|-------------|
| `prestador_codigo` | CNES | text | `p.re_cunid` | ✅ | — |
| `prestador_nome` | Prestador | text | `p.re_cnome` | ✅ | — |
| `tipo_financiamento` | Tipo Financiamento | text | `sp.prd_rub` | ✅ | — |
| `grupo` | Grupo | text | `sp.grupo` ³ | ✅ | — |
| `subgrupo` | Subgrupo | text | `sp.subgrupo` ³ | ✅ | — |
| `forma` | Forma | text | `sp.forma` ³ | ✅ | — |
| `procedimento_codigo` | Cód. Procedimento | text | `sp.prd_pa` | ✅ | — |
| `procedimento_nome` | Procedimento | text | `proc.procedimento` | ✅ | — |
| `valor_unitario` | Vlr. Unitário | currency | `CAST(proc.PA_TOTAL AS DECIMAL(15,2))` | ✅ | — |
| `qtyApproved` | Qtd. Aprovada | number | `SUM(CAST(sp.PRD_QT_A AS UNSIGNED))` | ❌ | ✅ |
| `valueApproved` | Vlr. Aprovado | currency | `SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2)))` | ❌ | ✅ |
| `qtyPresented` | Qtd. Apresentada | number | `SUM(CAST(sp.PRD_QT_P AS UNSIGNED))` | ❌ | ✅ |
| `valuePresented` | Vlr. Apresentado | currency | `SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * CAST(proc.PA_TOTAL AS DECIMAL(15,2)))` | ❌ | ✅ |

³ `grupo`, `subgrupo`, `forma` são **STORED GENERATED columns** derivadas de `prd_pa`. O v3 usa estas colunas diretamente, evitando `SUBSTRING(prd_pa, 1, 2/4/6)` em runtime (diferença do legado Laravel).

### GROUP BY fixo (Faturamento por Prestador)

```sql
GROUP BY
    p.re_cunid, p.re_cnome,
    sp.prd_rub,
    sp.grupo, sp.subgrupo, sp.forma,
    sp.prd_pa, proc.procedimento, proc.PA_TOTAL
```

---

## Mapeamento de Operadores → SQL

| Operador | SQL gerado | Tipos que aceitam |
|----------|-----------|------------------|
| `=` | `expr = ?` | todos |
| `>` | `expr > ?` | number, currency, date |
| `<` | `expr < ?` | number, currency, date |
| `>=` | `expr >= ?` | number, currency, date |
| `<=` | `expr <= ?` | number, currency, date |
| `like` | `expr LIKE '%v%'` | text, lookup |
| `starts_with` | `expr LIKE 'v%'` | text |
| `ends_with` | `expr LIKE '%v'` | text |
| `between` | `expr BETWEEN ?Min AND ?Max` | date, number, currency |
| `in` | `expr IN (:...param)` | lookup |

Todos os operadores usam **parâmetros nomeados** (sem interpolação direta no SQL).

---

## Regras de Segurança

1. **Whitelist obrigatória**: qualquer `fieldId` não presente em `SIA_PRODUCAO_FIELDS` ou `FATURAMENTO_PRESTADOR_FIELDS` é rejeitado com HTTP 400.
2. **Operador validado por campo**: o service chama `isOperatorAllowed(field, op)` antes de construir o WHERE.
3. **CAST obrigatório para VARCHAR numérico**: todos os campos numéricos do DATASUS (PRD_QT_P, PRD_QT_A, PRD_VL_P, PRD_VL_A, PA_TOTAL) têm CAST explícito na `sqlExpr`/`filterExpr`.
4. **Campos `isAggregate`** nunca entram no GROUP BY.
5. **Campos `filterOnly`** nunca aparecem no SELECT de resultado.
6. **Campos `displayOnly`** não aceitam filtros (sem operadores).
7. **`procedimento_descricao`**: filtro resolve via subquery — nunca `JOIN + WHERE` direto (evita full scan no JOIN).

---

## CAST Reference (VARCHAR numérico)

| Campo DB | Tipo real no schema | CAST correto |
|----------|--------------------|----|
| `PRD_QT_P` | varchar | `CAST(... AS UNSIGNED)` |
| `PRD_QT_A` | varchar | `CAST(... AS UNSIGNED)` |
| `PRD_VL_P` | varchar | `CAST(... AS DECIMAL(15,2))` |
| `PRD_VL_A` | varchar | `CAST(... AS DECIMAL(15,2))` |
| `proc.PA_TOTAL` | varchar | `CAST(... AS DECIMAL(15,2))` |
| `cs.valor` (cismetro) | float nativo | sem CAST necessário |
