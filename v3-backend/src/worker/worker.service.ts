import { Injectable, Logger, OnApplicationBootstrap } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { join } from 'path';
import * as fs from 'fs';
import * as ExcelJS from 'exceljs';
import { v4 as uuidv4 } from 'uuid';
import { ReportJob } from '../reports/entities/report-job.entity';
import { ReportResultHeader } from '../reports/entities/report-result-header.entity';
import { ReportResultRow } from '../reports/entities/report-result-rows.entity';
import { SPrd } from '../sia/entities/s-prd.entity';
import {
    SIA_PRODUCAO_FIELDS,
    RequiredJoin,
    Operator,
    applyOperator,
} from '../sia/field-catalog';

// ─── Constantes de exportação ────────────────────────────────────────────────

const MAX_EXPORT_ROWS = 100_000;
const MAX_PDF_ROWS = 5_000;
const UPLOADS_DIR = join(process.cwd(), 'uploads');

// ─── Helper: key → label legível ─────────────────────────────────────────────

function keyToLabel(key: string): string {
    if (SIA_PRODUCAO_FIELDS[key]) return SIA_PRODUCAO_FIELDS[key].label;
    if (key.endsWith('_display')) {
        const base = key.slice(0, -8); // remove '_display'
        if (SIA_PRODUCAO_FIELDS[base]) return `${SIA_PRODUCAO_FIELDS[base].label} (Nome)`;
    }
    return key.replace(/([A-Z])/g, ' $1').replace(/^./, s => s.toUpperCase()).trim();
}

// ─── Geradores de arquivo ─────────────────────────────────────────────────────

async function generateXlsx(columnKeys: string[], rows: any[], filePath: string): Promise<void> {
    const wb = new ExcelJS.Workbook();
    wb.creator = 'ConsultaProd v3';
    const ws = wb.addWorksheet('Relatório');

    // Cabeçalho
    const headerRow = ws.addRow(columnKeys.map(k => keyToLabel(k)));
    headerRow.font = { bold: true };
    headerRow.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFD9EAF7' } };

    // Dados
    for (const row of rows) {
        ws.addRow(columnKeys.map(k => {
            const v = row[k];
            return v == null ? '' : v;
        }));
    }

    // Largura automática (máximo 60 chars)
    ws.columns.forEach(col => {
        if (!col || !col.eachCell) return;
        let maxLen = 10;
        col.eachCell({ includeEmpty: true }, cell => {
            const len = cell.value != null ? String(cell.value).length : 0;
            if (len > maxLen) maxLen = len;
        });
        col.width = Math.min(maxLen + 2, 60);
    });

    await wb.xlsx.writeFile(filePath);
}

function generateCsv(columnKeys: string[], rows: any[], filePath: string): void {
    const escape = (v: unknown) => `"${String(v ?? '').replace(/"/g, '""')}"`;
    const lines: string[] = [
        columnKeys.map(k => escape(keyToLabel(k))).join(','),
        ...rows.map(row => columnKeys.map(k => escape(row[k])).join(',')),
    ];
    // BOM UTF-8 para compatibilidade com Excel
    fs.writeFileSync(filePath, '\uFEFF' + lines.join('\r\n'), 'utf8');
}

