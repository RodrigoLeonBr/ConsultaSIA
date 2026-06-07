import { Controller, Post, Body, Get, Param, Query, ParseIntPipe, DefaultValuePipe, Res } from '@nestjs/common';
import type { Response } from 'express';
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

    /**
     * Download do arquivo gerado por um job de exportação (xlsx, csv ou pdf).
     * O job deve estar no status "done" e ter um arquivo associado.
     */
    @Get('jobs/:id/download')
    async downloadFile(
        @Param('id', ParseIntPipe) id: number,
        @Res() res: Response,
    ) {
        const { fullPath, filename } = await this.reportsService.getJobFilePath(id);
        res.download(fullPath, filename);
    }
}
