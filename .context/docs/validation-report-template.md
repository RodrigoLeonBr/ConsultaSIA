# Relatório de Validação (Template)

**Data de Execução:** [DD/MM/AAAA]
**Versão/Branch:** [Node v3-backend] vs [Laravel Legacy]
**Avaliador:** [Nome/Automation]

## Tempos de Resposta (p95)
| Operação Node.js | Tempo Esperado | Tempo Medido | Status |
|---|---|---|---|
| Consulta paginada (Grid Result) | < 500ms | [___] ms | [ ] Pass |
| Criação de Job Pesado (POST) | < 150ms | [___] ms | [ ] Pass |
| Tempo de Execução Total do Job_03 | N/A | [___] s | [ ] Pass |

---

## Caso de Teste 1: Produção por Prestador (Síncrono)
**Filtros**: CMP = 202301, Ativo = 1

| Métrica | Valor Laravel | Valor Node.js | Diferença (Delta) | Resultado |
|---|---|---|---|---|
| Soma Quantidade (QT_A) | [Valor L] | [Valor N] | [Delta] | [ ] PASS / [ ] FAIL |
| Soma Monetária (VL_FED) | [Valor L] | [Valor N] | [Delta] | [ ] PASS / [ ] FAIL |

---

## Caso de Teste 2: Top Procedimentos (Síncrono)
**Filtros**: CMP = 202302

| Rank Procedimento (Top 1) | Ocorrências Laravel | Ocorrências Node.js |
|---|---|---|
| Código: [YYYYYYYYY] | [Valor L] | [Valor N] | 
| **Diferença (Delta)** | | | [Delta] |

**Resultado:** [ ] PASS / [ ] FAIL

---

## Caso de Teste 3: Matching Divergências (Job Assíncrono)
**Filtros**: Emissão Q1/2023, Com ERRO de importação.

| Métrica | Valor Laravel | Valor Node.js | Diferença (Delta) | Resultado |
|---|---|---|---|---|
| Total de Divergências Identificadas | [Valor L] | [Valor N] | [Delta] | [ ] PASS / [ ] FAIL |

**Verificação Adicional do Job Engine:**
- [ ] O Job foi criado na tabela MySQL local `report_job`.
- [ ] O Status progrediu visivelmente de `QUEUED` -> `RUNNING` -> `COMPLETED`.
- [ ] Os resultados salvos na paginação `report_result_rows` correspondem exatamente à quantidade de linhas de validação.

---

## Status Final da Bateria
[ ] **APROVADO** (Sem divergências em totalizadores de tela e lógicos)
[ ] **REPROVADO** (Relatar ID da issue ou inconsistência no banco de dados abaixo)

*Notas / Pendências Observadas durante a execução local:*
- (Se algo falhou em ler e mockar, registre o porquê aqui).
