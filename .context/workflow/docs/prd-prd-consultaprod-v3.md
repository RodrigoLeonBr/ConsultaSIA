# Product Requirements Document (PRD) - ConsultaProd v3

## 1. Introdução

### Visão Geral do Projeto
O ConsultaProd é um Sistema de Gerenciamento e Relatórios Dinâmicos projetado para unidades de saúde, focado na extração de inteligência a partir de grandes volumes de dados de produção ambulatorial (SIA/SUS) e hospitalar (SIHD/SUS).

### Objetivo da Migração e Evolução (v3)
A versão 3 do ConsultaProd representa um salto estratégico duplo:
1. **Modernização Arquitetural**: Migração do legado Laravel 12 para um ecossistema Node.js de alta performance, visando suportar a escalabilidade necessária para processamento de milhões de registros em tempo real.
2. **Revolução UI/UX**: Redesenho completo da interface com foco absoluto em Regulação e Gestão, priorizando uma UI de altíssima densidade de dados ("data-heavy") que reduza o tempo de análise e cruzamento de informações.
3. **Novas Capabilities de Negócio**: Introdução de complexos motores de comparação financeira e produtiva, confrontando dados do SUS com as regras do consórcio CISMETRO e da Tabela SUS Paulista.

### Escopo do Produto
O escopo engloba o desenvolvimento de uma plataforma analítica robusta que permite a ingestão regular de bases do DataSUS, o cruzamento destas com tabelas de referência privadas/estaduais, e a exposição desses dados através de uma interface voltada à produtividade extrema, garantindo controle, auditoria e exportação profissional.

---

## 2. Público-Alvo e Persona

O sistema é desenhado para profissionais que operam diariamente com dados complexos e necessitam de velocidade e precisão. Não é um sistema voltado ao paciente, mas sim ao "backoffice" da saúde.

**Personas Principais:**
- **Coordenador de Regulação**: Necessita visualizar o macro (dashboards) e rapidamente fazer *drill-down* para identificar gargalos ou discrepâncias entre a produção apresentada e as metas físicas.
- **Faturista / Auditor HC**: Focado no cruzamento de valores. Precisa validar se o que foi produzido no SIA/SIHD está sendo faturado corretamente segundo as tabelas CISMETRO ou SUS Paulista. Valoriza ações em massa e atalhos rápidos.
- **Diretoria de Saúde**: Consome relatórios agregados e sumarizados para prestação de contas governamental ou controle financeiro.

---

## 3. Diretrizes de UI/UX (Redesenho Completo)

A nova interface (Desktop-first) abandona o conceito de "telas de marketing" e abraça a filosofia de um cockpit de operações densas. O objetivo é reduzir drasticamente o tempo para encontrar informações, aplicar filtros, validar consistências e exportar evidências.

### Princípios Essenciais de Design
- **Produtividade > "Marketing UI":** A tela principal não deve ter espaço não utilizado. Foco em tabelas interativas, painéis colapsáveis, filtros avançados visíveis e ações em lote (bulk actions).
- **Hierarquia Visual Forte e Densidade:** Alta densidade de informação não deve significar caos visual. Exige-se o uso rigoroso de sistemas de grid, espaçamento matemático (design tokens), tipografia hiper-legível (ex: Inter ou Roboto Mono para dados tabulares) e agrupamento lógico em painéis ("Cards").
- **Filtros como "Primeira Classe":** Como um motor de consulta, os filtros não ficam escondidos. Eles são persistentes, podem ser salvos como visualizações nomeadas e reaplicados rapidamente. Suporte a operadores lógicos avançados (AND/OR, IN, BETWEEN).
- **Consistência Total:** O usuário nunca deve precisar reaprender um componente. A mesma tabela de dados usada para CBO deve ser usada para relatórios SIA, compartilhando os mesmos padrões de ordenação, paginação e exportação.
- **Transparência e Auditabilidade:** Em qualquer tela de dados, um "badge" ou painel deve informar em tempo real: a versão/data da tabela base sendo usada (SIGTAP/CISMETRO), a data da carga do dado de produção, e as competências ativas no filtro atual. Nada de "caixas pretas".
- **Acesso Rápido e Controle:** A interface deve ser navegável por "Power Users". Inclusão de atalhos de teclado (ex: `Ctrl + F` para busca global, `Ctrl + E` para exportar view atual), histórico das últimas consultas rodadas e sistema robusto de trilha de auditoria vinculada ao perfil.

