# ADR-0006: MySQL Externo por Padrão

**Data**: 2026-02-22  
**Status**: Aceito  

## Contexto
O banco de dados do ConsultaProd contém milhões de linhas em tabelas críticas como `s_apa` e `s_pap` (`producao.sql` tem > 2GB de volume histórico isolado). Colocar esse banco dentro de um container Docker gerenciado pelo novo ecossistema V3 traria alto risco em operações de volume persistente, overhead de migração inicial e duplicidade de dados em servidores físicos que já o processam muito bem (Legado PHP/XAMPP).

## Decisão
Determinar que a **Conexão ao Banco de Dados Legado será estritamente EXTERNA às fronteiras dos containers Node.js**.
A aplicação V3 fará bindings via variáveis de ecossistema (`DB_HOST`, `DB_PORT`) usando DNS de host virtual (e.g. `host.docker.internal`) referenciando o MySQL do servidor raiz (ou remoto).

## Consequências
- **Positivo**: Protege a integridade histórica dos dados (Sem migrações arriscadas).
- **Positivo**: Performance nativa de File System pelo servidor MySQL do host, que pode estar otimizado para o hardware corrente (sem overlay de filesystem em cima do docker daemon).
- **Negativo**: É preciso lidar ativamente com as limitações do Firewall do S.O hospedeiro e regras de Grants de usuário do MySQL permitindo IPS não nativos.
