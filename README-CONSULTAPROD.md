# Sistema ConsultaProd - Laravel 12

Sistema de Gerenciamento e Relatórios Dinâmicos para Unidades de Saúde desenvolvido em Laravel 12 com dados reais de produção hospitalar.

## 📋 Requisitos do Sistema

- PHP 8.2+
- MySQL 5.7+ ou MariaDB 10.3+
- Composer 2.0+
- XAMPP (recomendado para desenvolvimento local)
- **Node.js é OPCIONAL** - Sistema funciona com CDN

## 🚀 Instalação e Configuração

### 1. Configuração do Banco de Dados (XAMPP)

1. Inicie o XAMPP e ative MySQL
2. Acesse o phpMyAdmin (http://localhost/phpmyadmin)
3. Crie um banco de dados chamado `consultaprod`
4. **O sistema já possui migrations** - não precisa executar scripts SQL externos

### 2. Configuração do Laravel

1. Clone/copie o projeto Laravel:
```bash
cd consultaprod-php
```

2. Instale as dependências PHP:
```bash
composer install
```

3. Configure o arquivo `.env` (já configurado para MySQL):
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=consultaprod
DB_USERNAME=hospital
DB_PASSWORD=tebaldi
```

4. Execute as migrations:
```bash
php artisan migrate
```

### 3. Iniciar o Sistema

#### Opção 1: Via Artisan (Desenvolvimento)
```bash
php artisan serve
```
Acesse: http://localhost:8000

#### Opção 2: Via XAMPP (Produção Local)
- Coloque o projeto em `C:\xampp\htdocs\consultasia`
- Acesse: http://192.168.5.130/consultasia

### 4. Credenciais de Acesso

**Usuário Administrador:**
- Username: `admin`
- Password: `admin123`

**Usuário de Teste:**
- Username: `test`
- Password: `123456`

## 📦 Pacotes Instalados

### Principais
- **Laravel 12.x** - Framework principal
- **Laravel Breeze** - Autenticação completa com Blade views
- **Laravel Sanctum** - API authentication
- **Livewire 3.x** - Componentes dinâmicos
- **Tailwind CSS** - Framework CSS (via CDN)

### Utilitários
- **Maatwebsite/Excel** - Exportação de relatórios Excel (.xlsx)
- **Barryvdh/Laravel-DomPDF** - Geração de relatórios PDF
- **Alpine.js** - JavaScript reativo (via CDN)

## 🏗️ Estrutura do Projeto

### Principais Diretórios

```
consultaprod-php/
├── app/
│   ├── Http/Controllers/     # Controllers da aplicação
│   │   ├── Auth/            # Controllers de autenticação
│   │   ├── DashboardController.php    # Dashboard dinâmico
│   │   └── RelatorioController.php    # Sistema de relatórios
│   ├── Models/               # Models Eloquent
│   ├── Exports/             # Classes de exportação Excel/CSV
│   └── Http/Middleware/     # Middlewares customizados
├── resources/
│   ├── views/               # Templates Blade
│   │   ├── auth/           # Sistema de autenticação
│   │   ├── relatorios/     # Sistema de relatórios
│   │   ├── dashboard.blade.php  # Dashboard principal
│   │   └── layouts/        # Layouts responsivos
├── database/
│   ├── migrations/         # Estrutura completa do banco
│   └── seeders/           # Dados de teste
└── routes/
    └── web.php            # Todas as rotas do sistema
```

### Módulos Implementados

1. **Dashboard Dinâmico** - Estatísticas reais com 5.9M+ registros
2. **Sistema de Autenticação** - Login seguro com roles
3. **CBO (Ocupações)** - 2.812 códigos cadastrados
4. **Prestadores** - 76 unidades ativas
5. **Procedimentos** - 3.036 procedimentos SUS
6. **Relatórios Avançados** - Gerador dinâmico com filtros
7. **🆕 Faturamento por Prestador** - Relatório hierárquico analítico
8. **Exportações** - Excel, PDF, CSV com formatação

## 🔐 Sistema de Autenticação

### Funcionalidades Implementadas
- ✅ **Login seguro** com username/password
- ✅ **Controle de roles** (Admin/Operator)
- ✅ **Middleware de segurança** para rotas protegidas
- ✅ **Sessões persistentes** com driver file
- ✅ **Logout seguro** com invalidação de sessão

### Rotas de Autenticação
- **Login**: `/login`
- **Dashboard**: `/dashboard`
- **Logout**: Botão no menu superior

### Usuários Disponíveis

**Administrador:**
- Username: `admin`
- Password: `admin123`
- Role: `admin`
- Acesso: Completo ao sistema

**Operador de Teste:**
- Username: `test`
- Password: `123456`
- Role: `admin`
- Acesso: Completo ao sistema

## 🎨 Interface e UX

### Design System
- ✅ **Tailwind CSS** via CDN (sem necessidade de compilação)
- ✅ **Alpine.js** para interatividade
- ✅ **Layout responsivo** (mobile-first)
- ✅ **Componentes reutilizáveis** Blade
- ✅ **Ícones SVG** integrados

### Dashboard Dinâmico
- ✅ **Estatísticas reais** do banco de dados
- ✅ **Cards informativos** com links diretos
- ✅ **Atividade recente** carregada via AJAX
- ✅ **Indicadores visuais** de performance

### Sistema de Navegação
- ✅ **Menu principal** com todos os módulos
- ✅ **Breadcrumbs** contextuais
- ✅ **Links rápidos** nos cards do dashboard
- ✅ **Dropdown de usuário** com logout

## 📊 Sistema de Relatórios Avançados

### 🆕 Relatório de Faturamento por Prestador

#### Funcionalidades Principais
- ✅ **Filtro de Competência**: Dropdown com último período disponível
- ✅ **Filtro de Prestador**: Opcional, com opção "Todos os Prestadores"
- ✅ **Estrutura Hierárquica**: 6 níveis de agrupamento
- ✅ **Totalizações**: Em todos os níveis hierárquicos
- ✅ **Exportações**: PDF, Excel, CSV
- ✅ **Layout Limpo**: Sem prefixos desnecessários, colunas alinhadas

#### Estrutura Hierárquica
```
1. Prestador (Nome da Unidade)
   ├─ Total Geral do Prestador
   └─ 2. Tipo de Financiamento (ex: MAC, Atenção Básica)
       ├─ Total por Tipo de Financiamento
       └─ 3. Grupo (2 primeiros dígitos do procedimento)
           ├─ Total por Grupo
           └─ 4. Sub-grupo (4 primeiros dígitos)
               ├─ Total por Sub-grupo
               └─ 5. Forma de Organização (6 primeiros dígitos)
                   ├─ Total por Forma de Organização
                   └─ 6. Detalhe (Procedimentos individuais)
                       ├─ Código do Procedimento
                       ├─ Nome do Procedimento
                       ├─ Valor Unitário
                       ├─ Quantidade Apresentada
                       ├─ Valor Apresentado
                       ├─ Quantidade Aprovada
                       └─ Valor Aprovado
```

#### Campos de Dados
- **Quantidade Apresentada**: `sp.PRD_QT_P`
- **Valor Apresentado**: `sp.PRD_QT_P * proc.PA_TOTAL`
- **Quantidade Aprovada**: `sp.PRD_QT_A`
- **Valor Aprovado**: `sp.PRD_VL_A`

#### Acesso ao Relatório
- **Via Dashboard**: Clique em "Faturamento por Prestador"
- **URL Direta**: `/relatorios/faturamento-prestador`
- **Requisitos**: Usuário autenticado

### Gerador Dinâmico de Relatórios
- ✅ **Seleção de campos** via checkboxes
- ✅ **Filtros avançados** com múltiplos operadores
- ✅ **Agrupamento automático** para evitar duplicatas
- ✅ **Totalizadores** para campos numéricos
- ✅ **SQL visível** para debug e auditoria

### Campos Disponíveis
- **Data Competência** - Formato YYYY-MM
- **Prestador** - CNES + Nome da unidade
- **CBO** - Código + Descrição da ocupação
- **Procedimento** - Código SUS + Nome do procedimento
- **Quantidade** - Soma automática por agrupamento
- **Valor** - Soma automática em R$ formatado
- **Rubrica** - Tipo de financiamento
- **CID Principal** - Diagnóstico principal

### Filtros Avançados
- **Operadores**: =, >, <, >=, <=, contém, inicia com, entre
- **Tipos de dados**: Texto, número, data, moeda, lookup
- **Múltiplos filtros** com lógica AND
- **Interface modal** para configuração

### Exportações Profissionais
- ✅ **Excel (.xlsx)** - Formatação automática + totais
- ✅ **PDF** - Layout profissional com cabeçalho
- ✅ **CSV** - Separador ponto-vírgula + UTF-8 BOM
- ✅ **HTML** - Visualização em tabela responsiva

### Indicadores de UX
- ✅ **Loading spinners** durante processamento
- ✅ **Mensagens de erro** claras e informativas
- ✅ **Botões desabilitados** durante execução
- ✅ **Contadores** de registros encontrados

## 📈 Dados do Sistema (Produção)

### Volume de Dados Reais
- ✅ **5.988.427 registros** na tabela principal (s_prd)
- ✅ **76 prestadores** ativos cadastrados
- ✅ **3.036 procedimentos** SUS disponíveis
- ✅ **2.812 códigos CBO** de ocupações
- ✅ **70 rubricas** de financiamento

### Performance Otimizada
- ✅ **Queries indexadas** para consultas rápidas
- ✅ **Agrupamento eficiente** com GROUP BY
- ✅ **Paginação** automática em listagens
- ✅ **Cache de sessão** para melhor UX
- ✅ **Lazy loading** de dados pesados

## 🔧 Funcionalidades Implementadas

### ✅ Sistema Completo
1. **Autenticação e Autorização**
   - Login seguro com roles
   - Middleware de proteção
   - Controle de acesso por função

2. **Dashboard Dinâmico**
   - Estatísticas reais do banco
   - Atividade recente via AJAX
   - Links de navegação rápida

3. **Sistema de Relatórios**
   - Gerador dinâmico completo
   - Múltiplas exportações
   - Filtros avançados

4. **Interface Responsiva**
   - Design moderno com Tailwind
   - Componentes reutilizáveis
   - UX otimizada

### 🚀 Próximas Melhorias Sugeridas
1. **CRUD Modules** - Interfaces para edição de dados
2. **API REST** - Endpoints para integração
3. **Relatórios Salvos** - Templates de relatórios
4. **Notificações** - Sistema de alertas
5. **Auditoria** - Log de ações dos usuários

## 🔧 Comandos Úteis

### Comandos de Desenvolvimento
```bash
# Iniciar servidor de desenvolvimento
php artisan serve

# Limpar todos os caches
php artisan optimize:clear

# Limpar cache específico
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Executar migrations
php artisan migrate

# Ver status das migrations
php artisan migrate:status

# Gerar nova migration
php artisan make:migration create_nova_tabela

# Gerar novo controller
php artisan make:controller NovoController --resource
```

### Comandos de Produção
```bash
# Otimizar para produção
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar configuração
php artisan about

# Executar queue (se necessário)
php artisan queue:work
```

### Comandos de Debug
```bash
# Ver logs em tempo real
php artisan tail

# Limpar logs
php artisan log:clear

# Testar conexão com banco
php artisan migrate:status
```

## 🤝 Desenvolvimento

### Padrões de Código
- Seguir PSR-12
- Usar type hints quando possível
- Documentar métodos complexos
- Manter consistência nos nomes

### Git Workflow
- Feature branches para novas funcionalidades
- Commits semânticos
- Pull requests para review

## 🎯 URLs de Acesso

### Desenvolvimento (Artisan)
- **Dashboard**: http://localhost:8000/dashboard
- **Login**: http://localhost:8000/login
- **Relatórios**: http://localhost:8000/relatorios

### Produção Local (XAMPP)
- **Acesso Principal**: http://192.168.5.130/consultasia/ (redireciona automaticamente para login)
- **Dashboard**: http://192.168.5.130/consultasia/public/dashboard
- **Login**: http://192.168.5.130/consultasia/public/login
- **Relatórios**: http://192.168.5.130/consultasia/public/relatorios

## 📞 Suporte e Manutenção

### Logs do Sistema
- **Localização**: `storage/logs/laravel.log`
- **Rotação**: Automática por data
- **Níveis**: DEBUG, INFO, WARNING, ERROR

### Troubleshooting Comum
1. **Erro 500**: Verificar logs e permissões
2. **Sessão perdida**: Limpar cache de sessão
3. **Relatórios lentos**: Verificar índices do banco
4. **Exportação falha**: Verificar memória PHP

### Contato Técnico
- **Documentação**: Este arquivo README
- **Logs**: `storage/logs/laravel.log`
- **Debug**: Ativar `APP_DEBUG=true` no `.env`

---

**Sistema ConsultaProd v2.0**  
Desenvolvido para Gestão de Dados em Saúde  
Laravel 12.x + PHP 8.2+ + MySQL + 5.9M registros  
**Status**: ✅ Totalmente Funcional