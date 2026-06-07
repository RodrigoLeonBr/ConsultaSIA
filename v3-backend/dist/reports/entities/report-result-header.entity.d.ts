import { ReportJob } from './report-job.entity';
export declare class ReportResultHeader {
    id: number;
    job: ReportJob;
    reportType: string;
    rowCount: number;
    competence: string | null;
    filtersHash: string | null;
    sourceTablesVersionsJson: any;
    createdAt: Date;
    ttlExpiresAt: Date | null;
}
