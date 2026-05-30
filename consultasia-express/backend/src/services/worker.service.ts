import { db, pool } from '../db';
import { reportJob } from '../schemas/report-job';
import { reportResultHeader } from '../schemas/report-result-header';
import { reportResultRows } from '../schemas/report-result-rows';
import { eq, asc } from 'drizzle-orm';
import { siaService } from './sia.service';
import fs from 'fs';
import path from 'path';

const MAX_EXPORT_ROWS = 100_000;
const MAX_PDF_ROWS   = 5_000;
const CHUNK_SIZE     = 1_000;
const POLL_INTERVAL  = 5_000;
const EXPORTS_DIR    = path.resolve(process.cwd(), 'exports');

fs.mkdirSync(EXPORTS_DIR, { recursive: true });

export class WorkerService {
  private running = false;

  start(): void {
    this.running = true;
    void this.poll();
  }

  stop(): void {
    this.running = false;
  }

  private async poll(): Promise<void> {
    while (this.running) {
      try {
        await this.processNextJob();
      } catch (err) {
        console.error('[Worker] Unhandled poll error:', (err as Error).message);
      }
      await new Promise<void>(r => setTimeout(r, POLL_INTERVAL));
    }
  }

  private async processNextJob(): Promise<void> {
    const [job] = await db.select().from(reportJob)
      .where(eq(reportJob.status, 'queued'))
      .orderBy(asc(reportJob.createdAt))
      .limit(1);

    if (!job) return;

    console.log(`[Worker] Processing job ${job.id} type=${job.type}`);

    // Atomic lock: mark running
    await db.update(reportJob)
      .set({ status: 'running', startedAt: new Date() })
      .where(eq(reportJob.id, job.id));

    try {
      const rawParams = job.parameters;
      const params: Record<string, any> = typeof rawParams === 'string'
        ? JSON.parse(rawParams)
        : (rawParams as Record<string, any>);

      if (job.type === 'export') {
        await this.handleExport(job.id, params.resultId as number, params.format as 'xlsx' | 'csv' | 'pdf');
      } else {
        const rows = await this.executeQuery(job.type, params);
        await this.persistResults(job.id, job.type, params.competence as string, rows);
      }

      await db.update(reportJob)
        .set({ status: 'done', completedAt: new Date() })
        .where(eq(reportJob.id, job.id));

      console.log(`[Worker] Job ${job.id} done`);
    } catch (err: unknown) {
      const msg = err instanceof Error ? err.message : String(err);
      console.error(`[Worker] Job ${job.id} failed:`, msg);
      await db.update(reportJob)
        .set({ status: 'failed', completedAt: new Date(), errorMessage: msg.slice(0, 2000) })
        .where(eq(reportJob.id, job.id));
    }
  }

