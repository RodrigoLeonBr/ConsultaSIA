import { Controller, Get, Query } from '@nestjs/common';
import { SiaService } from './sia.service';
import { GetSiaReportsDto } from './dto/get-sia-reports.dto';

@Controller('reports/sia')
export class SiaController {
    constructor(private readonly siaService: SiaService) { }

    @Get()
    async getReports(@Query() query: GetSiaReportsDto) {
        return this.siaService.getReports(query);
    }
}
