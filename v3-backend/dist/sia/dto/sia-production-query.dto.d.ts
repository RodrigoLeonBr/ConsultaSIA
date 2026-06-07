export declare const VALID_OPERATORS: readonly ["=", ">", "<", ">=", "<=", "like", "starts_with", "ends_with", "between", "in"];
export type FilterOperator = (typeof VALID_OPERATORS)[number];
export declare const VALID_SIA_FIELD_IDS: readonly ["prd_cmp", "prd_uid", "prd_cbo", "prd_pa", "procedimento_descricao", "PRD_QT_P", "PRD_VL_P", "PRD_QT_A", "PRD_VL_A", "PRD_RUB", "PRD_CIDPRI", "cismetro_valor", "cismetro_total", "cismetro_descricao"];
export type SiaFieldId = (typeof VALID_SIA_FIELD_IDS)[number];
export declare class FilterItemDto {
    fieldId: string;
    operator: string;
    value: unknown;
}
export declare class SortDto {
    fieldId: string;
    direction: 'ASC' | 'DESC';
}
export declare class SiaProductionQueryDto {
    competence: string;
    select: string[];
    filters?: FilterItemDto[];
    page?: number;
    pageSize?: number;
    sort?: SortDto;
}
