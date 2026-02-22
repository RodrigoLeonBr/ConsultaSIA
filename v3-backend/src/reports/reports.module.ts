import { Module } from '@nestjs/common';
import { TypeOrmModule } from '@nestjs/typeorm';
import { ReportsController } from './reports.controller';
import { ReportsService } from './reports.service';
import { ReportJob } from './entities/report-job.entity';
import { ReportResultHeader } from './entities/report-result-header.entity';
import { ReportResultRow } from './entities/report-result-rows.entity';

@Module({
    imports: [TypeOrmModule.forFeature([ReportJob, ReportResultHeader, ReportResultRow])],
    controllers: [ReportsController],
    providers: [ReportsService],
    exports: [ReportsService],
})
export class ReportsModule { }
