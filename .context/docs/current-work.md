# Trabalho Atual — Sprint Ativa

> Atualizar esta seção ao iniciar/completar trabalho significativo.
> Última atualização: 2026-07-09

---

## Concluído recentemente

### Refactor de exportações
- Formatação numérica centralizada em `app/Support/BrazilianNumberFormatter.php`
- Classes Export (`RelatorioExport`, `RelatorioApacExport`, `MatrixReportExport`, `MatrixReportByPrestadorExport`) usam `parseForExcel()` e `columnFormatsForHeaders()`
- Trait `FormatsBrazilianExcelColumns` removida (lógica absorvida pelo helper)

### Consolidação de dashboard e dependências
- `HomeController` + `home.blade.php` substituem `DashboardController`
- `/painel` → redirect 301 para `/dashboard`
- Removidos: Livewire, Sanctum (dependência direta), Larapex Charts
- Registro público e verificação de e-mail removidos do auth

### Field configs unificados
- Padrão `getAllFieldConfigs()` nos controllers de relatório
- `getFields()` e `getFieldConfig()` delegam para `getAllFieldConfigs()`

---

## Próximos Passos

- [ ] Atualizar documentação restante (glossary, exporta.md) se necessário
- [ ] Limpar `welcome.blade.php` (bloco register morto)
- [ ] Regenerar ou remover `_ide_helper.php` obsoleto (refs Livewire)

---

## Histórico Recente (commits anteriores)

| Hash | Mensagem |
|---|---|
| `6a73f73c` | upload de prestador e procedimento |
| `75a18e0b` | data movimento |
| `84b90a5b` | relatorio producao individualizado |
| `f18a6ad4` | feat: enhance reports and SIA services with new functionalities |
| `24b226ca` | feat: fullstack monorepo setup with concurrently |
