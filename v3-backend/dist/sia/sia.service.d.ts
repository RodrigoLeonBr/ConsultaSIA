import { Repository } from 'typeorm';
import { SPap } from './entities/s-pap.entity';
import { GetSiaReportsDto } from './dto/get-sia-reports.dto';
export declare class SiaService {
    private readonly sPapRepository;
    constructor(sPapRepository: Repository<SPap>);
    getReports(queryDto: GetSiaReportsDto): Promise<{
        data: SPap[];
        meta: {
            totalRows: number;
            page: number;
            pageSize: number;
            totalPages: number;
            queryTimeMs: number;
        };
    }>;
}
