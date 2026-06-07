import { Repository } from 'typeorm';
import { SPrd } from './entities/s-prd.entity';
import { GetSiaReportsDto } from './dto/get-sia-reports.dto';
import { GetBillingProviderDto } from './dto/get-billing-provider.dto';
import { SiaProductionQueryDto } from './dto/sia-production-query.dto';
import { Operator } from './field-catalog';
export declare class SiaService {
    private readonly sPrdRepository;
    constructor(sPrdRepository: Repository<SPrd>);
    getReports(queryDto: GetSiaReportsDto): Promise<{
        data: SPrd[];
        meta: {
            totalRows: number;
            page: number;
            pageSize: number;
            totalPages: number;
            queryTimeMs: number;
        };
    }>;
    getBillingProvider(queryDto: GetBillingProviderDto): Promise<{
        data: any[];
        meta: {
            totalRows: number;
            page: number;
            pageSize: number;
            totalPages: number;
            queryTimeMs: number;
        };
    }>;
    getMetadata(): {
        producao: {
            description: string;
            fields: {
                id: string;
                label: string;
                type: import("./field-catalog").FieldType;
                allowedOperators: Operator[];
                sortable: boolean;
                groupable: boolean;
                filterOnly: boolean;
                displayOnly: boolean;
            }[];
        };
        faturamentoPrestador: {
            description: string;
            fields: {
                id: string;
                label: string;
                type: import("./field-catalog").FieldType;
                groupable: boolean;
                isAggregate: boolean;
            }[];
        };
        limits: {
            maxSelect: number;
            maxFilters: number;
            maxPageSize: number;
        };
    };
    getDynamicProduction(dto: SiaProductionQueryDto): Promise<{
        columns: {
            fieldId: string;
            label: string;
            type: string;
            displayAlias?: string;
        }[];
        rows: any[];
        meta: {
            warning?: string | undefined;
            totalRows: number;
            page: number;
            pageSize: number;
            totalPages: number;
            queryTimeMs: number;
            hasAggregates: boolean;
        };
    }>;
    private validateSelectFields;
    private validateFilterValues;
    private validateSortField;
    private attachJoins;
    private attachFilters;
}
