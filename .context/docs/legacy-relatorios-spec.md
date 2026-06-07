# Spec: Relatórios Legado Laravel — Análise de Código

**Data:** 2026-02-22
**Fonte:** `app/Http/Controllers/RelatorioController.php`, `RelatorioApacController.php`, `FaturamentoPrestadorController.php`, `BaseRelatorioController.php`, `Concerns/HasMatrixReport.php`, `routes/web.php`

---

## 1. Relatórios Existentes (3)

| # | Nome | Rota | Controller | Tabela principal |
|---|------|------|------------|-----------------|
| 1 | **Produção SIA** | `GET /relatorios` | `RelatorioController` | `s_prd` |
| 2 | **Relatório de APAC** | `GET /relatorios/apac` | `RelatorioApacController` | `s_pap` + `s_apa` |
| 2b | **Produção Individualizada (BPI)** | `GET /relatorios/bpi` | `RelatorioBpiController` | `s_bpi` |
| 3 | **Faturamento por Prestador** | `GET /relatorios/faturamento-prestador` | `FaturamentoPrestadorController` | `s_prd` |

---

## 2. Relatório 1 — Produção SIA (`s_prd`)

### Campos exibíveis / filtráveis

| Chave PHP | Label UI | Tipo | Tabela origem | CAST aplicado |
|-----------|----------|------|---------------|---------------|
| `prd_cmp` | Data Competência | `date` | `s_prd` | — (comparação string 'AAAAMM') |
| `prd_uid` | Prestador | `lookup` | `prestador` (re_cunid / re_cnome) | — |
| `prd_cbo` | CBO | `lookup` | `cbo` (cbo / ds_cbo) | — |
| `prd_pa` | Procedimento | `lookup` | `procedimento` (codigo / procedimento) | — |
| `procedimento_descricao` | Descrição do Procedimento | `text` | `procedimento.procedimento` | — (somente filtro, não exibido) |
| `PRD_QT_P` | Quantidade | `number` | `s_prd` | `CAST(PRD_QT_P AS UNSIGNED)` |
| `PRD_VL_P` | Valor | `currency` | `s_prd` | `CAST(PRD_VL_P AS DECIMAL(15,2))` |
| `PRD_RUB` | Rubrica | `lookup` | `s_rub` (RUB_ID / RUB_DC) | — |
| `PRD_CIDPRI` | CID Principal | `text` | `s_prd` | — |
| `cismetro_valor` | Cismetro — Valor Unitário | `currency` | `cismetro.valor` | `DECIMAL(15,2)` no agrupamento |
| `cismetro_total` | Cismetro — Valor Total | `currency` | calculado | `SUM(CAST(PRD_QT_P AS UNSIGNED) * COALESCE(cs.valor,0))` |
| `cismetro_descricao` | Cismetro — Descrição | `lookup` | `cismetro` (codigo / descricao) | — |

### SELECT gerado por campo (quando selecionado)

```sql
-- prd_uid (lookup)
sp.prd_uid AS cnes, pr.re_cnome AS prestador_nome
GROUP BY sp.prd_uid, pr.re_cnome

-- prd_pa (lookup)
sp.prd_pa AS procedimento_codigo, pc.procedimento AS procedimento_nome
GROUP BY sp.prd_pa, pc.procedimento

-- PRD_QT_P
SUM(CAST(sp.PRD_QT_P AS UNSIGNED)) AS total_quantidade

-- PRD_VL_P
SUM(CAST(sp.PRD_VL_P AS DECIMAL(15,2))) AS total_valor

-- prd_cmp
CONCAT(SUBSTRING(sp.prd_cmp,1,4),'-',SUBSTRING(sp.prd_cmp,5,2)) AS competencia
GROUP BY sp.prd_cmp

-- cismetro_total
SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * COALESCE(cs.valor,0)) AS cismetro_total
```

### Operadores por campo

| Tipo | Operadores permitidos |
|------|-----------------------|
| `date` | `=`, `>=`, `<=`, `between` |
| `lookup` | `=`, `in` (padrão) / + `like` para procedimento |
| `number` / `currency` | `=`, `>`, `<`, `>=`, `<=`, `between` |
| `text` | `=`, `like`, `starts_with`, `ends_with` |

---

## 3. Relatório 2 — Relatório de APAC (`s_pap` + `s_apa`)

Paridade funcional com Produção Individualizada (BPI). Tabela principal: `s_pap` (procedimentos internos); `s_apa` (cabeçalho) via `LEFT JOIN` em `PAP_NUM = APA_NUM`.

### Campos exibíveis / filtráveis — produção (`s_pap`)

