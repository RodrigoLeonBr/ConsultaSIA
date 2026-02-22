# Documentação Técnica - Sistema ConsultaProd

## 📋 Visão Geral Técnica

O Sistema ConsultaProd é uma aplicação web desenvolvida em Laravel 12 para gerenciamento e análise de dados de produção hospitalar. O sistema processa mais de 5.9 milhões de registros de produção médica com performance otimizada.

## 🏗️ Arquitetura do Sistema

### Stack Tecnológico
- **Backend**: Laravel 12.x (PHP 8.2+)
- **Frontend**: Blade Templates + Tailwind CSS + Alpine.js
- **Banco de Dados**: MySQL 5.7+
- **Servidor Web**: Apache (XAMPP) ou Built-in PHP Server
- **Exportações**: Maatwebsite/Excel + DomPDF

### Estrutura de Dados

#### Tabelas Principais
```sql
-- Tabela principal de produção (5.988.427 registros)
s_prd: Dados de produção hospitalar
├── prd_uid: Código do prestador (CNES)
├── prd_cmp: Competência (YYYYMM)
├── prd_pa: Código do procedimento
├── prd_cbo: Código CBO do profissional
├── PRD_QT_P: Quantidade de procedimentos
├── PRD_VL_P: Valor dos procedimentos
└── PRD_RUB: Rubrica de financiamento

-- Tabelas de referência
prestador: 76 unidades prestadoras ativas
procedimento: 3.036 procedimentos SUS
cbo: 2.812 códigos de ocupação
s_rub: 70 rubricas de financiamento
🆕 forma: Hierarquia de procedimentos (grupo, subgrupo, forma)
```

#### Relacionamentos
```
s_prd.prd_uid → prestador.re_cunid
s_prd.prd_pa → procedimento.codigo
s_prd.prd_cbo → cbo.cbo
s_prd.PRD_RUB → s_rub.RUB_ID
🆕 SUBSTRING(s_prd.prd_pa, 1, 2) → forma.grupo
🆕 SUBSTRING(s_prd.prd_pa, 1, 4) → forma.subgrupo
🆕 SUBSTRING(s_prd.prd_pa, 1, 6) → forma.forma
```

## 🔧 Componentes Técnicos

### Controllers Principais

#### DashboardController
```php
- index(): Dashboard com estatísticas dinâmicas
- getSystemStatistics(): Contadores de tabelas
- getFinanciamentosInfo(): Último período + total do ano
- getRecentActivity(): Top 10 atividades recentes
```

#### RelatorioController
```php
- index(): Interface do gerador de relatórios
- getFields(): Campos disponíveis para relatórios
- getLookupData(): Dados para campos de lookup
- generate(): Geração de relatórios com filtros
- exportExcel/PDF/CSV(): Exportações em múltiplos formatos
```

#### 🆕 FaturamentoPrestadorController
```php
- index(): Interface de filtros do relatório hierárquico
- gerar(): Geração do relatório com dados hierárquicos
- exportarPdf(): Exportação PDF com layout profissional
- processarDadosHierarquicos(): Processamento de dados em estrutura hierárquica
- traduzirTipoFinanciamento(): Tradução de códigos de financiamento
```

### Middleware Customizado

#### EnsurePasswordChanged
- Força troca de senha no primeiro login
- Redireciona para tela de alteração de senha

#### CheckActive
- Verifica se usuário está ativo
- Bloqueia acesso de usuários desativados

#### CheckRole
- Controla acesso por função (admin/operator)
- Protege rotas administrativas

### Sistema de Exportação

#### RelatorioExport (Excel)
```php
- Implementa FromCollection, WithHeadings, WithStyles
- Formatação automática de números e moedas
- Inclusão de totalizadores
- Tratamento robusto de erros
```

#### PDF Export
```php
- Layout profissional com cabeçalho
- Formatação de dados brasileira
- Inclusão de totais e metadados
- Orientação automática da página
```

## 📊 Sistema de Relatórios

### Arquitetura de Queries

