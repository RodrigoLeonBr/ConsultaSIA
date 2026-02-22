import { OnApplicationBootstrap } from '@nestjs/common';
import { Repository } from 'typeorm';
import { ReportJob } from '../reports/entities/report-job.entity';
import { ReportResultHeader } from '../reports/entities/report-result-header.entity';
import { ReportResultRow } from '../reports/entities/report-result-rows.entity';
import { SPap } from '../sia/entities/s-pap.entity';
export declare class WorkerService implements OnApplicationBootstrap {
    private readonly reportJobRepository;
    private readonly headerRepository;
    private readonly rowsRepository;
    private readonly sPapRepository;
    private readonly logger;
    constructor(reportJobRepository: Repository<ReportJob>, headerRepository: Repository<ReportResultHeader>, rowsRepository: Repository<ReportResultRow>, sPapRepository: Repository<SPap>);
    onApplicationBootstrap(): void;
    pollJobs(): Promise<void>;
    processJob(job: ReportJob): Promise<void>;
}
