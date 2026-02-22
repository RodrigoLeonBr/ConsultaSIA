"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
var __param = (this && this.__param) || function (paramIndex, decorator) {
    return function (target, key) { decorator(target, key, paramIndex); }
};
var WorkerService_1;
Object.defineProperty(exports, "__esModule", { value: true });
exports.WorkerService = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const typeorm_2 = require("typeorm");
const report_job_entity_1 = require("../reports/entities/report-job.entity");
const report_result_header_entity_1 = require("../reports/entities/report-result-header.entity");
const report_result_rows_entity_1 = require("../reports/entities/report-result-rows.entity");
const s_pap_entity_1 = require("../sia/entities/s-pap.entity");
let WorkerService = WorkerService_1 = class WorkerService {
    reportJobRepository;
    headerRepository;
    rowsRepository;
    sPapRepository;
    logger = new common_1.Logger(WorkerService_1.name);
    constructor(reportJobRepository, headerRepository, rowsRepository, sPapRepository) {
        this.reportJobRepository = reportJobRepository;
        this.headerRepository = headerRepository;
        this.rowsRepository = rowsRepository;
        this.sPapRepository = sPapRepository;
    }
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
                }
                else {
                    await new Promise(resolve => setTimeout(resolve, 5000));
                }
            }
            catch (error) {
                this.logger.error(`Error in polling loop: ${error.message}`);
                await new Promise(resolve => setTimeout(resolve, 5000));
            }
        }
    }
    async processJob(job) {
        this.logger.log(`Processing job ${job.id} of type ${job.type}`);
        job.status = 'running';
        job.startedAt = new Date();
        await this.reportJobRepository.save(job);
        try {
            let processedRows = [];
            let columns = [];
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
            }
            else {
                throw new Error(`Report type '${job.type}' is not supported by the worker.`);
            }
            const header = this.headerRepository.create({
                job,
                reportType: job.type || 'unknown',
                rowCount: processedRows.length,
                sourceTablesVersionsJson: { columns, generatedAt: new Date().toISOString() }
            });
            await this.headerRepository.save(header);
            const chunkSize = 1000;
            for (let i = 0; i < processedRows.length; i += chunkSize) {
                const chunk = processedRows.slice(i, i + chunkSize);
                const rowEntities = chunk.map((data, index) => this.rowsRepository.create({ id: header.id.toString(), header, rowIndex: i + index + 1, rowJson: JSON.stringify(data) }));
                await this.rowsRepository.save(rowEntities);
            }
            job.status = 'done';
            job.completedAt = new Date();
            await this.reportJobRepository.save(job);
            this.logger.log(`Job ${job.id} completed successfully with ${processedRows.length} rows aggregated`);
        }
        catch (error) {
            this.logger.error(`Job ${job.id} failed: ${error.message}`);
            job.status = 'failed';
            job.errorMessage = error.message;
            job.completedAt = new Date();
            await this.reportJobRepository.save(job);
        }
    }
};
exports.WorkerService = WorkerService;
exports.WorkerService = WorkerService = WorkerService_1 = __decorate([
    (0, common_1.Injectable)(),
    __param(0, (0, typeorm_1.InjectRepository)(report_job_entity_1.ReportJob)),
    __param(1, (0, typeorm_1.InjectRepository)(report_result_header_entity_1.ReportResultHeader)),
    __param(2, (0, typeorm_1.InjectRepository)(report_result_rows_entity_1.ReportResultRow)),
    __param(3, (0, typeorm_1.InjectRepository)(s_pap_entity_1.SPap)),
    __metadata("design:paramtypes", [typeorm_2.Repository,
        typeorm_2.Repository,
        typeorm_2.Repository,
        typeorm_2.Repository])
], WorkerService);
//# sourceMappingURL=worker.service.js.map