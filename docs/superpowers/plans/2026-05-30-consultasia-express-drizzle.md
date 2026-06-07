# ConsultAsia Express + Drizzle ORM — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the ConsultAsia SIA reporting system as a new standalone project using Express.js + Drizzle ORM (backend) and React + Vite (frontend), connecting to the existing MySQL `producao` database on XAMPP — maintaining exact API compatibility and business logic with the validated NestJS version.

**Architecture:** Express HTTP server + polling Worker (same process, flag-controlled via `RUN_WORKER=true`) reading from MySQL `producao` via Drizzle ORM. React SPA (Vite) consuming REST API. No Redis — job queue is MySQL-backed. Field whitelist (`field-catalog.ts`) is the single source of truth for all query building and validation.

**Tech Stack:** Node.js 18+ LTS, Express 5, Drizzle ORM 0.36+, mysql2, Zod (validation), ExcelJS (xlsx), PDFKit (pdf), React 18, Vite 5, Axios, React Router v6, TypeScript 5 throughout.

---

## Context

This is a **new project from scratch** at `consultasia-express/`, separate from `v3-backend/` (NestJS). It must access the same MySQL `producao` database (XAMPP, port 3306) without any DDL changes to core tables (`s_prd`, `prestador`, `cbo`, `procedimento`, `s_rub`, `cismetro`).

**Validated reference totals** (must match, competência 202301):
- `COUNT(*) = 31765`
- `SUM(CAST(PRD_QT_A AS UNSIGNED)) = 214675`
- `SUM(CAST(PRD_VL_A AS DECIMAL(15,2))) = 1606620.01`

**Key constraints:**
- Competência (`AAAAMM`, 6 chars) is mandatory in every query
- `maxPageSize = 500`, `maxSelect = 20`, `maxFilters = 20`
- `PRD_QT_*` fields: `CAST AS UNSIGNED`; `PRD_VL_*` fields: `CAST AS DECIMAL(15,2)`
- Worker: 1 job at a time, 5-second poll interval
- No payload logging (protect RAM)

---

## File Map

```
consultasia-express/
├── backend/
│   ├── src/
│   │   ├── index.ts              # Express bootstrap + CORS + routes
│   │   ├── worker-entry.ts       # Worker bootstrap (RUN_WORKER=true path)
│   │   ├── db.ts                 # Drizzle ORM connection pool
│   │   ├── field-catalog.ts      # Whitelist: all 13 SIA fields + operators
│   │   ├── schemas/
│   │   │   ├── s-prd.ts          # Drizzle schema for s_prd (read-only)
│   │   │   ├── lookup.ts         # prestador, cbo, procedimento, s_rub, cismetro
│   │   │   ├── report-job.ts     # report_job table schema + insert type
│   │   │   ├── report-result-header.ts
│   │   │   └── report-result-rows.ts
│   │   ├── routes/
│   │   │   ├── sia.routes.ts     # GET /reports/sia/metadata, POST /production, GET /sia
│   │   │   └── reports.routes.ts # POST /jobs, GET /jobs/:id, /results, /download
│   │   ├── services/
│   │   │   ├── sia.service.ts    # Dynamic query builder (uses field-catalog.ts)
│   │   │   ├── reports.service.ts# Job CRUD, result pagination
│   │   │   └── worker.service.ts # Poll loop, job dispatch, export generation
│   │   ├── validation/
│   │   │   ├── sia.schema.ts     # Zod schema for POST /production body
│   │   │   └── job.schema.ts     # Zod schema for POST /jobs body
│   │   └── middleware/
│   │       ├── timing.ts         # Log route + ms (no body)
│   │       └── error-handler.ts  # Global error → JSON 400/500
│   ├── migrations/
│   │   └── 0001_init_aux_tables.sql  # CREATE TABLE for report_* (if not exists)
│   ├── drizzle.config.ts
│   ├── package.json
│   └── tsconfig.json
├── frontend/
│   ├── src/
│   │   ├── main.tsx
│   │   ├── App.tsx
│   │   ├── routes.tsx
│   │   ├── api.ts                # Axios instance + all endpoint wrappers
│   │   ├── types.ts              # Shared API response types
│   │   ├── hooks/
│   │   │   └── useJobPolling.ts  # 2s interval, stops on done/failed
│   │   ├── pages/
│   │   │   ├── SiaDynamicPage.tsx        # Main query builder UI
│   │   │   ├── SiaReportsPage.tsx        # Simple paginated s_prd list
│   │   │   ├── AsyncReportsPage.tsx      # Job creation + polling
│   │   │   └── JobResultsPage.tsx        # Result viewer + export trigger
│   │   └── components/
│   │       ├── DataGrid.tsx      # Server-side paginated table
│   │       └── FilterBuilder.tsx # Dynamic filter rows UI
│   ├── index.html
│   ├── vite.config.ts
│   ├── package.json
│   └── tsconfig.json
├── .env.example
└── README.md
```

---

## Task 1: Project Scaffold & Database Connection

**Files:**
- Create: `consultasia-express/backend/package.json`
- Create: `consultasia-express/backend/tsconfig.json`
- Create: `consultasia-express/backend/drizzle.config.ts`
- Create: `consultasia-express/backend/src/db.ts`
- Create: `consultasia-express/.env.example`

- [ ] **Step 1: Create directory and initialize backend**

```bash
mkdir -p consultasia-express/backend/src/schemas
mkdir -p consultasia-express/backend/src/routes
mkdir -p consultasia-express/backend/src/services
mkdir -p consultasia-express/backend/src/validation
mkdir -p consultasia-express/backend/src/middleware
mkdir -p consultasia-express/backend/migrations
cd consultasia-express/backend
npm init -y
npm install express drizzle-orm mysql2 dotenv zod
npm install -D typescript tsx @types/express @types/node drizzle-kit
```

- [ ] **Step 2: Write `tsconfig.json`**

```json
{
  "compilerOptions": {
    "target": "ES2022",
    "module": "commonjs",
    "lib": ["ES2022"],
    "outDir": "./dist",
    "rootDir": "./src",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "resolveJsonModule": true
  },
  "include": ["src/**/*"],
  "exclude": ["node_modules", "dist"]
}
```

- [ ] **Step 3: Write `drizzle.config.ts`**

```typescript
import type { Config } from 'drizzle-kit';
import * as dotenv from 'dotenv';
dotenv.config();

export default {
  schema: './src/schemas/*.ts',
  out: './migrations',
  dialect: 'mysql',
  dbCredentials: {
    host: process.env.DB_HOST ?? 'localhost',
    port: Number(process.env.DB_PORT ?? 3306),
    user: process.env.DB_USER ?? 'root',
    password: process.env.DB_PASSWORD ?? '',
    database: process.env.DB_NAME ?? 'producao',
  },
} satisfies Config;
```

- [ ] **Step 4: Write `src/db.ts`**

```typescript
import { drizzle } from 'drizzle-orm/mysql2';
import mysql from 'mysql2/promise';

const pool = mysql.createPool({
  host: process.env.DB_HOST ?? 'localhost',
  port: Number(process.env.DB_PORT ?? 3306),
  user: process.env.DB_USER ?? 'root',
  password: process.env.DB_PASSWORD ?? '',
  database: process.env.DB_NAME ?? 'producao',
  connectionLimit: 10,
  timezone: 'Z',
});

export const db = drizzle(pool);
export { pool };
```

- [ ] **Step 5: Write `.env.example`**

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASSWORD=
DB_NAME=producao
PORT=3001
CORS_ORIGIN=http://localhost:5174
RUN_WORKER=false
```

- [ ] **Step 6: Verify DB connection**

Add `scripts` to `package.json`:
```json
{
  "scripts": {
    "dev": "tsx watch src/index.ts",
    "worker": "cross-env RUN_WORKER=true tsx watch src/index.ts",
    "build": "tsc",
    "start": "node dist/index.js",
    "db:push": "drizzle-kit push"
  }
}
```

Create `src/index.ts` with just a ping query:
```typescript
import 'dotenv/config';
import { db } from './db';
import { sql } from 'drizzle-orm';

async function main() {
  const result = await db.execute(sql`SELECT 1+1 AS result`);
  console.log('DB OK:', result[0]);
  process.exit(0);
}
main();
```

Run: `cd consultasia-express/backend && npm run dev`
Expected: `DB OK: [ { result: 2 } ]`

- [ ] **Step 7: Commit**

```bash
git add consultasia-express/
git commit -m "feat: scaffold consultasia-express backend with Drizzle ORM connection"
```

---

## Task 2: Drizzle Schema Definitions

**Files:**
- Create: `consultasia-express/backend/src/schemas/s-prd.ts`
- Create: `consultasia-express/backend/src/schemas/lookup.ts`
- Create: `consultasia-express/backend/src/schemas/report-job.ts`
- Create: `consultasia-express/backend/src/schemas/report-result-header.ts`
- Create: `consultasia-express/backend/src/schemas/report-result-rows.ts`

- [ ] **Step 1: Write `src/schemas/s-prd.ts`** (read-only, no migrations)

```typescript
import { mysqlTable, bigint, varchar, int, decimal } from 'drizzle-orm/mysql-core';

