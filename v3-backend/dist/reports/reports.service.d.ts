import { Repository } from 'typeorm';
import { ReportJob } from './entities/report-job.entity';
import { ReportResultHeader } from './entities/report-result-header.entity';
import { ReportResultRow } from './entities/report-result-rows.entity';
import { CreateJobDto } from './dto/create-job.dto';
export declare class ReportsService {
    private readonly reportJobRepository;
    private readonly headerRepository;
    private readonly rowsRepository;
    constructor(reportJobRepository: Repository<ReportJob>, headerRepository: Repository<ReportResultHeader>, rowsRepository: Repository<ReportResultRow>);
    createJob(createJobDto: CreateJobDto): Promise<ReportJob>;
    getJobStatus(id: number): Promise<ReportJob>;
    getResultPage(jobId: number, page?: number, limit?: number): Promise<{
        data: any[];
        meta: {
            page: number;
            limit: number;
            totalRowsFetched: number;
        };
    }>;
    getJobFilePath(jobId: number): Promise<{
        fullPath: string;
        format: string;
        filename: string;
    }>;
}
