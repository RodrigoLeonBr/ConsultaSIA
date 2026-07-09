# ConsultaProd

Sistema de gestão e relatórios para unidades de saúde (SUS/DATASUS), desenvolvido em **Laravel 12** com dados reais de produção hospitalar.

**Stack em produção:** Laravel 12 · PHP 8.2+ · MariaDB 10.4 · Blade · Tailwind CSS (CDN) · Alpine.js (CDN)

---

## Funcionalidades

### Relatórios dinâmicos

| Módulo | Tabela(s) | URI | Descrição |
|--------|-----------|-----|-----------|
| SIA Produção | `s_prd` | `/relatorios` | Gerador dinâmico com lista e matriz por competência |
| APAC | `s_pap` + `s_apa` | `/relatorios/apac` | Relatórios de APAC com matriz |
| BPI | `s_bpi` | `/relatorios/bpi` | Relatórios BPI com matriz |
| AIH Internações | `s_aih` | `/relatorios/aih` | Relatórios SIH — cabeçalho de internações |
| AIH Procedimentos | `s_aih_pa` | `/relatorios/aih-pa` | Relatórios SIH — itens por internação |
| Faturamento por Prestador | `s_prd` | `/relatorios/faturamento-prestador` | Relatório hierárquico analítico |

Todos os relatórios suportam exportação **Excel (.xlsx)**, **PDF** e **CSV**, com formatação numérica brasileira centralizada em `BrazilianNumberFormatter`.

### Cadastros e CRUDs

| Módulo | URI |
|--------|-----|
| Prestadores | `/prestador` |
| Procedimentos | `/procedimento` |
| CBO | `/cbo` |
| Cismetro | `/cismetro` |
| SAPA (`s_apa`) | `/sapa` |
| SPAP (`s_pap`) | `/spap` |
| SRUB (`s_rub`) | `/srub` |

### Importações

| Tipo | URI | Formato |
|------|-----|---------|
| Prestadores | `/prestador-import` | DBF (DATASUS) |
| Procedimentos | `/procedimento-import` | DBF (DATASUS) |
| AIH | `/aih-import` | Arquivo texto SIH |

### Administração

- **Dashboard** com estatísticas reais (`/dashboard`)
- **Gestão de usuários** — apenas role `admin` (`/admin`)
- **Autenticação** via Laravel Breeze (login, troca de senha obrigatória, roles)

---

## Requisitos

- PHP **8.2+** com extensões: `gd`, `mbstring`, `openssl`, `curl`, `zip`, `xml`, `simplexml`, `dom`
- **MariaDB 10.4+** ou MySQL 5.7+
- **Composer 2.x**
- XAMPP (recomendado para ambiente local Windows)
- **Node.js é opcional** — o frontend usa Tailwind e Alpine via CDN

---

## Instalação

### 1. Banco de dados

1. Inicie o MySQL/MariaDB (XAMPP).
2. Crie o banco `producao`.
3. Importe o schema e dados via `producao.sql` (fonte da verdade do contrato de dados).

> Em produção, as **tabelas core** (`s_prd`, `s_apa`, `s_bpi`, `s_pap`, `s_rub`, `prestador`, `procedimento`, `cbo`, `forma`, `cismetro`) **não devem ser alteradas** via migrations. Migrations servem apenas para tabelas auxiliares (`users`, `cache`, `jobs`, `s_aih`, etc.).

### 2. Aplicação Laravel

```bash
cd consultasia
composer install
cp .env.example .env   # ajustar credenciais
php artisan key:generate
php artisan migrate    # tabelas auxiliares e s_aih
```

Configure o `.env`:

```env
APP_NAME=ConsultaProd
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=producao
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

### 3. Permissões

Garanta escrita em:

```
storage/
bootstrap/cache/
```

### 4. Iniciar

**Desenvolvimento (Artisan):**
```bash
php artisan serve
# http://localhost:8000
```

**XAMPP (Apache):**
- Document root: `consultasia/public`
- Exemplo: `http://localhost/consultasia/public`

---

<a id="atualizacao"></a>

## Atualização (banco já existente)

Use esta seção quando o sistema **já está em produção** com `producao.sql` importado e você precisa aplicar apenas o que mudou no código, **sem recriar tabelas nem perder dados**.

### Antes de qualquer coisa

```bash
php artisan migrate:status
```

> **Atenção:** `php artisan migrate` sem filtro **falha** se o banco já veio do `producao.sql` (tabelas `users`, `s_prd`, `cismetro`, etc. já existem). Rode **somente** as migrations pendentes que ainda não foram aplicadas no seu ambiente, uma a uma com `--path`.

### Migrations incrementais (após import do `producao.sql`)