export const sPrd = mysqlTable('s_prd', {
  id: bigint('id', { mode: 'number', unsigned: true }).primaryKey().autoincrement(),
  prdCmp: varchar('prd_cmp', { length: 6 }).notNull(),
  prdUid: varchar('prd_uid', { length: 7 }).notNull(),
  prdPa:  varchar('prd_pa',  { length: 10 }).notNull(),
  prdCbo: varchar('prd_cbo', { length: 8 }),
  prdRub: varchar('prd_rub', { length: 6 }),
  PRD_QT_P: int('PRD_QT_P'),
  PRD_QT_A: int('PRD_QT_A'),
  PRD_VL_P: decimal('PRD_VL_P', { precision: 15, scale: 2 }),
  PRD_VL_A: decimal('PRD_VL_A', { precision: 15, scale: 2 }),
  PRD_CIDPRI: varchar('PRD_CIDPRI', { length: 4 }),
  grupo:     varchar('grupo',    { length: 2 }),
  subgrupo:  varchar('subgrupo', { length: 4 }),
  forma:     varchar('forma',    { length: 6 }),
});
```

- [ ] **Step 2: Write `src/schemas/lookup.ts`**

```typescript
import { mysqlTable, varchar, char, decimal } from 'drizzle-orm/mysql-core';

export const prestador = mysqlTable('prestador', {
  reCunid: varchar('re_cunid', { length: 7 }).primaryKey(),
  reCnome: varchar('re_cnome', { length: 80 }),
});

export const cbo = mysqlTable('cbo', {
  cbo:   varchar('cbo',   { length: 6 }).primaryKey(),
  dsCbo: varchar('ds_cbo', { length: 120 }),
});

export const procedimento = mysqlTable('procedimento', {
  codigo:      varchar('codigo',      { length: 10 }).primaryKey(),
  descricao:   varchar('procedimento', { length: 200 }),
  paTotal:     decimal('PA_TOTAL', { precision: 15, scale: 2 }),
});

export const sRub = mysqlTable('s_rub', {
  rubId: char('RUB_ID', { length: 4 }).primaryKey(),
  rubDc: varchar('RUB_DC', { length: 60 }),
});

export const cismetro = mysqlTable('cismetro', {
  codigo:    varchar('codigo',    { length: 10 }).primaryKey(),
  descricao: varchar('descricao', { length: 200 }),
  valor:     decimal('valor', { precision: 15, scale: 4 }),
});
```

- [ ] **Step 3: Write `src/schemas/report-job.ts`**

```typescript
import { mysqlTable, int, varchar, json, datetime, mysqlEnum } from 'drizzle-orm/mysql-core';

export const reportJob = mysqlTable('report_job', {
  id:          int('id').primaryKey().autoincrement(),
  status:      mysqlEnum('status', ['queued', 'running', 'done', 'failed']).notNull().default('queued'),
  type:        varchar('type', { length: 80 }).notNull(),
  parameters:  json('parameters').notNull(),
  errorMessage: varchar('error_message', { length: 2000 }),
  createdAt:   datetime('created_at').notNull(),
  startedAt:   datetime('started_at'),
  completedAt: datetime('completed_at'),
});

export type NewReportJob = typeof reportJob.$inferInsert;
export type ReportJob    = typeof reportJob.$inferSelect;
```

- [ ] **Step 4: Write `src/schemas/report-result-header.ts`**

```typescript
import { mysqlTable, int, varchar, json, datetime } from 'drizzle-orm/mysql-core';

export const reportResultHeader = mysqlTable('report_result_header', {
  id:                      int('id').primaryKey().autoincrement(),
  jobId:                   int('job_id').notNull(),
  reportType:              varchar('report_type', { length: 80 }),
  competence:              varchar('competence', { length: 6 }),
  totalRowsFetched:        int('total_rows_fetched').notNull().default(0),
  columnsJson:             json('columns_json'),
  sourceTablesVersionsJson: json('source_tables_versions_json'),
  ttl:                     datetime('ttl').notNull(),
  createdAt:               datetime('created_at').notNull(),
});

export type ReportResultHeader = typeof reportResultHeader.$inferSelect;
```

- [ ] **Step 5: Write `src/schemas/report-result-rows.ts`**

```typescript
import { mysqlTable, int, longtext } from 'drizzle-orm/mysql-core';

export const reportResultRows = mysqlTable('report_result_rows', {
  id:       int('id').primaryKey().autoincrement(),
  headerId: int('header_id').notNull(),
  rowIndex: int('row_index').notNull(),
  rowJson:  longtext('row_json').notNull(),
});
```

- [ ] **Step 6: Create auxiliary tables in DB**

Create `migrations/0001_init_aux_tables.sql`:

```sql
CREATE TABLE IF NOT EXISTS report_job (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  status ENUM('queued','running','done','failed') NOT NULL DEFAULT 'queued',
  type VARCHAR(80) NOT NULL,
  parameters JSON NOT NULL,
  error_message VARCHAR(2000),
  created_at DATETIME NOT NULL,
  started_at DATETIME,
  completed_at DATETIME,
  INDEX idx_status_created (status, created_at)
);

CREATE TABLE IF NOT EXISTS report_result_header (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  job_id INT NOT NULL,
  report_type VARCHAR(80),
  competence VARCHAR(6),
  total_rows_fetched INT NOT NULL DEFAULT 0,
  columns_json JSON,
  source_tables_versions_json JSON,
  ttl DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_job_id (job_id)
);

CREATE TABLE IF NOT EXISTS report_result_rows (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  header_id INT NOT NULL,
  row_index INT NOT NULL,
  row_json LONGTEXT NOT NULL,
  INDEX idx_header_idx (header_id, row_index)
);
```

Run against XAMPP MySQL: `mysql -u root producao < migrations/0001_init_aux_tables.sql`

- [ ] **Step 7: Commit**

```bash
git add consultasia-express/backend/src/schemas/ consultasia-express/backend/migrations/
git commit -m "feat: Drizzle schema definitions for s_prd, lookups, and report tables"
```

---

## Task 3: Field Catalog

**Files:**
- Create: `consultasia-express/backend/src/field-catalog.ts`

This is the single source of truth for all query building, validation, and metadata.

- [ ] **Step 1: Write `src/field-catalog.ts`**

```typescript
export type FieldType = 'date' | 'lookup' | 'number' | 'currency' | 'text';
export type Operator = '=' | '>' | '<' | '>=' | '<=' | 'between' | 'in' | 'like' | 'starts_with' | 'ends_with';

export interface LookupConfig {
  table: string;
  joinOn: string;
  displayCol: string;
  displayAlias: string;
}

export interface FieldDef {
  id: string;
  label: string;
  type: FieldType;
  sqlExpr: string;       // raw SQL expression for SELECT
  filterExpr?: string;   // if different from sqlExpr for WHERE
  allowedOperators: Operator[];
  sortable: boolean;
  groupable: boolean;
  isAggregate?: boolean; // uses SUM()
  filterOnly?: boolean;  // not allowed in select[]
  displayOnly?: boolean; // always included, not in select[] requirement
  requiresJoin?: string; // 'prestador' | 'cbo' | 'procedimento' | 's_rub' | 'cismetro'
  lookup?: LookupConfig;
  castAs?: 'UNSIGNED' | 'DECIMAL(15,2)';
}

export const SIA_PRODUCAO_FIELDS: Record<string, FieldDef> = {
  prd_cmp: {
    id: 'prd_cmp', label: 'Competência', type: 'date',
    sqlExpr: 'sp.prd_cmp',
    allowedOperators: ['=', '>=', '<=', 'between'],
    sortable: true, groupable: true,
  },
  prd_uid: {
    id: 'prd_uid', label: 'Prestador', type: 'lookup',
    sqlExpr: 'sp.prd_uid',
    allowedOperators: ['=', 'in'],
    sortable: true, groupable: true,
    requiresJoin: 'prestador',
    lookup: { table: 'prestador', joinOn: 'sp.prd_uid = pr.re_cunid', displayCol: 'pr.re_cnome', displayAlias: 'prd_uid_display' },
  },
  prd_cbo: {
    id: 'prd_cbo', label: 'CBO', type: 'lookup',
    sqlExpr: 'sp.prd_cbo',
    allowedOperators: ['=', 'in'],
    sortable: true, groupable: true,
    requiresJoin: 'cbo',
    lookup: { table: 'cbo', joinOn: 'sp.prd_cbo = cb.cbo', displayCol: 'cb.ds_cbo', displayAlias: 'prd_cbo_display' },
  },
  prd_pa: {
    id: 'prd_pa', label: 'Procedimento', type: 'lookup',
    sqlExpr: 'sp.prd_pa',
    allowedOperators: ['=', 'in', 'like'],
    sortable: true, groupable: true,
    requiresJoin: 'procedimento',
    lookup: { table: 'procedimento', joinOn: 'sp.prd_pa = pc.codigo', displayCol: 'pc.procedimento', displayAlias: 'prd_pa_display' },
  },
  PRD_RUB: {
    id: 'PRD_RUB', label: 'Financiamento', type: 'lookup',
    sqlExpr: 'sp.prd_rub',
    allowedOperators: ['=', 'in'],
    sortable: true, groupable: true,
    requiresJoin: 's_rub',
    lookup: { table: 's_rub', joinOn: 'sp.prd_rub = sr.RUB_ID', displayCol: 'sr.RUB_DC', displayAlias: 'PRD_RUB_display' },
  },
  PRD_CIDPRI: {
    id: 'PRD_CIDPRI', label: 'CID Primário', type: 'text',
    sqlExpr: 'sp.PRD_CIDPRI',
    allowedOperators: ['=', 'like', 'starts_with'],
    sortable: true, groupable: true,
  },
  PRD_QT_P: {
    id: 'PRD_QT_P', label: 'Qtd Apresentada', type: 'number',
    sqlExpr: 'CAST(sp.PRD_QT_P AS UNSIGNED)',
    allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
    sortable: true, groupable: false, isAggregate: true, castAs: 'UNSIGNED',
  },
  PRD_QT_A: {
    id: 'PRD_QT_A', label: 'Qtd Aprovada', type: 'number',
    sqlExpr: 'CAST(sp.PRD_QT_A AS UNSIGNED)',
    allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
    sortable: true, groupable: false, isAggregate: true, castAs: 'UNSIGNED',
  },
  PRD_VL_P: {
    id: 'PRD_VL_P', label: 'Valor Apresentado', type: 'currency',
    sqlExpr: 'CAST(sp.PRD_VL_P AS DECIMAL(15,2))',
    allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
    sortable: true, groupable: false, isAggregate: true, castAs: 'DECIMAL(15,2)',
  },
  PRD_VL_A: {
    id: 'PRD_VL_A', label: 'Valor Aprovado', type: 'currency',
    sqlExpr: 'CAST(sp.PRD_VL_A AS DECIMAL(15,2))',
    allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
    sortable: true, groupable: false, isAggregate: true, castAs: 'DECIMAL(15,2)',
  },
  cismetro_valor: {
    id: 'cismetro_valor', label: 'Valor Unitário (Cismetro)', type: 'currency',
    sqlExpr: 'cs.valor',
    allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
    sortable: true, groupable: true, requiresJoin: 'cismetro',
    lookup: { table: 'cismetro', joinOn: 'sp.prd_pa = cs.codigo', displayCol: 'cs.descricao', displayAlias: 'cismetro_descricao' },
  },
  cismetro_descricao: {
    id: 'cismetro_descricao', label: 'Descrição Cismetro', type: 'text',
    sqlExpr: 'cs.descricao',
    allowedOperators: ['=', 'like'],
    sortable: true, groupable: true, requiresJoin: 'cismetro',
  },
  cismetro_total: {
    id: 'cismetro_total', label: 'Total Cismetro', type: 'currency',
    sqlExpr: 'SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * cs.valor)',
    allowedOperators: [],
    sortable: false, groupable: false, isAggregate: true, displayOnly: true, requiresJoin: 'cismetro',
  },
  procedimento_descricao: {
    id: 'procedimento_descricao', label: 'Descrição Procedimento', type: 'text',
    sqlExpr: 'pc.procedimento',
    filterExpr: `sp.prd_pa IN (SELECT codigo FROM procedimento WHERE procedimento LIKE ?)`,
    allowedOperators: ['=', 'like', 'starts_with', 'ends_with'],
    sortable: false, groupable: false, filterOnly: true, requiresJoin: 'procedimento',
  },
};

