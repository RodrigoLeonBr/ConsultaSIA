---
type: doc
name: project-overview
description: High-level overview of the project, its purpose, and key components
category: overview
generated: 2026-02-22
status: completed
scaffoldVersion: "2.0.0"
---

# Project Overview

Sistema de Gerenciamento e Relatórios Dinâmicos para Unidades de Saúde desenvolvido em Laravel 12 com dados reais de produção hospitalar. O sistema permite a visualização de dashboards dinâmicos e a geração de relatórios complexos (hierárquicos e matrizes) baseados em milhões de registros.

## Codebase Reference

> **Detailed Analysis**: For complete symbol counts, architecture layers, and dependency graphs, see [`codebase-map.json`](./codebase-map.json).

## Quick Facts

- Root: `e:\xampp\htdocs\consultasia`
- Languages: PHP (Laravel 12), JavaScript (Alpine.js), CSS (Tailwind via CDN)
- Entry: `public/index.php`, `routes/web.php`
- Full analysis: [`codebase-map.json`](./codebase-map.json)

## Entry Points

- [Dashboard](routes/web.php#L20) — Main landing page after login.
- [Relatórios](routes/web.php#L30) — Access to dynamic report generators.
- [Faturamento por Prestador](routes/web.php#L45) — New hierarchical billing reports.

## File Structure & Code Organization

- `app/Http/Controllers/` — Application logic and report generation.
- `app/Models/` — Eloquent models (e.g., `SPrd`, `Forma`).
- `resources/views/` — Blade templates for UI and PDF reports.
- `database/migrations/` — Database schema definitions.
- `public/js/` — Client-side logic for dynamic reports.

## Technology Stack Summary

- **Backend**: PHP 8.2+, Laravel 12.x
- **Frontend**: Blade, Tailwind CSS (CDN), Alpine.js
- **Database**: MySQL/MariaDB
- **Reporting**: DomPDF (PDF), Maatwebsite/Excel (XLSX/CSV)

## Getting Started Checklist

1. Install dependencies with `composer install`.
2. Configure `.env` with database credentials.
3. Run migrations with `php artisan migrate`.
4. Start the server with `php artisan serve`.
5. Login with `admin` / `admin123`.
