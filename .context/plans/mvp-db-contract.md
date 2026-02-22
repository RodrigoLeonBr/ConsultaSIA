---
title: "MVP DB Contract (Immutable DB Contract + ORM Mapping)"
status: pending
phases:
  - P
  - R
  - E
  - V
  - C
agents:
  - architect-specialist
  - backend-specialist
  - database-specialist
---

# MVP DB Contract

## Objetivos
- Estabelecer o contrato imutável com o banco MySQL/MariaDB do XAMPP baseado em `producao.sql`.
- Mapear a base de dados em leitura (e escrita estritamente permitida) através de um ORM no Node.js (ex: Prisma, TypeORM, ou Sequelize) respeitando a proibição de alterações de DDL em tabelas core.
- Assegurar que migrations automáticas para o banco de produção estejam desabilitadas.

## Tarefas
- [ ] Analisar o arquivo `producao.sql` para extrair entidades do core.
- [ ] Configurar conexão com o banco legado via variáveis de ambiente.
- [ ] Definir os modelos (models/entities) no ORM compatíveis com o schema existente sem trigger de sincro/migrations automáticas.
- [ ] Documentar quais tabelas são apenas leitura (core) e quais poderão receber escritas se aplicável.

## Dependências
- `mvp-foundation.md` (Projeto Node Base).
- Acesso e estabilização de dump do `producao.sql`.

## Critérios de Aceite
- ORM realiza selects com sucesso na base definida em `producao.sql` sem criar/alterar tabelas do sistema legado.
- Tentativas de migração automática dão erro proposital ou são bloqueadas por configuração.

## Riscos e Mitigação
- **Risco**: Configuração do ORM tentar "sincronizar" esquemas e alterar as tabelas core.
  **Mitigação**: Desabilitar explicitamente `synchronize` no TypeORM ou gerar cliente Prisma usando apenas `db pull` (introspection). Utilizar usuário do banco sem permissões DDL se possível.