export function getField(id: string): FieldDef | undefined {
  return SIA_PRODUCAO_FIELDS[id];
}

export function isOperatorAllowed(field: FieldDef, op: Operator): boolean {
  return field.allowedOperators.includes(op);
}

export const LIMITS = {
  maxSelect: 20,
  maxFilters: 20,
  maxPageSize: 500,
} as const;

export const METADATA_RESPONSE = {
  producao: {
    description: 'Relatório dinâmico de Produção SIA (s_prd). Filtro de competência obrigatório.',
    fields: Object.values(SIA_PRODUCAO_FIELDS).map(f => ({
      id: f.id,
      label: f.label,
      type: f.type,
      allowedOperators: f.allowedOperators,
      sortable: f.sortable,
      groupable: f.groupable,
      isAggregate: f.isAggregate ?? false,
      filterOnly: f.filterOnly ?? false,
      displayOnly: f.displayOnly ?? false,
    })),
  },
  limits: LIMITS,
};
```

- [ ] **Step 2: Verify field catalog compiles**

```bash
cd consultasia-express/backend && npx tsx -e "import { METADATA_RESPONSE } from './src/field-catalog'; console.log(JSON.stringify(METADATA_RESPONSE.producao.fields.length))"
```

Expected: `14` (13 fields + procedimento_descricao)

- [ ] **Step 3: Commit**

```bash
git add consultasia-express/backend/src/field-catalog.ts
git commit -m "feat: complete field catalog with 13 SIA fields + operators + metadata"
```

---

## Task 4: Zod Validation Schemas

**Files:**
- Create: `consultasia-express/backend/src/validation/sia.schema.ts`
- Create: `consultasia-express/backend/src/validation/job.schema.ts`

- [ ] **Step 1: Write `src/validation/sia.schema.ts`**

```typescript
import { z } from 'zod';
import { SIA_PRODUCAO_FIELDS, LIMITS } from '../field-catalog';

const validFieldIds = Object.keys(SIA_PRODUCAO_FIELDS);

const filterItemSchema = z.object({
  fieldId:  z.string().refine(id => validFieldIds.includes(id), { message: 'fieldId inválido' }),
  operator: z.enum(['=', '>', '<', '>=', '<=', 'between', 'in', 'like', 'starts_with', 'ends_with']),
  value:    z.union([z.string(), z.array(z.string()).min(1)]),
});

export const siaProductionSchema = z.object({
  competence: z.string().length(6, 'competence deve ter exatamente 6 caracteres (AAAAMM)'),
  select: z.array(z.string()).min(1).max(LIMITS.maxSelect),
  filters: z.array(filterItemSchema).max(LIMITS.maxFilters).optional().default([]),
  page:     z.number().int().min(1).optional().default(1),
  pageSize: z.number().int().min(1).max(LIMITS.maxPageSize).optional().default(50),
  sort: z.object({
    fieldId:   z.string(),
    direction: z.enum(['ASC', 'DESC']),
  }).optional(),
});

export type SiaProductionQuery = z.infer<typeof siaProductionSchema>;
```

- [ ] **Step 2: Write `src/validation/job.schema.ts`**

```typescript
import { z } from 'zod';

const jobTypeEnum = z.enum(['sia-aggregated', 'sia-faturamento-prestador', 'sia-dynamic-production', 'export']);

export const createJobSchema = z.object({
  type: jobTypeEnum,
  parameters: z.object({
    competence: z.string().length(6).optional(),
    select:     z.array(z.string()).optional(),
    filters:    z.array(z.any()).optional(),
    resultId:   z.number().int().optional(),
    format:     z.enum(['xlsx', 'csv', 'pdf']).optional(),
  }),
});

export type CreateJobDto = z.infer<typeof createJobSchema>;
```

- [ ] **Step 3: Commit**

```bash
git add consultasia-express/backend/src/validation/
git commit -m "feat: Zod validation schemas for SIA production query and job creation"
```

---

## Task 5: Middleware — Timing & Error Handler

**Files:**
- Create: `consultasia-express/backend/src/middleware/timing.ts`
- Create: `consultasia-express/backend/src/middleware/error-handler.ts`

- [ ] **Step 1: Write `src/middleware/timing.ts`**

```typescript
import { Request, Response, NextFunction } from 'express';

export function timingMiddleware(req: Request, res: Response, next: NextFunction) {
  const start = Date.now();
  res.on('finish', () => {
    const ms = Date.now() - start;
    console.log(`[${req.method}] ${req.path} — ${ms}ms`);
  });
  next();
}
```

- [ ] **Step 2: Write `src/middleware/error-handler.ts`**

```typescript
import { Request, Response, NextFunction } from 'express';
import { ZodError } from 'zod';

export function errorHandler(err: unknown, req: Request, res: Response, next: NextFunction) {
  if (err instanceof ZodError) {
    return res.status(400).json({
      statusCode: 400,
      message: err.errors.map(e => e.message),
      error: 'Bad Request',
    });
  }
  if (err instanceof AppError) {
    return res.status(err.statusCode).json({
      statusCode: err.statusCode,
      message: err.message,
    });
  }
  console.error('[Unhandled]', err);
  return res.status(500).json({ statusCode: 500, message: 'Internal server error' });
}

export class AppError extends Error {
  constructor(public readonly statusCode: number, message: string) {
    super(message);
    this.name = 'AppError';
  }
}
```

- [ ] **Step 3: Commit**

```bash
git add consultasia-express/backend/src/middleware/
git commit -m "feat: timing middleware and centralized error handler"
```

---

## Task 6: SIA Service — Dynamic Query Builder

**Files:**
- Create: `consultasia-express/backend/src/services/sia.service.ts`

This is the core of the system. It builds raw SQL using the field catalog.

- [ ] **Step 1: Write `src/services/sia.service.ts`**

```typescript
import { db } from '../db';
import { sql } from 'drizzle-orm';
import { SIA_PRODUCAO_FIELDS, FieldDef, LIMITS, METADATA_RESPONSE } from '../field-catalog';
import type { SiaProductionQuery } from '../validation/sia.schema';
import { AppError } from '../middleware/error-handler';

type FilterItem = { fieldId: string; operator: string; value: string | string[] };

export class SiaService {

  getMetadata() {
    return METADATA_RESPONSE;
  }

