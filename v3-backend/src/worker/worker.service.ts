import { Injectable, Logger, OnApplicationBootstrap } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { ReportJob } from '../reports/entities/report-job.entity';
import { ReportResultHeader } from '../reports/entities/report-result-header.entity';
import { ReportResultRow } from '../reports/entities/report-result-rows.entity';
import { SPap } from '../sia/entities/s-pap.entity';

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
        @InjectRepository(SPap)
        private readonly sPapRepository: Repository<SPap>,
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
                const job = await this.reportJobRepository.findOne({ where: { status: 'queued' }, order: { createdAt: 'ASC' } });
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
            let processedRows: any[] = [];
            let columns: string[] = [];

            if (job.type === 'sia-aggregated') {
                const { competence, providerId } = job.parameters || {};

                const qb = this.sPapRepository.createQueryBuilder('s_pap')
                    .select('s_pap.PAP_CBO', 'cbo')
                    .addSelect('SUM(s_pap.PAP_QT_A)', 'totalQuantity')
                    .addSelect('SUM(s_pap.PAP_VL_FED)', 'totalFederalValue')
                    .groupBy('s_pap.PAP_CBO');

                if (competence) {
                    qb.andWhere('s_pap.PAP_CMP = :competence', { competence });
                }
                if (providerId) {
                    qb.andWhere('s_pap.PAP_CNPJ = :providerId', { providerId });
                }

                processedRows = await qb.getRawMany();
                columns = ['cbo', 'totalQuantity', 'totalFederalValue'];
            } else {
                throw new Error(`Report type '${job.type}' is not supported by the worker.`);
            }

            // Save results
            const header = this.headerRepository.create({
                job,
                reportType: job.type || 'unknown',
                rowCount: processedRows.length,
                sourceTablesVersionsJson: { columns, generatedAt: new Date().toISOString() }
            });
            await this.headerRepository.save(header);

            // Chunk insert to avoid memory overflow on millions of rows returning (though aggregated is usually smaller)
            const chunkSize = 1000;
            for (let i = 0; i < processedRows.length; i += chunkSize) {
                const chunk = processedRows.slice(i, i + chunkSize);
                const rowEntities = chunk.map((data, index) =>
                    this.rowsRepository.create({ id: header.id.toString(), header, rowIndex: i + index + 1, rowJson: JSON.stringify(data) })
                );
                await this.rowsRepository.save(rowEntities);
            }

            job.status = 'done';
            job.completedAt = new Date();
            await this.reportJobRepository.save(job);
            this.logger.log(`Job ${job.id} completed successfully with ${processedRows.length} rows aggregated`);

        } catch (error) {
            this.logger.error(`Job ${job.id} failed: ${error.message}`);
            job.status = 'failed';
            job.errorMessage = error.message;
            job.completedAt = new Date();
            await this.reportJobRepository.save(job);
        }
    }
}
