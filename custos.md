
# Plano de Requisitos Gerais (PRG) - Sistema de Gestão de Custos em Saúde

## 1. Visão Geral do Sistema

### 1.1 Objetivo
Sistema web para gestão integrada de custos operacionais em organizações de saúde, permitindo controle de recursos humanos, materiais, produção assistencial e custos recorrentes.

### 1.2 Escopo
- Gestão de Recursos Humanos e custos salariais
- Controle de materiais e movimentações de estoque
- Acompanhamento da produção assistencial
- Gestão de custos recorrentes e contratos
- Centros de custos e alocação de recursos
- Relatórios gerenciais e análises de custos

### 1.3 Tecnologias Utilizadas
- **Frontend**: React 18 + TypeScript + Vite
- **UI/UX**: Tailwind CSS + Shadcn/UI
- **Gráficos**: Recharts
- **Formulários**: React Hook Form + Zod
- **Roteamento**: React Router DOM
- **Estado**: Hooks nativos do React

## 2. Módulos Funcionais

### 2.1 Módulo de Recursos Humanos

#### 2.1.1 Gestão de Profissionais
- **Cadastro de funcionários** com dados pessoais e profissionais
- **Alocação por unidades organizacionais** e centros de custo
- **Controle de carga horária** e vínculos empregatícios
- **Histórico de alterações** com auditoria completa

#### 2.1.2 Importação e Processamento Salarial
- **Importação de dados salariais** via arquivos
- **Confirmação mensal** de folhas de pagamento
- **Relatórios de custos de RH** por período e unidade
- **Análise de variações** salariais entre períodos

#### 2.1.3 Funcionalidades Implementadas
```typescript
interface Employee {
  id: string;
  name: string;
  cpf: string;
  registration: string;
  position: string;
  admissionDate: Date;
  workload: number;
  unitId: string;
  costCenterId: string;
  status: 'ativo' | 'inativo';
}

interface MonthlySalaryData {
  employeeId: string;
  competenceMonth: number;
  competenceYear: number;
  grossSalary: number;
  benefits: number;
  taxes: number;
  netSalary: number;
}
```

### 2.2 Módulo de Materiais

#### 2.2.1 Cadastro de Materiais
- **Categorização** por tipo (medicamentos, suprimentos, equipamentos)
- **Códigos internos e externos** (código de barras)
- **Unidades de medida** padronizadas
- **Descrições detalhadas** e especificações técnicas

#### 2.2.2 Controle de Movimentações
- **Entradas e saídas** de estoque com origem/destino
- **Movimentações entre unidades** organizacionais
- **Rastreabilidade completa** de movimentos
- **Relatórios de consumo** por período e unidade

#### 2.2.3 Estrutura de Dados
```typescript
interface Material {
  id: string;
  name: string;
  category: MaterialCategory;
  description: string;
  unitOfMeasure: string;
  internalCode: string;
  barcode?: string;
}

interface MaterialMovement {
  id: string;
  materialId: string;
  quantity: number;
  movementType: MovementType.IN | MovementType.OUT;
  movementDate: Date;
  origin: string;
  unitId: string;
}
```

### 2.3 Módulo de Produção Assistencial

#### 2.3.1 Tipos de Produção
- **Cadastro de procedimentos** e atividades assistenciais
- **Categorização** por especialidade e complexidade
- **Unidades de medida** específicas para cada tipo
- **Códigos SIA/SIH** para integração com sistemas externos

#### 2.3.2 Registro de Produção
- **Lançamento mensal** por unidade organizacional
- **Validação de dados** antes do fechamento
- **Importação automática** de sistemas externos (SIA/SIH)
- **Controle de competências** e períodos

#### 2.3.3 Relatórios de Produção
- **Tendências de produção** (mensal/trimestral)
- **Produção por unidade** com gráficos comparativos
- **Resumo por tipo** de procedimento
- **Análises temporais** e sazonalidade

#### 2.3.4 Interfaces de Dados
```typescript
interface ProductionType {
  id: string;
  code: string;
  name: string;
  category: string;
  unit: string;
  isActive: boolean;
}

interface MonthlyProduction {
  id: string;
  organizationalUnitId: string;
  productionTypeId: string;
  competenceMonth: number;
  competenceYear: number;
  quantity: number;
  isValidated: boolean;
}
```

### 2.4 Módulo de Custos Recorrentes

#### 2.4.1 Gestão de Contratos
- **Cadastro de fornecedores** e prestadores
- **Contratos de serviços** (água, luz, telefone, internet)
- **Periodicidade de cobrança** flexível
- **Critérios de rateio** por unidade organizacional

#### 2.4.2 Lançamentos Mensais
- **Registro de faturas** e comprovantes
- **Controle de vencimentos** e pagamentos
- **Anexo de documentos** comprobatórios
- **Status de pagamento** em tempo real

#### 2.4.3 Alocação de Custos
- **Rateio automático** baseado em critérios pré-definidos
- **Alocação manual** para casos específicos
- **Histórico de alterações** nos critérios
- **Relatórios de distribuição** por centro de custo

### 2.5 Módulo de Centros de Custo

#### 2.5.1 Estrutura Organizacional
- **Hierarquia de centros** de custo (pai/filho)
- **Tipos de centro** (finalístico, apoio, administrativo)
- **Responsáveis** por cada centro
- **Objetivos e metas** orçamentárias

#### 2.5.2 Métodos de Alocação
- **Apropriação direta** de custos
- **Rateio** baseado em critérios específicos
- **Método recíproco** para centros de apoio
- **Critérios personalizados** definidos pelo usuário