| Migration | O que altera | Verificar se já existe |
|-----------|--------------|------------------------|
| `2026_06_21_000000_create_s_aih_tables` | Cria `s_aih` e `s_aih_pa` (módulo AIH/SIH) | `SHOW TABLES LIKE 's_aih';` |
| `2026_06_21_100000_add_fields_to_s_aih` | Campos extras em `s_aih` (datas, idade, valor total) | `SHOW COLUMNS FROM s_aih LIKE 'VALOR_TOTAL_AIH';` |
| `2026_06_22_152310_add_aih_values_to_procedimento_table` | `VL_SP`, `VL_SH` em `procedimento` + descrição até 255 chars | `SHOW COLUMNS FROM procedimento LIKE 'VL_SP';` |
| `2026_06_24_000001_create_sus_paulista_table` | Cria tabela `sus_paulista` (tabela paulista) | `SHOW TABLES LIKE 'sus_paulista';` |
| `2025_09_17_184500_add_must_change_password_to_users_table` | `must_change_password` e `password_changed_at` em `users` | `SHOW COLUMNS FROM users LIKE 'must_change_password';` |
| `2025_10_23_130415_update_prestador_relatorio_field_size` | Campo `prestador.relatorio` de 12 → 40 chars | `SHOW COLUMNS FROM prestador LIKE 'relatorio';` |
| `2025_12_17_152908_add_matrix_performance_indexes` | Índices de performance em `s_prd` (matriz) | `SHOW INDEX FROM s_prd WHERE Key_name LIKE 'idx_s_prd_%';` |
| **`2026_07_09_192229_add_tipo_valor_to_cismetro_table`** | Coluna `tipo_valor` em `cismetro` | `SHOW COLUMNS FROM cismetro LIKE 'tipo_valor';` |

### Comandos — copiar e rodar o que faltar

Substitua `CAMINHO` pelo arquivo da migration. Rode apenas as que `migrate:status` mostrar como **Pending** e que a verificação SQL confirmar como ausentes.

```bash
# Exemplo: aplicar uma migration específica
php artisan migrate --path=database/migrations/CAMINHO.php --no-interaction
```

**Ordem sugerida** (respeite dependências — AIH antes dos campos extras):

```bash
php artisan migrate --path=database/migrations/2026_06_21_000000_create_s_aih_tables.php --no-interaction
php artisan migrate --path=database/migrations/2026_06_21_100000_add_fields_to_s_aih.php --no-interaction
php artisan migrate --path=database/migrations/2026_06_22_152310_add_aih_values_to_procedimento_table.php --no-interaction
php artisan migrate --path=database/migrations/2026_06_24_000001_create_sus_paulista_table.php --no-interaction
php artisan migrate --path=database/migrations/2025_09_17_184500_add_must_change_password_to_users_table.php --no-interaction
php artisan migrate --path=database/migrations/2025_10_23_130415_update_prestador_relatorio_field_size.php --no-interaction
php artisan migrate --path=database/migrations/2025_12_17_152908_add_matrix_performance_indexes.php --no-interaction
php artisan migrate --path=database/migrations/2026_07_09_192229_add_tipo_valor_to_cismetro_table.php --no-interaction
```

Confirme ao final:

```bash
php artisan migrate:status
```

### Atualização 2026-07-09 — `cismetro.tipo_valor`

Nova coluna de classificação na tabela `cismetro`:

| `tipo_valor` | Significado |
|---|---|
| `1` | Município / Geral (padrão) |
| `2` | Prestador (descrição contém "PRESTADOR") |
| `0` | Código duplicado (2º registro em diante com mesmo `codigo`) |

**1. Migration** (se ainda não aplicada):

```bash
php artisan migrate --path=database/migrations/2026_07_09_192229_add_tipo_valor_to_cismetro_table.php --no-interaction
```

**2. Popular/classificar registros existentes:**

```bash
php artisan cismetro:classificar
```

Saída esperada (valores variam conforme a base):

```
Classificando registros da cismetro...
Total de registros: 1654
Classificados como Municipio (tipo_valor=1): ...
Classificados como Prestador (tipo_valor=2): ...
Marcados como duplicado (tipo_valor=0): ...
Classificacao concluida!
```

**3. Revisar duplicados** (opcional):

```sql
SELECT id, codigo, descricao, valor, tipo_valor
FROM cismetro
WHERE tipo_valor = 0
ORDER BY codigo;
```

> O command é **idempotente** — pode rodar de novo após importar novos dados de cismetro. Não altera tabelas core além da coluna `tipo_valor`.

### Migrations que NÃO rodar em banco importado

As migrations que **criam** tabelas core (`s_prd`, `s_apa`, `cbo`, `prestador`, `procedimento`, `cismetro`, etc.) servem para ambiente zerado. Se você importou `producao.sql`, marque-as como já executadas ou ignore — os dados e o schema já estão lá.

