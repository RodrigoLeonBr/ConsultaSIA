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
