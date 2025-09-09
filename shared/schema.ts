import { sql } from "drizzle-orm";
import { pgTable, varchar, text, decimal, timestamp, boolean, integer, index } from "drizzle-orm/pg-core";
import { relations } from "drizzle-orm";
import { createInsertSchema } from "drizzle-zod";
import { z } from "zod";

// Users table for authentication
export const users = pgTable("users", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  username: varchar("username", { length: 255 }).notNull().unique(),
  password: text("password").notNull(),
  email: varchar("email", { length: 255 }).unique(),
  firstName: varchar("first_name", { length: 255 }),
  lastName: varchar("last_name", { length: 255 }),
  role: varchar("role", { length: 50 }).notNull().default("operator"), // admin, operator
  active: boolean("active").notNull().default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// CBO (Ocupações) table
export const cbo = pgTable("cbo", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  codigo: varchar("codigo", { length: 20 }).notNull().unique(),
  descricao: text("descricao").notNull(),
  status: boolean("status").notNull().default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Prestador table
export const prestador = pgTable("prestador", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  codigo: varchar("codigo", { length: 20 }).notNull().unique(),
  nomeRazaoSocial: varchar("nome_razao_social", { length: 255 }).notNull(),
  cnpjCpf: varchar("cnpj_cpf", { length: 20 }).notNull(),
  tipo: varchar("tipo", { length: 50 }).notNull(), // pessoa_fisica, pessoa_juridica
  status: boolean("status").notNull().default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Procedimento table
export const procedimento = pgTable("procedimento", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  codigo: varchar("codigo", { length: 50 }).notNull().unique(),
  descricao: text("descricao").notNull(),
  valor: decimal("valor", { precision: 10, scale: 2 }).notNull(),
  complexidade: varchar("complexidade", { length: 50 }).notNull(), // baixa, media, alta
  status: boolean("status").notNull().default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// S_RUB (Financiamentos) table
export const sRub = pgTable("s_rub", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  codigo: varchar("codigo", { length: 20 }).notNull().unique(),
  descricao: text("descricao").notNull(),
  tipoFinanciamento: varchar("tipo_financiamento", { length: 100 }).notNull(),
  status: boolean("status").notNull().default(true),
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
});

// Main production data table
export const consultaProd = pgTable("consulta_prod", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  prdDtcomp: timestamp("prd_dtcomp").notNull(), // Data Competência
  prdDtreal: timestamp("prd_dtreal").notNull(), // Data Realização
  prdCbo: varchar("prd_cbo", { length: 255 }).references(() => cbo.id),
  prdPrest: varchar("prd_prest", { length: 255 }).references(() => prestador.id),
  prdProc: varchar("prd_proc", { length: 255 }).references(() => procedimento.id),
  prdQtd: integer("prd_qtd").notNull(), // Quantidade
  prdVlP: decimal("prd_vl_p", { precision: 10, scale: 2 }).notNull(), // Valor
  prdRub: varchar("prd_rub", { length: 255 }).references(() => sRub.id),
  prdCidpri: varchar("prd_cidpri", { length: 20 }), // CID Principal
  createdAt: timestamp("created_at").defaultNow(),
  updatedAt: timestamp("updated_at").defaultNow(),
}, (table) => [
  index("idx_consulta_prod_dtcomp").on(table.prdDtcomp),
  index("idx_consulta_prod_dtreal").on(table.prdDtreal),
]);

// Audit log table
export const auditLog = pgTable("audit_log", {
  id: varchar("id").primaryKey().default(sql`gen_random_uuid()`),
  userId: varchar("user_id", { length: 255 }).references(() => users.id),
  action: varchar("action", { length: 100 }).notNull(), // create, update, delete, export
  tableName: varchar("table_name", { length: 100 }).notNull(),
  recordId: varchar("record_id", { length: 255 }),
  oldValues: text("old_values"), // JSON
  newValues: text("new_values"), // JSON
  createdAt: timestamp("created_at").defaultNow(),
});

// Relations
export const consultaProdRelations = relations(consultaProd, ({ one }) => ({
  cbo: one(cbo, {
    fields: [consultaProd.prdCbo],
    references: [cbo.id],
  }),
  prestador: one(prestador, {
    fields: [consultaProd.prdPrest],
    references: [prestador.id],
  }),
  procedimento: one(procedimento, {
    fields: [consultaProd.prdProc],
    references: [procedimento.id],
  }),
  sRub: one(sRub, {
    fields: [consultaProd.prdRub],
    references: [sRub.id],
  }),
}));

export const auditLogRelations = relations(auditLog, ({ one }) => ({
  user: one(users, {
    fields: [auditLog.userId],
    references: [users.id],
  }),
}));

// Insert schemas
export const insertUserSchema = createInsertSchema(users).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export const insertCboSchema = createInsertSchema(cbo).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export const insertPrestadorSchema = createInsertSchema(prestador).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export const insertProcedimentoSchema = createInsertSchema(procedimento).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export const insertSRubSchema = createInsertSchema(sRub).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export const insertConsultaProdSchema = createInsertSchema(consultaProd).omit({
  id: true,
  createdAt: true,
  updatedAt: true,
});

export const insertAuditLogSchema = createInsertSchema(auditLog).omit({
  id: true,
  createdAt: true,
});

// Export types
export type User = typeof users.$inferSelect;
export type InsertUser = z.infer<typeof insertUserSchema>;

export type CBO = typeof cbo.$inferSelect;
export type InsertCBO = z.infer<typeof insertCboSchema>;

export type Prestador = typeof prestador.$inferSelect;
export type InsertPrestador = z.infer<typeof insertPrestadorSchema>;

export type Procedimento = typeof procedimento.$inferSelect;
export type InsertProcedimento = z.infer<typeof insertProcedimentoSchema>;

export type SRub = typeof sRub.$inferSelect;
export type InsertSRub = z.infer<typeof insertSRubSchema>;

export type ConsultaProd = typeof consultaProd.$inferSelect;
export type InsertConsultaProd = z.infer<typeof insertConsultaProdSchema>;

export type AuditLog = typeof auditLog.$inferSelect;
export type InsertAuditLog = z.infer<typeof insertAuditLogSchema>;

// Authentication schemas
export const loginSchema = z.object({
  username: z.string().min(1, "Username é obrigatório"),
  password: z.string().min(1, "Senha é obrigatória"),
});

export type LoginRequest = z.infer<typeof loginSchema>;