async function generatePdf(
    columnKeys: string[],
    rows: any[],
    filePath: string,
    totalRows: number,
): Promise<void> {
    // eslint-disable-next-line @typescript-eslint/no-var-requires
    const PDFDocument = require('pdfkit');

    const limited = rows.length > MAX_PDF_ROWS;
    const dataRows = rows.slice(0, MAX_PDF_ROWS);

    const doc = new PDFDocument({ margin: 30, size: 'A4', layout: 'landscape' });
    const stream = fs.createWriteStream(filePath);
    doc.pipe(stream);

    // Título
    doc.fontSize(13).font('Helvetica-Bold').text('Relatório SIA — ConsultaProd v3', { align: 'center' });
    doc.fontSize(8).font('Helvetica').text(`Gerado em: ${new Date().toLocaleString('pt-BR')}`, { align: 'center' });
    if (limited) {
        doc.text(
            `* PDF limitado a ${MAX_PDF_ROWS.toLocaleString()} linhas. Total de registros: ${totalRows.toLocaleString()}.`,
            { align: 'center' },
        );
    }
    doc.moveDown(0.5);

    // Dimensões da tabela
    const pageW = doc.page.width - 60;
    const colCount = columnKeys.length;
    const colW = Math.max(Math.min(pageW / colCount, 100), 40);
    const rowH = 14;
    const fontSize = 7;
    const startX = 30;
    let curY = doc.y;

    const drawRow = (values: string[], isHeader: boolean) => {
        if (curY + rowH > doc.page.height - 40) {
            doc.addPage();
            curY = 30;
        }
        values.forEach((val, i) => {
            const x = startX + i * colW;
            if (isHeader) {
                doc.rect(x, curY, colW, rowH).fill('#D9EAF7').stroke();
                doc.fillColor('black').font('Helvetica-Bold').fontSize(fontSize);
            } else {
                doc.rect(x, curY, colW, rowH).stroke();
                doc.fillColor('#222').font('Helvetica').fontSize(fontSize);
            }
            doc.text(val, x + 2, curY + 3, { width: colW - 4, lineBreak: false, ellipsis: true });
        });
        curY += rowH;
    };

    drawRow(columnKeys.map(k => keyToLabel(k)), true);
    for (const row of dataRows) {
        drawRow(columnKeys.map(k => String(row[k] ?? '')), false);
    }

    doc.end();

    await new Promise<void>((resolve, reject) => {
        stream.on('finish', resolve);
        stream.on('error', reject);
    });
}

// ─── Worker Service ───────────────────────────────────────────────────────────

@Injectable()
export class WorkerService implements OnApplicationBootstrap {
    private readonly logger = new Logger(WorkerService.name);

    constructor(
        @InjectRepository(ReportJob)
        private readonly reportJobRepository: Repository<ReportJob>,
        @InjectRepository(ReportResultHeader)
        private readonly headerRepository: Repository<ReportResultHeader>,
        @InjectRepository(ReportResultRow)
        private readonly rowsRepository: Repository<ReportResultRow>,
        @InjectRepository(SPrd)
        private readonly sPrdRepository: Repository<SPrd>,
    ) { }

    onApplicationBootstrap() {
        if (process.env.RUN_WORKER === 'true') {
            this.logger.log('Starting worker polling...');
            this.pollJobs();
        }
    }

    async pollJobs() {
        while (true) {
            try {
                const job = await this.reportJobRepository.findOne({
                    where: { status: 'queued' },
                    order: { createdAt: 'ASC' },
                });
                if (job) {
                    await this.processJob(job);
                } else {
                    await new Promise(resolve => setTimeout(resolve, 5000));
                }
            } catch (error) {
                this.logger.error(`Error in polling loop: ${error.message}`);
                await new Promise(resolve => setTimeout(resolve, 5000));
            }
        }
    }

