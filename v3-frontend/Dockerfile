# ==========================================
# Estágio de Build (Node.js)
# ==========================================
FROM node:22-alpine AS builder

# Diretório de trabalho dentro do container
WORKDIR /usr/src/app

# Copia arquivos de dependência
COPY package*.json ./

# Instala todas as dependências via npm ci para consistência (necessita package-lock.json)
RUN npm ci

# Copia o código-fonte
COPY . .

# Aceita a URL da API como variável de build para injetar no processo do Vite
ARG VITE_API_URL
ENV VITE_API_URL=${VITE_API_URL}

# Compila o TypeScript e constrói os estáticos do Vite para o diretório /dist
RUN npm run build

# ==========================================
# Estágio de Runtime (Nginx)
# ==========================================
FROM nginx:alpine AS runtime

# Remove os arquivos HTML padrão do Nginx
RUN rm -rf /usr/share/nginx/html/*

# Copia os arquivos estáticos gerados no estágio de build para o diretório que o Nginx serve
COPY --from=builder /usr/src/app/dist /usr/share/nginx/html

# Copia nossa configuração de roteamento customizada do Nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expõe a porta 80 que o Nginx usará internamente
EXPOSE 80

# Inicia o Nginx em foreground (para o container não morrer)
CMD ["nginx", "-g", "daemon off;"]
