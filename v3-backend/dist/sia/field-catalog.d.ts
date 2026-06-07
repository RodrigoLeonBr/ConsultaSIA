import { SelectQueryBuilder } from 'typeorm';
export type FieldType = 'date' | 'text' | 'number' | 'currency' | 'lookup';
export type Operator = '=' | '>' | '<' | '>=' | '<=' | 'like' | 'starts_with' | 'ends_with' | 'between' | 'in';
export type RequiredJoin = 'prestador' | 'cbo' | 'procedimento' | 's_rub' | 'cismetro';
export interface LookupConfig {
    table: string;
    joinAlias: string;
    key: string;
    display: string;
    joinOn: string;
}
export interface FieldDef {
    id: string;
    label: string;
    type: FieldType;
    sqlExpr: string;
    filterExpr?: string;
    allowedOperators: Operator[];
    sortable: boolean;
    groupable: boolean;
    lookup?: LookupConfig;
    requiresJoin?: RequiredJoin;
    isAggregate?: boolean;
    filterOnly?: boolean;
    displayOnly?: boolean;
}
export declare const SIA_PRODUCAO_FIELDS: Readonly<Record<string, FieldDef>>;
export declare const FATURAMENTO_PRESTADOR_FIELDS: Readonly<Record<string, FieldDef>>;
export declare const FATURAMENTO_PRESTADOR_GROUP_BY: readonly ["p.re_cunid", "p.re_cnome", "sp.prd_rub", "sp.grupo", "sp.subgrupo", "sp.forma", "sp.prd_pa", "proc.procedimento", "proc.PA_TOTAL"];
export declare const VALID_SIA_PRODUCAO_IDS: string[];
export declare function isValidSiaField(id: string): boolean;
export declare function isValidFaturamentoField(id: string): boolean;
export declare function isOperatorAllowed(field: FieldDef, op: Operator): boolean;
export declare function applyOperator(qb: SelectQueryBuilder<any>, expr: string, operator: Operator, value: unknown, paramKey: string): void;
