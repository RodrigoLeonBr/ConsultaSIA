# ConsultaProd — CLAUDE.md

> **Hub de navegação para AI.** Leia isto primeiro. Siga os links para detalhes.
> Última atualização: 2026-06-21

---

## 1. Navegação Rápida

| Preciso... | Ler este arquivo |
|---|---|
| Escrever query (tabelas, colunas, índices, anomalias) | [`.context/docs/data-contract.md`](.context/docs/data-contract.md) + `producao.sql` |
| Entender ou modificar um relatório | [`.context/docs/legacy-relatorios-spec.md`](.context/docs/legacy-relatorios-spec.md) |
| Localizar rota, URI ou nome de rota | [`.context/docs/routes-map.md`](.context/docs/routes-map.md) |
| Adicionar/alterar exportação (Excel/PDF/CSV) | [`.context/docs/exports-pattern.md`](.context/docs/exports-pattern.md) |
| Ver o que está em andamento agora | [`.context/docs/current-work.md`](.context/docs/current-work.md) |
| Vocabulário do domínio (SIA, APAC, BPI, SUS) | [`.context/docs/glossary.md`](.context/docs/glossary.md) |
| Visão arquitetural v3 futura (NestJS/React) | [`.context/docs/architecture.md`](.context/docs/architecture.md) |
| Performance + índices | [`.context/docs/performance-playbook.md`](.context/docs/performance-playbook.md) |
| Segurança | [`.context/docs/security.md`](.context/docs/security.md) |

---

## 2. Identidade do Projeto

- **Nome**: ConsultaProd — Sistema de Gestão e Relatórios para Unidades de Saúde
- **Stack ativa (produção hoje)**: Laravel 12.30.1 / PHP 8.2.12 / MariaDB 10.4 / XAMPP Windows
- **Banco**: `producao` (127.0.0.1:3306) — credenciais em `.env`
- **Volume**: 5.9M+ registros em `s_prd`
- **Dev URL**: `http://localhost:8000` (`php artisan serve`) ou `http://192.168.5.130/consultasia/public`
- **Estratégia**: Strangler Fig — Laravel em produção; migração futura Node.js/React (v3) é aspiracional

---

## 3. Stack

| Camada | Tecnologia |
|---|---|
| Framework | Laravel 12 + Breeze (auth) + Sanctum + Livewire 3 |
| Frontend | Blade + Tailwind CSS (CDN) + Alpine.js (CDN) |
| Exportações | Maatwebsite/Excel (xlsx) + Barryvdh/DomPDF (pdf) |
| DB | MariaDB 10.4 (engines mistas MyISAM/InnoDB — ver data-contract.md) |
| Auth | Username/password + roles (admin, operator) |
| **Sem** | Redis / Node.js / SSR / Docker (em produção atual) |

---

## 4. Regras Invioláveis

### 4.1 Schema DB — NUNCA alterar tabelas core

**Tabelas core (schema IMUTÁVEL — contrato DATASUS):**
```
s_prd        s_apa        s_bpi        s_pap        s_rub
prestador    procedimento cbo          forma        cismetro
```
- **PROIBIDO**: `ALTER TABLE`, `DROP`, qualquer DDL nessas tabelas
- **PROIBIDO**: Migrations automáticas que toquem tabelas core
- Em produção: migrations automáticas desabilitadas

**Tabelas auxiliares (podem ser modificadas via migration):**
```
report_job   report_result_header   report_result_rows
users        cache / sessions       jobs / failed_jobs
```

### 4.2 Queries — Obrigações

- SEMPRE `CAST` em campos numéricos armazenados como VARCHAR (ver padrão abaixo)
- SEMPRE filtrar por `competência` — sem ele o sistema recusa (5.9M rows sem filtro = timeout)
- NUNCA carregar `s_prd` sem filtro de competência

**CAST obrigatório:**
```sql
-- s_prd (usa UNSIGNED para qty, DECIMAL para valor)
SUM(CAST(sp.PRD_QT_P AS UNSIGNED))
SUM(CAST(sp.PRD_QT_A AS UNSIGNED))
SUM(CAST(sp.PRD_VL_P AS DECIMAL(15,2)))
SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2)))

-- s_pap/APAC (usa DECIMAL para qty — divergência intencional do legado)
SUM(CAST(pap.PAP_QT_P AS DECIMAL(15,2)))
```

### 4.3 UI — Padrões fixos

- Filtros com botão **"Aplicar"** (nunca `onChange`)
- DataGrid sempre **server-side** (paginação/ordenação/filtros no backend)
- Não logar payloads grandes (proteger RAM)

---

## 5. Módulos e Controllers

Ver detalhes completos em [`.context/docs/routes-map.md`](.context/docs/routes-map.md).

| Módulo | URI base | Controller |
|---|---|---|
| Dashboard | `/dashboard` | `DashboardController` |
| Admin (usuários) | `/admin` | `AdminController` |
| Prestadores | `/prestador` | `PrestadorController` |
| Import Prestador | `/prestador-import` | `PrestadorImportController` |
| Procedimentos | `/procedimento` | `ProcedimentoController` |
| Import Procedimento | `/procedimento-import` | `ProcedimentoImportController` |
| CBO | `/cbo` | `CboController` |
| Cismetro | `/cismetro` | `CismetroController` |
| Relatório SIA dinâmico | `/relatorios` | `RelatorioController` |
| Relatório APAC | `/relatorios/apac` | `RelatorioApacController` |
| Relatório BPI | `/relatorios/bpi` | `RelatorioBpiController` |
| Faturamento por Prestador | `/relatorios/faturamento-prestador` | `FaturamentoPrestadorController` |
| SAPA (s_apa CRUD) | `/sapa` | `SApaController` |
| SPAP (s_pap CRUD) | `/spap` | `SPapController` |
| SRUB (s_rub CRUD) | `/srub` | `SRubController` |
| Auth (Breeze) | `/login`, `/logout`, etc. | `Auth\*` |