  async getDynamicProduction(query: SiaProductionQuery) {
    const { competence, select, filters = [], page, pageSize, sort } = query;

    // Validate select fields
    for (const fieldId of select) {
      const field = SIA_PRODUCAO_FIELDS[fieldId];
      if (!field) throw new AppError(400, `Campo "${fieldId}" não existe no catálogo.`);
      if (field.filterOnly) throw new AppError(400, `Campo "${fieldId}" é somente-filtro e não pode aparecer em "select".`);
    }

    // Determine joins needed
    const joinsNeeded = new Set<string>();
    for (const fieldId of select) {
      const f = SIA_PRODUCAO_FIELDS[fieldId];
      if (f?.requiresJoin) joinsNeeded.add(f.requiresJoin);
    }
    for (const filter of filters) {
      const f = SIA_PRODUCAO_FIELDS[filter.fieldId];
      if (f?.requiresJoin && filter.fieldId !== 'procedimento_descricao') joinsNeeded.add(f.requiresJoin);
    }

    // Determine if GROUP BY applies
    const hasAggregate = select.some(id => SIA_PRODUCAO_FIELDS[id]?.isAggregate);

    // Build SELECT clause
    const selectParts: string[] = [];
    const columns: object[] = [];

    for (const fieldId of select) {
      const field = SIA_PRODUCAO_FIELDS[fieldId]!;
      if (hasAggregate && field.isAggregate) {
        const castExpr = field.sqlExpr.startsWith('SUM') ? field.sqlExpr : `SUM(${field.sqlExpr})`;
        selectParts.push(`${castExpr} AS \`${fieldId}\``);
      } else {
        selectParts.push(`${field.sqlExpr} AS \`${fieldId}\``);
      }
      columns.push({ fieldId, label: field.label, type: field.type, ...(field.lookup ? { displayAlias: field.lookup.displayAlias } : {}) });

      // Add display column for lookups
      if (field.lookup && !field.filterOnly) {
        selectParts.push(`${field.lookup.displayCol} AS \`${field.lookup.displayAlias}\``);
        if (hasAggregate && field.groupable) {
          // display col goes into GROUP BY too — handled below
        }
      }
    }

    // Build FROM + JOINs
    let fromClause = 'FROM s_prd sp';
    if (joinsNeeded.has('prestador'))   fromClause += ' LEFT JOIN prestador pr ON sp.prd_uid = pr.re_cunid';
    if (joinsNeeded.has('cbo'))         fromClause += ' LEFT JOIN cbo cb ON sp.prd_cbo = cb.cbo';
    if (joinsNeeded.has('procedimento')) fromClause += ' LEFT JOIN procedimento pc ON sp.prd_pa = pc.codigo';
    if (joinsNeeded.has('s_rub'))       fromClause += ' LEFT JOIN s_rub sr ON sp.prd_rub = sr.RUB_ID';
    if (joinsNeeded.has('cismetro'))    fromClause += ' LEFT JOIN cismetro cs ON sp.prd_pa = cs.codigo';

    // Build WHERE clause
    const whereParts: string[] = [`sp.prd_cmp = ?`];
    const params: unknown[] = [competence];

    for (const filter of filters) {
      const field = SIA_PRODUCAO_FIELDS[filter.fieldId];
      if (!field) throw new AppError(400, `Filtro fieldId "${filter.fieldId}" inválido.`);
      if (!field.allowedOperators.includes(filter.operator as any)) {
        throw new AppError(400, `Operador "${filter.operator}" não permitido para "${filter.fieldId}".`);
      }

      // Special case: procedimento_descricao uses subquery
      if (filter.fieldId === 'procedimento_descricao') {
        const val = String(filter.value);
        const like = filter.operator === 'starts_with' ? `${val}%`
                   : filter.operator === 'ends_with'   ? `%${val}`
                   : `%${val}%`;
        whereParts.push(`sp.prd_pa IN (SELECT codigo FROM procedimento WHERE procedimento LIKE ?)`);
        params.push(like);
        continue;
      }

      const expr = field.sqlExpr;
      if (filter.operator === 'between') {
        const arr = Array.isArray(filter.value) ? filter.value : [];
        if (arr.length !== 2) throw new AppError(400, `"between" requer array com exatamente 2 elementos.`);
        whereParts.push(`${expr} BETWEEN ? AND ?`);
        params.push(arr[0], arr[1]);
      } else if (filter.operator === 'in') {
        const arr = Array.isArray(filter.value) ? filter.value : [];
        if (arr.length === 0) throw new AppError(400, `"in" requer array não-vazio.`);
        whereParts.push(`${expr} IN (${arr.map(() => '?').join(',')})`);
        params.push(...arr);
      } else if (filter.operator === 'like') {
        whereParts.push(`${expr} LIKE ?`);
        params.push(`%${filter.value}%`);
      } else if (filter.operator === 'starts_with') {
        whereParts.push(`${expr} LIKE ?`);
        params.push(`${filter.value}%`);
      } else if (filter.operator === 'ends_with') {
        whereParts.push(`${expr} LIKE ?`);
        params.push(`%${filter.value}`);
      } else {
        whereParts.push(`${expr} ${filter.operator} ?`);
        params.push(filter.value);
      }
    }

    const whereClause = `WHERE ${whereParts.join(' AND ')}`;

    // GROUP BY clause
    let groupByClause = '';
    if (hasAggregate) {
      const groupByExprs: string[] = [];
      for (const fieldId of select) {
        const field = SIA_PRODUCAO_FIELDS[fieldId]!;
        if (field.groupable && !field.isAggregate) {
          groupByExprs.push(field.sqlExpr);
          if (field.lookup) groupByExprs.push(field.lookup.displayCol);
        }
      }
      if (groupByExprs.length > 0) {
        groupByClause = `GROUP BY ${groupByExprs.join(', ')}`;
      }
    }

    // ORDER BY
    let orderByClause = '';
    if (sort) {
      const sortField = SIA_PRODUCAO_FIELDS[sort.fieldId];
      if (!sortField) throw new AppError(400, `sort.fieldId "${sort.fieldId}" inválido.`);
      if (!sortField.sortable) throw new AppError(400, `Campo "${sort.fieldId}" não é sortable.`);
      const sortExpr = hasAggregate && sortField.isAggregate ? `SUM(${sortField.sqlExpr})` : sortField.sqlExpr;
      orderByClause = `ORDER BY ${sortExpr} ${sort.direction}`;
    }

    // Pagination
    const offset = (page - 1) * pageSize;
    const paginationClause = `LIMIT ${pageSize} OFFSET ${offset}`;

    // Count query
    const countSql = `SELECT COUNT(*) AS cnt ${fromClause} ${whereClause}`;
    const dataSql  = `SELECT ${selectParts.join(', ')} ${fromClause} ${whereClause} ${groupByClause} ${orderByClause} ${paginationClause}`;

    const start = Date.now();
    const [[countResult], rows] = await Promise.all([
      db.execute(sql.raw(countSql, params)) as Promise<[{cnt: number}[]]>,
      db.execute(sql.raw(dataSql, params)) as Promise<[Record<string, unknown>[]]>,
    ]);
    const queryTimeMs = Date.now() - start;

    const totalRows = Number((countResult as any)[0]?.cnt ?? 0);

    // Warning for heavy queries
    const likeFilterCount = filters.filter(f => ['like','starts_with','ends_with'].includes(f.operator)).length;
    const hasCismetroTotal = select.includes('cismetro_total');
    const warning = (likeFilterCount > 2 || hasCismetroTotal)
      ? 'Query potencialmente lenta. Para exportação completa, use POST /reports/jobs com type="sia-dynamic-production".'
      : undefined;

    return {
      columns,
      rows,
      meta: {
        totalRows,
        page,
        pageSize,
        totalPages: Math.ceil(totalRows / pageSize),
        queryTimeMs,
        hasAggregates: hasAggregate,
        warning: warning ?? null,
      },
    };
  }

  async getSimpleList(competence: string, page = 1, pageSize = 50) {
    const offset = (page - 1) * pageSize;
    const start = Date.now();
    const [[countRow], rows] = await Promise.all([
      db.execute(sql`SELECT COUNT(*) AS cnt FROM s_prd sp WHERE sp.prd_cmp = ${competence}`) as any,
      db.execute(sql`
        SELECT sp.prd_uid, sp.prd_cmp, sp.prd_pa, sp.grupo, sp.subgrupo, sp.forma,
               CAST(sp.PRD_QT_P AS UNSIGNED) AS PRD_QT_P, CAST(sp.PRD_VL_P AS DECIMAL(15,2)) AS PRD_VL_P,
               CAST(sp.PRD_QT_A AS UNSIGNED) AS PRD_QT_A, CAST(sp.PRD_VL_A AS DECIMAL(15,2)) AS PRD_VL_A
        FROM s_prd sp
        WHERE sp.prd_cmp = ${competence}
        LIMIT ${pageSize} OFFSET ${offset}
      `) as any,
    ]);
    const totalRows = Number(countRow[0]?.cnt ?? 0);
    return {
      data: rows[0],
      meta: { totalRows, page, pageSize, totalPages: Math.ceil(totalRows / pageSize), queryTimeMs: Date.now() - start },
    };
  }
}

export const siaService = new SiaService();
```

- [ ] **Step 2: Verify the service compiles**

```bash
cd consultasia-express/backend && npx tsx -e "import { siaService } from './src/services/sia.service'; console.log('SiaService OK')"
```

Expected: `SiaService OK`

- [ ] **Step 3: Commit**

```bash
git add consultasia-express/backend/src/services/sia.service.ts
git commit -m "feat: SIA dynamic query builder service with GROUP BY, operator, and CAST logic"
```

---

## Task 7: SIA Routes

**Files:**
- Create: `consultasia-express/backend/src/routes/sia.routes.ts`

- [ ] **Step 1: Write `src/routes/sia.routes.ts`**

```typescript
import { Router, Request, Response, NextFunction } from 'express';
import { siaProductionSchema } from '../validation/sia.schema';
import { siaService } from '../services/sia.service';

export const siaRouter = Router();

// GET /reports/sia/metadata
siaRouter.get('/metadata', (_req: Request, res: Response) => {
  res.json(siaService.getMetadata());
});

// GET /reports/sia
siaRouter.get('/', async (req: Request, res: Response, next: NextFunction) => {
  try {
    const { competence, page = '1', pageSize = '50' } = req.query as Record<string, string>;
    if (!competence || competence.length !== 6) {
      return res.status(400).json({ statusCode: 400, message: 'competence obrigatório (6 chars AAAAMM)' });
    }
    const result = await siaService.getSimpleList(competence, +page, +pageSize);
    res.json(result);
  } catch (err) {
    next(err);
  }
});