| Chave PHP | Label UI | Tipo | Tabela origem | CAST aplicado |
|-----------|----------|------|---------------|---------------|
| `PAP_CMP` | Data Competência | `date` | `s_pap` | `CONCAT(SUBSTRING, YYYY-MM)` |
| `PAP_MVM` | Data Movimento | `date` | `s_pap` | `CONCAT(SUBSTRING, YYYY-MM)` |
| `PAP_UID` | Unidade (CNES) | `lookup` | `prestador` | — |
| `tipo_relatorio` | Tipo de Relatório | `text` | `prestador.relatorio` | — |
| `PAP_PA` | Procedimento | `lookup` | `procedimento` | — |
| `procedimento_descricao` | Descrição do Procedimento | `text` | `procedimento` | — (somente filtro) |
| `PAP_CBO` | CBO Profissional | `lookup` | `cbo` | — |
| `PAP_CIDPRI` | CID Principal | `text` | `s_pap` | — |
| `PAP_QT_P` | Quantidade Produzida | `number` | `s_pap` | `CAST(PAP_QT_P AS DECIMAL(15,2))` ¹ |
| `PAP_VALOR` | Valor (Unitário e Total) | `currency` | `procedimento.pa_total` | `CAST(PAP_QT_P AS DECIMAL(15,2)) * CAST(pc.pa_total AS DECIMAL(15,2))` |
| `PAP_IDADE` | Idade | `number` | `s_pap` | — |
| `faixa_etaria_1` | Faixa Etária (detalhada) | `calculated` | `s_pap` | CASE em `PAP_IDADE` |
| `faixa_etaria_2` | Faixa Etária (resumida) | `calculated` | `s_pap` | CASE em `PAP_IDADE` |
| `grupo` / `descgrupo` | Grupo | `text` | `forma` via `SUBSTRING(PAP_PA)` | — |
| `subgrupo` / `descsubgrupo` | Subgrupo | `text` | `forma` | — |
| `forma` / `descforma` | Forma de Organização | `text` | `forma` | — |
| `cismetro_valor` | Cismetro — Valor Unitário | `currency` | `cismetro` | — |
| `cismetro_total` | Cismetro — Valor Total | `currency` | calculado | `SUM(CAST(PAP_QT_P AS DECIMAL(15,2)) * COALESCE(cs.valor,0))` |
| `cismetro_descricao` | Cismetro — Descrição | `lookup` | `cismetro` | — |

### Campos exibíveis / filtráveis — cabeçalho APAC (`s_apa`)

| Chave PHP | Label UI | Tipo | Tabela origem |
|-----------|----------|------|---------------|
| `APA_NUM` | Número APAC | `text` | `s_apa` |
| `APA_CMP` | Competência APAC | `date` | `s_apa` |
| `APA_MVM` | Movimento APAC | `date` | `s_apa` |
| `APA_PRIPAL` | Procedimento Principal APAC | `text` | `s_apa` |
| `APA_NMPCN` | Nome do Paciente | `text` | `s_apa` |
| `APA_CNSPCT` | CNS Paciente | `text` | `s_apa` |
| `APA_DTNASC` | Data de Nascimento | `text` | `s_apa` |
| `APA_SEXPCN` | Sexo | `choice` M/F | `s_apa` |
| `APA_CIDCA` | CID Principal APAC | `text` | `s_apa` |
| `APA_RACA` | Raça/Cor | `text` | `s_apa` |
| `APA_DTINIC` | Data Início Validade | `text` | `s_apa` |
| `APA_DTFIM` | Data Fim Validade | `text` | `s_apa` |
| `APA_TPATEN` | Tipo de Atendimento | `text` | `s_apa` |
| `APA_TPAPAC` | Tipo APAC | `text` | `s_apa` |

¹ Atenção: APAC usa `DECIMAL(15,2)` para quantidades (vs `UNSIGNED` em PRD/BPI — diferença no legado).

### Matriz pivot
Eixo temporal: `PAP_CMP` **ou** `PAP_MVM` (mutuamente exclusivos na UI, igual BPI).

### Filtro especial OCI
Campo virtual `filter_oci` (boolean). Quando `true`, o JOIN com `s_apa` é convertido de `LEFT JOIN` para `INNER JOIN` com condição `apa.APA_PRIPAL LIKE '09%'` (seleciona apenas procedimentos oncológicos).

---

## 4. Relatório 3 — Faturamento por Prestador (fixo, sem seleção de colunas)

Ao contrário dos outros dois, este relatório **não tem colunas selecionáveis**. O SELECT é fixo e os únicos filtros são competência e CNES do prestador.