#### Query Builder Dinâmico
```php
// Exemplo de query gerada
SELECT 
    sp.prd_uid as cnes,
    pr.re_cnome as prestador_nome,
    SUM(CAST(sp.PRD_QT_P as UNSIGNED)) as total_quantidade,
    SUM(CAST(sp.PRD_VL_P as DECIMAL(15,2))) as total_valor,
    CONCAT(SUBSTRING(sp.prd_cmp, 1, 4), '-', SUBSTRING(sp.prd_cmp, 5, 2)) as competencia
FROM s_prd sp
LEFT JOIN prestador pr ON sp.prd_uid = pr.re_cunid
WHERE sp.prd_cmp >= '202001'
GROUP BY sp.prd_uid, pr.re_cnome, sp.prd_cmp
ORDER BY sp.prd_cmp DESC
```

#### 🆕 Query Hierárquica de Faturamento
```php
// Query do relatório de faturamento por prestador
SELECT 
    pr.re_cunid as prestador_codigo,
    pr.re_cnome as prestador_nome,
    sp.prd_rub as tipo_financiamento,
    SUBSTRING(sp.prd_pa, 1, 2) as grupo_codigo,
    f_grupo.descricao as grupo_descricao,
    SUBSTRING(sp.prd_pa, 1, 4) as subgrupo_codigo,
    f_subgrupo.descricao as subgrupo_descricao,
    SUBSTRING(sp.prd_pa, 1, 6) as forma_codigo,
    f_forma.descricao as forma_descricao,
    sp.prd_pa as procedimento_codigo,
    proc.procedimento as procedimento_nome,
    proc.PA_TOTAL as valor_unitario,
    SUM(CAST(sp.PRD_QT_P as UNSIGNED)) as quantidade_apresentada,
    SUM(CAST(sp.PRD_QT_P as UNSIGNED) * CAST(proc.PA_TOTAL as DECIMAL(15,2))) as valor_apresentado,
    SUM(CAST(sp.PRD_QT_A as UNSIGNED)) as quantidade_aprovada,
    SUM(CAST(sp.PRD_VL_A as DECIMAL(15,2))) as valor_aprovado
FROM s_prd sp
LEFT JOIN prestador pr ON sp.prd_uid = pr.re_cunid
LEFT JOIN procedimento proc ON sp.prd_pa = proc.codigo
LEFT JOIN forma f_grupo ON SUBSTRING(sp.prd_pa, 1, 2) = f_grupo.grupo 
    AND f_grupo.subgrupo = CONCAT(SUBSTRING(sp.prd_pa, 1, 2), '00')
    AND f_grupo.forma = CONCAT(SUBSTRING(sp.prd_pa, 1, 2), '0000')
LEFT JOIN forma f_subgrupo ON SUBSTRING(sp.prd_pa, 1, 4) = f_subgrupo.subgrupo 
    AND f_subgrupo.forma = CONCAT(SUBSTRING(sp.prd_pa, 1, 4), '00')
LEFT JOIN forma f_forma ON SUBSTRING(sp.prd_pa, 1, 6) = f_forma.forma
WHERE sp.prd_cmp = ? AND pr.re_tipo = 'P'
GROUP BY pr.re_cunid, pr.re_cnome, sp.prd_rub, grupo_codigo, f_grupo.descricao, 
         subgrupo_codigo, f_subgrupo.descricao, forma_codigo, f_forma.descricao, 
         sp.prd_pa, proc.procedimento, proc.PA_TOTAL
ORDER BY pr.re_cnome ASC, sp.prd_rub ASC, grupo_codigo ASC, 
         subgrupo_codigo ASC, forma_codigo ASC, sp.prd_pa ASC
```

#### Otimizações de Performance
- **Índices compostos** em campos de filtro frequente
- **GROUP BY** automático para evitar duplicatas
- **LIMIT** dinâmico para grandes datasets
- **Lazy loading** de dados de lookup

### Filtros Avançados

#### Operadores Suportados
```php
'=' => 'Igual a'
'>' => 'Maior que'
'<' => 'Menor que'
'>=' => 'Maior ou igual'
'<=' => 'Menor ou igual'
'like' => 'Contém'
'starts_with' => 'Inicia com'
'between' => 'Entre valores'
'in' => 'Lista de valores'
```

