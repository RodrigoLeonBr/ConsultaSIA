import { Injectable, NotFoundException } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { ReportJob } from './entities/report-job.entity';
import { ReportResultHeader } from './entities/report-result-header.entity';
import { ReportResultRow } from './entities/report-result-rows.entity';
import { CreateJobDto } from './dto/create-job.dto';

@Injectable()
export class ReportsService {
    constructor(
        @InjectRepository(ReportJob)
        private readonly reportJobRepository: Repository<ReportJob>,
        @InjectRepository(ReportResultHeader)
        private readonly headerRepository: Repository<ReportResultHeader>,
        @InjectRepository(ReportResultRow)
        private readonly rowsRepository: Repository<ReportResultRow>,
    ) { }

    async createJob(createJobDto: CreateJobDto): Promise<ReportJob> {
        const job = this.reportJobRepository.create({
            type: createJobDto.type,
            parameters: createJobDto.parameters,
            status: 'queued',
        });
        return this.reportJobRepository.save(job);
    }

    async getJobStatus(id: number): Promise<ReportJob> {
        const job = await this.reportJobRepository.findOne({ where: { id } });
        if (!job) {
            throw new NotFoundException(`Job with ID ${id} not found`);
        }
        return job;
    }

    async getResultPage(jobId: number, page: number = 1, limit: number = 200) {
        // Limits of pagination
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
}