### Campos exibidos (sempre)

| Alias SQL | Label | Origem | CAST |
|-----------|-------|--------|------|
| `prestador_codigo` | CNES | `pr.re_cunid` | — |
| `prestador_nome` | Prestador | `pr.re_cnome` | — |
| `tipo_financiamento` | Tipo Financiamento | `sp.prd_rub` | — (traduzido em PHP) |
| `grupo_codigo` | Grupo (cód.) | `SUBSTRING(sp.prd_pa, 1, 2)` | — |
| `grupo_descricao` | Grupo (desc.) | `f_grupo.descricao` | — (LEFT JOIN `forma`) |
| `subgrupo_codigo` | Subgrupo (cód.) | `SUBSTRING(sp.prd_pa, 1, 4)` | — |
| `subgrupo_descricao` | Subgrupo (desc.) | `f_subgrupo.descricao` | — |
| `forma_codigo` | Forma (cód.) | `SUBSTRING(sp.prd_pa, 1, 6)` | — |
| `forma_descricao` | Forma (desc.) | `f_forma.descricao` | — |
| `procedimento_codigo` | Cód. Procedimento | `sp.prd_pa` | — |
| `procedimento_nome` | Procedimento | `proc.procedimento` | — |
| `valor_unitario` | Vlr. Unitário | `proc.PA_TOTAL` | — (raw, sem CAST) |
| `quantidade_apresentada` | Qtd. Apres. | `s_prd` | `SUM(CAST(PRD_QT_P AS UNSIGNED))` |
| `valor_apresentado` | Vlr. Apres. | calculado | `SUM(CAST(PRD_QT_P AS UNSIGNED) * CAST(proc.PA_TOTAL AS DECIMAL(15,2)))` |
| `quantidade_aprovada` | Qtd. Aprov. | `s_prd` | `SUM(CAST(PRD_QT_A AS UNSIGNED))` |
| `valor_aprovado` | Vlr. Aprov. | `s_prd` | `SUM(CAST(PRD_VL_A AS DECIMAL(15,2)))` |

### Filtros aceitos (form POST)

| Parâmetro | Obrigatoriedade | Validação |
|-----------|-----------------|-----------|
| `competencia` | **obrigatório** | `string\|size:6` → `sp.prd_cmp = ?` |
| `prestador_id` | opcional | `nullable\|string` → `sp.prd_uid = ?` |

### GROUP BY (11 campos)
```sql
GROUP BY
  pr.re_cunid, pr.re_cnome, sp.prd_rub,
  grupo_codigo, f_grupo.descricao,
  subgrupo_codigo, f_subgrupo.descricao,
  forma_codigo, f_forma.descricao,
  sp.prd_pa, proc.procedimento, proc.PA_TOTAL
```

### Hierarquia processada em PHP (6 níveis)
```
Prestador (re_cunid / re_cnome)
  └─ Tipo Financiamento (prd_rub)
       └─ Grupo (SUBSTRING(prd_pa,1,2))
            └─ Subgrupo (SUBSTRING(prd_pa,1,4))
                 └─ Forma (SUBSTRING(prd_pa,1,6))
                      └─ Procedimento (prd_pa)
```
Totais são acumulados em **todos os 5 níveis** (prestador, tipo, grupo, subgrupo, forma).

---

## 5. Mapeamento de Operadores → SQL

| Operador PHP | SQL gerado | Observação |
|--------------|-----------|------------|
| `=` | `WHERE field = ?` | |
| `>` | `WHERE field > ?` | |
| `<` | `WHERE field < ?` | |
| `>=` | `WHERE field >= ?` | |
| `<=` | `WHERE field <= ?` | |
| `like` | `WHERE field LIKE '%value%'` | wrapping automático |
| `starts_with` | `WHERE field LIKE 'value%'` | |
| `ends_with` | `WHERE field LIKE '%value'` | |
| `between` | `WHERE field BETWEEN ? AND ?` | value deve ser array `[min, max]` |
| `in` | `WHERE field IN (?, ?, ...)` | value deve ser array |

**Campo especial `procedimento_descricao`:** filtro resolve para subquery `SELECT codigo FROM procedimento WHERE procedimento LIKE ?` + `WHERE sp.prd_pa IN (...)`. Se nenhum procedimento for encontrado, injeta `WHERE 1=0` (zero resultados garantido).

---

## 6. Tratamento de Valores Numéricos Guardados como VARCHAR

Todos os campos numéricos em `s_prd` e `s_pap` são VARCHAR no schema DATASUS:

| Campo | CAST usado | Tipo PHP resultado |
|-------|-----------|-------------------|
| `PRD_QT_P` | `CAST(... AS UNSIGNED)` | inteiro sem sinal |
| `PRD_QT_A` | `CAST(... AS UNSIGNED)` | inteiro sem sinal |
| `PRD_VL_P` | `CAST(... AS DECIMAL(15,2))` | valor monetário |
| `PRD_VL_A` | `CAST(... AS DECIMAL(15,2))` | valor monetário |
| `PAP_QT_P` | `CAST(... AS DECIMAL(15,2))` | ¹ APAC usa DECIMAL, não UNSIGNED |
| `proc.PA_TOTAL` | `CAST(... AS DECIMAL(15,2))` | valor unitário da tabela `procedimento` |
| `cs.valor` (cismetro) | `COALESCE(cs.valor, 0)` sem CAST | float nativo MySQL |

> **Nota divergência:** NestJS v3 usa `CAST(PRD_QT_P AS UNSIGNED)` e `CAST(PRD_QT_A AS UNSIGNED)` — alinhado com o legado.
> O legado usa `CAST(PAP_QT_P AS DECIMAL(15,2))` para APAC — divergência que deve ser mantida ao implementar o módulo APAC no v3.

---

## 7. Payload/Request Laravel (exemplos)

### Relatório Produção — POST `/relatorios/generate`
```json
{
  "fields": ["prd_cmp", "prd_uid", "prd_pa", "PRD_QT_P", "PRD_VL_P"],
  "filters": [
    { "field": "prd_cmp",  "operator": "=",  "value": "202301" },
    { "field": "prd_uid",  "operator": "=",  "value": "2058790" }
  ],
  "format": "html",
  "group_by": true
}
```

### Relatório Matriz — POST `/relatorios/generate-matrix`
```json
{
  "fields": ["prd_cmp", "prd_uid", "PRD_QT_P"],
  "filters": [
    { "field": "prd_cmp", "operator": "between", "value": ["202301", "202312"] }
  ],
  "format": "html"
}
```

### Faturamento por Prestador — POST `/relatorios/faturamento-prestador/gerar`
```
competencia=202301&prestador_id=2058790
```
*(form-data, não JSON)*

---

## 8. Observações de Performance

1. **Filtro de competência obrigatório em todos os relatórios** — sem ele o legado recusa (validação Laravel `required`). O v3 segue o mesmo padrão.

2. **Faturamento por Prestador é o mais pesado**: GROUP BY em 12 campos + 4 JOINs + 4 SUM/CAST. O legado carrega **tudo em memória PHP** de uma vez (sem paginação). O v3 implementa paginação server-side para mitigar.

3. **Hierarquia no legado é processada em PHP** (`processarDadosHierarquicos`), não no banco. O v3 retorna flat e delega ao frontend o agrupamento visual — abordagem mais escalável.

4. **Subquery para `procedimento_descricao`**: o legado resolve em duas queries separadas. No v3 pode-se usar JOIN direto com `proc.procedimento LIKE ?` no WHERE.

5. **Cismetro requer LEFT JOIN dinâmico**: apenas adicionado quando algum campo `cismetro_*` é selecionado — otimização correta, preservar no v3.

6. **`forma` table JOIN triplo** no Faturamento por Prestador: 3 instâncias (`f_grupo`, `f_subgrupo`, `f_forma`) com condições diferentes. O v3 evita isso usando STORED GENERATED columns (`sp.grupo`, `sp.subgrupo`, `sp.forma`) + tabela `procedimento` diretamente — sem necessidade de JOIN com `forma`.

7. **Sem índice por `prd_cmp`** no schema original DATASUS: verificar se índice foi criado manualmente antes de rodar queries em produção.

---

## 9. Diferenças-chave entre Legado e v3 NestJS

| Aspecto | Laravel legado | NestJS v3 |
|---------|---------------|-----------|
| Grupo/Subgrupo/Forma | `SUBSTRING(prd_pa, 1, 2/4/6)` em runtime | STORED GENERATED columns (sem SUBSTRING) |
| Hierarquia | Processada em PHP (6 níveis aninhados) | Retorno flat, agrupamento visual no frontend |
| Paginação | Sem paginação — carrega tudo | Server-side paginação obrigatória |
| Campos selecionáveis | Dinâmico (checkbox multiplo) | Colunas fixas por relatório (MVP) |
| Export | HTML / Excel / PDF / CSV inline | Job assíncrono → result rows JSON |
| JOIN `forma` | 3 LEFT JOINs com aliases diferentes | Desnecessário (STORED GENERATED) |