// POST /reports/sia/production
siaRouter.post('/production', async (req: Request, res: Response, next: NextFunction) => {
  try {
    const parsed = siaProductionSchema.safeParse(req.body);
    if (!parsed.success) {
      return res.status(400).json({ statusCode: 400, message: parsed.error.errors.map(e => e.message), error: 'Bad Request' });
    }
    const result = await siaService.getDynamicProduction(parsed.data);
    res.json(result);
  } catch (err) {
    next(err);
  }
});
```

- [ ] **Step 2: Commit**

```bash
git add consultasia-express/backend/src/routes/sia.routes.ts
git commit -m "feat: SIA routes — GET /metadata, GET /, POST /production"
```

---

## Task 8: Reports Service + Routes (Jobs System)

**Files:**
- Create: `consultasia-express/backend/src/services/reports.service.ts`
- Create: `consultasia-express/backend/src/routes/reports.routes.ts`

- [ ] **Step 1: Write `src/services/reports.service.ts`**

```typescript
import { db } from '../db';
import { reportJob, reportResultHeader, reportResultRows, NewReportJob } from '../schemas/report-job';
import { eq, and, asc } from 'drizzle-orm';
import { AppError } from '../middleware/error-handler';

export class ReportsService {

  async createJob(type: string, parameters: object) {
    const now = new Date();
    const [result] = await db.insert(reportJob).values({
      status: 'queued',
      type,
      parameters,
      createdAt: now,
    });
    const id = (result as any).insertId as number;
    const [job] = await db.select().from(reportJob).where(eq(reportJob.id, id));
    return job;
  }

  async getJob(id: number) {
    const [job] = await db.select().from(reportJob).where(eq(reportJob.id, id));
    if (!job) throw new AppError(404, `Job ${id} não encontrado.`);
    return job;
  }

  async getJobResults(jobId: number, page: number, limit: number) {
    const [job] = await db.select().from(reportJob).where(eq(reportJob.id, jobId));
    if (!job) throw new AppError(404, `Job ${jobId} não encontrado.`);
    if (job.status !== 'done') throw new AppError(400, `Job status é "${job.status}", não "done".`);

    const [header] = await db.select().from(reportResultHeader)
      .where(eq(reportResultHeader.jobId, jobId));
    if (!header) throw new AppError(404, `Resultado para job ${jobId} não encontrado.`);

    // Check TTL
    if (header.ttl < new Date()) throw new AppError(410, `Resultado expirado (TTL: ${header.ttl.toISOString()}).`);

    const offset = (page - 1) * limit;
    const rows = await db.select().from(reportResultRows)
      .where(eq(reportResultRows.headerId, header.id))
      .orderBy(asc(reportResultRows.rowIndex))
      .limit(limit)
      .offset(offset);

    const data = rows.map(r => JSON.parse(r.rowJson));
    return {
      columns: header.columnsJson,
      data,
      meta: { page, limit, totalRowsFetched: header.totalRowsFetched },
    };
  }

  async getDownloadPath(jobId: number): Promise<string> {
    const [header] = await db.select().from(reportResultHeader)
      .where(eq(reportResultHeader.jobId, jobId));
    if (!header) throw new AppError(404, `Resultado para job ${jobId} não encontrado.`);
    const filePath = (header.sourceTablesVersionsJson as any)?.filePath as string | undefined;
    if (!filePath) throw new AppError(404, `Arquivo de download não encontrado para job ${jobId}.`);
    return filePath;
  }
}

export const reportsService = new ReportsService();
```

- [ ] **Step 2: Write `src/routes/reports.routes.ts`**

```typescript
import { Router, Request, Response, NextFunction } from 'express';
import { createJobSchema } from '../validation/job.schema';
import { reportsService } from '../services/reports.service';
import path from 'path';
import fs from 'fs';

export const reportsRouter = Router();

// POST /reports/jobs
reportsRouter.post('/jobs', async (req: Request, res: Response, next: NextFunction) => {
  try {
    const parsed = createJobSchema.safeParse(req.body);
    if (!parsed.success) {
      return res.status(400).json({ statusCode: 400, message: parsed.error.errors.map(e => e.message) });
    }
    const job = await reportsService.createJob(parsed.data.type, parsed.data.parameters);
    res.status(202).json(job);
  } catch (err) { next(err); }
});

// GET /reports/jobs/:id
reportsRouter.get('/jobs/:id', async (req: Request, res: Response, next: NextFunction) => {
  try {
    const job = await reportsService.getJob(Number(req.params.id));
    res.json(job);
  } catch (err) { next(err); }
});

// GET /reports/jobs/:id/results
reportsRouter.get('/jobs/:id/results', async (req: Request, res: Response, next: NextFunction) => {
  try {
    const { page = '1', limit = '200' } = req.query as Record<string, string>;
    const result = await reportsService.getJobResults(Number(req.params.id), +page, +limit);
    res.json(result);
  } catch (err) { next(err); }
});

// GET /reports/jobs/:id/download
reportsRouter.get('/jobs/:id/download', async (req: Request, res: Response, next: NextFunction) => {
  try {
    const filePath = await reportsService.getDownloadPath(Number(req.params.id));
    if (!fs.existsSync(filePath)) return res.status(404).json({ statusCode: 404, message: 'Arquivo não encontrado.' });
    res.download(filePath, path.basename(filePath));
  } catch (err) { next(err); }
});
```

- [ ] **Step 3: Commit**

```bash
git add consultasia-express/backend/src/services/reports.service.ts consultasia-express/backend/src/routes/reports.routes.ts
git commit -m "feat: reports service and routes for job creation, status, results, download"
```

---

## Task 9: Express App Entry Point

**Files:**
- Create: `consultasia-express/backend/src/index.ts`

- [ ] **Step 1: Write `src/index.ts`**

```typescript
import 'dotenv/config';
import express from 'express';
import cors from 'cors';
import { timingMiddleware } from './middleware/timing';
import { errorHandler } from './middleware/error-handler';
import { siaRouter } from './routes/sia.routes';
import { reportsRouter } from './routes/reports.routes';

const isWorker = process.env.RUN_WORKER === 'true';

if (isWorker) {
  // Worker mode: start polling loop only
  import('./services/worker.service').then(({ workerService }) => {
    console.log('[Worker] Starting polling loop...');
    workerService.start();
  });
} else {
  const app = express();
  const PORT = Number(process.env.PORT ?? 3001);

  app.use(cors({ origin: process.env.CORS_ORIGIN ?? 'http://localhost:5174', methods: ['GET', 'POST'] }));
  app.use(express.json());
  app.use(timingMiddleware);

  app.get('/health', (_req, res) => res.json({ status: 'ok', ts: new Date().toISOString() }));
  app.use('/reports/sia', siaRouter);
  app.use('/reports', reportsRouter);

  app.use(errorHandler as any);

  app.listen(PORT, () => console.log(`[API] Listening on port ${PORT}`));
}
```

Install cors: `npm install cors && npm install -D @types/cors`

- [ ] **Step 2: Start and test**

```bash
cd consultasia-express/backend && npm run dev
```

In another terminal:
```bash
curl http://localhost:3001/health
# Expected: {"status":"ok","ts":"..."}

curl http://localhost:3001/reports/sia/metadata
# Expected: {"producao":{"description":"...","fields":[...]}, "limits":{...}}

curl "http://localhost:3001/reports/sia?competence=202301&pageSize=5"
# Expected: {"data":[...],"meta":{"totalRows":31765,...}}
```

Validate total count: `meta.totalRows` must equal **31765**.

- [ ] **Step 3: Test POST /production**

```bash
curl -s -X POST http://localhost:3001/reports/sia/production \
  -H "Content-Type: application/json" \
  -d '{
    "competence": "202301",
    "select": ["prd_uid", "PRD_QT_A", "PRD_VL_A"],
    "page": 1,
    "pageSize": 10
  }' | jq '.meta'
```

Expected: `hasAggregates: true`, `totalRows > 0`.

Validate SUM: run without pagination to check totals match reference.

- [ ] **Step 4: Commit**

```bash
git add consultasia-express/backend/src/index.ts
git commit -m "feat: Express app entry point with routing and worker mode bootstrap"
```

---

## Task 10: Worker Service

**Files:**
- Create: `consultasia-express/backend/src/services/worker.service.ts`

Install export libraries: `npm install exceljs pdfkit && npm install -D @types/pdfkit`

- [ ] **Step 1: Write `src/services/worker.service.ts`**

```typescript
import { db } from '../db';
import { reportJob, reportResultHeader, reportResultRows } from '../schemas/report-job';
import { eq, asc } from 'drizzle-orm';
import { sql } from 'drizzle-orm';
import { siaService } from './sia.service';
import fs from 'fs';
import path from 'path';

const MAX_EXPORT_ROWS = 100_000;
const MAX_PDF_ROWS   = 5_000;
const CHUNK_SIZE      = 1_000;
const POLL_INTERVAL   = 5_000;
const EXPORTS_DIR     = path.resolve('/tmp/exports');

fs.mkdirSync(EXPORTS_DIR, { recursive: true });

export class WorkerService {
  private running = false;

  start() {
    this.running = true;
    this.poll();
  }

  private async poll() {
    while (this.running) {
      try {
        await this.processNextJob();
      } catch (err) {
        console.error('[Worker] Unhandled error in poll:', err);
      }
      await new Promise(r => setTimeout(r, POLL_INTERVAL));
    }
  }

