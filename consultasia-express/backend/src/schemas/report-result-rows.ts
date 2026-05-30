import { mysqlTable, int, longtext } from 'drizzle-orm/mysql-core';

export const reportResultRows = mysqlTable('report_result_rows', {
  id:       int('id').primaryKey().autoincrement(),
  headerId: int('header_id').notNull(),
  rowIndex: int('row_index').notNull(),
  rowJson:  longtext('row_json').notNull(),
});
