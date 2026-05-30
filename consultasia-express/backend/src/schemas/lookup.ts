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
  codigo:    varchar('codigo',      { length: 10 }).primaryKey(),
  descricao: varchar('procedimento', { length: 200 }),
  paTotal:   decimal('PA_TOTAL', { precision: 15, scale: 2 }),
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