    async processJob(job: ReportJob) {
        this.logger.log(`Processing job ${job.id} of type ${job.type}`);
        job.status = 'running';
        job.startedAt = new Date();
        await this.reportJobRepository.save(job);

        try {
            // ── Rota especial: jobs de exportação ────────────────────────────
            if (job.type === 'export') {
                await this.processExportJob(job);
                return;
            }

            // ── Jobs de query (produzem rows para persistir) ──────────────────
            let processedRows: any[] = [];
            let columns: string[] = [];

            if (job.type === 'sia-aggregated') {
                const { competence, providerId } = job.parameters || {};

                const qb = this.sPrdRepository.createQueryBuilder('s_prd')
                    .select('s_prd.prd_cbo', 'cbo')
                    .addSelect('SUM(s_prd.PRD_QT_A)', 'totalQuantity')
                    .addSelect('SUM(s_prd.PRD_VL_A)', 'totalValue')
                    .groupBy('s_prd.prd_cbo');

                if (competence) qb.andWhere('s_prd.prd_cmp = :competence', { competence });
                if (providerId) qb.andWhere('s_prd.prd_uid = :providerId', { providerId });

                processedRows = await qb.getRawMany();
                columns = ['cbo', 'totalQuantity', 'totalValue'];

            } else if (job.type === 'sia-faturamento-prestador') {
                const { competence, providerId } = job.parameters || {};

                if (!competence) {
                    throw new Error('Competência é obrigatória para o job sia-faturamento-prestador.');
                }

                const qb = this.sPrdRepository.createQueryBuilder('sp')
                    .select('p.re_cunid', 'prestadorCnes')
                    .addSelect('p.re_cnome', 'prestadorNome')
                    .addSelect('sp.prd_rub', 'financingType')
                    .addSelect('sp.grupo', 'grupo')
                    .addSelect('sp.subgrupo', 'subgrupo')
                    .addSelect('sp.forma', 'forma')
                    .addSelect('sp.prd_pa', 'procedureCode')
                    .addSelect('proc.procedimento', 'procedureName')
                    .addSelect('CAST(proc.PA_TOTAL AS DECIMAL(15,2))', 'unitValue')
                    .addSelect('SUM(CAST(sp.PRD_QT_A AS UNSIGNED))', 'qtyApproved')
                    .addSelect('SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2)))', 'valueApproved')
                    .addSelect('SUM(CAST(sp.PRD_QT_P AS UNSIGNED))', 'qtyPresented')
                    .addSelect(
                        'SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * CAST(proc.PA_TOTAL AS DECIMAL(15,2)))',
                        'valuePresented',
                    )
                    .leftJoin('prestador', 'p', 'p.re_cunid = sp.prd_uid')
                    .leftJoin('procedimento', 'proc', 'proc.codigo = sp.prd_pa')
                    .where('sp.prd_cmp = :competence', { competence })
                    .andWhere('p.ativo = 1')
                    .groupBy('p.re_cunid').addGroupBy('p.re_cnome')
                    .addGroupBy('sp.prd_rub').addGroupBy('sp.grupo')
                    .addGroupBy('sp.subgrupo').addGroupBy('sp.forma')
                    .addGroupBy('sp.prd_pa').addGroupBy('proc.procedimento').addGroupBy('proc.PA_TOTAL')
                    .orderBy('p.re_cnome').addOrderBy('sp.prd_rub')
                    .addOrderBy('sp.grupo').addOrderBy('sp.subgrupo').addOrderBy('sp.forma').addOrderBy('sp.prd_pa');

                if (providerId) qb.andWhere('sp.prd_uid = :providerId', { providerId });

                processedRows = await qb.getRawMany();
                columns = [
                    'prestadorCnes', 'prestadorNome', 'financingType',
                    'grupo', 'subgrupo', 'forma',
                    'procedureCode', 'procedureName', 'unitValue',
                    'qtyApproved', 'valueApproved', 'qtyPresented', 'valuePresented',
                ];

            } else if (job.type === 'sia-dynamic-production') {
                const { competence, select, filters = [] } = job.parameters || {};

                if (!competence) throw new Error('competence é obrigatório para sia-dynamic-production.');
                if (!Array.isArray(select) || select.length === 0) throw new Error('select[] é obrigatório para sia-dynamic-production.');

                const selectFields = select as string[];
                const filterItems = filters as Array<{ fieldId: string; operator: string; value: unknown }>;
                const hasAggregates = selectFields.some(id => SIA_PRODUCAO_FIELDS[id]?.isAggregate);

                const requiredJoins = new Set<RequiredJoin>();
                for (const fieldId of [...selectFields, ...filterItems.map(f => f.fieldId)]) {
                    if (fieldId === 'procedimento_descricao') continue;
                    const field = SIA_PRODUCAO_FIELDS[fieldId];
                    if (field?.requiresJoin) requiredJoins.add(field.requiresJoin);
                }

                const selectExprs: Array<{ expr: string; alias: string }> = [];
                const groupByExprs: string[] = [];
                const colsMeta: string[] = [];

                for (const fieldId of selectFields) {
                    const field = SIA_PRODUCAO_FIELDS[fieldId];
                    if (!field || field.filterOnly) continue;

                    if (fieldId === 'cismetro_descricao') {
                        selectExprs.push({ expr: 'sp.prd_pa', alias: 'cismetro_codigo' });
                        selectExprs.push({ expr: 'cs.descricao', alias: 'cismetro_descricao' });
                        if (hasAggregates) groupByExprs.push('sp.prd_pa', 'cs.descricao');
                        colsMeta.push('cismetro_codigo', 'cismetro_descricao');
                        continue;
                    }
                    if (field.isAggregate) {
                        selectExprs.push({ expr: field.sqlExpr, alias: fieldId });
                        colsMeta.push(fieldId);
                        continue;
                    }
                    selectExprs.push({ expr: field.sqlExpr, alias: fieldId });
                    if (hasAggregates) groupByExprs.push(field.sqlExpr);
                    if (field.lookup && requiredJoins.has(field.requiresJoin!)) {
                        const displayAlias = `${fieldId}_display`;
                        selectExprs.push({ expr: `${field.lookup.joinAlias}.${field.lookup.display}`, alias: displayAlias });
                        if (hasAggregates) groupByExprs.push(`${field.lookup.joinAlias}.${field.lookup.display}`);
                        colsMeta.push(fieldId, displayAlias);
                    } else {
                        colsMeta.push(fieldId);
                    }
                }

                if (selectExprs.length === 0) throw new Error('Nenhuma coluna válida para exportação.');

                const qb = this.sPrdRepository.createQueryBuilder('sp')
                    .where('sp.prd_cmp = :competence', { competence });

                if (requiredJoins.has('prestador')) qb.leftJoin('prestador', 'pr', 'sp.prd_uid = pr.re_cunid');
                if (requiredJoins.has('cbo'))       qb.leftJoin('cbo', 'cb', 'sp.prd_cbo = cb.cbo');
                if (requiredJoins.has('procedimento')) qb.leftJoin('procedimento', 'pc', 'sp.prd_pa = pc.codigo');
                if (requiredJoins.has('s_rub'))     qb.leftJoin('s_rub', 'sr', 'sp.prd_rub = sr.RUB_ID');
                if (requiredJoins.has('cismetro'))  qb.leftJoin('cismetro', 'cs', 'sp.prd_pa = cs.codigo');

                for (let i = 0; i < filterItems.length; i++) {
                    const { fieldId, operator, value } = filterItems[i];
                    const paramKey = `wF${i}`;
                    if (fieldId === 'procedimento_descricao') {
                        const val = String(value);
                        const pattern = operator === 'like' ? `%${val}%` : operator === 'starts_with' ? `${val}%` : operator === 'ends_with' ? `%${val}` : val;
                        qb.andWhere(`sp.prd_pa IN (SELECT codigo FROM procedimento WHERE procedimento LIKE :${paramKey})`, { [paramKey]: pattern });
                        continue;
                    }
                    const field = SIA_PRODUCAO_FIELDS[fieldId];
                    if (field) applyOperator(qb, field.filterExpr ?? field.sqlExpr, operator as Operator, value, paramKey);
                }

                const [firstSel, ...restSel] = selectExprs;
                qb.select(firstSel.expr, firstSel.alias);
                for (const s of restSel) qb.addSelect(s.expr, s.alias);

                if (hasAggregates && groupByExprs.length > 0) {
                    qb.groupBy(groupByExprs[0]);
                    for (const g of groupByExprs.slice(1)) qb.addGroupBy(g);
                }

                processedRows = await qb.getRawMany();
                columns = colsMeta;

            } else {
                throw new Error(`Report type '${job.type}' is not supported by the worker.`);
            }

            // ── Persistir resultados ──────────────────────────────────────────
            const { competence: jobCompetence } = job.parameters || {};
            const header = this.headerRepository.create({
                job,
                reportType: job.type,
                rowCount: processedRows.length,
                competence: jobCompetence ?? null,
                filtersHash: null,
                sourceTablesVersionsJson: { columns, generatedAt: new Date().toISOString() },
                ttlExpiresAt: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000),
            });
            await this.headerRepository.save(header);

            const chunkSize = 1000;
            for (let i = 0; i < processedRows.length; i += chunkSize) {
                const chunk = processedRows.slice(i, i + chunkSize);
                const rowEntities = chunk.map((data, index) =>
                    this.rowsRepository.create({
                        id: header.id.toString(),
                        header,
                        rowIndex: i + index + 1,
                        rowJson: JSON.stringify(data),
                    }),
                );
                await this.rowsRepository.save(rowEntities);
            }

            job.status = 'done';
            job.completedAt = new Date();
            await this.reportJobRepository.save(job);
            this.logger.log(`Job ${job.id} completed — ${processedRows.length} rows`);

        } catch (error) {
            this.logger.error(`Job ${job.id} failed: ${error.message}`);
            job.status = 'failed';
            job.errorMessage = error.message;
            job.completedAt = new Date();
            await this.reportJobRepository.save(job);
        }
    }

    // ─── Export job (gera arquivo, sem persistir rows) ────────────────────────

    private async processExportJob(job: ReportJob) {
        try {
            const { resultId, format } = job.parameters || {};

            if (!resultId) throw new Error('resultId é obrigatório para jobs de exportação.');
            if (!['xlsx', 'csv', 'pdf'].includes(format)) throw new Error('format deve ser xlsx, csv ou pdf.');

            // Localizar o header do job de origem
            const sourceHeader = await this.headerRepository.findOne({
                where: { job: { id: Number(resultId) } },
                relations: ['job'],
            });
            if (!sourceHeader) throw new Error(`Resultado #${resultId} não encontrado.`);

            const totalRows = sourceHeader.rowCount;
            if (totalRows > MAX_EXPORT_ROWS) {
                throw new Error(
                    `O resultado contém ${totalRows.toLocaleString()} linhas. Limite de exportação: ${MAX_EXPORT_ROWS.toLocaleString()}.`,
                );
            }

            const columnKeys: string[] = sourceHeader.sourceTablesVersionsJson?.columns ?? [];
            if (columnKeys.length === 0) throw new Error('Metadados de colunas não encontrados no resultado.');

            // Carregar todas as rows
            const rowEntities = await this.rowsRepository.find({
                where: { header: { id: sourceHeader.id } },
                order: { rowIndex: 'ASC' },
            });
            const rows = rowEntities.map(r => JSON.parse(r.rowJson));

            // Gerar arquivo
            fs.mkdirSync(join(UPLOADS_DIR, 'exports'), { recursive: true });
            const uuid = uuidv4();
            const relPath = `exports/${uuid}.${format}`;
            const fullPath = join(UPLOADS_DIR, relPath);

            if (format === 'xlsx') {
                await generateXlsx(columnKeys, rows, fullPath);
            } else if (format === 'csv') {
                generateCsv(columnKeys, rows, fullPath);
            } else {
                await generatePdf(columnKeys, rows, fullPath, totalRows);
            }

            // Criar header para este job de exportação (sem rows, filePath no JSON)
            const exportHeader = this.headerRepository.create({
                job,
                reportType: 'export',
                rowCount: rows.length,
                competence: sourceHeader.competence,
                filtersHash: null,
                sourceTablesVersionsJson: {
                    columns: columnKeys,
                    filePath: relPath,
                    format,
                    sourceResultId: resultId,
                    generatedAt: new Date().toISOString(),
                },
                ttlExpiresAt: new Date(Date.now() + 2 * 24 * 60 * 60 * 1000), // 2 dias
            });
            await this.headerRepository.save(exportHeader);

            job.status = 'done';
            job.completedAt = new Date();
            await this.reportJobRepository.save(job);
            this.logger.log(`Export job ${job.id} completed — format: ${format}, file: ${relPath}`);

        } catch (error) {
            this.logger.error(`Export job ${job.id} failed: ${error.message}`);
            job.status = 'failed';
            job.errorMessage = error.message;
            job.completedAt = new Date();
            await this.reportJobRepository.save(job);
        }
    }
}
