# Contrato de Dados (DB Contract)

## Fontes Validadas
- **Arquivo**: `producao.sql` do repositório
- **Banco Vivo**: `producao` no host 127.0.0.1 via MCP interno

## Tabelas Core
- **Prestador (`prestador`)**: Cadastros de prestadores de serviços. PK aparente: `re_cunid` (`varchar(7)`).
- **CBO (`cbo`)**: Classificação Brasileira de Ocupações. PK: `cbo` (`varchar(6)`).
- **Cismetro (`cismetro`)**: Tabela de procedimentos/reajustes. PK: `id` (`bigint unsigned`).
- **Forma de Organização (`forma`)**: Estrutura hierárquica do SUS. PK: `id_registro` (`int`).
- **Rubrica (`s_rub`)**: Tipos de financiamento ou fonte de recursos. PK: `rub_id` (`char(4)`).
- **Autorização de Procedimento de Alta Complexidade (`s_apa`)**: Cabeçalho de guias APAC. **Não confirmada PK explícita** na tabela.
- **Procedimento Principal da APAC (`s_pap`)**: Lançamentos/Itens das guias APAC. **Não confirmada PK explícita** na tabela (`PAP_UID` é chave composta lógica).

## Tipos e Anomalias
- Identificadores (IDs) em múltiplos locais são tratados como `varchar` (ex.: `re_cunid`, `PAP_UID`, `APA_UID`, `PAP_NUM`, `cbo`). Essas colunas sofrem de ordenação alfabética (lexicográfica) em vez de numérica (ex.: '10' aparece antes de '2'). Necessário o uso de `CAST` para agregadores e ordenações estritas numéricas.
- Informações temporais em string (Ex.: `APA_EMISSA varchar(8)`, `APA_DTNASC varchar(8)`). Possível gargalo com comparações e indexação baseada em datas.
- Colunas de valor numérico não formatado (ex.: `PAP_QT_P double`) em tabelas críticas em vez de `decimal()`.

## Índices e Engines
- Mistura de Engines detectada no status do banco local: 
  - **InnoDB**: `cbo`, `cismetro`, `forma`, `s_apa`, `s_pap`
  - **MyISAM**: `prestador`, `s_rub`
- Uso de índices simples e compostos presentes. `s_pap` utiliza um índice chave forte composto `idx_pap_composite` (`PAP_UID`, `PAP_CMP`, `PAP_NUM`) indicativo dos joins frequentes com a `s_apa`. 
- `s_apa` e `s_pap` sofrem da **ausência total de uma Primary Key** formalizada no schema lido do banco, usando apenas índices KEY tradicionais.

## Riscos
- **Ausência de Foreign Keys (FKs)** documentadas nestas 7 tabelas cruciais. A integridade referencial não é garantida pelo banco. Relacionar dados de dependências no ORM precisará de configuração de chaves puramente lógicas; e existe alto risco de existência de dados órfãos se inseridos incorretamente pelo ecossistema.
- O fato de existirem tabelas pesadas em MyISAM (`prestador`) tira a capacidade integral de Table-Level Locking em favor de row-level ao fazer writes, o que afeta workers de conciliação. 

## Regras Impostas ao Novo Desenvolvimento
- Não propor e não executar instruções de `ALTER TABLE` ou modificação estrutural.
- Tratar tipos legados de IDs via decorators/casts diretamente pela modelagem de APIs Rest.
- Compensar na camada de Serviços/Models a manutenção das relações "soft-linked" causadas pela ausência de Constraints Relacionais (FKs).
