import { Entity, PrimaryGeneratedColumn, PrimaryColumn, Column, ManyToOne, JoinColumn, Index } from 'typeorm';
import { ReportResultHeader } from './report-result-header.entity';

@Entity('report_result_rows', { database: 'producao' })
@Index(['header', 'rowIndex'])
export class ReportResultRow {
    @PrimaryColumn({ name: 'result_id', type: 'bigint' })
    id: string;

    @ManyToOne(() => ReportResultHeader, { onDelete: 'CASCADE' })
    @JoinColumn({ name: 'result_id', referencedColumnName: 'id' })
    header: ReportResultHeader;

    @PrimaryColumn({ name: 'row_index' })
    rowIndex: number;

    @Column({ type: 'longtext', name: 'row_json' })
    rowJson: string;
}