  private async processNextJob() {
    const [job] = await db.select().from(reportJob)
      .where(eq(reportJob.status, 'queued'))
      .orderBy(asc(reportJob.createdAt))
      .limit(1);

    if (!job) return;

    console.log(`[Worker] Processing job ${job.id} (type: ${job.type})`);

    // Atomic lock
    await db.update(reportJob).set({ status: 'running', startedAt: new Date() }).where(eq(reportJob.id, job.id));

    try {
      const params = job.parameters as any;

      if (job.type === 'export') {
        await this.handleExport(job.id, params.resultId, params.format);
      } else {
        const rows = await this.executeQuery(job.type, params);
        await this.persistResults(job.id, job.type, params.competence, rows, params);
      }

      await db.update(reportJob).set({ status: 'done', completedAt: new Date() }).where(eq(reportJob.id, job.id));
      console.log(`[Worker] Job ${job.id} done`);
    } catch (err: any) {
      console.error(`[Worker] Job ${job.id} failed:`, err.message);
      await db.update(reportJob).set({
        status: 'failed',
        completedAt: new Date(),
        errorMessage: String(err.message ?? err).slice(0, 2000),
      }).where(eq(reportJob.id, job.id));
    }
  }

  private async executeQuery(type: string, params: any): Promise<Record<string, unknown>[]> {
    if (type === 'sia-dynamic-production') {
      const result = await siaService.getDynamicProduction({
        competence: params.competence,
        select: params.select ?? [],
        filters: params.filters ?? [],
        page: 1,
        pageSize: MAX_EXPORT_ROWS,
      });
      return result.rows as Record<string, unknown>[];
    }
    if (type === 'sia-aggregated') {
      const [rows] = await db.execute(sql.raw(
        `SELECT sp.prd_cbo, cb.ds_cbo AS cbo_display,
                SUM(CAST(sp.PRD_QT_P AS UNSIGNED)) AS PRD_QT_P,
                SUM(CAST(sp.PRD_QT_A AS UNSIGNED)) AS PRD_QT_A,
                SUM(CAST(sp.PRD_VL_P AS DECIMAL(15,2))) AS PRD_VL_P,
                SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2))) AS PRD_VL_A
         FROM s_prd sp
         LEFT JOIN cbo cb ON sp.prd_cbo = cb.cbo
         WHERE sp.prd_cmp = '${params.competence}'
         GROUP BY sp.prd_cbo, cb.ds_cbo
         ORDER BY PRD_VL_A DESC`
      )) as [Record<string, unknown>[]];
      return rows;
    }
    if (type === 'sia-faturamento-prestador') {
      const [rows] = await db.execute(sql.raw(
        `SELECT sp.prd_uid, pr.re_cnome AS prestador_nome,
                sp.prd_rub AS tipo_financiamento,
                sp.grupo, sp.subgrupo, sp.forma,
                sp.prd_pa AS procedimento_codigo, pc.procedimento AS procedimento_nome,
                CAST(pc.PA_TOTAL AS DECIMAL(15,2)) AS valor_unitario,
                SUM(CAST(sp.PRD_QT_A AS UNSIGNED)) AS qtyApproved,
                SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2))) AS valueApproved,
                SUM(CAST(sp.PRD_QT_P AS UNSIGNED)) AS qtyPresented,
                SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * CAST(pc.PA_TOTAL AS DECIMAL(15,2))) AS valuePresented
         FROM s_prd sp
         LEFT JOIN prestador pr ON sp.prd_uid = pr.re_cunid
         LEFT JOIN procedimento pc ON sp.prd_pa = pc.codigo
         WHERE sp.prd_cmp = '${params.competence}'
         GROUP BY sp.prd_uid, pr.re_cnome, sp.prd_rub, sp.grupo, sp.subgrupo, sp.forma, sp.prd_pa, pc.procedimento, pc.PA_TOTAL
         ORDER BY pr.re_cnome, sp.prd_rub, sp.grupo, sp.subgrupo, sp.forma, sp.prd_pa`
      )) as [Record<string, unknown>[]];
      return rows;
    }
    throw new Error(`Job type desconhecido: ${type}`);
  }

  private async persistResults(jobId: number, reportType: string, competence: string, rows: Record<string, unknown>[], params: any) {
    const now = new Date();
    const ttl = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);

    const columns = rows.length > 0 ? Object.keys(rows[0]).map(k => ({ fieldId: k, label: k })) : [];

    const [headerResult] = await db.insert(reportResultHeader).values({
      jobId,
      reportType,
      competence,
      totalRowsFetched: rows.length,
      columnsJson: columns,
      sourceTablesVersionsJson: {},
      ttl,
      createdAt: now,
    });
    const headerId = (headerResult as any).insertId as number;

    // Chunk insert
    for (let i = 0; i < rows.length; i += CHUNK_SIZE) {
      const chunk = rows.slice(i, i + CHUNK_SIZE);
      const values = chunk.map((row, j) => ({ headerId, rowIndex: i + j, rowJson: JSON.stringify(row) }));
      await db.insert(reportResultRows).values(values);
    }
  }

  private async handleExport(jobId: number, resultId: number, format: 'xlsx' | 'csv' | 'pdf') {
    const header = await db.select().from(reportResultHeader).where(eq(reportResultHeader.id, resultId));
    if (!header[0]) throw new Error(`Result header ${resultId} não encontrado.`);

    const rows = await db.select().from(reportResultRows)
      .where(eq(reportResultRows.headerId, resultId))
      .orderBy(asc(reportResultRows.rowIndex))
      .limit(format === 'pdf' ? MAX_PDF_ROWS : MAX_EXPORT_ROWS);

    const data = rows.map(r => JSON.parse(r.rowJson));
    const columns = (header[0].columnsJson as { fieldId: string; label: string }[]) ?? [];
    const fileName = `${jobId}.${format}`;
    const filePath = path.join(EXPORTS_DIR, fileName);

    if (format === 'xlsx') await this.generateXlsx(filePath, columns, data);
    else if (format === 'csv') await this.generateCsv(filePath, columns, data);
    else await this.generatePdf(filePath, columns, data);

    await db.update(reportResultHeader).set({
      sourceTablesVersionsJson: { filePath },
    }).where(eq(reportResultHeader.id, resultId));

    // Link export result to export job
    const now = new Date();
    const ttl = new Date(now.getTime() + 2 * 24 * 60 * 60 * 1000);
    await db.insert(reportResultHeader).values({
      jobId,
      reportType: 'export',
      competence: '',
      totalRowsFetched: data.length,
      columnsJson: columns,
      sourceTablesVersionsJson: { filePath },
      ttl,
      createdAt: now,
    });
  }

  private async generateXlsx(filePath: string, columns: { fieldId: string; label: string }[], data: any[]) {
    const ExcelJS = (await import('exceljs')).default;
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Relatório');
    ws.columns = columns.map(c => ({ header: c.label, key: c.fieldId, width: 18 }));
    const headerRow = ws.getRow(1);
    headerRow.font = { bold: true };
    headerRow.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD9EAF7' } };
    ws.addRows(data.map(row => columns.reduce((acc: any, c) => { acc[c.fieldId] = row[c.fieldId]; return acc; }, {})));
    await wb.xlsx.writeFile(filePath);
  }

  private async generateCsv(filePath: string, columns: { fieldId: string; label: string }[], data: any[]) {
    const bom = '﻿';
    const header = columns.map(c => `"${c.label}"`).join(',');
    const dataRows = data.map(row =>
      columns.map(c => `"${String(row[c.fieldId] ?? '').replace(/"/g, '""')}"`).join(',')
    );
    fs.writeFileSync(filePath, bom + [header, ...dataRows].join('\r\n'), 'utf-8');
  }

  private async generatePdf(filePath: string, columns: { fieldId: string; label: string }[], data: any[]) {
    const PDFDocument = (await import('pdfkit')).default;
    const doc = new PDFDocument({ layout: 'landscape', size: 'A4', margin: 30 });
    const stream = fs.createWriteStream(filePath);
    doc.pipe(stream);
    doc.fontSize(10).text('Relatório ConsultAsia', { align: 'center' });
    doc.moveDown();
    const colWidth = (doc.page.width - 60) / columns.length;
    columns.forEach((c, i) => {
      doc.rect(30 + i * colWidth, doc.y, colWidth, 20).stroke();
      doc.text(c.label, 32 + i * colWidth, doc.y + 4, { width: colWidth - 4 });
    });
    doc.moveDown(1.5);
    data.forEach(row => {
      columns.forEach((c, i) => {
        doc.text(String(row[c.fieldId] ?? ''), 32 + i * colWidth, doc.y, { width: colWidth - 4 });
      });
      doc.moveDown(0.5);
    });
    doc.end();
    return new Promise<void>((resolve, reject) => stream.on('finish', resolve).on('error', reject));
  }
}

export const workerService = new WorkerService();
```

- [ ] **Step 2: Commit**

```bash
git add consultasia-express/backend/src/services/worker.service.ts
git commit -m "feat: worker service with polling loop, 3 job types, and xlsx/csv/pdf export"
```

---

## Task 11: Frontend Scaffold + API Client

**Files:**
- Create: `consultasia-express/frontend/` (Vite React TypeScript)

- [ ] **Step 1: Create frontend**

```bash
cd consultasia-express
npm create vite@latest frontend -- --template react-ts
cd frontend
npm install
npm install axios react-router-dom
```

- [ ] **Step 2: Write `src/types.ts`**

```typescript
export interface FieldMetadata {
  id: string; label: string; type: string;
  allowedOperators: string[]; sortable: boolean; groupable: boolean;
  isAggregate: boolean; filterOnly: boolean; displayOnly: boolean;
}

export interface Column { fieldId: string; label: string; type: string; displayAlias?: string; }
export interface QueryMeta { totalRows: number; page: number; pageSize: number; totalPages: number; queryTimeMs: number; hasAggregates: boolean; warning: string | null; }
export interface DynamicResult { columns: Column[]; rows: Record<string, unknown>[]; meta: QueryMeta; }

