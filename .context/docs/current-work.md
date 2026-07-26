# Trabalho Atual — Sprint Ativa

> Atualizar esta seção ao iniciar/completar trabalho significativo.
> Última atualização: 2026-07-26

---

## Em andamento / concluído recentemente

### AIH — campos SIHD estendidos + docs/DB
- Campos novos em `s_aih`: `IDENT_AIH`, `MUN_RESIDENCIA`, `CARATER_INTERNACAO`, `DIAG_SECUNDARIO`, `CID_OBITO`
- UK `uk_aih` = `(AIH, CNES, COMPETENCIA, DT_SAIDA)` (reabertura UTI)
- Importação: `AihTextImportService` (23/22 colunas)
- Script SQL: `database/sql/atualizar_producao_2026_07.sql`
- Docs alinhados: `docs/sih-aih-schema-for-llm.md`, data-contract, routes-map, glossary

### Matriz / SUS Paulista (diff local)
- `HasMatrixReport`: meta de eixos/métricas na resposta JSON da matriz
- `HasSusPaulistaReport`: vigência via subquery `MAX(competencia_inicial)` cobrindo a competência do registro

### Refactor de exportações (anterior)
- Formatação numérica em `BrazilianNumberFormatter`
- Dashboard consolidado em `HomeController` + `home.blade.php`

---

## Próximos Passos

- [ ] Aplicar `atualizar_producao_2026_07.sql` (ou migrations 2026_07_10_*) nos ambientes que ainda não têm os campos estendidos
- [ ] Limpar `welcome.blade.php` (bloco register morto)
- [ ] Regenerar ou remover `_ide_helper.php` obsoleto

---

## Histórico Recente (commits)

| Hash | Mensagem |
|---|---|
| `d52c0170` | feat(aih): estender importação SIHD e relatórios com novos campos |
| `410773bc` | feat(sus-paulista): adicionar seeders Tabela SUS Paulista SIA e SIH |
| `3d8f67a8` | feat(prestador): substituir coluna CNPJ por relatório na listagem |
| `a3f0…` | (ver `git log`) |
