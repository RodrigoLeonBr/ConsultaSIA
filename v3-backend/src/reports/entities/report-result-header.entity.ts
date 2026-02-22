import { Entity, PrimaryGeneratedColumn, Column, CreateDateColumn, ManyToOne, JoinColumn } from 'typeorm';
import { ReportJob } from './report-job.entity';

@Entity('report_result_header', { database: 'producao' })
export class ReportResultHeader {
    @PrimaryGeneratedColumn({ name: 'result_id' })
    id: number;

    @ManyToOne(() => ReportJob, { onDelete: 'CASCADE' })
    @JoinColumn({ name: 'job_id', referencedColumnName: 'id' })
    job: ReportJob;

    @Column({ name: 'report_type', length: 100 })
    reportType: string;

    @Column({ name: 'row_count', default: 0 })
    rowCount: number;

    @Column({ type: 'json', nullable: true, name: 'source_tables_versions_json' })
    sourceTablesVersionsJson: any;

    @CreateDateColumn({ name: 'created_at' })
    createdAt: Date;
}
