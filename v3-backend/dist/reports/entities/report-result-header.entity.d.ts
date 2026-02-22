import { ReportJob } from './report-job.entity';
export declare class ReportResultHeader {
    id: number;
    job: ReportJob;
    reportType: string;
    rowCount: number;
    sourceTablesVersionsJson: any;
    createdAt: Date;
}
