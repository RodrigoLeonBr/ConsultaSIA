---
type: doc
name: security
description: Security policies, current protections, authentication roadmap, and compliance notes
category: security
generated: 2026-02-22
status: filled
scaffoldVersion: "2.0.0"
---

# Security

## Estado Atual (MVP)

**Sem autenticação.** A aplicação roda em rede local (XAMPP/intranet), acessível apenas pela equipe interna. Não exposta à internet no MVP.

## Proteções Existentes

| Ameaça | Mitigação atual |
|--------|----------------|
| SQL Injection | Field whitelist em `field-catalog.ts`; TypeORM parameterized queries em todo lugar |
| Payload gigante | `LoggingInterceptor` não loga body (protege RAM/overlay do processo) |
| Dados sensíveis em log | Nenhum PII logado; só rota + timing em ms |
| Input inválido | `ValidationPipe` global com `class-validator`; DTOs com constraints explícitas |
| Paginação abusiva | `maxPageSize: 500` enforced no backend (não negociável) |
| Export abusivo | `MAX_EXPORT_ROWS=100.000` (xlsx/csv), `MAX_PDF_ROWS=5.000` |
| CORS | `CORS_ORIGIN` env var configura origem permitida; default `http://localhost:5173` |

## Secrets / Credenciais

- Credenciais do banco em `.env` (não commitado — está no `.gitignore`)
- Sem secrets manager no MVP

**Antes de qualquer acesso externo:**
- Criar usuário MySQL dedicado com permissões apenas `SELECT` nas tabelas core
- Mover credenciais para cofre (ex: 1Password, AWS Secrets Manager, Vault)
- Revisar `CORS_ORIGIN` para domínio real

## Dados do Domínio

Dados de produção SIA são dados do SUS (saúde pública). O sistema expõe **apenas totais agregados** por competência/procedimento/prestador — sem dado individual de paciente, sem CPF, sem nome de beneficiário.

Não há processamento de dados pessoais sensíveis no MVP.

## Roadmap de Autenticação (Fase 2)

1. **JWT** com access token (15 min) + refresh token (7 dias)
2. Endpoint `POST /auth/login` com credencial interna (usuário/senha ou SSO)
3. Middleware de autenticação Laravel em todas as rotas protegidas
4. **RBAC**: roles `viewer` (só leitura), `analyst` (cria jobs), `admin` (todas as ações)
5. Audit log de queries: `requested_by` já existe em `report_job` (não mapeado no ORM — mapear ao implementar auth)

## Checklist Pré-Produção

- [ ] Usuário MySQL read-only dedicado
- [ ] `.env` fora do repositório (secrets manager ou variável de CI)
- [ ] `CORS_ORIGIN` aponta para domínio real (sem wildcard)
- [ ] HTTPS no frontend (Nginx + certificado)
- [ ] Autenticação implementada e testada
- [ ] Logs de acesso persistidos (stdout → arquivo com rotação)
