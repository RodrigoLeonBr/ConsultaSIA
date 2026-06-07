
# Módulo Dados de Produção - Sistema de Gestão de Custos em Saúde

## Visão Geral
O módulo de Dados de Produção é responsável pelo registro, controle e análise da produção assistencial da organização. Atua como fonte primária de dados para cálculo de custos unitários e análise de produtividade, integrando-se com sistemas externos (SIA/SIH) e permitindo registros manuais complementares.

## Estrutura do Módulo

### 1. Tipos de Produção
**Função:** Cadastro e classificação dos diferentes tipos de procedimentos e atividades assistenciais.

**Estrutura de Dados:**
```typescript
interface ProductionType {
  id: string;
  code: string;
  name: string;
  description: string;
  unit: string; // Unidade de medida (consulta, exame, procedimento, etc.)
  category: string; // Categoria do procedimento
  isActive: boolean;
  createdAt: Date;
  updatedBy: string;
}
```

**Relacionamentos:**
- **1:N** com Registros de Produção Mensal
- **1:N** com Mapeamentos de Códigos SIA/SIH
- **N:N** com Centros de Custos (via tabela de associação)

### 2. Registros de Produção Mensal
**Função:** Armazenamento da produção efetiva realizada por unidade e período.

**Estrutura de Dados:**
```typescript
interface MonthlyProduction {
  id: string;
  organizationalUnitId: string;
  organizationalUnitName?: string; // Desnormalizado para consultas
  productionTypeId: string;
  productionTypeName?: string; // Desnormalizado para consultas
  competenceMonth: number;
  competenceYear: number;
  quantity: number;
  comments?: string;
  isValidated: boolean;
  isImported: boolean; // Diferencia dados importados vs manuais
  importId?: string; // Referência à importação SIA/SIH
  createdAt: Date;
  createdBy: string;
  lastModifiedAt?: Date;
  lastModifiedBy?: string;
}
```

**Relacionamentos:**
- **N:1** com Unidades Organizacionais
- **N:1** com Tipos de Produção
- **N:1** com Importações SIA/SIH (quando aplicável)

### 3. Importações SIA/SIH
**Função:** Controle do processo de importação automática de dados dos sistemas oficiais.

**Estrutura de Dados:**
```typescript
interface SiaImport {
  id: string;
  importDate: Date;
  importBy: string;
  competenceMonth: number;
  competenceYear: number;
  fileName: string;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  recordsProcessed: number;
  recordsImported: number;
  recordsFailed: number;
  errorLog?: string;
  completedAt?: Date;
}
```

**Relacionamentos:**
- **1:N** com Registros de Produção Mensal (importação gera múltiplos registros)

### 4. Mapeamento de Códigos
**Função:** Tradução entre códigos externos (SIA/SIH/SIGTAP) e tipos de produção internos.

**Estrutura de Dados:**
```typescript
interface CodeMapping {
  id: string;
  externalCode: string; // Código SIA/SIH/SIGTAP
  externalSystem: 'SIA' | 'SIH' | 'SIGTAP' | 'other';
  productionTypeId: string;
  conversionFactor: number; // Fator de conversão se necessário
  isActive: boolean;
  createdAt: Date;
  updatedAt?: Date;
  updatedBy?: string;
}
```

**Relacionamentos:**
- **N:1** com Tipos de Produção
- Usado no processo de importação SIA/SIH

### 5. Log de Alterações de Produção
**Função:** Auditoria completa de todas as modificações nos dados de produção.

**Estrutura de Dados:**
```typescript
interface ProductionChangeLog {
  id: string;
  productionId: string;
  changeType: 'create' | 'update' | 'delete';
  changedBy: string;
  changedAt: Date;
  previousValue?: any;
  newValue?: any;
  justification?: string;
}
```

## Fluxos de Dados

### Fluxo de Importação SIA/SIH
1. **Upload de Arquivo** → Sistema recebe arquivo SIA/SIH
2. **Validação** → Verifica estrutura e integridade dos dados
3. **Mapeamento** → Traduz códigos externos para tipos internos
4. **Processamento** → Cria registros de produção mensal
5. **Validação Final** → Permite revisão antes da confirmação
6. **Integração** → Dados ficam disponíveis para análises

### Fluxo de Registro Manual
1. **Seleção de Parâmetros** → Unidade, tipo, competência
2. **Inserção de Quantidade** → Registro da produção efetiva
3. **Validação** → Conferência dos dados inseridos
4. **Aprovação** → Confirmação dos registros
5. **Auditoria** → Log automático das alterações

### Fluxo de Análise e Relatórios
1. **Coleta de Dados** → Agregação de registros por diferentes critérios
2. **Processamento** → Cálculos de totais, médias, tendências
3. **Visualização** → Gráficos e tabelas comparativas
4. **Exportação** → Relatórios para análise externa

## Objetivo no Sistema de Gestão de Custos

### Propósito Estratégico
- **Base para Custeio:** Fornecer denominador para cálculo de custos unitários
- **Análise de Produtividade:** Medir eficiência das unidades assistenciais
- **Planejamento Orçamentário:** Base histórica para projeções futuras
- **Compliance Regulatória:** Atendimento às exigências de prestação de contas

### Impacto na Gestão de Custos
1. **Custo Unitário por Procedimento:** Permite calcular custo/consulta, custo/exame, etc.
2. **Análise de Eficiência:** Identifica unidades com melhor relação custo-benefício
3. **Otimização de Recursos:** Direciona investimentos para áreas de maior produtividade
4. **Controle de Qualidade:** Monitora variações na produção que podem indicar problemas

### Indicadores de Performance
- Produção total por unidade/período
- Custo unitário por tipo de procedimento
- Variação da produção ao longo do tempo
- Eficiência comparativa entre unidades
- Taxa de crescimento da produção assistencial

## Integrações Críticas

### Com Sistema SIA/SIH
- **Importação Automática:** Reduz trabalho manual e erros
- **Atualização Periódica:** Mantém dados sempre atualizados
- **Validação Cruzada:** Compara dados internos com oficiais

### Com Módulo de Custos
- **Denominador de Custeio:** Produção como base para rateio
- **Análise de Lucratividade:** Identifica procedimentos mais/menos rentáveis
- **Projeção de Custos:** Estima impacto de mudanças na produção

### Com Módulo RH
- **Produtividade por Profissional:** Relaciona produção com equipe alocada
- **Dimensionamento de Equipes:** Base para cálculo de necessidade de pessoal

## Considerações de Implementação
- **Flexibilidade de Mapeamento:** Permite diferentes classificações de procedimentos
- **Validação de Consistência:** Evita duplicações e inconsistências
- **Performance de Consultas:** Otimizada para relatórios agregados
- **Integração Robusta:** Tratamento de erros em importações
- **Auditoria Completa:** Rastreabilidade total de alterações
- **Backup de Segurança:** Preservação de dados históricos críticos

## Relatórios Principais
- **Produção por Unidade:** Comparativo mensal/anual entre unidades
- **Tendências de Produção:** Análise temporal de crescimento/declínio
- **Resumo por Tipo:** Distribuição da produção por categoria de procedimento
- **Eficiência Operacional:** Indicadores de produtividade e custos unitários
