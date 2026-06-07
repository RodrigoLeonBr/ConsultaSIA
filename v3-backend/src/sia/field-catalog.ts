/**
 * Field Catalog — SIA (Produção + Faturamento por Prestador)
 *
 * Whitelist de campos permitidos para queries dinâmicas.
 * Baseado na análise do legado Laravel (.context/docs/legacy-relatorios-spec.md).
 *
 * Regras de segurança:
 *  - Apenas campos desta lista são aceitos em SELECT, WHERE e GROUP BY.
 *  - Operadores fora de `allowedOperators` são rejeitados no service.
 *  - sqlExpr/filterExpr contêm o CAST correto para campos VARCHAR numéricos.
 *  - Campos `isAggregate` nunca entram no GROUP BY.
 *  - Campos `filterOnly` nunca aparecem nas colunas de resultado.
 */

import { SelectQueryBuilder } from 'typeorm';

// ─── Tipos base ────────────────────────────────────────────────────────────────

export type FieldType = 'date' | 'text' | 'number' | 'currency' | 'lookup';

export type Operator =
    | '='
    | '>'
    | '<'
    | '>='
    | '<='
    | 'like'
    | 'starts_with'
    | 'ends_with'
    | 'between'
    | 'in';

export type RequiredJoin =
    | 'prestador'
    | 'cbo'
    | 'procedimento'
    | 's_rub'
    | 'cismetro';

export interface LookupConfig {
    /** Tabela de referência (ex: 'prestador') */
    table: string;
    /** Alias usado no JOIN (ex: 'pr') */
    joinAlias: string;
    /** Coluna chave na tabela de referência (ex: 're_cunid') */
    key: string;
    /** Coluna exibida ao usuário (ex: 're_cnome') */
    display: string;
    /** Expressão ON do LEFT JOIN (ex: 'sp.prd_uid = pr.re_cunid') */
    joinOn: string;
}

export interface FieldDef {
    id: string;
    label: string;
    type: FieldType;
    /** Expressão usada no SELECT (pode conter SUM/CAST) */
    sqlExpr: string;
    /** Expressão usada no WHERE; se omitida, usa sqlExpr sem agregação */
    filterExpr?: string;
    allowedOperators: Operator[];
    sortable: boolean;
    /** Pode participar do GROUP BY (false para campos agregados) */
    groupable: boolean;
    /** Configuração de lookup para joins automáticos */
    lookup?: LookupConfig;
    /** JOIN adicional necessário ao usar este campo */
    requiresJoin?: RequiredJoin;
    /** true → campo calculado (SUM), não vai para GROUP BY */
    isAggregate?: boolean;
    /** true → apenas filtro, não aparece nas colunas de resultado */
    filterOnly?: boolean;
    /** true → apenas exibição, não aceita filtro direto */
    displayOnly?: boolean;
}

// ─── Catálogo: Produção SIA (s_prd) ──────────────────────────────────────────
// Alias da tabela principal: sp (s_prd as sp)

export const SIA_PRODUCAO_FIELDS: Readonly<Record<string, FieldDef>> = {
    prd_cmp: {
        id: 'prd_cmp',
        label: 'Competência',
        type: 'date',
        sqlExpr: 'sp.prd_cmp',
        // Para exibição: CONCAT(SUBSTRING(sp.prd_cmp,1,4),'-',SUBSTRING(sp.prd_cmp,5,2))
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

    // Campo filtro-only: resolve para subquery SELECT codigo FROM procedimento WHERE procedimento LIKE ?
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

    // VARCHAR numérico — CAST obrigatório
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

    // Campo calculado — filtro direto não suportado (requereria HAVING)
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
} as const;

// ─── Catálogo: Faturamento por Prestador (s_prd) ─────────────────────────────
// Campos fixos — GROUP BY + hierarquia Prestador→Tipo→Grupo→Subgrupo→Forma→Proc
// Alias tabela principal: sp | prestador: p | procedimento: proc

export const FATURAMENTO_PRESTADOR_FIELDS: Readonly<Record<string, FieldDef>> = {
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

    // STORED GENERATED columns — sem SUBSTRING em runtime
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

    // PA_TOTAL é varchar no schema DATASUS — CAST obrigatório
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

    // Valor calculado: QT_P × PA_TOTAL (não usa PRD_VL_P)
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
} as const;

// ─── GROUP BY fixo para Faturamento por Prestador ────────────────────────────

export const FATURAMENTO_PRESTADOR_GROUP_BY = [
    'p.re_cunid',
    'p.re_cnome',
    'sp.prd_rub',
    'sp.grupo',
    'sp.subgrupo',
    'sp.forma',
    'sp.prd_pa',
    'proc.procedimento',
    'proc.PA_TOTAL',
] as const;

// ─── Helpers de validação ─────────────────────────────────────────────────────

/** IDs válidos do catálogo Produção SIA (fonte canônica) */
export const VALID_SIA_PRODUCAO_IDS = Object.keys(SIA_PRODUCAO_FIELDS);

/** Verifica se um campo é válido no catálogo Produção SIA */
export function isValidSiaField(id: string): boolean {
    return id in SIA_PRODUCAO_FIELDS;
}

/** Verifica se um campo é válido no catálogo Faturamento Prestador */
export function isValidFaturamentoField(id: string): boolean {
    return id in FATURAMENTO_PRESTADOR_FIELDS;
}

/** Verifica se um operador é permitido para um campo */
export function isOperatorAllowed(field: FieldDef, op: Operator): boolean {
    return (field.allowedOperators as readonly string[]).includes(op);
}

// ─── Aplicação de operadores na query ────────────────────────────────────────

/**
 * Aplica um operador seguro ao QueryBuilder.
 * Não interpola valores direto no SQL — usa parâmetros nomeados.
 */
export function applyOperator(
    qb: SelectQueryBuilder<any>,
    expr: string,
    operator: Operator,
    value: unknown,
    paramKey: string,
): void {
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