export interface Job {
  id: number; status: 'queued' | 'running' | 'done' | 'failed';
  type: string; parameters: unknown; createdAt: string;
  startedAt?: string; completedAt?: string; errorMessage?: string;
}
```

- [ ] **Step 3: Write `src/api.ts`**

```typescript
import axios from 'axios';
import type { DynamicResult, Job } from './types';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:3001',
  headers: { 'Content-Type': 'application/json' },
});

export const getMetadata = () => api.get('/reports/sia/metadata').then(r => r.data);

export const getSimpleList = (competence: string, page: number, pageSize: number) =>
  api.get('/reports/sia', { params: { competence, page, pageSize } }).then(r => r.data);

export const postProduction = (body: object, signal?: AbortSignal): Promise<DynamicResult> =>
  api.post('/reports/sia/production', body, { signal }).then(r => r.data);

export const createJob = (type: string, parameters: object): Promise<Job> =>
  api.post('/reports/jobs', { type, parameters }).then(r => r.data);

export const getJob = (id: number): Promise<Job> =>
  api.get(`/reports/jobs/${id}`).then(r => r.data);

export const getJobResults = (id: number, page: number, limit: number) =>
  api.get(`/reports/jobs/${id}/results`, { params: { page, limit } }).then(r => r.data);
```

- [ ] **Step 4: Write `src/hooks/useJobPolling.ts`**

```typescript
import { useEffect, useState, useRef } from 'react';
import { getJob } from '../api';
import type { Job } from '../types';

export function useJobPolling(jobId: number | null) {
  const [job, setJob] = useState<Job | null>(null);
  const [error, setError] = useState<string | null>(null);
  const timerRef = useRef<ReturnType<typeof setTimeout>>();

  useEffect(() => {
    if (!jobId) return;

    const poll = async () => {
      try {
        const j = await getJob(jobId);
        setJob(j);
        if (j.status !== 'done' && j.status !== 'failed') {
          timerRef.current = setTimeout(poll, 2000);
        }
      } catch (err: any) {
        setError(err.message ?? 'Erro ao buscar status do job.');
      }
    };

    poll();
    return () => { if (timerRef.current) clearTimeout(timerRef.current); };
  }, [jobId]);

  return { job, error };
}
```

- [ ] **Step 5: Commit**

```bash
git add consultasia-express/frontend/
git commit -m "feat: React frontend scaffold with api.ts, types.ts, useJobPolling hook"
```

---

## Task 12: Frontend Pages

**Files:**
- Create: `consultasia-express/frontend/src/pages/SiaDynamicPage.tsx`
- Create: `consultasia-express/frontend/src/pages/AsyncReportsPage.tsx`
- Create: `consultasia-express/frontend/src/pages/JobResultsPage.tsx`
- Create: `consultasia-express/frontend/src/components/DataGrid.tsx`
- Create: `consultasia-express/frontend/src/routes.tsx`
- Modify: `consultasia-express/frontend/src/App.tsx`

- [ ] **Step 1: Write `src/components/DataGrid.tsx`**

```typescript
import React from 'react';
import type { Column } from '../types';

interface DataGridProps {
  columns: Column[];
  rows: Record<string, unknown>[];
  page: number; pageSize: number; totalRows: number;
  onPageChange: (page: number) => void;
  onPageSizeChange: (size: number) => void;
  loading?: boolean;
  queryTimeMs?: number;
}

