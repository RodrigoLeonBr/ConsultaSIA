import {
  users,
  cbo,
  prestador,
  procedimento,
  sRub,
  consultaProd,
  auditLog,
  type User,
  type InsertUser,
  type CBO,
  type InsertCBO,
  type Prestador,
  type InsertPrestador,
  type Procedimento,
  type InsertProcedimento,
  type SRub,
  type InsertSRub,
  type ConsultaProd,
  type InsertConsultaProd,
  type AuditLog,
  type InsertAuditLog,
} from "@shared/schema";
import { db } from "./db";
import { eq, and, or, like, gte, lte, desc, asc, count } from "drizzle-orm";
import bcrypt from "bcrypt";

export interface IStorage {
  // User operations
  getUser(id: string): Promise<User | undefined>;
  getUserByUsername(username: string): Promise<User | undefined>;
  createUser(user: InsertUser): Promise<User>;
  updateUser(id: string, user: Partial<InsertUser>): Promise<User | undefined>;
  deleteUser(id: string): Promise<boolean>;

  // CBO operations
  getCBOs(page?: number, limit?: number, search?: string): Promise<{ data: CBO[]; total: number }>;
  getCBO(id: string): Promise<CBO | undefined>;
  createCBO(cbo: InsertCBO): Promise<CBO>;
  updateCBO(id: string, cbo: Partial<InsertCBO>): Promise<CBO | undefined>;
  deleteCBO(id: string): Promise<boolean>;

  // Prestador operations
  getPrestadores(page?: number, limit?: number, search?: string): Promise<{ data: Prestador[]; total: number }>;
  getPrestador(id: string): Promise<Prestador | undefined>;
  createPrestador(prestador: InsertPrestador): Promise<Prestador>;
  updatePrestador(id: string, prestador: Partial<InsertPrestador>): Promise<Prestador | undefined>;
  deletePrestador(id: string): Promise<boolean>;

  // Procedimento operations
  getProcedimentos(page?: number, limit?: number, search?: string): Promise<{ data: Procedimento[]; total: number }>;
  getProcedimento(id: string): Promise<Procedimento | undefined>;
  createProcedimento(procedimento: InsertProcedimento): Promise<Procedimento>;
  updateProcedimento(id: string, procedimento: Partial<InsertProcedimento>): Promise<Procedimento | undefined>;
  deleteProcedimento(id: string): Promise<boolean>;

  // SRub operations
  getSRubs(page?: number, limit?: number, search?: string): Promise<{ data: SRub[]; total: number }>;
  getSRub(id: string): Promise<SRub | undefined>;
  createSRub(srub: InsertSRub): Promise<SRub>;
  updateSRub(id: string, srub: Partial<InsertSRub>): Promise<SRub | undefined>;
  deleteSRub(id: string): Promise<boolean>;

  // ConsultaProd operations
  getConsultaProdData(filters?: any, page?: number, limit?: number): Promise<{ data: any[]; total: number }>;
  createConsultaProd(consultaProd: InsertConsultaProd): Promise<ConsultaProd>;

  // Dashboard statistics
  getDashboardStats(): Promise<any>;

  // Audit log
  createAuditLog(auditLog: InsertAuditLog): Promise<AuditLog>;
}