#### 2.5.3 Vinculação de Procedimentos
- **Associação** entre procedimentos e centros de custo
- **Múltiplas vinculações** para procedimentos compartilhados
- **Gestão visual** de relacionamentos
- **Relatórios de produtividade** por centro

## 3. Funcionalidades Transversais

### 3.1 Sistema de Auditoria
- **Log de alterações** em todas as entidades
- **Rastreabilidade** de usuários e timestamps
- **Comparação** de valores antes/depois
- **Relatórios de auditoria** personalizáveis

### 3.2 Importação de Dados
- **Processamento de arquivos** Excel/CSV
- **Validação** de dados na importação
- **Relatórios de inconsistências** encontradas
- **Rollback** de importações com problemas

### 3.3 Relatórios Gerenciais
- **Dashboards** interativos com gráficos
- **Filtros temporais** e por unidade
- **Exportação** em múltiplos formatos
- **Agendamento** de relatórios automáticos

## 4. Arquitetura Técnica

### 4.1 Estrutura de Componentes
```
src/
├── components/          # Componentes reutilizáveis
│   ├── CostCenter/     # Gestão de centros de custo
│   ├── HR/             # Recursos humanos
│   ├── Materials/      # Gestão de materiais
│   ├── Production/     # Produção assistencial
│   └── RecurringCost/  # Custos recorrentes
├── hooks/              # Hooks personalizados
├── interfaces/         # Definições TypeScript
├── pages/              # Páginas da aplicação
└── utils/              # Utilitários e helpers
```

### 4.2 Gestão de Estado
- **Hooks locais** para estado de componentes
- **React Query** para cache de dados (preparado)
- **Context API** para autenticação
- **LocalStorage** para persistência local

### 4.3 Validação de Dados
- **Zod schemas** para validação de formulários
- **React Hook Form** para gestão de formulários
- **Validações customizadas** por módulo
- **Mensagens de erro** contextualizadas

## 5. Interfaces de Usuário

### 5.1 Design System
- **Tailwind CSS** para estilização
- **Shadcn/UI** como biblioteca de componentes
- **Design responsivo** para diferentes dispositivos
- **Temas** claro/escuro (preparado)

### 5.2 Componentes Principais
- **Formulários** com validação em tempo real
- **Tabelas** com paginação e filtros
- **Gráficos** interativos com Recharts
- **Modais** para ações secundárias
- **Notifications** com feedback visual

### 5.3 Navegação
- **Sidebar** colapsível com menu hierárquico
- **Breadcrumbs** para orientação
- **Tabs** para organização de conteúdo
- **Routing** com React Router DOM

## 6. Segurança e Controle de Acesso

### 6.1 Autenticação
- **Sistema de login** implementado
- **Context de autenticação** global
- **Proteção de rotas** por perfil
- **Sessão persistente** entre navegação

### 6.2 Auditoria
- **Log de todas as operações** CRUD
- **Identificação do usuário** em cada ação
- **Timestamp** de todas as modificações
- **Histórico navegável** por entidade

## 7. Integrações Externas

### 7.1 Sistemas de Saúde
- **Importação SIA** (Sistema de Informações Ambulatoriais)
- **Importação SIH** (Sistema de Informações Hospitalares)
- **Mapeamento de códigos** externos para internos
- **Validação** de dados importados

### 7.2 Sistemas Corporativos
- **Folha de pagamento** via importação de arquivos
- **Sistemas de compras** (preparado)
- **ERP financeiro** (preparado)
- **APIs REST** para integrações futuras

## 8. Requisitos Não Funcionais

### 8.1 Performance
- **Carregamento lazy** de componentes
- **Paginação** em listas extensas
- **Cache** de dados frequentemente acessados
- **Otimização** de renders React

### 8.2 Usabilidade
- **Interface intuitiva** com feedback visual
- **Validações** em tempo real
- **Mensagens** de sucesso/erro contextualizadas
- **Loading states** durante operações

### 8.3 Manutenibilidade
- **Código TypeScript** com tipagem forte
- **Componentização** modular
- **Hooks personalizados** para lógica de negócio
- **Documentação** inline no código

## 9. Roadmap de Desenvolvimento

### 9.1 Fase Atual (Implementado)
- ✅ Estrutura base da aplicação
- ✅ Módulos principais (HR, Materiais, Produção, Custos)
- ✅ Sistema de auditoria
- ✅ Relatórios básicos
- ✅ Importação de dados

### 9.2 Próximas Funcionalidades
- 🔄 Integração com Supabase (backend)
- 🔄 Autenticação robusta
- 🔄 Controle de permissões por usuário
- 🔄 Relatórios avançados
- 🔄 API para integrações externas

### 9.3 Funcionalidades Futuras
- ⏳ Dashboard executivo
- ⏳ Análises preditivas
- ⏳ Mobile app companion
- ⏳ Integrações em tempo real
- ⏳ Workflow de aprovações

## 10. Conclusão

O sistema representa uma solução completa para gestão de custos em organizações de saúde, abrangendo desde o controle operacional até relatórios gerenciais estratégicos. A arquitetura modular permite expansão gradual das funcionalidades, enquanto a base tecnológica moderna garante performance e manutenibilidade a longo prazo.

O projeto está estruturado para crescer de forma sustentável, com separação clara de responsabilidades e interfaces bem definidas entre os módulos. A implementação atual fornece uma base sólida para futuras integrações e expansões funcionais.
