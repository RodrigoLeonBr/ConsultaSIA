# Guia do Usuário - Relatórios em Matriz por Competência

## 📊 Visão Geral

A funcionalidade de **Relatórios em Matriz por Competência** permite transformar dados de produção hospitalar em uma visualização de tabela pivot, onde as competências (períodos) aparecem como colunas e outras categorias como linhas. Isso facilita análises temporais e comparativas.

## 🚀 Como Usar

### 1. Acessando a Funcionalidade

1. Acesse o sistema ConsultaProd
2. Navegue para **Relatórios de Produção** (`/relatorios`)
3. A funcionalidade de matriz será ativada automaticamente quando você selecionar o campo **"Data Competência"**

### 2. Configurando um Relatório Matriz

#### Passo 1: Selecionar Campos
- **Obrigatório**: Marque o campo **"Data Competência"** 
- **Adicional**: Selecione pelo menos um campo adicional (ex: Prestador, Procedimento, CBO)
- **Numéricos**: Inclua campos como "Quantidade" ou "Valor" para agregações

#### Passo 2: Escolher Visualização
- Após selecionar "Data Competência", aparecerá a seção **"Tipo de Visualização"**
- Escolha entre:
  - **Lista Simples**: Formato tradicional de tabela
  - **Matriz por Competência**: Formato pivot table

#### Passo 3: Aplicar Filtros (Recomendado)
- **Filtro de Competência**: Limite o período (ex: 01/2024 a 12/2024)
- **Outros Filtros**: Prestador específico, tipo de procedimento, etc.
- **Dica**: Use filtros para melhorar a performance com grandes volumes

#### Passo 4: Gerar Relatório
- Clique em **"🔍 Gerar Relatório"**
- Aguarde o processamento (matrizes podem demorar mais)
- Visualize o resultado na tela

## 📋 Estrutura da Matriz

### Layout da Matriz
```
Categoria        | 01/2024 | 02/2024 | 03/2024 | Total
Hospital ABC     |   1.250 |   1.180 |   1.320 | 3.750
Hospital XYZ     |     890 |     920 |     850 | 2.660
Clínica DEF      |     450 |     480 |     520 | 1.450
Total            |   2.590 |   2.580 |   2.690 | 7.860
```

### Elementos da Matriz

- **Coluna Categoria**: Combinação dos campos selecionados (ex: "Hospital ABC - Consultas")
- **Colunas de Competência**: Períodos no formato MM/AAAA
- **Coluna Total**: Soma horizontal de cada linha
- **Linha Total**: Soma vertical de cada competência
- **Total Geral**: Soma de todos os valores

## 📊 Tipos de Dados Suportados

### Campos de Agrupamento
- **Prestador**: Agrupa por unidade de saúde
- **Procedimento**: Agrupa por tipo de procedimento
- **CBO**: Agrupa por ocupação profissional
- **Rubrica**: Agrupa por tipo de financiamento

### Campos Numéricos (Agregados)
- **Quantidade**: Soma das quantidades por período
- **Valor**: Soma dos valores monetários por período
- **Campos Cismetro**: Valores unitários e totais

## 📁 Exportações

### Formatos Disponíveis

#### Excel (.xlsx)
- Mantém formato de matriz com colunas separadas
- Formatação automática de números e moedas
- Totalizações incluídas
- Larguras de coluna otimizadas

#### PDF
- Orientação paisagem automática para matrizes grandes
- Layout profissional com cabeçalho
- Formatação brasileira (R$ e separadores)
- Ajuste automático de fonte para caber na página

#### CSV
- Estrutura pivot mantida
- Separador ponto-vírgula (;)
- Codificação UTF-8 com BOM
- Compatível com Excel brasileiro

### Como Exportar
1. Configure e gere a matriz
2. Clique no formato desejado:
   - **📊 Exportar Excel**
   - **📄 Exportar PDF** 
   - **📋 Exportar CSV**
3. O arquivo será baixado automaticamente

## 🎯 Casos de Uso Práticos

### 1. Análise de Produção Mensal por Prestador
**Objetivo**: Ver como cada hospital produziu ao longo dos meses

**Configuração**:
- Campos: Data Competência + Prestador + Quantidade
- Filtros: Competência entre 01/2024 e 12/2024
- Visualização: Matriz