> [!NOTE]
> **Referência de Setup:** O sistema deve parecer e comportar-se mais como um Bloomberg Terminal ou painel de AWS Console do que como um app de consumo comum. O foco é ferramental.

---

## 4. Requisitos Funcionais

A inteligência do sistema divide-se nos seguintes módulos principais:

### Módulo 1: Gerenciamento e Importação de Tabelas Base (Core)
O sistema não funciona sem suas tabelas de domínio estruturais atualizadas.
- **[RF1.1] Motor de Importação:** Rotinas otimizadas para ingestão periódica (via CSV/TXT ou APIs governamentais) das tabelas: CBO, CISMETRO, Tabela SUS Paulista e SIGTAP.
- **[RF1.2] Versionamento e Vigência:** O sistema DEVE guardar o histórico de valores. Um procedimento pode ter valor `X` em Dezembro e `Y` em Janeiro. Cálculos retroativos dependem disso.
- **[RF1.3] Visualizador de Tabelas Base:** Interface de consulta rápida (Dicionário de Dados) para que técnicos validem atributos de um procedimento SIGTAP ou regra do SUS Paulista.

### Módulo 2: CRUD de Prestadores
- **[RF2.1] Gestão Completa:** Cadastro de unidades de saúde (CNES, nome, esferas administrativas, contatos).
- **[RF2.2] Configuração Contratual:** Habilidade de atrelar a qual tabela de remuneração (SIA/SIHD puro vs. SUS Paulista vs. CISMETRO) um determinado prestador pertence em dada competência.

### Módulo 3: Exploração e Relatórios Ambulatoriais (SIA/SUS) e Hospitalares (SIHD/SUS)
- **[RF3.1] Motor de Filtros Dinâmicos:** Interface complexa permitindo cruzar Prestador + Competência + Procedimento (SIA) ou CID (SIHD).
- **[RF3.2] Construção Hierárquica:** Geração de relatórios respeitando a cadeia: Tipo de Financiamento → Grupo → Subgrupo → Forma de Organização → Detalhe/Procedimento.
- **[RF3.3] Exportação Profissional:** Capacidade de extrair qualquer view consolidada para Excel (.xlsx), PDF ou CSV estruturado.

### Módulo 4: Motor de Comparação e Cruzamento (Matching)
- **[RF4.1] Divergência Financeira:** Rotina que analisa uma competência fechada (SIHD ou SIA), pega a quantidade executada de procedimentos e calcula: `Valor Repassado (Tabela SUS)` VS `Valor Estimado (Tabela CISMETRO / SUS Paulista)`.
- **[RF4.2] Identificação de Lacunas:** Relatório destacando "Gargalos" — procedimentos faturados que não têm correspondência na tabela privada contratada, ou vice-versa.

### Módulo 5: Dashboards Gerenciais Dinâmicos
- **[RF5.1] Overview de Produção:** Cards com as métricas vitais da competência selecionada (Volume Total Apresentado x Aprovado, Valor Total R$).
- **[RF5.2] Performance de Prestadores:** Gráficos hierárquicos e rankings exibindo a distribuição da produção entre os prestadores de serviço ativos da rede.

---

## 5. Requisitos Não Funcionais

A volumetria de dados no contexto de saúde dita o padrão arquitetural:

- **[RNF1] Performance Extrema (Big Data Handling):** A principal tabela de processamento (`s_prd` ou equivalente) lida com dezenas de milhões de registros. Consultas complexas que envolvem `GROUP BY` e `JOIN` em massa DEVEM ocorrer em frações de segundos ou com tempo aceitável garantido por indexação robusta ou Views materializadas. *"Time to first byte"* sob carga pesada é crítico.
- **[RNF2] Escalabilidade Horizontal:** A adoção do Node.js permite um comportamento *event-driven* mais leve. A arquitetura deve prever deployment em containeres *stateless* e ser capaz de processar cargas paralelas de arquivos de importação.
- **[RNF3] Segurança e Privacidade:** Dados governamentais e de atendimento exigem sigilo. Autenticação forte, RBAC (Role-Based Access Control) detalhada nas rotas da API, sanitização estrita de SQL Inject (mesmo em ORMs) e auditoria de cada relatório e exportação gerada.
- **[RNF4] Manutenibilidade e Qualidade de Código:** O código Node.js deve seguir forte tipagem, injeção de dependências clara e desacoplamento da camada de banco de dados (Repository Pattern) para facilitar testes e manutenções futuras.

---

## 6. Arquitetura Técnica Sugerida e Estratégia de Migração (Laravel -> Node.js)

### Stack Principal
- **Backend**: Node.js rodando com um framework Enterprise moderno (ex: NestJS para tipagem estrita via TypeScript e padrão MVC/Dependency Injection similar à filosofia madura do Laravel).
- **Banco de Dados (Restrição Obrigatória)**: Mantém-se a infraestrutura relacional utilizando Xampp/MySQL ou MariaDB v10.3+. **É mandatório o uso da estrutura já implantada refletida no arquivo `producao.sql`**. Nenhuma migração Node.js deve alterar as tabelas de domínio core listadas abaixo, devendo o ORM mapeá-las exatamente como existem. **Migrations automáticas DEVEM permanecer desabilitadas em produção.**
  - `s_apa` e `s_pap`: Tabelas de alta indexação contendo a produção (milhões de registros). Nota-se o uso maciço de `varchar` em campos numéricos e identificadores compostos (`PAP_UID`, `PAP_CMP`, `PAP_NUM`).
  - `prestador`: Chave primária alfanumérica `re_cunid` com índices em `cnpj` e `ativo` (observa-se engine MyISAM no legado).
  - `cismetro`, `cbo`, `forma`, `s_rub`: Tabelas estruturais e de de/para.
- **ORM**: Recomenda-se Prisma ORM ou Kysely. Devido ao design legado (ex: engine MyISAM em `prestador`, ausência de chaves estrangeiras explícitas em `s_pap` no dump, chaves primárias string como `cbo`, `rub_id`), o ORM escolhido deve suportar mapeamento agressivo sem tentar forçar convenções de chaves substitutas numéricas ou alterar a DDL atual do banco de produção.
- **Frontend (UI/UX)**: Aplicação **SPA Desktop-first** com **React** e componentes de DataGrid com **server-side pagination/filter/sort**. SSR não é prioridade devido à restrição de recursos do servidor e natureza interna do sistema.

### Estratégia de Transição e Agentes AI
A migração de um monólito Laravel maduro lidando com ~6M registros não pode ser trivial:

- **Abordagem de Estrangulamento (Strangler Fig Pattern):** Em vez de reescrever tudo como "Big Bang", sugere-se manter o banco de dados atual intocado. Inicie a re-criação no Node.js pelas rotas de **relatórios e comparações** (que exigem alta performance assíncrona), eventualmente substituindo módulos inteiros gradativamente.
- **Uso Crítico de Agentes (Antigravity e Gemini 3.1):**
  - Na fase atual, o agente *Architect-Specialist* pode mapear as pesadas queries de agrupamento em `RelatorioController` originais do Laravel e traduzi-las em equivalentes modernas e otimizadas em Prisma/Raw Node.
  - O agente *Frontend-Specialist* orquestrará os componentes densos da UI sem precisar lidar com o backend PHP, focando 100% no front SPA (React).
  - Testes e documentação contínua da migração fluem pelo *ai-context*.

