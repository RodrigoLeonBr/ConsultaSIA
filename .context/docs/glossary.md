---
type: doc
name: glossary
description: Project terminology, SIA domain terms, business rules, and technical conventions
category: glossary
generated: 2026-02-22
status: filled
scaffoldVersion: "2.0.0"
---

# Glossário

## Termos de Domínio (SIA / SUS)

| Termo | Significado |
|-------|-------------|
| **SIA** | Sistema de Informações Ambulatoriais — sistema federal de registro e faturamento da produção ambulatorial do SUS |
| **SIHD** | Sistema de Informações Hospitalares Descentralizado |
| **APA** | Autorização de Procedimento Ambulatorial — autorização prévia para procedimentos de média/alta complexidade |
| **PAP** | Produção Ambulatorial Paga — registro de produção efetivamente paga pelo gestor |
| **PRD** | Produção Ambulatorial (tabela `s_prd`) — registro bruto de todos os procedimentos apresentados |
| **CBO** | Classificação Brasileira de Ocupações — código que identifica a categoria profissional que executou o procedimento |
| **CISMETRO** | Identificador de registro de equipe de saúde (lookup na tabela `cismetro`, JOIN via `prd_pa`) |
| **CNES** | Cadastro Nacional de Estabelecimentos de Saúde — código único do prestador (estabelecimento de saúde) |
| **Competência** | Mês/ano de referência da produção no formato `AAAAMM` (ex: `202301` = Janeiro 2023). Filtro obrigatório em todas as queries |
| **Prestador** | Estabelecimento de saúde (hospital, clínica, UBS) identificado pelo CNES; tabela `prestador` |
| **Procedimento** | Código do procedimento médico/odontológico realizado (tabela SIGTAP / `procedimento`) |
| **Grupo** | 2 primeiros dígitos do código `prd_pa` — categoria hierárquica de procedimento |
| **Subgrupo** | 4 primeiros dígitos do código `prd_pa` |
| **Forma** | 5 primeiros dígitos do código `prd_pa` — subcategoria da forma de organização |
| **PA** | Código do procedimento ambulatorial — campo `prd_pa` em `s_prd` (10 dígitos, VARCHAR) |
| **QT_A / VL_A** | Quantidade/Valor **Aprovado** — o que o gestor efetivamente aprovou para pagamento |
| **QT_P / VL_P** | Quantidade/Valor **Apresentado** — o que o prestador enviou/solicitou |
| **Lacuna** | Diferença entre apresentado e aprovado (`VL_P - VL_A`); principal métrica de auditoria |
| **Faturamento** | Relatório de valores aprovados agrupado por hierarquia: prestador → tipo → grupo → subgrupo → forma → procedimento |
| **Rubrica** | Classificação contábil de despesa; tabela `s_rub`, campo `prd_rub` |

## Tabelas Core (Imutáveis)

| Tabela | Conteúdo | Volume | Motor |
|--------|----------|--------|-------|
| `s_prd` | Produção SIA (registros de procedimentos) | ~6.3M rows | InnoDB |
| `s_pap` | Produção APAC paga | 0 rows (ainda) | InnoDB |
| `s_apa` | Autorizações de procedimento (APAC) | 0 rows (ainda) | InnoDB |
| `prestador` | Cadastro de estabelecimentos | ~80 rows | MyISAM |
| `procedimento` | Tabela SIGTAP | ~3.160 rows | InnoDB |
| `cbo` | Classificação de ocupações | ~500 rows | InnoDB |
| `cismetro` | Equipes de saúde | pequeno | InnoDB |
| `s_rub` | Rubricas contábeis | pequeno | MyISAM |

## Tabelas Auxiliares (Gerenciadas pelo v3)

| Tabela | Conteúdo |
|--------|----------|
| `report_job` | Fila de jobs assíncronos |
| `report_result_header` | Metadados de cada resultado (colunas, TTL, caminho de export) |
| `report_result_rows` | Linhas do resultado armazenadas em JSON |

## Termos Técnicos do Projeto

| Termo | Significado |
|-------|-------------|
| **Field Catalog** | Whitelist de campos disponíveis para query dinâmica, em `v3-backend/src/sia/field-catalog.ts` |
| **`_display` alias** | Convenção: campos de lookup retornam uma coluna extra `<fieldId>_display` com o nome legível (ex: `prd_uid_display: "HOSPITAL REGIONAL XYZ"`) |
| **filterOnly** | Campo disponível apenas em filtros WHERE, não como coluna selecionável no SELECT |
| **displayOnly** | Campo sempre incluído no retorno sem precisar ser declarado no `select[]` |
| **isAggregate** | Campo numérico que usa `SUM()` quando GROUP BY está ativo (ex: `PRD_QT_A`, `PRD_VL_A`) |
| **Slice** | Unidade de entrega incremental do MVP. Slice 1 = SIA sync; Slice 2 = Faturamento job assíncrono |
| **Job** | Tarefa assíncrona executada pelo Worker; persiste em `report_job` com status `queued → running → done | failed` |
| **TTL** | Time-to-live dos resultados (7 dias para resultados; 2 dias para arquivos de export) |
| **Strangler Fig** | Padrão de migração gradual: o Node.js assume funcionalidades uma a uma sem derrubar o Laravel legado |
| **Delta** | Diferença entre totais do Node.js vs Laravel. Critério de aprovação: **delta = 0** |
| **STORED GENERATED columns** | Colunas `grupo`, `subgrupo`, `forma` em `s_prd` geradas automaticamente pelo MySQL a partir de `prd_pa` (não aparecem no dump `producao.sql` mas existem no banco live) |
