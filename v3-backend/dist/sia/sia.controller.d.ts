import { SiaService } from './sia.service';
import { GetSiaReportsDto } from './dto/get-sia-reports.dto';
import { GetBillingProviderDto } from './dto/get-billing-provider.dto';
import { SiaProductionQueryDto } from './dto/sia-production-query.dto';
export declare class SiaController {
    private readonly siaService;
    constructor(siaService: SiaService);
    getReports(query: GetSiaReportsDto): Promise<{
        data: import("./entities/s-prd.entity").SPrd[];
        meta: {
            totalRows: number;
            page: number;
            pageSize: number;
            totalPages: number;
            queryTimeMs: number;
        };
    }>;
    getBillingProvider(query: GetBillingProviderDto): Promise<{
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
                allowedOperators: import("./field-catalog").Operator[];
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
    getProduction(body: SiaProductionQueryDto): Promise<{
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
}
