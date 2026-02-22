import { ReportsService } from './reports.service';
import { CreateJobDto } from './dto/create-job.dto';
export declare class ReportsController {
    private readonly reportsService;
    constructor(reportsService: ReportsService);
    createJob(createJobDto: CreateJobDto): Promise<import("./entities/report-job.entity").ReportJob>;
    getJobStatus(id: number): Promise<import("./entities/report-job.entity").ReportJob>;
    getJobResults(jobId: number, page: number, limit: number): Promise<{
        data: any[];
        meta: {
            page: number;
            limit: number;
            totalRowsFetched: number;
        };
    }>;
}
