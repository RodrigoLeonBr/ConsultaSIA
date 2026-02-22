# Performance Playbook: Otimização Pós-Relacional (V3)

O schema do V3 atua sobre tabelas que sofrem desde misturas de engine (MyISAM vs InnoDB) até a ausência de relacionamentos literais. A execução do código Node.js deve observar estritamente as condutas abaixo.

## Práticas para Queries com Varchar Numérico
Tabelas como a `s_apa` e `s_pap` tratam IDs numéricos com formatação `VARCHAR`, por exemplo `APA_NUM varchar(13)`. Cuidado ao fazer group by ou order by no Node.js.

**Ruim (Ordenação Lexicográfica Indesejada):**
```typescript
// '10' vem antes de '2' textualmente.
.orderBy('pap.PAP_UID', 'DESC') 
```

**Bom (Casting ou Extração Nativa na Query):**
```typescript
// Casting via builder ou query mapping
.orderBy('CAST(pap.PAP_UID AS UNSIGNED)', 'DESC')
```
*Se a lentidão não permitir casting direto no indexado, transfira o reordenamento para o backend local (para arrays paginados sub-10K itens).*

## Checklist de Performance

- [ ] **Evitar Full Table Scans Obvios**: Usar sempre restrições parciais nas queries com suporte de índice. Por exemplo, em `s_pa`, buscar indexações como a data de emissão antes de analisar colunas livres que matam a performance.
- [ ] **EXPLAIN em Queries Customizadas**: O Query Builder ou Raw SQL deve sempre ser validado pelo desenvolvedor executando e reportando o `EXPLAIN <query>`. O `type` na resposta não pode ser `ALL` na maioria dos casos complexos.
- [ ] **Paginação Obrigatória (Frontend)**: O Frontend NUNCA deve solicitar `limit > 500`. 
- [ ] **Apenas Buscar Propriedades Alvo (`select`)**: O `producao.sql` tem tabelas `s_apa` com dezenas de colunas, como `APA_VARIA varchar(141)`. Usar sempre DTOs ou Models que extraiam somente as 5/6 propriedades necessárias àquela tela em vez de mapear objetos inteiros (`SELECT *`).

## Limites de Concorrência do Worker

Sem controle distribuído Redis e dependendo do Table-Lock (MyISAM na tabela `prestador`) e do CPU bound do processo Node.js, é imprescindível limitar o paralelismo interno.

- **Workers Simutâneos Max**: Somente lidar com **1 Job Pesado por vez** no loop de polling de uma única instância.
- Se houver escalabilidade horizontal (Múltiplas instâncias do V3 rodando PM2), introduza Lock Transacional ao mudar status: `UPDATE report_job SET status='running' WHERE status='queued' AND id=? LIMIT 1`.
