import { OnApplicationBootstrap } from '@nestjs/common';
import { Repository } from 'typeorm';
import { ReportJob } from '../reports/entities/report-job.entity';
import { ReportResultHeader } from '../reports/entities/report-result-header.entity';
import { ReportResultRow } from '../reports/entities/report-result-rows.entity';
import { SPrd } from '../sia/entities/s-prd.entity';
export declare class WorkerService implements OnApplicationBootstrap {
    private readonly reportJobRepository;
    private readonly headerRepository;
    private readonly rowsRepository;
    private readonly sPrdRepository;
    private readonly logger;
    constructor(reportJobRepository: Repository<ReportJob>, headerRepository: Repository<ReportResultHeader>, rowsRepository: Repository<ReportResultRow>, sPrdRepository: Repository<SPrd>);
    onApplicationBootstrap(): void;
    pollJobs(): Promise<void>;
    processJob(job: ReportJob): Promise<void>;
    private processExportJob;
}
