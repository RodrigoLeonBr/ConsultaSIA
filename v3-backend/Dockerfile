# Usa imagem Alpine Node.js LTS (Atualmente 20/22) para menor superfície de ataque e tamanho
FROM node:22-alpine AS builder

# Diretório de trabalho dentro do container
WORKDIR /usr/src/app

# Copia arquivos de dependência primeiro para aproveitar cache de camadas do Docker
COPY package*.json ./

# Instala todas as dependências (incluindo dev para buildar o NestJS) via npm ci para consistência
RUN npm ci

# Copia o código-fonte
COPY . .

# Faz o build compaginando o TypeScript para JavaScript no diretório /dist
RUN npm run build

# Opcional: Remove arquivos devDependencies para deixar a imagem de runtime mais leve
# RUN npm prune --production

# ==========================================
# Estágio de Runtime (Produção)
# ==========================================
FROM node:22-alpine AS runtime

# Define modo de produção
ENV NODE_ENV=production

# Diretório de trabalho final
WORKDIR /usr/src/app

# Copia os manifestos e instala apenas dependências de produção para emagrecer a imagem final
COPY package*.json ./
RUN npm ci --only=production && npm cache clean --force

# Copia a compilação finalizada do estágio "builder"
COPY --from=builder /usr/src/app/dist ./dist

# Configurações de segurança: Executa como usuário não-root (o node nativamente pré-existe no alpine)
USER node

# A porta será exposta no docker-compose (3000 padrão da API)
EXPOSE 3000

# Healthcheck simples: Pode ser ativado via Docker Compose ou injetado aqui.
# Exige adição de um endpoint GET /health no NestJS
# HEALTHCHECK --interval=30s --timeout=5s --start-period=5s --retries=3 \
#   CMD wget --no-verbose --tries=1 --spider http://localhost:3000/health || exit 1

# O Comando padrão levanta a API. O Worker sobrescreverá via docker-compose (command: npm run start:worker)
CMD ["node", "dist/main.js"]
