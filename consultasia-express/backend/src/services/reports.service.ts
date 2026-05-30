import { db } from '../db';
import { reportJob } from '../schemas/report-job';
import { reportResultHeader } from '../schemas/report-result-header';
import { reportResultRows } from '../schemas/report-result-rows';
import { eq, asc } from 'drizzle-orm';
import { AppError } from '../middleware/error-handler';

export class ReportsService {

  async createJob(type: string, parameters: object) {
    const now = new Date();
    const result = await db.insert(reportJob).values({
      status: 'queued',
      type,
      parameters: parameters as any,
      createdAt: now,
    });
    const insertId = (result[0] as any).insertId as number;
    const [job] = await db.select().from(reportJob).where(eq(reportJob.id, insertId));
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

    if (header.ttl < new Date()) {
      throw new AppError(410, `Resultado expirado (TTL: ${header.ttl.toISOString()}).`);
    }

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
