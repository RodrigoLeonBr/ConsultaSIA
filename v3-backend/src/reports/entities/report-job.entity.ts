import { Entity, PrimaryGeneratedColumn, Column, CreateDateColumn } from 'typeorm';

@Entity('report_job', { database: 'producao' })
export class ReportJob {
    @PrimaryGeneratedColumn()
    id: number;

    @Column({ type: 'enum', enum: ['queued', 'running', 'done', 'failed'], default: 'queued' })
    status: string;

    @Column({ length: 100 })
    type: string;

    @Column({ type: 'json', nullable: true, name: 'payload_json' })
    parameters: any;

    @Column({ type: 'text', nullable: true, name: 'error_message' })
    errorMessage: string;

    @CreateDateColumn({ name: 'created_at' })
    createdAt: Date;

    @Column({ type: 'timestamp', nullable: true, name: 'started_at' })
    startedAt: Date;

    @Column({ type: 'timestamp', nullable: true, name: 'finished_at' })
    completedAt: Date;
}
