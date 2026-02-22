# Plano de Validação (Legado vs V3) - MVP Fase 1

O objetivo desta etapa da validação é assegurar que o novo sistema em Node.js produza os **mesmos totalizadores** (quantidades e valores) que o sistema legado PHP/Laravel, garantindo exatidão, sem que as tabelas core (`producao.sql`) do legado sejam perturbadas.

## Relatórios Alvo do MVP (3 Casos de Teste)

### 1. Produção por Prestador (Síncrono / Grid)
- **Descrição**: Listagem de total de procedimentos e valor faturado por instituição de saúde (`prestador`).
- **Tabelas Core Envolvidas**: `s_pap`, `prestador`.
- **Filtros Fixos**:
  - Competência (`PAP_CMP`): "202301"
  - UF (`APA_UFPCN`): "SP" (via Join opcional se basear no prestador local)
  - `ativo` (prestador): 1
- **Métricas Esperadas (Totalizadores)**: Soma de `PAP_QT_A` (Quantidade Aprovada) e Soma de `PAP_VL_FED` (Valor Federal).

### 2. Top Procedimentos (Síncrono / Grid)
- **Descrição**: Ranking dos procedimentos (SIGTAP/Cismetro) mais faturados.
- **Tabelas Core Envolvidas**: `s_pap`.
- **Filtros Fixos**:
  - Competência (`PAP_CMP`): "202302"
- **Métricas Esperadas (Totalizadores)**: Top 10 códigos `PAP_PA` agrupados pela Contagem Absoluta de ocorrências descendente.

### 3. Matching / Divergência Básica (Assíncrono / Job Pesado)
- **Descrição**: Processamento em pipeline longo que cruza as APACs com guias de prestação locais à procura de IDs com problemas ou divergência de valores.
- **Tabelas Core Envolvidas**: `s_apa`, `s_pap`.
- **Filtros Fixos**:
  - `APA_EMISSA`: "202301" a "202303"
  - `PAP_FLER` != '0' (Erro ou alerta marcado na importação)
- **Métricas Esperadas**: 
  - Total de registros com divergências de valor >= R$0,01.
  - O Job finaliza com `status: COMPLETED` em `report_job`.

## Critérios de Aprovação

1. **Exatidão Absoluta**: A diferença (Delta) entre as somas de valor (`PAP_VL_FED`) e contagem (`PAP_QT_A`) entre o Laravel legado e o Node v3 deve ser **EXATAMENTE 0** (zero). Qualquer divergência não justificada significa reprovação do ORM/Queries novas.
2. **Paginação Eficiente**: O p95 das consultas síncronas/Server-side DataGrid no Node.js (`GET /reports/results...`) não deve exceder **500ms** na rede local.
3. **Escalabilidade Vertical do Job**: O `POST /reports/jobs` (criação da rotina) deve devolver `202 Accepted` em p95 **<150ms**. O tempo total do robô processando as milhares de linhas dependerá do hardware, mas o status nunca deve ficar preso indefinidamente em `running`.
