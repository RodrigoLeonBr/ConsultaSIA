import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { WorkerService } from './worker.service';
import { ReportJob } from '../reports/entities/report-job.entity';
import { ReportResultHeader } from '../reports/entities/report-result-header.entity';
import { ReportResultRow } from '../reports/entities/report-result-rows.entity';
import { SPap } from '../sia/entities/s-pap.entity';

@Module({
    imports: [TypeOrmModule.forFeature([ReportJob, ReportResultHeader, ReportResultRow, SPap])],
    providers: [WorkerService],
})
export class WorkerModule { }
