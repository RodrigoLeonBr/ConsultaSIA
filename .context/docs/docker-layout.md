# Docker Topology Spec (v3-backend + v3-frontend)

## Contexto e Inventário Atual
- **Backend API (NestJS)**: 
  - Diretório: `v3-backend`
  - Command: `npm run start:api`
  - Porta: `3000`
- **Worker (Processo assíncrono NestJS)**: 
  - Diretório: `v3-backend`
  - Command: `npm run start:worker` (`RUN_WORKER=true`)
- **Frontend Web (React/Vite)**: 
  - Diretório: `v3-frontend`
  - Build: `npm run build`
  - Preview/Serve: `npm run preview` (Porta padrão 4173)
- **Host Dependencies**: O banco de dados MySQL (`producao`) continuará externo no XAMPP na máquina host.

## Proposta de Layout Docker
A topologia utilizará um modelo multi-container simples via `docker-compose.yml` na raiz do projeto, preservando a estrutura exata das pastas originais, conectando-se ao XAMPP vivo no Host (`host.docker.internal`).

### Nomes dos Serviços (`docker-compose.yml` root)
1. **`api`**
   - **Build**: `./v3-backend/Dockerfile`
   - **Comando**: `npm run start:prod` (ou equivalente de produção pós-build)
   - **Porta Exposta**: `3000:3000`
   - **Variáveis**: `DB_HOST=host.docker.internal`, `DB_PORT=3306`, `DB_NAME=producao`, `DB_USER=hospital`, etc.

2. **`worker`**
   - **Build**: `./v3-backend/Dockerfile`
   - **Comando**: `npm run start:worker`
   - **Variáveis**: Mesmas da `api` + `RUN_WORKER=true`. Não expõe portas diretamente pois apenas pesquisa o DB.

3. **`web`**
   - **Build**: `./v3-frontend/Dockerfile`
   - **Comando**: Servidor estático (ex.: Nginx embarcado na imagem servindo `/usr/share/nginx/html`).
   - **Porta Exposta**: `8080:80`
   - **Variáveis (Build Args)**: `VITE_API_URL=http://localhost:3000`

### Checklist de Arquivos a Serem Criados na Próxima Etapa
- [ ] `docker-compose.yml` (Na pasta raiz `consultasia`)
- [ ] `v3-backend/Dockerfile` (Multi-stage build enxuto do NestJS)
- [ ] `v3-backend/.dockerignore` (Evitar sync de `node_modules` locais)
- [ ] `v3-frontend/Dockerfile` (Multi-stage build Vite -> Nginx Alpine)
- [ ] `v3-frontend/.dockerignore`
- [ ] `.env` de Docker local (Mapeado no compose para setar `host.docker.internal` dinamicamente)
