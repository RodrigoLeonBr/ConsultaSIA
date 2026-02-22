# Requirements Document - Relatórios em Matriz por Competência

## Introduction

Esta funcionalidade permitirá aos usuários transformar relatórios de listagem simples em visualizações de matriz (Pivot Table) quando o campo "Data Competência" estiver selecionado. A matriz organizará os dados com competências como colunas e outras categorias como linhas, facilitando análises temporais e comparativas.

## Requirements

### Requirement 1

**User Story:** Como um usuário do sistema de relatórios, eu quero poder alternar entre visualização de lista simples e matriz quando selecionar o campo "Data Competência", para que eu possa analisar dados de forma temporal e comparativa.

#### Acceptance Criteria

1. WHEN o usuário seleciona o campo "Data Competência" no gerador de relatórios THEN o sistema SHALL exibir uma opção de visualização "Matriz por Competência"
2. WHEN o usuário escolhe visualização "Matriz" THEN o sistema SHALL transformar os dados em formato de tabela pivot
3. WHEN o usuário escolhe visualização "Lista" THEN o sistema SHALL manter o formato atual de listagem simples
4. IF nenhum campo "Data Competência" estiver selecionado THEN a opção "Matriz" SHALL estar desabilitada

### Requirement 2

**User Story:** Como um analista de dados, eu quero que as competências apareçam como colunas na matriz, para que eu possa comparar valores entre diferentes períodos facilmente.

#### Acceptance Criteria

1. WHEN a visualização matriz é ativada THEN as competências SHALL aparecer como colunas no formato "MM/AAAA"
2. WHEN há múltiplas competências nos dados THEN elas SHALL ser ordenadas cronologicamente
3. WHEN uma competência não possui dados para uma linha THEN SHALL exibir "0" ou "-" conforme o tipo de campo
4. WHEN há mais de 12 competências THEN o sistema SHALL permitir scroll horizontal

### Requirement 3

**User Story:** Como um usuário do sistema, eu quero que os demais campos selecionados formem as linhas da matriz, para que eu possa ver a distribuição dos dados por categoria e tempo.

#### Acceptance Criteria

1. WHEN campos não-competência são selecionados THEN eles SHALL formar as linhas da matriz
2. WHEN múltiplos campos formam as linhas THEN eles SHALL ser agrupados hierarquicamente
3. WHEN campos numéricos são incluídos THEN eles SHALL ser agregados (SUM) por competência
4. WHEN campos de texto são incluídos THEN eles SHALL ser agrupados sem agregação

### Requirement 4

**User Story:** Como um usuário do sistema, eu quero poder exportar a matriz em diferentes formatos, para que eu possa usar os dados em outras ferramentas de análise.

#### Acceptance Criteria

1. WHEN a visualização matriz está ativa THEN as exportações (Excel, PDF, CSV) SHALL manter o formato de matriz
2. WHEN exportando para Excel THEN as competências SHALL aparecer como colunas separadas
3. WHEN exportando para PDF THEN a matriz SHALL ser formatada para caber na página
4. WHEN há muitas colunas THEN o PDF SHALL usar orientação paisagem automaticamente

### Requirement 5

**User Story:** Como um administrador do sistema, eu quero que a funcionalidade de matriz seja performática mesmo com grandes volumes de dados, para que os usuários tenham uma experiência fluida.

#### Acceptance Criteria

1. WHEN há mais de 100.000 registros THEN o sistema SHALL processar a matriz em até 30 segundos
2. WHEN a query de matriz é executada THEN ela SHALL usar índices otimizados
3. WHEN há timeout na query THEN o sistema SHALL exibir mensagem de erro clara
4. WHEN a matriz é muito grande THEN o sistema SHALL implementar paginação ou limitação

### Requirement 6

**User Story:** Como um usuário do sistema, eu quero poder aplicar filtros na visualização matriz, para que eu possa focar em dados específicos mantendo a estrutura temporal.

#### Acceptance Criteria

1. WHEN filtros são aplicados THEN eles SHALL afetar tanto linhas quanto colunas da matriz
2. WHEN um filtro de competência é aplicado THEN apenas as competências filtradas SHALL aparecer como colunas
3. WHEN filtros de outros campos são aplicados THEN apenas as linhas correspondentes SHALL aparecer
4. WHEN todos os filtros são removidos THEN a matriz SHALL voltar ao estado completo

### Requirement 7

**User Story:** Como um usuário do sistema, eu quero ver totalizações na matriz, para que eu possa entender rapidamente os valores agregados por linha e coluna.

#### Acceptance Criteria

1. WHEN campos numéricos estão na matriz THEN SHALL haver uma coluna "Total" à direita
2. WHEN campos numéricos estão na matriz THEN SHALL haver uma linha "Total" na parte inferior
3. WHEN há subtotais por agrupamento THEN eles SHALL aparecer em linhas intermediárias
4. WHEN não há dados numéricos THEN as totalizações SHALL ser omitidas