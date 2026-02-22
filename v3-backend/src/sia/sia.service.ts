import { Injectable } from '@nestjs/common';
import { InjectRepository } from '@nestjs/typeorm';
import { Repository } from 'typeorm';
import { SPap } from './entities/s-pap.entity';
import { GetSiaReportsDto } from './dto/get-sia-reports.dto';

@Injectable()
export class SiaService {
    constructor(
        @InjectRepository(SPap)
        private readonly sPapRepository: Repository<SPap>,
    ) { }

    async getReports(queryDto: GetSiaReportsDto) {
        const { page = 1, limit = 50, competence, providerId } = queryDto;

        // Calculate offset
        const skip = (page - 1) * limit;

        // Use QueryBuilder to safely parameterize the query
        const queryBuilder = this.sPapRepository.createQueryBuilder('s_pap');

        if (competence) {
            queryBuilder.andWhere('s_pap.PAP_CMP = :competence', { competence });
        }

        if (providerId) {
            queryBuilder.andWhere('s_pap.PAP_CNPJ = :providerId', { providerId });
        }

        // Measure only the query execution time for diagnostics
        const startTime = Date.now();

        const [rows, totalRows] = await queryBuilder
            .skip(skip)
            .take(limit)
            .getManyAndCount();

        const queryTimeMs = Date.now() - startTime;

        return {
            data: rows,
            meta: {
                totalRows,
                page,
                pageSize: limit,
                totalPages: Math.ceil(totalRows / limit),
                queryTimeMs,
            }
        };
    }
}