### Desafios de Risco na Transição
- O ORM Prisma, embora excelente, costuma performar mal em agregações de grandes conjuntos de dados se não configurado com views indexadas no banco. Ferramentas mais cruas (como node-mysql2 purista ou Query Builders como Knex/Kysely) podem ser necessárias nas apurações mensais.

---

## 7. Glossário Domínio-Específico

Para o alinhamento total do corpo de engenharia de software:

- **SIA/SUS**: Sistema de Informações Ambulatoriais do SUS. Refere-se à produção não ligada à internação.
- **SIHD/SUS**: Sistema de Informações Hospitalares Descentralizado. Relata as autorizações de internações (AIHs).
- **CBO**: Classificação Brasileira de Ocupações. Código que define qual tipo de profissional (ex: médico clínico, enfermeiro) realizou o atendimento. Regras de faturamento diferem baseadas nisso.
- **CISMETRO**: Consórcio Intermunicipal de Saúde cuja tabela de valores remunera prestadores de forma distinta e, por vezes, superior ou complementar à do SUS.
- **Tabela SUS Paulista**: Tabela complementar estadual de São Paulo criada para tentar corrigir defasagens da tabela nacional (SIGTAP). Base vital para cenários de rentabilidade dos prestadores.
- **SIGTAP**: O dicionário máximo de regras do SUS (Procedimentos, Medicamentos e OPMs). Define valores base nacionais, limites de idade, CBOs permitidos etc.

---

## 8. SLOs (Metas de Serviço e Performance) — Novo
Para suportar infraestrutura limitada (Node + MySQL no mesmo servidor, banco legado com `3GB+` e tabelas massivas), o produto adota metas por categoria:

- **SLO-UI (carregamento e interação):**
  - Primeira renderização de páginas de lista (shell + grid): p95 ≤ `1,0s` (sem depender de dados completos).
  - Aplicação de filtros (disparo de consulta): feedback visual imediato (loading/skeleton) e cancelamento de requisições concorrentes no front.

- **SLO-Consulta (interativa, paginada):**
  - Consultas paginadas (server-side, 50–200 linhas): p95 ≤ `800ms`, p99 ≤ `2s`, assumindo filtros indexáveis (competência/prestador).
  - Proibido retornar conjuntos "não paginados" para UI.

- **SLO-Relatório (agregado padrão):**
  - Agregações típicas por competência (1–2 joins + group by): p95 ≤ `3s`.
  - Caso exceda o limite, o sistema deve **rebaixar automaticamente** para execução assíncrona (job).

- **SLO-Relatório pesado / Matricial / Hierárquico:**
  - Deve rodar como **job assíncrono** por padrão, com:
    - tempo de conclusão alvo ≤ `2min` (variável por volumetria),
    - acompanhamento de progresso/status,
    - persistência do resultado em **tabela auxiliar no MySQL**.

- **SLO-Exportação:**
  - Exportações acima de `100k` linhas: sempre via job.
  - Exportações menores podem ser síncronas, desde que não comprometam SLO de UI.

---

## 9. Jornadas Críticas (End-to-End) — Novo
As jornadas abaixo definem o “caminho feliz” mínimo do produto e guiam UI/API/DB:

1. **Jornada A — Gestão (Dashboard → Drill-down → Evidência)**
   - Selecionar competência → visualizar KPIs → abrir ranking de prestadores → drill-down por procedimento/CID → exportar visão consolidada.
   - O relatório exportado deve registrar: competência, filtros, versões de tabelas de referência e usuário.

2. **Jornada B — Relatório pesado (Solicitar → Job → Resultado persistido)**
   - Usuário configura filtros complexos → clica “Gerar relatório” → sistema cria `job` e retorna `job_id` → usuário acompanha status → ao finalizar, o resultado aparece como nova “visão” consultável (persistida em tabela auxiliar).
   - O resultado deve ser reabrível sem reprocessar, dentro de uma política de retenção.

