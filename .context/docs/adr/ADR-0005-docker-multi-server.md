# ADR-0005: Empacotamento Docker para Múltiplos Servidores

**Data**: 2026-02-22  
**Status**: Aceito  

## Contexto
O ecossistema V3 de Consulta precisa conviver pacificamente com uma infraestrutura XAMPP (Windows/Linux) legada onde o banco de dados `producao` reside nativamente no host físico. Contudo, as novas partes (NestJS API, NestJS Worker e React Web) precisam ser imutáveis e de fácil distribuição se houver necessidade de escala (ex.: subir a API/Web em uma VPC isolada que se conecta à matriz).

## Decisão
Optar por **Docker Multi-stage Builds** sem provisionar o próprio Banco de Dados no cluster (`docker-compose.yml` foca exclusivamente nas camadas de aplicação). 
1. `api` e `worker` são construídos na mesma imagem Node.js Alpine base, separados apenas pelo container command.
2. `web` é servido nativamente via Nginx num container próprio.

## Consequências
- **Positivo**: Implantação e orquestração independente do servidor físico que hospeda o XAMPP.
- **Positivo**: Não há dependência fixa de sistema operacional para as novas pilhas (Windows/Linux/Mac).
- **Negativo**: Requer administração e atenção em pontes de rede (DNS interno `host.docker.internal` vs IPs reais externos).