#### Tipos de Campo
```php
'date' => Campos de data (competência)
'lookup' => Campos com tabela relacionada
'number' => Campos numéricos (quantidade)
'currency' => Campos monetários (valor)
'text' => Campos de texto livre
```

## 🔐 Segurança

### Autenticação
- **Laravel Breeze** com customizações
- **Sessões seguras** com driver file
- **CSRF Protection** em todas as forms
- **Rate limiting** em tentativas de login

### Autorização
- **Role-based access** (admin/operator)
- **Middleware de proteção** em rotas sensíveis
- **Validação de entrada** em todos os endpoints
- **Sanitização** de dados de relatório

### Proteção de Dados
- **Prepared statements** para prevenir SQL injection
- **Validação de tipos** em filtros de relatório
- **Escape de HTML** em outputs
- **Logs de auditoria** para ações críticas

## 🚀 Performance

### Otimizações de Banco
```sql
-- Índices principais
CREATE INDEX idx_s_prd_composite ON s_prd (prd_uid, prd_cmp, prd_flh, prd_seq);
CREATE INDEX idx_s_prd_uid ON s_prd (prd_uid);
CREATE INDEX idx_s_prd_cmp ON s_prd (prd_cmp);
CREATE INDEX idx_s_prd_pa ON s_prd (prd_pa);
CREATE INDEX idx_prestador_ativo ON prestador (ativo);
```

### Cache Strategy
- **Config cache** para produção
- **Route cache** para melhor routing
- **View cache** para templates compilados
- **Session cache** para dados de usuário

### Monitoramento
- **Query logging** para queries lentas
- **Error logging** com stack traces
- **Performance metrics** no dashboard
- **Memory usage** tracking

## 🔧 Configuração de Ambiente

### Variáveis Críticas (.env)
```bash
# Aplicação
APP_NAME="ConsultaProd - Sistema de Gestão"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.5.130/consultasia

# Banco de Dados
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=consultaprod
DB_USERNAME=hospital
DB_PASSWORD=tebaldi

# Sessões
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=false

# Cache
CACHE_STORE=database
```

### Configurações de Produção
```bash
# Otimizações
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Permissões
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

## 📈 Monitoramento e Logs

### Estrutura de Logs
```
storage/logs/
├── laravel.log          # Log principal da aplicação
├── laravel-YYYY-MM-DD.log  # Logs diários (rotação automática)
```

### Níveis de Log
- **DEBUG**: Informações de desenvolvimento
- **INFO**: Operações normais do sistema
- **WARNING**: Situações que merecem atenção
- **ERROR**: Erros que impedem operações
- **CRITICAL**: Falhas críticas do sistema

### Métricas de Performance
```php
// Exemplo de log de performance
[INFO] Dashboard loaded in 245ms with 4 queries
[INFO] Report generated: 15,432 records in 1.2s
[WARNING] Slow query detected: 3.5s for complex filter
[ERROR] Excel export failed: Memory limit exceeded
```

## 🔄 Manutenção

### Rotinas de Manutenção
```bash
# Limpeza de logs antigos (mensal)
find storage/logs -name "*.log" -mtime +30 -delete

# Otimização de tabelas (semanal)
php artisan db:optimize

# Backup de configurações
cp .env .env.backup.$(date +%Y%m%d)

# Verificação de integridade
php artisan migrate:status
```

### Troubleshooting

#### Problemas Comuns
1. **Relatórios lentos**: Verificar índices e filtros
2. **Exportação falha**: Aumentar memory_limit PHP
3. **Sessão perdida**: Verificar permissões storage/
4. **Erro 500**: Consultar logs para detalhes

#### Comandos de Diagnóstico
```bash
# Verificar status geral
php artisan about

# Testar conexão com banco
php artisan migrate:status

# Ver últimos erros
tail -f storage/logs/laravel.log

# Verificar configuração
php artisan config:show database
```

---

**Documentação Técnica v2.0**  
Sistema ConsultaProd - Laravel 12  
Atualizado em: Setembro 2025