3. **Jornada C — Importação e vigência (Tabela → Validação → Ativação)**
   - Admin importa (CBO/SIGTAP/CISMETRO/SUS Paulista) → valida estrutura e vigência → executa carga → sistema registra versão/vigência → tabelas entram em uso para competência(s) selecionada(s).
   - Falhas devem gerar relatório de rejeições (linhas/problemas).

4. **Jornada D — Matching e divergência (Comparação → Divergências → Evidência)**
   - Selecionar competência + prestador/contrato → rodar comparação SUS vs CISMETRO/SUS Paulista → listar divergências e lacunas → exportar evidência auditável.

---

## 10. MVP vs Fase 2 (Prioridade de Entrega) — Novo

### MVP (Fase 1) — objetivo: entregar valor rápido sem estressar o servidor
- **MVP-1 UI/UX:** SPA React Desktop-first, DataGrid server-side, filtros persistentes, exportação básica.
- **MVP-2 Relatórios essenciais (SIA e SIHD):**
  - consultas paginadas por competência/prestador,
  - agregações padrão por procedimento/grupos (cadeia hierárquica quando aplicável),
  - exportação de visão consolidada.
- **MVP-3 Matching básico (CISMETRO e SUS Paulista):**
  - divergência financeira por competência,
  - lacunas (sem correspondência).
- **MVP-4 Importação e versionamento mínimo:**
  - importar tabelas base,
  - registrar versão/vigência,
  - histórico de cargas e auditoria.
- **MVP-5 Execução assíncrona sem Redis:**
  - jobs persistidos em MySQL,
  - resultados persistidos em **tabelas auxiliares**.

### Fase 2 — objetivo: avançar capacidade e refinamento
- Construtor avançado de relatórios (mais flexível, pivôs/matriz completa).
- “Relatórios salvos” com compartilhamento por perfil (templates e favoritos).
- Regras avançadas de matching (mapeamentos complexos, exceções, N:N).
- Dashboards configuráveis por perfil e alertas automatizados.
- Otimizações de pré-agregação por competência e janelas de processamento.

---

## 11. Matching (Regras mínimas) — Novo
Para que as comparações (SUS vs CISMETRO/SUS Paulista) sejam determinísticas e auditáveis, o motor deve obedecer regras mínimas:

1. **Chave de contexto do matching**
   - Toda comparação deve ser executada com base em:
     - **competência**,
     - **prestador** (ou contrato),
     - **tabela de referência aplicável** (SUS puro / CISMETRO / SUS Paulista),
     - **vigência** das tabelas.

2. **Regra mínima de correspondência (baseline)**
   - O sistema deve suportar ao menos o matching:
     - **1:1 por código** quando houver equivalência direta (procedimento SUS ↔ item CISMETRO/SUS Paulista).
   - Quando não houver correspondência:
     - registrar como **lacuna** (procedimento sem match),
     - permitir filtragem e exportação das lacunas.

3. **Cálculo mínimo de divergência**
   - Para cada item correspondente:
     - `quantidade_executada` × `valor_sus_vigente` = `valor_repassado`
     - `quantidade_executada` × `valor_tabela_referencia_vigente` = `valor_estimado`
     - `diferenca` = `valor_estimado` − `valor_repassado`

4. **Auditabilidade obrigatória**
   - Cada execução de matching deve persistir:
     - filtros,
     - competência,
     - versão/vigência das tabelas usadas,
     - totalizadores (somatórios),
     - usuário e timestamp,
     - referência ao resultado persistido (tabela auxiliar).

5. **Escopo das regras avançadas (fora do baseline do MVP)**
   - Regras 1:N, N:1, N:N, exceções por prestador, composição de itens e tabelas de equivalência complexas ficam para **Fase 2**, salvo necessidade explícita.

---
