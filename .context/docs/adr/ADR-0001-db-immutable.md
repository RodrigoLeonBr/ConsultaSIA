# ADR 0001: Contrato de Banco de Dados Imutável

## Status
Aceito

## Contexto
O projeto ConsultaProd v3 rodará em paralelo ao sistema legado em PHP/Laravel. Ambos compartilharão o banco MySQL/MariaDB (XAMPP). O schema atual está homologado e estável. 

## Decisão
O core da modelagem do banco de dados (conforme `producao.sql`) é imutável. Migrations automáticas do ORM estão estritamente proibidas no ambiente de produção para tabelas core. O aplicativo Node.js deve agir primariamente como leitor destas tabelas. É permitida a criação de tabelas auxiliares (ex: jobs, cache, reports) desde que não alterem a integridade ou schema original mantido pelo projeto anterior.

## Consequências
- Diminui drasticamente o risco de corrupção ou quebra do sistema legado.
- Obriga a equipe a manter tabelas complementares separadas.
- O Node.js deve ter credenciais e configurações de ORM (ex: `synchronize: false` no TypeORM ou `db pull` no Prisma) que impossibilitem modificações não intencionais na DDL.
