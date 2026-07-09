# ADR 0001: Contrato de Banco de Dados Imutável

## Status
Aceito

## Contexto
O ConsultaProd opera sobre o banco MySQL/MariaDB `producao` (XAMPP). O schema está homologado e alinhado ao contrato DATASUS.

## Decisão
O core da modelagem (`producao.sql`) é **imutável**. Migrations automáticas estão proibidas para tabelas core em produção. Apenas tabelas auxiliares (`report_job`, `users`, `sessions`, etc.) podem ser alteradas via migration.

## Consequências
- Reduz risco de corrupção ou quebra de dados históricos.
- Tabelas complementares ficam separadas do schema DATASUS.
- Queries devem usar CAST em campos numéricos VARCHAR e filtro de competência obrigatório em `s_prd`.
