"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.VALID_SIA_PRODUCAO_IDS = exports.FATURAMENTO_PRESTADOR_GROUP_BY = exports.FATURAMENTO_PRESTADOR_FIELDS = exports.SIA_PRODUCAO_FIELDS = void 0;
exports.isValidSiaField = isValidSiaField;
exports.isValidFaturamentoField = isValidFaturamentoField;
exports.isOperatorAllowed = isOperatorAllowed;
exports.applyOperator = applyOperator;
exports.SIA_PRODUCAO_FIELDS = {
    prd_cmp: {
        id: 'prd_cmp',
        label: 'Competência',
        type: 'date',
        sqlExpr: 'sp.prd_cmp',
        filterExpr: 'sp.prd_cmp',
        allowedOperators: ['=', '>=', '<=', 'between'],
        sortable: true,
        groupable: true,
    },
    prd_uid: {
        id: 'prd_uid',
        label: 'Prestador',
        type: 'lookup',
        sqlExpr: 'sp.prd_uid',
        filterExpr: 'sp.prd_uid',
        allowedOperators: ['=', 'in'],
        sortable: true,
        groupable: true,
        requiresJoin: 'prestador',
        lookup: {
            table: 'prestador',
            joinAlias: 'pr',
            key: 're_cunid',
            display: 're_cnome',
            joinOn: 'sp.prd_uid = pr.re_cunid',
        },
    },
    prd_cbo: {
        id: 'prd_cbo',
        label: 'CBO',
        type: 'lookup',
        sqlExpr: 'sp.prd_cbo',
        filterExpr: 'sp.prd_cbo',
        allowedOperators: ['=', 'in'],
        sortable: true,
        groupable: true,
        requiresJoin: 'cbo',
        lookup: {
            table: 'cbo',
            joinAlias: 'cb',
            key: 'cbo',
            display: 'ds_cbo',
            joinOn: 'sp.prd_cbo = cb.cbo',
        },
    },
    prd_pa: {
        id: 'prd_pa',
        label: 'Procedimento',
        type: 'lookup',
        sqlExpr: 'sp.prd_pa',
        filterExpr: 'sp.prd_pa',
        allowedOperators: ['=', 'in', 'like'],
        sortable: true,
        groupable: true,
        requiresJoin: 'procedimento',
        lookup: {
            table: 'procedimento',
            joinAlias: 'pc',
            key: 'codigo',
            display: 'procedimento',
            joinOn: 'sp.prd_pa = pc.codigo',
        },
    },
    procedimento_descricao: {
        id: 'procedimento_descricao',
        label: 'Descrição do Procedimento',
        type: 'text',
        sqlExpr: 'pc.procedimento',
        filterExpr: 'pc.procedimento',
        allowedOperators: ['=', 'like', 'starts_with', 'ends_with'],
        sortable: false,
        groupable: false,
        filterOnly: true,
        requiresJoin: 'procedimento',
    },
    PRD_QT_P: {
        id: 'PRD_QT_P',
        label: 'Quantidade Apresentada',
        type: 'number',
        sqlExpr: 'SUM(CAST(sp.PRD_QT_P AS UNSIGNED))',
        filterExpr: 'CAST(sp.PRD_QT_P AS UNSIGNED)',
        allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
        sortable: true,
        groupable: false,
        isAggregate: true,
    },
    PRD_VL_P: {
        id: 'PRD_VL_P',
        label: 'Valor Apresentado',
        type: 'currency',
        sqlExpr: 'SUM(CAST(sp.PRD_VL_P AS DECIMAL(15,2)))',
        filterExpr: 'CAST(sp.PRD_VL_P AS DECIMAL(15,2))',
        allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
        sortable: true,
        groupable: false,
        isAggregate: true,
    },
    PRD_QT_A: {
        id: 'PRD_QT_A',
        label: 'Quantidade Aprovada',
        type: 'number',
        sqlExpr: 'SUM(CAST(sp.PRD_QT_A AS UNSIGNED))',
        filterExpr: 'CAST(sp.PRD_QT_A AS UNSIGNED)',
        allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
        sortable: true,
        groupable: false,
        isAggregate: true,
    },
    PRD_VL_A: {
        id: 'PRD_VL_A',
        label: 'Valor Aprovado',
        type: 'currency',
        sqlExpr: 'SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2)))',
        filterExpr: 'CAST(sp.PRD_VL_A AS DECIMAL(15,2))',
        allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
        sortable: true,
        groupable: false,
        isAggregate: true,
    },
    PRD_RUB: {
        id: 'PRD_RUB',
        label: 'Rubrica / Tipo Financiamento',
        type: 'lookup',
        sqlExpr: 'sp.prd_rub',
        filterExpr: 'sp.prd_rub',
        allowedOperators: ['=', 'in'],
        sortable: true,
        groupable: true,
        requiresJoin: 's_rub',
        lookup: {
            table: 's_rub',
            joinAlias: 'sr',
            key: 'RUB_ID',
            display: 'RUB_DC',
            joinOn: 'sp.prd_rub = sr.RUB_ID',
        },
    },
    PRD_CIDPRI: {
        id: 'PRD_CIDPRI',
        label: 'CID Principal',
        type: 'text',
        sqlExpr: 'sp.PRD_CIDPRI',
        filterExpr: 'sp.PRD_CIDPRI',
        allowedOperators: ['=', 'like', 'starts_with'],
        sortable: true,
        groupable: true,
    },
    cismetro_valor: {
        id: 'cismetro_valor',
        label: 'Cismetro — Valor Unitário',
        type: 'currency',
        sqlExpr: 'cs.valor',
        filterExpr: 'cs.valor',
        allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
        sortable: true,
        groupable: true,
        requiresJoin: 'cismetro',
    },
    cismetro_total: {
        id: 'cismetro_total',
        label: 'Cismetro — Valor Total',
        type: 'currency',
        sqlExpr: 'SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * COALESCE(cs.valor, 0))',
        allowedOperators: [],
        sortable: false,
        groupable: false,
        isAggregate: true,
        displayOnly: true,
        requiresJoin: 'cismetro',
    },
    cismetro_descricao: {
        id: 'cismetro_descricao',
        label: 'Cismetro — Descrição',
        type: 'lookup',
        sqlExpr: 'sp.prd_pa',
        filterExpr: 'sp.prd_pa',
        allowedOperators: ['=', 'like'],
        sortable: true,
        groupable: true,
        requiresJoin: 'cismetro',
        lookup: {
            table: 'cismetro',
            joinAlias: 'cs',
            key: 'codigo',
            display: 'descricao',
            joinOn: 'sp.prd_pa = cs.codigo',
        },
    },
};
exports.FATURAMENTO_PRESTADOR_FIELDS = {
    prestador_codigo: {
        id: 'prestador_codigo',
        label: 'CNES',
        type: 'text',
        sqlExpr: 'p.re_cunid',
        filterExpr: 'p.re_cunid',
        allowedOperators: ['='],
        sortable: true,
        groupable: true,
        requiresJoin: 'prestador',
    },
    prestador_nome: {
        id: 'prestador_nome',
        label: 'Prestador',
        type: 'text',
        sqlExpr: 'p.re_cnome',
        filterExpr: 'p.re_cnome',
        allowedOperators: ['=', 'like'],
        sortable: true,
        groupable: true,
        requiresJoin: 'prestador',
    },
    tipo_financiamento: {
        id: 'tipo_financiamento',
        label: 'Tipo Financiamento',
        type: 'text',
        sqlExpr: 'sp.prd_rub',
        filterExpr: 'sp.prd_rub',
        allowedOperators: ['=', 'in'],
        sortable: true,
        groupable: true,
    },
    grupo: {
        id: 'grupo',
        label: 'Grupo',
        type: 'text',
        sqlExpr: 'sp.grupo',
        filterExpr: 'sp.grupo',
        allowedOperators: ['=', 'like'],
        sortable: true,
        groupable: true,
    },
    subgrupo: {
        id: 'subgrupo',
        label: 'Subgrupo',
        type: 'text',
        sqlExpr: 'sp.subgrupo',
        filterExpr: 'sp.subgrupo',
        allowedOperators: ['=', 'like'],
        sortable: true,
        groupable: true,
    },
    forma: {
        id: 'forma',
        label: 'Forma',
        type: 'text',
        sqlExpr: 'sp.forma',
        filterExpr: 'sp.forma',
        allowedOperators: ['=', 'like'],
        sortable: true,
        groupable: true,
    },
    procedimento_codigo: {
        id: 'procedimento_codigo',
        label: 'Cód. Procedimento',
        type: 'text',
        sqlExpr: 'sp.prd_pa',
        filterExpr: 'sp.prd_pa',
        allowedOperators: ['=', 'like', 'starts_with'],
        sortable: true,
        groupable: true,
    },
    procedimento_nome: {
        id: 'procedimento_nome',
        label: 'Procedimento',
        type: 'text',
        sqlExpr: 'proc.procedimento',
        filterExpr: 'proc.procedimento',
        allowedOperators: ['=', 'like', 'starts_with'],
        sortable: true,
        groupable: true,
        requiresJoin: 'procedimento',
    },
    valor_unitario: {
        id: 'valor_unitario',
        label: 'Vlr. Unitário',
        type: 'currency',
        sqlExpr: 'CAST(proc.PA_TOTAL AS DECIMAL(15,2))',
        filterExpr: 'CAST(proc.PA_TOTAL AS DECIMAL(15,2))',
        allowedOperators: ['=', '>', '<', '>=', '<=', 'between'],
        sortable: true,
        groupable: true,
        requiresJoin: 'procedimento',
    },
    qtyApproved: {
        id: 'qtyApproved',
        label: 'Qtd. Aprovada',
        type: 'number',
        sqlExpr: 'SUM(CAST(sp.PRD_QT_A AS UNSIGNED))',
        filterExpr: 'CAST(sp.PRD_QT_A AS UNSIGNED)',
        allowedOperators: ['=', '>', '<', '>=', '<='],
        sortable: true,
        groupable: false,
        isAggregate: true,
    },
    valueApproved: {
        id: 'valueApproved',
        label: 'Vlr. Aprovado',
        type: 'currency',
        sqlExpr: 'SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2)))',
        filterExpr: 'CAST(sp.PRD_VL_A AS DECIMAL(15,2))',
        allowedOperators: ['=', '>', '<', '>=', '<='],
        sortable: true,
        groupable: false,
        isAggregate: true,
    },
    qtyPresented: {
        id: 'qtyPresented',
        label: 'Qtd. Apresentada',
        type: 'number',
        sqlExpr: 'SUM(CAST(sp.PRD_QT_P AS UNSIGNED))',
        filterExpr: 'CAST(sp.PRD_QT_P AS UNSIGNED)',
        allowedOperators: ['=', '>', '<', '>=', '<='],
        sortable: true,
        groupable: false,
        isAggregate: true,
    },
    valuePresented: {
        id: 'valuePresented',
        label: 'Vlr. Apresentado',
        type: 'currency',
        sqlExpr: 'SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * CAST(proc.PA_TOTAL AS DECIMAL(15,2)))',
        allowedOperators: ['=', '>', '<', '>=', '<='],
        sortable: true,
        groupable: false,
        isAggregate: true,
        requiresJoin: 'procedimento',
    },
};
exports.FATURAMENTO_PRESTADOR_GROUP_BY = [
    'p.re_cunid',
    'p.re_cnome',
    'sp.prd_rub',
    'sp.grupo',
    'sp.subgrupo',
    'sp.forma',
    'sp.prd_pa',
    'proc.procedimento',
    'proc.PA_TOTAL',
];
exports.VALID_SIA_PRODUCAO_IDS = Object.keys(exports.SIA_PRODUCAO_FIELDS);
function isValidSiaField(id) {
    return id in exports.SIA_PRODUCAO_FIELDS;
}
function isValidFaturamentoField(id) {
    return id in exports.FATURAMENTO_PRESTADOR_FIELDS;
}
function isOperatorAllowed(field, op) {
    return field.allowedOperators.includes(op);
}
function applyOperator(qb, expr, operator, value, paramKey) {
    switch (operator) {
        case '=':
            qb.andWhere(`${expr} = :${paramKey}`, { [paramKey]: value });
            break;
        case '>':
            qb.andWhere(`${expr} > :${paramKey}`, { [paramKey]: value });
            break;
        case '<':
            qb.andWhere(`${expr} < :${paramKey}`, { [paramKey]: value });
            break;
        case '>=':
            qb.andWhere(`${expr} >= :${paramKey}`, { [paramKey]: value });
            break;
        case '<=':
            qb.andWhere(`${expr} <= :${paramKey}`, { [paramKey]: value });
            break;
        case 'like':
            qb.andWhere(`${expr} LIKE :${paramKey}`, { [paramKey]: `%${value}%` });
            break;
        case 'starts_with':
            qb.andWhere(`${expr} LIKE :${paramKey}`, { [paramKey]: `${value}%` });
            break;
        case 'ends_with':
            qb.andWhere(`${expr} LIKE :${paramKey}`, { [paramKey]: `%${value}` });
            break;
        case 'between':
            if (Array.isArray(value) && value.length === 2) {
                qb.andWhere(`${expr} BETWEEN :${paramKey}Min AND :${paramKey}Max`, {
                    [`${paramKey}Min`]: value[0],
                    [`${paramKey}Max`]: value[1],
                });
            }
            break;
        case 'in':
            if (Array.isArray(value) && value.length > 0) {
                qb.andWhere(`${expr} IN (:...${paramKey})`, { [paramKey]: value });
            }
            break;
    }
}
//# sourceMappingURL=field-catalog.js.map