export class DatabaseStorage implements IStorage {
  // User operations
  async getUser(id: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.id, id));
    return user || undefined;
  }

  async getUserByUsername(username: string): Promise<User | undefined> {
    const [user] = await db.select().from(users).where(eq(users.username, username));
    return user || undefined;
  }

  async createUser(userData: InsertUser): Promise<User> {
    const hashedPassword = await bcrypt.hash(userData.password, 10);
    const [user] = await db
      .insert(users)
      .values({ ...userData, password: hashedPassword })
      .returning();
    return user;
  }

  async updateUser(id: string, userData: Partial<InsertUser>): Promise<User | undefined> {
    let updateData = { ...userData };
    if (updateData.password) {
      updateData.password = await bcrypt.hash(updateData.password, 10);
    }
    
    const [user] = await db
      .update(users)
      .set({ ...updateData, updatedAt: new Date() })
      .where(eq(users.id, id))
      .returning();
    return user || undefined;
  }

  async deleteUser(id: string): Promise<boolean> {
    const result = await db.delete(users).where(eq(users.id, id));
    return result.rowCount > 0;
  }

  // CBO operations
  async getCBOs(page = 1, limit = 10, search?: string): Promise<{ data: CBO[]; total: number }> {
    const offset = (page - 1) * limit;
    const whereCondition = search 
      ? or(
          like(cbo.codigo, `%${search}%`),
          like(cbo.descricao, `%${search}%`)
        )
      : undefined;

    const [data, totalResult] = await Promise.all([
      db.select().from(cbo)
        .where(whereCondition)
        .orderBy(desc(cbo.createdAt))
        .limit(limit)
        .offset(offset),
      db.select({ count: count() }).from(cbo).where(whereCondition)
    ]);

    return { data, total: totalResult[0].count };
  }

  async getCBO(id: string): Promise<CBO | undefined> {
    const [result] = await db.select().from(cbo).where(eq(cbo.id, id));
    return result || undefined;
  }

  async createCBO(cboData: InsertCBO): Promise<CBO> {
    const [result] = await db.insert(cbo).values(cboData).returning();
    return result;
  }

  async updateCBO(id: string, cboData: Partial<InsertCBO>): Promise<CBO | undefined> {
    const [result] = await db
      .update(cbo)
      .set({ ...cboData, updatedAt: new Date() })
      .where(eq(cbo.id, id))
      .returning();
    return result || undefined;
  }

  async deleteCBO(id: string): Promise<boolean> {
    const result = await db.delete(cbo).where(eq(cbo.id, id));
    return result.rowCount > 0;
  }

  // Prestador operations
  async getPrestadores(page = 1, limit = 10, search?: string): Promise<{ data: Prestador[]; total: number }> {
    const offset = (page - 1) * limit;
    const whereCondition = search 
      ? or(
          like(prestador.codigo, `%${search}%`),
          like(prestador.nomeRazaoSocial, `%${search}%`),
          like(prestador.cnpjCpf, `%${search}%`)
        )
      : undefined;

    const [data, totalResult] = await Promise.all([
      db.select().from(prestador)
        .where(whereCondition)
        .orderBy(desc(prestador.createdAt))
        .limit(limit)
        .offset(offset),
      db.select({ count: count() }).from(prestador).where(whereCondition)
    ]);

    return { data, total: totalResult[0].count };
  }

  async getPrestador(id: string): Promise<Prestador | undefined> {
    const [result] = await db.select().from(prestador).where(eq(prestador.id, id));
    return result || undefined;
  }

  async createPrestador(prestadorData: InsertPrestador): Promise<Prestador> {
    const [result] = await db.insert(prestador).values(prestadorData).returning();
    return result;
  }

  async updatePrestador(id: string, prestadorData: Partial<InsertPrestador>): Promise<Prestador | undefined> {
    const [result] = await db
      .update(prestador)
      .set({ ...prestadorData, updatedAt: new Date() })
      .where(eq(prestador.id, id))
      .returning();
    return result || undefined;
  }

  async deletePrestador(id: string): Promise<boolean> {
    const result = await db.delete(prestador).where(eq(prestador.id, id));
    return result.rowCount > 0;
  }

  // Procedimento operations
  async getProcedimentos(page = 1, limit = 10, search?: string): Promise<{ data: Procedimento[]; total: number }> {
    const offset = (page - 1) * limit;
    const whereCondition = search 
      ? or(
          like(procedimento.codigo, `%${search}%`),
          like(procedimento.descricao, `%${search}%`)
        )
      : undefined;

    const [data, totalResult] = await Promise.all([
      db.select().from(procedimento)
        .where(whereCondition)
        .orderBy(desc(procedimento.createdAt))
        .limit(limit)
        .offset(offset),
      db.select({ count: count() }).from(procedimento).where(whereCondition)
    ]);

    return { data, total: totalResult[0].count };
  }

  async getProcedimento(id: string): Promise<Procedimento | undefined> {
    const [result] = await db.select().from(procedimento).where(eq(procedimento.id, id));
    return result || undefined;
  }

  async createProcedimento(procedimentoData: InsertProcedimento): Promise<Procedimento> {
    const [result] = await db.insert(procedimento).values(procedimentoData).returning();
    return result;
  }

  async updateProcedimento(id: string, procedimentoData: Partial<InsertProcedimento>): Promise<Procedimento | undefined> {
    const [result] = await db
      .update(procedimento)
      .set({ ...procedimentoData, updatedAt: new Date() })
      .where(eq(procedimento.id, id))
      .returning();
    return result || undefined;
  }

  async deleteProcedimento(id: string): Promise<boolean> {
    const result = await db.delete(procedimento).where(eq(procedimento.id, id));
    return result.rowCount > 0;
  }

  // SRub operations
  async getSRubs(page = 1, limit = 10, search?: string): Promise<{ data: SRub[]; total: number }> {
    const offset = (page - 1) * limit;
    const whereCondition = search 
      ? or(
          like(sRub.codigo, `%${search}%`),
          like(sRub.descricao, `%${search}%`)
        )
      : undefined;

    const [data, totalResult] = await Promise.all([
      db.select().from(sRub)
        .where(whereCondition)
        .orderBy(desc(sRub.createdAt))
        .limit(limit)
        .offset(offset),
      db.select({ count: count() }).from(sRub).where(whereCondition)
    ]);

    return { data, total: totalResult[0].count };
  }

  async getSRub(id: string): Promise<SRub | undefined> {
    const [result] = await db.select().from(sRub).where(eq(sRub.id, id));
    return result || undefined;
  }

  async createSRub(srubData: InsertSRub): Promise<SRub> {
    const [result] = await db.insert(sRub).values(srubData).returning();
    return result;
  }

  async updateSRub(id: string, srubData: Partial<InsertSRub>): Promise<SRub | undefined> {
    const [result] = await db
      .update(sRub)
      .set({ ...srubData, updatedAt: new Date() })
      .where(eq(sRub.id, id))
      .returning();
    return result || undefined;
  }

  async deleteSRub(id: string): Promise<boolean> {
    const result = await db.delete(sRub).where(eq(sRub.id, id));
    return result.rowCount > 0;
  }

  // ConsultaProd operations
  async getConsultaProdData(filters?: any, page = 1, limit = 50): Promise<{ data: any[]; total: number }> {
    const offset = (page - 1) * limit;
    
    // Build dynamic where conditions based on filters
    let whereConditions: any[] = [];
    
    if (filters) {
      if (filters.dateFrom) {
        whereConditions.push(gte(consultaProd.prdDtcomp, new Date(filters.dateFrom)));
      }
      if (filters.dateTo) {
        whereConditions.push(lte(consultaProd.prdDtcomp, new Date(filters.dateTo)));
      }
      if (filters.prestador) {
        whereConditions.push(eq(consultaProd.prdPrest, filters.prestador));
      }
      if (filters.procedimento) {
        whereConditions.push(eq(consultaProd.prdProc, filters.procedimento));
      }
    }

    const whereCondition = whereConditions.length > 0 ? and(...whereConditions) : undefined;

    const [data, totalResult] = await Promise.all([
      db.select({
        id: consultaProd.id,
        prdDtcomp: consultaProd.prdDtcomp,
        prdDtreal: consultaProd.prdDtreal,
        prdQtd: consultaProd.prdQtd,
        prdVlP: consultaProd.prdVlP,
        prdCidpri: consultaProd.prdCidpri,
        cbo: {
          id: cbo.id,
          codigo: cbo.codigo,
          descricao: cbo.descricao,
        },
        prestador: {
          id: prestador.id,
          codigo: prestador.codigo,
          nomeRazaoSocial: prestador.nomeRazaoSocial,
        },
        procedimento: {
          id: procedimento.id,
          codigo: procedimento.codigo,
          descricao: procedimento.descricao,
        },
        sRub: {
          id: sRub.id,
          codigo: sRub.codigo,
          descricao: sRub.descricao,
        },
      })
        .from(consultaProd)
        .leftJoin(cbo, eq(consultaProd.prdCbo, cbo.id))
        .leftJoin(prestador, eq(consultaProd.prdPrest, prestador.id))
        .leftJoin(procedimento, eq(consultaProd.prdProc, procedimento.id))
        .leftJoin(sRub, eq(consultaProd.prdRub, sRub.id))
        .where(whereCondition)
        .orderBy(desc(consultaProd.prdDtcomp))
        .limit(limit)
        .offset(offset),
      db.select({ count: count() }).from(consultaProd).where(whereCondition)
    ]);

    return { data, total: totalResult[0].count };
  }

  async createConsultaProd(consultaProdData: InsertConsultaProd): Promise<ConsultaProd> {
    const [result] = await db.insert(consultaProd).values(consultaProdData).returning();
    return result;
  }

  // Dashboard statistics
  async getDashboardStats(): Promise<any> {
    const [totalProceduresResult] = await db.select({ count: count() }).from(consultaProd);
    const [totalValueResult] = await db.select({ 
      sum: sql<number>`sum(${consultaProd.prdVlP})` 
    }).from(consultaProd);
    const [activePrestadoresResult] = await db.select({ count: count() }).from(prestador).where(eq(prestador.status, true));

    return {
      totalProcedures: totalProceduresResult.count,
      totalValue: totalValueResult.sum || 0,
      activePrestadores: activePrestadoresResult.count,
      occupancyRate: 87.4, // This would be calculated based on business rules
    };
  }

  // Audit log
  async createAuditLog(auditLogData: InsertAuditLog): Promise<AuditLog> {
    const [result] = await db.insert(auditLog).values(auditLogData).returning();
    return result;
  }
}

export const storage = new DatabaseStorage();
