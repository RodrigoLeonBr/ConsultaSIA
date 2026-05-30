import { mysqlTable, int, varchar, json, datetime } from 'drizzle-orm/mysql-core';

export const reportResultHeader = mysqlTable('report_result_header', {
  id:                       int('id').primaryKey().autoincrement(),
  jobId:                    int('job_id').notNull(),
  reportType:               varchar('report_type', { length: 80 }),
  competence:               varchar('competence', { length: 6 }),
  totalRowsFetched:         int('total_rows_fetched').notNull().default(0),
  columnsJson:              json('columns_json'),
  sourceTablesVersionsJson: json('source_tables_versions_json'),
  ttl:                      datetime('ttl').notNull(),
  createdAt:                datetime('created_at').notNull(),
});

export type ReportResultHeader = typeof reportResultHeader.$inferSelect;
