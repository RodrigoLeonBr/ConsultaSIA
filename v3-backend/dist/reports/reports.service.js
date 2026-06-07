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
Object.defineProperty(exports, "__esModule", { value: true });
exports.ReportsService = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const typeorm_2 = require("typeorm");
const path_1 = require("path");
const report_job_entity_1 = require("./entities/report-job.entity");
const report_result_header_entity_1 = require("./entities/report-result-header.entity");
const report_result_rows_entity_1 = require("./entities/report-result-rows.entity");
let ReportsService = class ReportsService {
    reportJobRepository;
    headerRepository;
    rowsRepository;
    constructor(reportJobRepository, headerRepository, rowsRepository) {
        this.reportJobRepository = reportJobRepository;
        this.headerRepository = headerRepository;
        this.rowsRepository = rowsRepository;
    }
    async createJob(createJobDto) {
        const job = this.reportJobRepository.create({
            type: createJobDto.type,
            parameters: createJobDto.parameters,
            status: 'queued',
        });
        return this.reportJobRepository.save(job);
    }
    async getJobStatus(id) {
        const job = await this.reportJobRepository.findOne({ where: { id } });
        if (!job) {
            throw new common_1.NotFoundException(`Job with ID ${id} not found`);
        }
        return job;
    }
    async getResultPage(jobId, page = 1, limit = 200) {
        const boundedLimit = Math.min(Math.max(limit, 1), 500);
        const offset = (Math.max(page, 1) - 1) * boundedLimit;
        const [rows, count] = await this.rowsRepository.findAndCount({
            where: { header: { job: { id: jobId } } },
            relations: ['header', 'header.job'],
            order: { rowIndex: 'ASC' },
            skip: offset,
            take: boundedLimit,
        });
        return {
            data: rows.map(r => JSON.parse(r.rowJson)),
            meta: {
                page: Math.max(page, 1),
                limit: boundedLimit,
                totalRowsFetched: count,
            }
        };
    }
    async getJobFilePath(jobId) {
        const header = await this.headerRepository.findOne({
            where: { job: { id: jobId } },
            relations: ['job'],
        });
        if (!header) {
            throw new common_1.NotFoundException(`Resultado do job #${jobId} não encontrado.`);
        }
        const filePath = header.sourceTablesVersionsJson?.filePath;
        const format = header.sourceTablesVersionsJson?.format ?? 'bin';
        if (!filePath) {
            throw new common_1.NotFoundException(`O job #${jobId} não possui arquivo para download.`);
        }
        const fullPath = (0, path_1.join)(process.cwd(), 'uploads', filePath);
        const filename = `relatorio-${jobId}.${format}`;
        return { fullPath, format, filename };
    }
};
exports.ReportsService = ReportsService;
exports.ReportsService = ReportsService = __decorate([
    (0, common_1.Injectable)(),
    __param(0, (0, typeorm_1.InjectRepository)(report_job_entity_1.ReportJob)),
    __param(1, (0, typeorm_1.InjectRepository)(report_result_header_entity_1.ReportResultHeader)),
    __param(2, (0, typeorm_1.InjectRepository)(report_result_rows_entity_1.ReportResultRow)),
    __metadata("design:paramtypes", [typeorm_2.Repository,
        typeorm_2.Repository,
        typeorm_2.Repository])
], ReportsService);
//# sourceMappingURL=reports.service.js.map