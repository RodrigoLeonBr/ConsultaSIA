export type FieldType = 'date' | 'lookup' | 'number' | 'currency' | 'text';
export type Operator = '=' | '>' | '<' | '>=' | '<=' | 'between' | 'in' | 'like' | 'starts_with' | 'ends_with';

export interface LookupConfig {
  table: string;
  joinOn: string;
  displayCol: string;
  displayAlias: string;
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
  isAggregate?: boolean;
  filterOnly?: boolean;
  displayOnly?: boolean;
  requiresJoin?: string;
  lookup?: LookupConfig;
  castAs?: 'UNSIGNED' | 'DECIMAL(15,2)';
}

export const SIA_PRODUCAO_FIELDS: Record<string, FieldDef> = {
  prd_cmp: {
    id: 'prd_cmp', label: 'Competência', type: 'date',
    sqlExpr: 'sp.prd_cmp',
    allowedOperators: ['=', '>=', '<=', 'between'],
    sortable: true, groupable: true,
  },
  prd_uid: {
    id: 'prd_uid', label: 'Prestador', type: 'lookup',
    sqlExpr: 'sp.prd_uid',
    allowedOperators: ['=', 'in'],
    sortable: true, groupable: true,
    requiresJoin: 'prestador',
    lookup: { table: 'prestador', joinOn: 'sp.prd_uid = pr.re_cunid', displayCol: 'pr.re_cnome', displayAlias: 'prd_uid_display' },
  },
  prd_cbo: {
    id: 'prd_cbo', label: 'CBO', type: 'lookup',
    sqlExpr: 'sp.prd_cbo',
    allowedOperators: ['=', 'in'],
    sortable: true, groupable: true,
    requiresJoin: 'cbo',
    lookup: { table: 'cbo', joinOn: 'sp.prd_cbo = cb.cbo', displayCol: 'cb.ds_cbo', displayAlias: 'prd_cbo_display' },
  },
  prd_pa: {
    id: 'prd_pa', label: 'Procedimento', type: 'lookup',
    sqlExpr: 'sp.prd_pa',
    allowedOperators: ['=', 'in', 'like'],
    sortable: true, groupable: true,
    requiresJoin: 'procedimento',
    lookup: { table: 'procedimento', joinOn: 'sp.prd_pa = pc.codigo', displayCol: 'pc.procedimento', displayAlias: 'prd_pa_display' },
  },
  PRD_RUB: {
    id: 'PRD_RUB', label: 'Financiamento', type: 'lookup',
    sqlExpr: 'sp.prd_rub',
    allowedOperators: ['=', 'in'],
    sortable: true, groupable: true,
    requiresJoin: 's_rub',
    lookup: { table: 's_rub', joinOn: 'sp.prd_rub = sr.RUB_ID', displayCol: 'sr.RUB_DC', displayAlias: 'PRD_RUB_display' },
  },
  PRD_CIDPRI: {
    id: 'PRD_CIDPRI', label: 'CID Primário', type: 'text',
    sqlExpr: 'sp.PRD_CIDPRI',
    allowedOperators: ['=', 'like', 'starts_with'],
    sortable: true, groupable: true,
  },
  PRD_QT_P: {
    id: 'PRD_QT_P', label: 'Qtd Apresentada', type: 'number',
    sqlExpr: 'CAST(sp.PRD_QT_P AS UNSIGNED)',
    allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
    sortable: true, groupable: false, isAggregate: true, castAs: 'UNSIGNED',
  },
  PRD_QT_A: {
    id: 'PRD_QT_A', label: 'Qtd Aprovada', type: 'number',
    sqlExpr: 'CAST(sp.PRD_QT_A AS UNSIGNED)',
    allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
    sortable: true, groupable: false, isAggregate: true, castAs: 'UNSIGNED',
  },
  PRD_VL_P: {
    id: 'PRD_VL_P', label: 'Valor Apresentado', type: 'currency',
    sqlExpr: 'CAST(sp.PRD_VL_P AS DECIMAL(15,2))',
    allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
    sortable: true, groupable: false, isAggregate: true, castAs: 'DECIMAL(15,2)',
  },
  PRD_VL_A: {
    id: 'PRD_VL_A', label: 'Valor Aprovado', type: 'currency',
    sqlExpr: 'CAST(sp.PRD_VL_A AS DECIMAL(15,2))',
    allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
    sortable: true, groupable: false, isAggregate: true, castAs: 'DECIMAL(15,2)',
  },
  cismetro_valor: {
    id: 'cismetro_valor', label: 'Valor Unitário (Cismetro)', type: 'currency',
    sqlExpr: 'cs.valor',
    allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
    sortable: true, groupable: true, requiresJoin: 'cismetro',
    lookup: { table: 'cismetro', joinOn: 'sp.prd_pa = cs.codigo', displayCol: 'cs.descricao', displayAlias: 'cismetro_descricao' },
  },
  cismetro_descricao: {
    id: 'cismetro_descricao', label: 'Descrição Cismetro', type: 'text',
    sqlExpr: 'cs.descricao',
    allowedOperators: ['=', 'like'],
    sortable: true, groupable: true, requiresJoin: 'cismetro',
  },
  cismetro_total: {
    id: 'cismetro_total', label: 'Total Cismetro', type: 'currency',
    sqlExpr: 'SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * cs.valor)',
    allowedOperators: [],
    sortable: false, groupable: false, isAggregate: true, displayOnly: true, requiresJoin: 'cismetro',
  },
  procedimento_descricao: {
    id: 'procedimento_descricao', label: 'Descrição Procedimento', type: 'text',
    sqlExpr: 'pc.procedimento',
    filterExpr: `sp.prd_pa IN (SELECT codigo FROM procedimento WHERE procedimento LIKE ?)`,
    allowedOperators: ['like', 'starts_with', 'ends_with'],
    sortable: false, groupable: false, filterOnly: true, requiresJoin: 'procedimento',
  },
};

export function getField(id: string): FieldDef | undefined {
  return SIA_PRODUCAO_FIELDS[id];
}

export function isOperatorAllowed(field: FieldDef, op: Operator): boolean {
  return field.allowedOperators.includes(op);
}

export const LIMITS = {
  maxSelect: 20,
  maxFilters: 20,
  maxPageSize: 500,
} as const;

export const METADATA_RESPONSE = {
  producao: {
    description: 'Relatório dinâmico de Produção SIA (s_prd). Filtro de competência obrigatório.',
    fields: Object.values(SIA_PRODUCAO_FIELDS).map(f => ({
      id: f.id,
      label: f.label,
      type: f.type,
      allowedOperators: f.allowedOperators,
      sortable: f.sortable,
      groupable: f.groupable,
      isAggregate: f.isAggregate ?? false,
      filterOnly: f.filterOnly ?? false,
      displayOnly: f.displayOnly ?? false,
    })),
  },
  limits: LIMITS,
};
