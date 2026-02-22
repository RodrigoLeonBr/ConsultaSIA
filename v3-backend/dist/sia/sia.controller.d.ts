import { SiaService } from './sia.service';
import { GetSiaReportsDto } from './dto/get-sia-reports.dto';
export declare class SiaController {
    private readonly siaService;
    constructor(siaService: SiaService);
    getReports(query: GetSiaReportsDto): Promise<{
        data: import("./entities/s-pap.entity").SPap[];
        meta: {
            totalRows: number;
            page: number;
            pageSize: number;
            totalPages: number;
            queryTimeMs: number;
        };
    }>;
}