export function DataGrid({ columns, rows, page, pageSize, totalRows, onPageChange, onPageSizeChange, loading, queryTimeMs }: DataGridProps) {
  const totalPages = Math.ceil(totalRows / pageSize);

  return (
    <div style={{ fontFamily: 'monospace', fontSize: 13 }}>
      {queryTimeMs !== undefined && <div style={{ color: '#666', marginBottom: 8 }}>Tempo: {queryTimeMs}ms — {totalRows} registros</div>}
      {loading && <div>Carregando...</div>}
      <div style={{ overflowX: 'auto' }}>
        <table style={{ borderCollapse: 'collapse', width: '100%' }}>
          <thead>
            <tr>
              {columns.map(col => (
                <th key={col.fieldId} style={{ border: '1px solid #ccc', padding: '4px 8px', background: '#f0f0f0', textAlign: 'left', whiteSpace: 'nowrap' }}>
                  {col.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {rows.map((row, i) => (
              <tr key={i} style={{ background: i % 2 === 0 ? '#fff' : '#fafafa' }}>
                {columns.map(col => (
                  <td key={col.fieldId} style={{ border: '1px solid #eee', padding: '3px 8px', whiteSpace: 'nowrap' }}>
                    {col.displayAlias
                      ? String(row[col.displayAlias] ?? row[col.fieldId] ?? '')
                      : String(row[col.fieldId] ?? '')}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div style={{ marginTop: 8, display: 'flex', gap: 8, alignItems: 'center' }}>
        <button onClick={() => onPageChange(page - 1)} disabled={page <= 1}>◀</button>
        <span>Página {page} de {totalPages}</span>
        <button onClick={() => onPageChange(page + 1)} disabled={page >= totalPages}>▶</button>
        <select value={pageSize} onChange={e => onPageSizeChange(+e.target.value)}>
          {[50, 100, 200, 500].map(n => <option key={n} value={n}>{n}/página</option>)}
        </select>
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Write `src/pages/SiaDynamicPage.tsx`**

```typescript
import React, { useEffect, useState } from 'react';
import { getMetadata, postProduction } from '../api';
import type { FieldMetadata, DynamicResult, Column } from '../types';
import { DataGrid } from '../components/DataGrid';

export function SiaDynamicPage() {
  const [fields, setFields] = useState<FieldMetadata[]>([]);
  const [competence, setCompetence] = useState('202301');
  const [selectedFields, setSelectedFields] = useState<string[]>(['prd_uid', 'PRD_QT_A', 'PRD_VL_A']);
  const [result, setResult] = useState<DynamicResult | null>(null);
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(50);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortRef = React.useRef<AbortController>();

  useEffect(() => {
    getMetadata().then(m => setFields(m.producao.fields.filter((f: FieldMetadata) => !f.filterOnly)));
  }, []);

  const handleApply = async () => {
    if (abortRef.current) abortRef.current.abort();
    abortRef.current = new AbortController();
    setLoading(true); setError(null);
    try {
      const r = await postProduction({ competence, select: selectedFields, page, pageSize }, abortRef.current.signal);
      setResult(r);
    } catch (err: any) {
      if (err.name !== 'CanceledError') setError(err.response?.data?.message?.join(', ') ?? err.message);
    } finally {
      setLoading(false);
    }
  };

  const toggleField = (id: string) =>
    setSelectedFields(prev => prev.includes(id) ? prev.filter(f => f !== id) : [...prev, id]);

  return (
    <div style={{ padding: 24 }}>
      <h2>Relatório Dinâmico SIA</h2>
      <div style={{ display: 'flex', gap: 16, flexWrap: 'wrap', marginBottom: 16 }}>
        <label>Competência: <input value={competence} onChange={e => setCompetence(e.target.value)} maxLength={6} style={{ width: 80 }} /></label>
        <button onClick={handleApply} disabled={loading} style={{ padding: '4px 16px' }}>Aplicar</button>
        {loading && <button onClick={() => abortRef.current?.abort()}>Cancelar</button>}
      </div>
      <div style={{ marginBottom: 16 }}>
        <strong>Campos:</strong>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8, marginTop: 8 }}>
          {fields.map(f => (
            <label key={f.id} style={{ display: 'flex', alignItems: 'center', gap: 4 }}>
              <input type="checkbox" checked={selectedFields.includes(f.id)} onChange={() => toggleField(f.id)} />
              {f.label}
            </label>
          ))}
        </div>
      </div>
      {error && <div style={{ color: 'red', marginBottom: 12 }}>{error}</div>}
      {result?.meta.warning && <div style={{ color: '#e67e22', marginBottom: 12 }}>⚠️ {result.meta.warning}</div>}
      {result && (
        <DataGrid
          columns={result.columns}
          rows={result.rows}
          page={result.meta.page}
          pageSize={result.meta.pageSize}
          totalRows={result.meta.totalRows}
          onPageChange={p => { setPage(p); handleApply(); }}
          onPageSizeChange={s => { setPageSize(s); setPage(1); }}
          queryTimeMs={result.meta.queryTimeMs}
          loading={loading}
        />
      )}
    </div>
  );
}
```

- [ ] **Step 3: Write `src/pages/AsyncReportsPage.tsx`**

```typescript
import React, { useState } from 'react';
import { createJob } from '../api';
import { useJobPolling } from '../hooks/useJobPolling';

export function AsyncReportsPage() {
  const [jobType, setJobType] = useState('sia-aggregated');
  const [competence, setCompetence] = useState('202301');
  const [jobId, setJobId] = useState<number | null>(null);
  const { job, error } = useJobPolling(jobId);

  const handleSubmit = async () => {
    const j = await createJob(jobType, { competence });
    setJobId(j.id);
  };

  const statusColor: Record<string, string> = { queued: '#888', running: '#e67e22', done: '#27ae60', failed: '#e74c3c' };

  return (
    <div style={{ padding: 24 }}>
      <h2>Relatórios Assíncronos</h2>
      <div style={{ display: 'flex', gap: 12, marginBottom: 16 }}>
        <select value={jobType} onChange={e => setJobType(e.target.value)}>
          <option value="sia-aggregated">SIA Agregado (CBO)</option>
          <option value="sia-faturamento-prestador">Faturamento por Prestador</option>
          <option value="sia-dynamic-production">SIA Dinâmico</option>
        </select>
        <input value={competence} onChange={e => setCompetence(e.target.value)} maxLength={6} placeholder="AAAAMM" style={{ width: 80 }} />
        <button onClick={handleSubmit}>Criar Job</button>
      </div>
      {error && <div style={{ color: 'red' }}>{error}</div>}
      {job && (
        <div>
          <p>Job ID: <strong>{job.id}</strong></p>
          <p>Status: <strong style={{ color: statusColor[job.status] }}>{job.status}</strong></p>
          {job.status === 'done' && (
            <a href={`/job-results/${job.id}`}>Ver Resultados →</a>
          )}
          {job.status === 'failed' && <p style={{ color: 'red' }}>Erro: {job.errorMessage}</p>}
        </div>
      )}
    </div>
  );
}
```

- [ ] **Step 4: Write `src/pages/JobResultsPage.tsx`**

```typescript
import React, { useEffect, useState } from 'react';
import { useParams } from 'react-router-dom';
import { getJobResults, createJob, getJob } from '../api';
import { DataGrid } from '../components/DataGrid';
import type { Column } from '../types';

export function JobResultsPage() {
  const { jobId } = useParams<{ jobId: string }>();
  const [data, setData] = useState<Record<string, unknown>[]>([]);
  const [columns, setColumns] = useState<Column[]>([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(200);
  const [exportJobId, setExportJobId] = useState<number | null>(null);
  const [exportStatus, setExportStatus] = useState<string>('');

  const load = async (p: number, ps: number) => {
    const result = await getJobResults(Number(jobId), p, ps);
    setColumns(result.columns ?? []);
    setData(result.data);
    setTotal(result.meta.totalRowsFetched);
  };

  useEffect(() => { load(page, pageSize); }, [jobId]);

  const handleExport = async (format: 'xlsx' | 'csv' | 'pdf') => {
    const resultRes = await getJobResults(Number(jobId), 1, 1);
    const j = await createJob('export', { resultId: Number(jobId), format });
    setExportJobId(j.id);
    setExportStatus('Gerando arquivo...');
    const poll = setInterval(async () => {
      const status = await getJob(j.id);
      if (status.status === 'done') {
        clearInterval(poll);
        setExportStatus('');
        window.location.href = `/reports/jobs/${j.id}/download`;
      } else if (status.status === 'failed') {
        clearInterval(poll);
        setExportStatus(`Erro: ${status.errorMessage}`);
      }
    }, 2000);
  };

  return (
    <div style={{ padding: 24 }}>
      <h2>Resultados — Job #{jobId}</h2>
      <div style={{ marginBottom: 12, display: 'flex', gap: 8 }}>
        <button onClick={() => handleExport('xlsx')}>Exportar XLSX</button>
        <button onClick={() => handleExport('csv')}>Exportar CSV</button>
        <button onClick={() => handleExport('pdf')}>Exportar PDF</button>
        {exportStatus && <span style={{ color: '#e67e22' }}>{exportStatus}</span>}
      </div>
      <DataGrid
        columns={columns}
        rows={data}
        page={page}
        pageSize={pageSize}
        totalRows={total}
        onPageChange={p => { setPage(p); load(p, pageSize); }}
        onPageSizeChange={s => { setPageSize(s); setPage(1); load(1, s); }}
      />
    </div>
  );
}
```

- [ ] **Step 5: Write `src/routes.tsx`**

```typescript
import React from 'react';
import { BrowserRouter, Routes, Route, Link } from 'react-router-dom';
import { SiaDynamicPage } from './pages/SiaDynamicPage';
import { AsyncReportsPage } from './pages/AsyncReportsPage';
import { JobResultsPage } from './pages/JobResultsPage';

export function AppRoutes() {
  return (
    <BrowserRouter>
      <nav style={{ padding: '8px 24px', background: '#f5f5f5', display: 'flex', gap: 16 }}>
        <Link to="/">SIA Dinâmico</Link>
        <Link to="/async">Relatórios Assíncronos</Link>
      </nav>
      <Routes>
        <Route path="/" element={<SiaDynamicPage />} />
        <Route path="/async" element={<AsyncReportsPage />} />
        <Route path="/job-results/:jobId" element={<JobResultsPage />} />
      </Routes>
    </BrowserRouter>
  );
}
```

- [ ] **Step 6: Update `src/App.tsx`**

```typescript
import { AppRoutes } from './routes';
export default function App() { return <AppRoutes />; }
```

- [ ] **Step 7: Create `frontend/.env`**

```env
VITE_API_URL=http://localhost:3001
```

- [ ] **Step 8: Start frontend and test**

```bash
cd consultasia-express/frontend && npm run dev
# Open http://localhost:5174
```

Verify:
1. `/` loads SIA Dinâmico page with field checkboxes
2. Entering `202301` and clicking "Aplicar" returns data
3. Table shows correct data with pagination
4. `/async` creates a job and polls status

- [ ] **Step 9: Commit**

```bash
git add consultasia-express/frontend/src/
git commit -m "feat: React frontend pages — SiaDynamicPage, AsyncReportsPage, JobResultsPage, DataGrid"
```

---

## Task 13: End-to-End Validation

**Goal:** Verify delta = 0 against reference totals.

- [ ] **Step 1: Validate simple SIA list totals**

```bash
curl -s "http://localhost:3001/reports/sia?competence=202301&pageSize=1" | jq '.meta.totalRows'
# Expected: 31765
```

- [ ] **Step 2: Validate aggregated totals via POST /production**

```bash
curl -s -X POST http://localhost:3001/reports/sia/production \
  -H "Content-Type: application/json" \
  -d '{
    "competence": "202301",
    "select": ["PRD_QT_A", "PRD_VL_A", "PRD_QT_P", "PRD_VL_P"],
    "page": 1,
    "pageSize": 1
  }' | jq '.rows[0]'
```

Expected rows[0]:
```json
{
  "PRD_QT_A": 214675,
  "PRD_VL_A": 1606620.01,
  "PRD_QT_P": 214866,
  "PRD_VL_P": 1673942.13
}
```

- [ ] **Step 3: Validate job async flow**

```bash
# Create job
JOB_ID=$(curl -s -X POST http://localhost:3001/reports/jobs \
  -H "Content-Type: application/json" \
  -d '{"type":"sia-aggregated","parameters":{"competence":"202301"}}' | jq -r '.id')

# Wait ~5s, then check status
sleep 6 && curl -s "http://localhost:3001/reports/jobs/$JOB_ID" | jq '.status'
# Expected: "done"

# Fetch results
curl -s "http://localhost:3001/reports/jobs/$JOB_ID/results?limit=5" | jq '.meta'
```

- [ ] **Step 4: Validate export flow**

```bash
# Export results as XLSX
EXPORT_JOB=$(curl -s -X POST http://localhost:3001/reports/jobs \
  -H "Content-Type: application/json" \
  -d "{\"type\":\"export\",\"parameters\":{\"resultId\":1,\"format\":\"xlsx\"}}" | jq -r '.id')

sleep 6 && curl -s "http://localhost:3001/reports/jobs/$EXPORT_JOB" | jq '.status'
# Expected: "done"
```

- [ ] **Step 5: Final commit**

```bash
git add .
git commit -m "feat: complete ConsultAsia Express+Drizzle implementation — E2E validated"
```

---

## Self-Review

### Spec Coverage
- [x] Express backend with Drizzle ORM
- [x] MySQL 'producao' connection (no DDL changes)
- [x] Field catalog whitelist (13 SIA fields + operators)
- [x] POST /reports/sia/production (dynamic query builder)
- [x] GET /reports/sia/metadata
- [x] GET /reports/sia (simple list)
- [x] Job system: POST /jobs, GET /jobs/:id, /results, /download
- [x] Worker polling (3 job types + export)
- [x] XLSX/CSV/PDF export
- [x] Zod validation (competence, select, filters, operators)
- [x] CAST handling for VARCHAR numeric fields
- [x] GROUP BY logic for aggregate fields
- [x] Lookup display columns (_display convention)
- [x] React frontend with DataGrid, SiaDynamicPage, AsyncReportsPage, JobResultsPage
- [x] useJobPolling hook
- [x] AbortController on cancel
- [x] E2E validation against reference totals

### No Placeholders Found ✓

### Type Consistency
- `FieldDef` defined in Task 3, used in Tasks 5, 6
- `SiaProductionQuery` defined in Task 4, used in Tasks 6, 7
- `reportJob`, `reportResultHeader`, `reportResultRows` defined in Task 2, used in Tasks 8, 10
- `Column`, `DynamicResult`, `Job` defined in Task 11, used in Tasks 11, 12
- All consistent ✓

---

## Verification Checklist

| Test | Command | Expected |
|------|---------|----------|
| DB connection | `curl localhost:3001/health` | `{"status":"ok"}` |
| Metadata endpoint | `curl localhost:3001/reports/sia/metadata` | 14 fields in producao.fields |
| Row count | `GET /reports/sia?competence=202301&pageSize=1` | `totalRows: 31765` |
| Sum totals | `POST /production` select all aggregates | `PRD_QT_A: 214675, PRD_VL_A: 1606620.01` |
| Job creation | `POST /reports/jobs` | HTTP 202, `status: "queued"` |
| Job completion | `GET /reports/jobs/:id` after 6s | `status: "done"` |
| Results | `GET /reports/jobs/:id/results` | rows with JSON data |
| Export | POST job type "export" + download | File served as download |
| Frontend | `http://localhost:5174` | SIA Dinâmico page loads |
| Frontend apply | Select fields + Aplicar | DataGrid populates |
