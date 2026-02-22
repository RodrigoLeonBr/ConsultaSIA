# Deployment Guide: Docker + MySQL Externo (V3)

Este documento dita as regras de infraestrutura para o V3 operar acoplado ao banco de dados legado (`producao`). O ambiente Node.js roda 100% conteinerizado, mas o MySQL não.

## Cenário 1: Tudo no "Mesmo Host" (Docker Desktop/Engine Engine + XAMPP)
Quando a stack Docker (API, Worker, Web) roda na *mesma máquina física* que o MySQL legado.

- **O Problema do `127.0.0.1`**: Configurar `DB_HOST=127.0.0.1` ou `localhost` no `.env` do back-end fará o container procurar o banco dentro da *própria rede isolada* do container (onde o MySQL não existe), gerando um erro de "Connection Refused".
- **A Solução (Docker Desktop Windows/Mac)**: Use **`DB_HOST=host.docker.internal`**. O Docker resolve magicamente esse DNS virtual de volta pro Kernel da sua máquina física (XAMPP).
- **A Solução (Linux Nativo)**: O `host.docker.internal` pode não existir sem flags extras no compose. A alternativa universal é descobrir o IP da ponte do Docker (geralmente `172.17.0.1`) ou o IP da rede local da máquina (Ex. `192.168.1.50`) e cravá-lo no `.env`.

## Cenário 2: Multihost (Docker Host A + MySQL Host B)
Cenário clássico de produção onde a VM da Aplicação (Nest/React) é escalada ou isolada da VM do Banco de Dados.

- **Configuração**: O `.env` recebe o IP/Hostname público ou da VPC privada: `DB_HOST=10.0.X.Y`.
- **Portas e Firewall**: 
  - A porta `3306/TCP` do Servidor MySQL "B" precisa estar aberta para o IP da VM do Docker "A".
  - O usuário do MySQL (`hospital`) precisa ter **módulos de grants (`%` ou IP específico)** permitindo tráfego externo. Se estiver travado em `'hospital'@'localhost'`, a conexão do Host A falhará com Erro "1045 Access Denied".
  
---

## Como testar conectividade do Container -> DB
Antes de chorar que o Node.js "não funciona", verifique se o ambiente Docker físico tem rota para sua base de dados.

Suba um container descartável de Alpine no modo interativo para testar se há firewall no meio do caminho ou erro de DNS:
```bash
docker run --rm -it alpine sh
# Dentro do Container: 
apk add netcat-openbsd
nc -zv [SEU_DB_HOST_AQUI] 3306

# Esperado: 
# Connection to [DB_HOST] 3306 port [tcp/mysql] succeeded!
```

---

## Estratégia de Rollback por Tag
Substitua sempre o `latest` nos arquivos Docker/Compose por tags literais atreladas ao SemVer ou Commit Hash nos ambientes produtivos.

1. O CI constrói `consultasia/v3-backend:v1.2.0`.
2. Um bug crítico é encontrado logo após o deployment?
3. Parecia tudo quebrado: `$ docker-compose down`
4. Mude a tag no seu `docker-compose.yml` de volta para `image: consultasia/v3-backend:v1.1.9`.
5. Recrie a topologia instantaneamente e com segurança lógca intocada: `$ docker-compose up -d`.
