import { mysqlTable, int, varchar, json, datetime, mysqlEnum } from 'drizzle-orm/mysql-core';

export const reportJob = mysqlTable('report_job', {
  id:           int('id').primaryKey().autoincrement(),
  status:       mysqlEnum('status', ['queued', 'running', 'done', 'failed']).notNull().default('queued'),
  type:         varchar('type', { length: 80 }).notNull(),
  parameters:   json('parameters').notNull(),
  errorMessage: varchar('error_message', { length: 2000 }),
  createdAt:    datetime('created_at').notNull(),
  startedAt:    datetime('started_at'),
  completedAt:  datetime('completed_at'),
});

export type NewReportJob = typeof reportJob.$inferInsert;
export type ReportJob    = typeof reportJob.$inferSelect;