**Resultado**: Tabela com hospitais nas linhas e meses nas colunas

### 2. Evolução de Procedimentos por Trimestre
**Objetivo**: Acompanhar tendência de procedimentos específicos

**Configuração**:
- Campos: Data Competência + Procedimento + Quantidade + Valor
- Filtros: Procedimentos específicos + período trimestral
- Visualização: Matriz

**Resultado**: Evolução temporal de cada procedimento

### 3. Análise de Faturamento por Tipo de Financiamento
**Objetivo**: Comparar diferentes fontes de financiamento

**Configuração**:
- Campos: Data Competência + Rubrica + Valor
- Filtros: Período anual
- Visualização: Matriz

**Resultado**: Comparativo mensal por tipo de financiamento

## ⚡ Dicas de Performance

### Para Matrizes Grandes
- **Use filtros de competência**: Limite a 12-24 meses
- **Filtre prestadores**: Selecione unidades específicas
- **Evite muitos campos**: Máximo 3-4 campos de agrupamento
- **Monitore o tempo**: Matrizes podem levar 30+ segundos

### Para Melhor Visualização
- **Competências limitadas**: Máximo 12 colunas para boa legibilidade
- **Use scroll horizontal**: Para matrizes com muitas colunas
- **Modo compacto**: Use o botão "Compacto" para economizar espaço
- **Dispositivos móveis**: Matriz se adapta automaticamente

## 🔧 Funcionalidades Avançadas

### Controles de Visualização
- **Compacto/Completo**: Alterna densidade da tabela
- **Scroll responsivo**: Navegação otimizada para mobile
- **Colunas fixas**: Categoria e Total ficam fixos durante scroll
- **Hover effects**: Destaque visual ao passar mouse

### Validações Automáticas
- **Competência obrigatória**: Sistema valida se campo está selecionado
- **Limite de dados**: Aviso quando há muitas competências
- **Campos mínimos**: Pelo menos um campo além de competência
- **Timeout protection**: Cancelamento automático se demorar muito

## ❓ Perguntas Frequentes

### P: Por que a matriz demora mais que a lista?
**R**: A matriz precisa processar e reorganizar os dados em estrutura pivot, o que requer mais processamento. Use filtros para acelerar.

### P: Quantas competências posso incluir?
**R**: Recomendamos máximo 24 meses (2 anos). Mais que isso pode impactar a performance e legibilidade.

### P: A matriz funciona em dispositivos móveis?
**R**: Sim! A matriz se adapta automaticamente com scroll horizontal e layout otimizado para touch.

### P: Posso salvar configurações de matriz?
**R**: Atualmente não, mas você pode exportar os resultados e recriar a configuração quando necessário.

### P: Como interpretar células vazias?
**R**: Células com "-" ou "0" indicam que não há dados para aquela combinação categoria/competência.

## 🆘 Solução de Problemas

### Erro: "Campo Data Competência é obrigatório"
**Solução**: Marque o checkbox "Data Competência" na seleção de campos.

### Erro: "Muitas competências encontradas"
**Solução**: Adicione filtros de competência para limitar o período (ex: últimos 12 meses).

### Matriz não carrega ou demora muito
**Soluções**:
1. Adicione mais filtros para reduzir volume de dados
2. Limite o período de competências
3. Selecione prestadores específicos
4. Use o botão "Cancelar Pesquisa" se necessário

### Exportação falha
**Soluções**:
1. Tente reduzir o tamanho da matriz com filtros
2. Use formato CSV se Excel/PDF falharem
3. Verifique se há dados suficientes na matriz

### Layout quebrado em mobile
**Soluções**:
1. Use scroll horizontal para navegar
2. Ative o modo "Compacto" 
3. Rotacione o dispositivo para paisagem
4. Considere usar menos colunas (competências)

## 📞 Suporte

Para dúvidas ou problemas não cobertos neste guia:
1. Verifique os logs do sistema em caso de erros
2. Teste com dados menores primeiro
3. Consulte a documentação técnica para desenvolvedores
4. Entre em contato com o suporte técnico

---

**Sistema ConsultaProd - Relatórios em Matriz por Competência**  
Versão 2.1 - Guia do Usuário  
Atualizado em: Dezembro 2025