  private async executeQuery(type: string, params: Record<string, any>): Promise<Record<string, unknown>[]> {
    const competence = params.competence as string;

    if (type === 'sia-dynamic-production') {
      const result = await siaService.getDynamicProduction({
        competence,
        select: (params.select as string[]) ?? [],
        filters: (params.filters as any[]) ?? [],
        page: 1,
        pageSize: MAX_EXPORT_ROWS,
      });
      return result.rows as Record<string, unknown>[];
    }

    if (type === 'sia-aggregated') {
      const [rows] = await pool.execute<any[]>(
        `SELECT sp.prd_cbo, cb.ds_cbo AS cbo_display,
                SUM(CAST(sp.PRD_QT_P AS UNSIGNED)) AS PRD_QT_P,
                SUM(CAST(sp.PRD_QT_A AS UNSIGNED)) AS PRD_QT_A,
                SUM(CAST(sp.PRD_VL_P AS DECIMAL(15,2))) AS PRD_VL_P,
                SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2))) AS PRD_VL_A
         FROM s_prd sp
         LEFT JOIN cbo cb ON sp.prd_cbo = cb.cbo
         WHERE sp.prd_cmp = ?
         GROUP BY sp.prd_cbo, cb.ds_cbo
         ORDER BY PRD_VL_A DESC`,
        [competence]
      );
      return rows;
    }

    if (type === 'sia-faturamento-prestador') {
      const [rows] = await pool.execute<any[]>(
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
         WHERE sp.prd_cmp = ?
         GROUP BY sp.prd_uid, pr.re_cnome, sp.prd_rub, sp.grupo, sp.subgrupo, sp.forma, sp.prd_pa, pc.procedimento, pc.PA_TOTAL
         ORDER BY pr.re_cnome, sp.prd_rub, sp.grupo`,
        [competence]
      );
      return rows;
    }

    throw new Error(`Job type desconhecido: ${type}`);
  }

  private async persistResults(
    jobId: number,
    reportType: string,
    competence: string,
    rows: Record<string, unknown>[]
  ): Promise<void> {
    const now = new Date();
    const ttl = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000); // 7 days

    const columns = rows.length > 0
      ? Object.keys(rows[0]).map(k => ({ fieldId: k, label: k }))
      : [];

    const [headerResult] = await db.insert(reportResultHeader).values({
      jobId,
      reportType,
      competence,
      totalRowsFetched: rows.length,
      columnsJson: columns as any,
      sourceTablesVersionsJson: {} as any,
      ttl,
      createdAt: now,
    });

    const headerId = (headerResult as any).insertId as number;

    // Chunk insert rows
    for (let i = 0; i < rows.length; i += CHUNK_SIZE) {
      const chunk = rows.slice(i, i + CHUNK_SIZE);
      const values = chunk.map((row, j) => ({
        headerId,
        rowIndex: i + j,
        rowJson: JSON.stringify(row),
      }));
      await db.insert(reportResultRows).values(values);
    }
  }

  private async handleExport(jobId: number, resultId: number, format: 'xlsx' | 'csv' | 'pdf'): Promise<void> {
    const [header] = await db.select().from(reportResultHeader).where(eq(reportResultHeader.id, resultId));
    if (!header) throw new Error(`Result header ${resultId} não encontrado.`);

    const limit = format === 'pdf' ? MAX_PDF_ROWS : MAX_EXPORT_ROWS;
    const rows = await db.select().from(reportResultRows)
      .where(eq(reportResultRows.headerId, resultId))
      .orderBy(asc(reportResultRows.rowIndex))
      .limit(limit);

    const data = rows.map(r => JSON.parse(r.rowJson) as Record<string, unknown>);
    const columns = (header.columnsJson as Array<{ fieldId: string; label: string }>) ?? [];
    const fileName = `${jobId}.${format}`;
    const filePath = path.join(EXPORTS_DIR, fileName);

    if (format === 'xlsx') await this.generateXlsx(filePath, columns, data);
    else if (format === 'csv') this.generateCsv(filePath, columns, data);
    else await this.generatePdf(filePath, columns, data);

    // Update result header with file path
    await db.update(reportResultHeader)
      .set({ sourceTablesVersionsJson: { filePath } as any })
      .where(eq(reportResultHeader.id, resultId));

    // Create export-specific result header
    const now = new Date();
    const ttl = new Date(now.getTime() + 2 * 24 * 60 * 60 * 1000); // 2 days
    await db.insert(reportResultHeader).values({
      jobId,
      reportType: 'export',
      competence: '',
      totalRowsFetched: data.length,
      columnsJson: columns as any,
      sourceTablesVersionsJson: { filePath } as any,
      ttl,
      createdAt: now,
    });
  }

  private async generateXlsx(
    filePath: string,
    columns: Array<{ fieldId: string; label: string }>,
    data: Record<string, unknown>[]
  ): Promise<void> {
    const ExcelJS = (await import('exceljs')).default;
    const wb = new ExcelJS.Workbook();
    const ws = wb.addWorksheet('Relatório');

    ws.columns = columns.map(c => ({ header: c.label, key: c.fieldId, width: 18 }));

    const headerRow = ws.getRow(1);
    headerRow.font = { bold: true };
    headerRow.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD9EAF7' } };

    ws.addRows(data.map(row =>
      columns.reduce<Record<string, unknown>>((acc, c) => { acc[c.fieldId] = row[c.fieldId]; return acc; }, {})
    ));

    await wb.xlsx.writeFile(filePath);
  }

  private generateCsv(
    filePath: string,
    columns: Array<{ fieldId: string; label: string }>,
    data: Record<string, unknown>[]
  ): void {
    const bom = '﻿';
    const headerLine = columns.map(c => `"${c.label}"`).join(',');
    const dataLines = data.map(row =>
      columns.map(c => `"${String(row[c.fieldId] ?? '').replace(/"/g, '""')}"`).join(',')
    );
    fs.writeFileSync(filePath, bom + [headerLine, ...dataLines].join('\r\n'), 'utf-8');
  }

  private async generatePdf(
    filePath: string,
    columns: Array<{ fieldId: string; label: string }>,
    data: Record<string, unknown>[]
  ): Promise<void> {
    const PDFDocument = (await import('pdfkit')).default;
    const doc = new PDFDocument({ layout: 'landscape', size: 'A4', margin: 30 });
    const stream = fs.createWriteStream(filePath);
    doc.pipe(stream);

    doc.fontSize(12).text('Relatório ConsultAsia', { align: 'center' });
    doc.moveDown();

    const colWidth = (doc.page.width - 60) / Math.max(columns.length, 1);
    let yPos = doc.y;

    // Header row
    columns.forEach((c, i) => {
      doc.rect(30 + i * colWidth, yPos, colWidth, 20).stroke();
      doc.fontSize(8).text(c.label, 32 + i * colWidth, yPos + 4, { width: colWidth - 4, lineBreak: false });
    });
    yPos += 20;

    // Data rows
    data.forEach(row => {
      if (yPos + 16 > doc.page.height - 30) {
        doc.addPage({ layout: 'landscape', size: 'A4' });
        yPos = 30;
      }
      columns.forEach((c, i) => {
        doc.rect(30 + i * colWidth, yPos, colWidth, 16).stroke();
        doc.fontSize(7).text(String(row[c.fieldId] ?? ''), 32 + i * colWidth, yPos + 3, { width: colWidth - 4, lineBreak: false });
      });
      yPos += 16;
    });

    doc.end();
    return new Promise<void>((resolve, reject) => {
      stream.on('finish', resolve);
      stream.on('error', reject);
    });
  }
}

export const workerService = new WorkerService();
