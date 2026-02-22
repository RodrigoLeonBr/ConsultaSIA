import { Controller, Post, Body, Get, Param, Query, ParseIntPipe, DefaultValuePipe } from '@nestjs/common';
import { ReportsService } from './reports.service';
import { CreateJobDto } from './dto/create-job.dto';

@Controller('reports')
export class ReportsController {
    constructor(private readonly reportsService: ReportsService) { }

    @Post('jobs')
    async createJob(@Body() createJobDto: CreateJobDto) {
        return this.reportsService.createJob(createJobDto);
    }

    @Get('jobs/:id')
    async getJobStatus(@Param('id', ParseIntPipe) id: number) {
        return this.reportsService.getJobStatus(id);
    }

    @Get('jobs/:id/results')
    async getJobResults(
        @Param('id', ParseIntPipe) jobId: number,
        @Query('page', new DefaultValuePipe(1), ParseIntPipe) page: number,
        @Query('limit', new DefaultValuePipe(200), ParseIntPipe) limit: number,
    ) {
        return this.reportsService.getResultPage(jobId, page, limit);
    }
}