**Total: 123 rotas** — lista completa em `routes-map.md`

---

## 6. Estrutura de Arquivos Chave

```
app/Http/Controllers/
  RelatorioController.php              ← relatório SIA dinâmico (s_prd)
  RelatorioApacController.php          ← relatório APAC (s_pap + s_apa)
  RelatorioBpiController.php           ← relatório BPI (s_bpi)
  FaturamentoPrestadorController.php   ← faturamento hierárquico (s_prd)
  DashboardController.php
  AdminController.php
  PrestadorController.php + PrestadorImportController.php
  ProcedimentoController.php + ProcedimentoImportController.php
  SApaController.php / SPapController.php / SRubController.php
  Auth/                                ← Breeze controllers
  
app/Http/Middleware/
  CheckActive.php                      ← bloqueia usuários inativos
  CheckRole.php                        ← controle de acesso por role
  EnsurePasswordChanged.php            ← força troca de senha

app/Exports/
  RelatorioExport.php                  ← export lista SIA
  RelatorioApacExport.php              ← export lista APAC
  MatrixReportExport.php               ← export matriz pivot (SIA/BPI)
  MatrixReportByPrestadorExport.php    ← export matriz por prestador (APAC)
  Concerns/
    FormatsBrazilianExcelColumns.php   ← trait: formatação BR em xlsx

app/Support/
  BrazilianNumberFormatter.php         ← helper: formatação numérica R$

public/js/
  relatorios-base.js                   ← JS frontend do sistema de relatórios

resources/views/
  relatorios/                          ← todas as views de relatório
  dashboard.blade.php
  layouts/

routes/web.php                         ← todas as 123 rotas

producao.sql                           ← FONTE DA VERDADE do schema DB
.context/docs/                         ← documentação de referência
```

---

## 7. Auth e Permissões

| Role | Acesso |
|---|---|
| `admin` | Tudo — inclusive `/admin/*` (gestão de usuários) |
| `operator` | Tudo exceto `/admin/*` |

**Middleware chain** (rotas protegidas):
```
web → Authenticate → CheckActive → EnsurePasswordChanged → CheckRole:role
```

**Credenciais dev:**
- `admin` / `admin123` (role: admin)
- `test` / `123456` (role: admin)

---

## 8. Comandos Comuns

```bash
# Desenvolvimento
php artisan serve                    # servidor :8000
php artisan route:list               # todas as 123 rotas
php artisan migrate:status           # status das migrations
php artisan about                    # info do sistema

# Cache (sempre limpar antes de testar mudanças de config/rota)
php artisan optimize:clear           # limpa tudo
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Produção (após deploy)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 9. Orientação de Trabalho

**Antes de qualquer mudança, seguir esta ordem:**

1. Identifique o módulo → seção 5 acima ou `routes-map.md`
2. Se envolve query → leia `data-contract.md` e confirme coluna em `producao.sql`
3. Se envolve exportação → leia `exports-pattern.md`
4. Se envolve relatório → leia seção relevante de `legacy-relatorios-spec.md`
5. Implemente em **1-2 arquivos por vez**
6. **Explique o que vai mudar ANTES de mudar**

---

## 10. Trabalho Ativo

Ver [`.context/docs/current-work.md`](.context/docs/current-work.md) para status detalhado do sprint atual.

**Resumo (2026-06-21):**
- Refactor exports: `Concerns/FormatsBrazilianExcelColumns.php` + `Support/BrazilianNumberFormatter.php`
- `MatrixReportExport`, `MatrixReportByPrestadorExport`, `RelatorioApacExport`, `RelatorioExport` modificados
- `RelatorioController.php` e `relatorios-base.js` com mudanças ativas

---

## 11. Índice de Documentos `.context/docs/`

| Arquivo | Conteúdo |
|---|---|
| `data-contract.md` | Tabelas core, tipos, anomalias MyISAM/InnoDB, riscos sem FK |
| `legacy-relatorios-spec.md` | Spec completa dos 4 relatórios: campos, SQL, operadores, CASTs |
| `routes-map.md` | 123 rotas organizadas por módulo (método + URI + nome + controller) |
| `exports-pattern.md` | Classes Export, Concerns, Support — padrão e como adicionar novo |
| `current-work.md` | Sprint atual: arquivos modificados, objetivo, próximos passos |
| `glossary.md` | Vocabulário SIA/APAC/BPI/SUS/DATASUS |
| `architecture.md` | Visão v3 futura (NestJS + React — aspiracional) |
| `performance-playbook.md` | Índices, queries pesadas, estratégias de performance |
| `security.md` | Práticas de segurança do projeto |
| `prd.md` | Product Requirements Document completo |
| `data-flow.md` | Fluxo de dados entre módulos |
| `development-workflow.md` | Workflow de desenvolvimento |
| `sia-field-catalog.md` | Catálogo completo de campos SIA |