### Após atualizar o código

```bash
composer install --no-dev   # produção
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Credenciais de desenvolvimento

| Usuário | Senha | Role |
|---------|-------|------|
| `admin` | `admin123` | admin |
| `test` | `123456` | admin |

> Altere essas credenciais em ambientes reais.

---

## Estrutura do projeto

```
consultasia/
├── app/
│   ├── Console/Commands/    # Comandos Artisan (ex.: cismetro:classificar)
│   ├── Http/Controllers/    # Controllers (relatórios, CRUDs, auth, imports)
│   ├── Http/Middleware/     # CheckRole, CheckActive, EnsurePasswordChanged
│   ├── Models/              # SPrd, SApa, SPap, Prestador, User, etc.
│   ├── Exports/             # Excel/PDF (Maatwebsite, DomPDF)
│   ├── Services/            # Importação DBF e AIH
│   └── Support/             # BrazilianNumberFormatter
├── bootstrap/               # Bootstrap Laravel 12
├── config/                  # Configurações da aplicação
├── database/
│   ├── migrations/          # Tabelas auxiliares (não core DATASUS)
│   └── seeders/
├── public/                  # Entry point web (index.php)
│   └── js/relatorios-base.js
├── resources/views/         # Templates Blade
├── routes/
│   ├── web.php              # ~123 rotas
│   └── auth.php
├── storage/                 # Logs, cache, sessões (gravável)
├── tests/                   # PHPUnit
├── producao.sql             # Schema de referência do banco
├── .context/docs/           # Documentação técnica para desenvolvimento
└── vendor/                  # Dependências PHP (composer install)
```

---

## Autenticação e permissões

| Role | Acesso |
|------|--------|
| `admin` | Tudo, inclusive `/admin/*` |
| `operator` | Tudo exceto gestão de usuários |

**Middleware chain** nas rotas protegidas:
```
web → Authenticate → CheckActive → EnsurePasswordChanged → CheckRole
```

---

## Regras importantes de queries

- Sempre filtrar por **competência** em relatórios sobre `s_prd` (5,9M+ registros sem filtro = timeout).
- Usar `CAST` em campos numéricos armazenados como `VARCHAR`:

```sql
SUM(CAST(sp.PRD_QT_P AS UNSIGNED))
SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2)))
SUM(CAST(pap.PAP_QT_P AS DECIMAL(15,2)))  -- APAC
```

Detalhes completos em `.context/docs/data-contract.md` e `.context/docs/legacy-relatorios-spec.md`.

---

## Pacotes principais

| Pacote | Uso |
|--------|-----|
| Laravel Breeze | Autenticação |
| Maatwebsite/Excel | Exportação .xlsx |
| Barryvdh/DomPDF | Exportação PDF |
| hisamu/php-xbase | Leitura de arquivos DBF |

---

## Comandos úteis

```bash
# Desenvolvimento
php artisan serve
php artisan route:list
php artisan about

# Cache
php artisan optimize:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Produção (após deploy)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Testes
php artisan test

# Cismetro — classificar tipo_valor após migration ou import
php artisan cismetro:classificar
```

---

## URLs principais

| Página | URL (Artisan) |
|--------|---------------|
| Login | http://localhost:8000/login |
| Dashboard | http://localhost:8000/dashboard |
| Relatório SIA | http://localhost:8000/relatorios |
| Relatório APAC | http://localhost:8000/relatorios/apac |
| Relatório BPI | http://localhost:8000/relatorios/bpi |
| Relatório AIH | http://localhost:8000/relatorios/aih |
| Faturamento | http://localhost:8000/relatorios/faturamento-prestador |

---

## Documentação para desenvolvedores

| Arquivo | Conteúdo |
|---------|----------|
| `CLAUDE.md` | Hub de navegação do projeto |
| `.context/docs/routes-map.md` | Mapa completo das rotas |
| `.context/docs/data-contract.md` | Contrato de dados e tabelas |
| `.context/docs/legacy-relatorios-spec.md` | Spec dos relatórios |
| `.context/docs/exports-pattern.md` | Padrão de exportações |
| `.context/docs/glossary.md` | Vocabulário SUS/DATASUS |

---

## Troubleshooting

| Problema | Solução |
|----------|---------|
| Erro 500 | Verificar `storage/logs/laravel.log` e permissões de `storage/` |
| Exportação PDF falha | Habilitar extensão `gd` no `php.ini` |
| Relatório lento | Confirmar filtro de competência; ver `.context/docs/performance-playbook.md` |
| Sessão perdida | `php artisan optimize:clear` |

---

## Licença

MIT

---

**ConsultaProd** — Laravel 12 · PHP 8.2+ · MariaDB · 5,9M+ registros em produção
