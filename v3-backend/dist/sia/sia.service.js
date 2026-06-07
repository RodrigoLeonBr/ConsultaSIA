"use strict";
var __decorate = (this && this.__decorate) || function (decorators, target, key, desc) {
    var c = arguments.length, r = c < 3 ? target : desc === null ? desc = Object.getOwnPropertyDescriptor(target, key) : desc, d;
    if (typeof Reflect === "object" && typeof Reflect.decorate === "function") r = Reflect.decorate(decorators, target, key, desc);
    else for (var i = decorators.length - 1; i >= 0; i--) if (d = decorators[i]) r = (c < 3 ? d(r) : c > 3 ? d(target, key, r) : d(target, key)) || r;
    return c > 3 && r && Object.defineProperty(target, key, r), r;
};
var __metadata = (this && this.__metadata) || function (k, v) {
    if (typeof Reflect === "object" && typeof Reflect.metadata === "function") return Reflect.metadata(k, v);
};
var __param = (this && this.__param) || function (paramIndex, decorator) {
    return function (target, key) { decorator(target, key, paramIndex); }
};
Object.defineProperty(exports, "__esModule", { value: true });
exports.SiaService = void 0;
const common_1 = require("@nestjs/common");
const typeorm_1 = require("@nestjs/typeorm");
const typeorm_2 = require("typeorm");
const s_prd_entity_1 = require("./entities/s-prd.entity");
const field_catalog_1 = require("./field-catalog");
const METADATA_RESPONSE = {
    producao: {
        description: 'Relatório dinâmico de Produção SIA (s_prd). Filtro de competência obrigatório.',
        fields: Object.values(field_catalog_1.SIA_PRODUCAO_FIELDS).map(f => ({
            id: f.id,
            label: f.label,
            type: f.type,
            allowedOperators: f.allowedOperators,
            sortable: f.sortable,
            groupable: f.groupable,
            filterOnly: f.filterOnly ?? false,
            displayOnly: f.displayOnly ?? false,
        })),
    },
    faturamentoPrestador: {
        description: 'Campos do relatório hierárquico de Faturamento por Prestador (colunas fixas).',
        fields: Object.values(field_catalog_1.FATURAMENTO_PRESTADOR_FIELDS).map(f => ({
            id: f.id,
            label: f.label,
            type: f.type,
            groupable: f.groupable,
            isAggregate: f.isAggregate ?? false,
        })),
    },
    limits: {
        maxSelect: 20,
        maxFilters: 20,
        maxPageSize: 500,
    },
};
let SiaService = class SiaService {
    sPrdRepository;
    constructor(sPrdRepository) {
        this.sPrdRepository = sPrdRepository;
    }
    async getReports(queryDto) {
        const { page = 1, limit = 50, competence, providerId } = queryDto;
        if (!competence) {
            throw new common_1.BadRequestException('O filtro de competência (competence) é obrigatório.');
        }
        const skip = (page - 1) * limit;
        const queryBuilder = this.sPrdRepository
            .createQueryBuilder('s_prd')
            .where('s_prd.prd_cmp = :competence', { competence });
        if (providerId) {
            queryBuilder.andWhere('s_prd.prd_uid = :providerId', { providerId });
        }
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
            },
        };
    }
    async getBillingProvider(queryDto) {
        const { page = 1, limit = 50, competence, providerId } = queryDto;
        if (!competence) {
            throw new common_1.BadRequestException('O filtro de competência (competence) é obrigatório.');
        }
        const skip = (page - 1) * limit;
        const dataQb = this.sPrdRepository.createQueryBuilder('sp')
            .select('p.re_cunid', 'prestadorCnes')
            .addSelect('p.re_cnome', 'prestadorNome')
            .addSelect('sp.prd_rub', 'financingType')
            .addSelect('sp.grupo', 'grupo')
            .addSelect('sp.subgrupo', 'subgrupo')
            .addSelect('sp.forma', 'forma')
            .addSelect('sp.prd_pa', 'procedureCode')
            .addSelect('proc.procedimento', 'procedureName')
            .addSelect('CAST(proc.PA_TOTAL AS DECIMAL(15,2))', 'unitValue')
            .addSelect('SUM(CAST(sp.PRD_QT_A AS UNSIGNED))', 'qtyApproved')
            .addSelect('SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2)))', 'valueApproved')
            .addSelect('SUM(CAST(sp.PRD_QT_P AS UNSIGNED))', 'qtyPresented')
            .addSelect('SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * CAST(proc.PA_TOTAL AS DECIMAL(15,2)))', 'valuePresented')
            .leftJoin('prestador', 'p', 'p.re_cunid = sp.prd_uid')
            .leftJoin('procedimento', 'proc', 'proc.codigo = sp.prd_pa')
            .where('sp.prd_cmp = :competence', { competence })
            .andWhere('p.ativo = 1')
            .groupBy('p.re_cunid').addGroupBy('p.re_cnome')
            .addGroupBy('sp.prd_rub').addGroupBy('sp.grupo')
            .addGroupBy('sp.subgrupo').addGroupBy('sp.forma')
            .addGroupBy('sp.prd_pa').addGroupBy('proc.procedimento').addGroupBy('proc.PA_TOTAL')
            .orderBy('p.re_cnome').addOrderBy('sp.prd_rub')
            .addOrderBy('sp.grupo').addOrderBy('sp.subgrupo').addOrderBy('sp.forma').addOrderBy('sp.prd_pa');
        if (providerId) {
            dataQb.andWhere('sp.prd_uid = :providerId', { providerId });
        }
        const countParams = [competence];
        if (providerId)
            countParams.push(providerId);
        const countSql = `
            SELECT COUNT(*) AS cnt FROM (
                SELECT 1 FROM s_prd sp
                LEFT JOIN prestador p ON p.re_cunid = sp.prd_uid
                LEFT JOIN procedimento proc ON proc.codigo = sp.prd_pa
                WHERE sp.prd_cmp = ? AND p.ativo = 1
                ${providerId ? 'AND sp.prd_uid = ?' : ''}
                GROUP BY p.re_cunid, p.re_cnome, sp.prd_rub, sp.grupo, sp.subgrupo,
                         sp.forma, sp.prd_pa, proc.procedimento, proc.PA_TOTAL
            ) sub`;
        const startTime = Date.now();
        const [rows, countResult] = await Promise.all([
            dataQb.skip(skip).take(limit).getRawMany(),
            this.sPrdRepository.manager.query(countSql, countParams),
        ]);
        const queryTimeMs = Date.now() - startTime;
        const totalRows = parseInt(countResult[0].cnt, 10);
        return {
            data: rows,
            meta: {
                totalRows,
                page,
                pageSize: limit,
                totalPages: Math.ceil(totalRows / limit),
                queryTimeMs,
            },
        };
    }
    getMetadata() {
        return METADATA_RESPONSE;
    }
    async getDynamicProduction(dto) {
        const { competence, select, filters = [], page = 1, pageSize = 50, sort } = dto;
        this.validateSelectFields(select);
        this.validateFilterValues(filters);
        if (sort)
            this.validateSortField(sort.fieldId);
        const hasAggregates = select.some(id => field_catalog_1.SIA_PRODUCAO_FIELDS[id]?.isAggregate);
        const requiredJoins = new Set();
        const allFieldIds = [
            ...select,
            ...filters.map(f => f.fieldId),
            ...(sort ? [sort.fieldId] : []),
        ];
        for (const fieldId of allFieldIds) {
            if (fieldId === 'procedimento_descricao')
                continue;
            const field = field_catalog_1.SIA_PRODUCAO_FIELDS[fieldId];
            if (field?.requiresJoin)
                requiredJoins.add(field.requiresJoin);
        }
        const selectExprs = [];
        const groupByExprs = [];
        const columnsMeta = [];
        for (const fieldId of select) {
            const field = field_catalog_1.SIA_PRODUCAO_FIELDS[fieldId];
            if (!field || field.filterOnly)
                continue;
            if (fieldId === 'cismetro_descricao') {
                selectExprs.push({ expr: 'sp.prd_pa', alias: 'cismetro_codigo' });
                selectExprs.push({ expr: 'cs.descricao', alias: 'cismetro_descricao' });
                if (hasAggregates) {
                    groupByExprs.push('sp.prd_pa', 'cs.descricao');
                }
                columnsMeta.push({ fieldId, label: field.label, type: field.type });
                continue;
            }
            if (field.isAggregate) {
                selectExprs.push({ expr: field.sqlExpr, alias: fieldId });
                columnsMeta.push({ fieldId, label: field.label, type: field.type });
                continue;
            }
            selectExprs.push({ expr: field.sqlExpr, alias: fieldId });
            if (hasAggregates)
                groupByExprs.push(field.sqlExpr);
            if (field.lookup && requiredJoins.has(field.requiresJoin)) {
                const displayAlias = `${fieldId}_display`;
                selectExprs.push({
                    expr: `${field.lookup.joinAlias}.${field.lookup.display}`,
                    alias: displayAlias,
                });
                if (hasAggregates) {
                    groupByExprs.push(`${field.lookup.joinAlias}.${field.lookup.display}`);
                }
                columnsMeta.push({ fieldId, label: field.label, type: field.type, displayAlias });
            }
            else {
                columnsMeta.push({ fieldId, label: field.label, type: field.type });
            }
        }
        if (selectExprs.length === 0) {
            throw new common_1.BadRequestException('Nenhuma coluna válida para exibição após filtrar campos filter-only.');
        }
        const buildBase = () => {
            const qb = this.sPrdRepository
                .createQueryBuilder('sp')
                .where('sp.prd_cmp = :competence', { competence });
            this.attachJoins(qb, requiredJoins);
            this.attachFilters(qb, filters);
            return qb;
        };
        const dataQb = buildBase();
        const [firstSel, ...restSel] = selectExprs;
        dataQb.select(firstSel.expr, firstSel.alias);
        for (const s of restSel)
            dataQb.addSelect(s.expr, s.alias);
        if (hasAggregates && groupByExprs.length > 0) {
            dataQb.groupBy(groupByExprs[0]);
            for (const g of groupByExprs.slice(1))
                dataQb.addGroupBy(g);
        }
        if (sort) {
            const sortField = field_catalog_1.SIA_PRODUCAO_FIELDS[sort.fieldId];
            const sortExpr = sortField.filterExpr ?? sortField.sqlExpr;
            dataQb.orderBy(sortExpr, sort.direction);
        }
        let totalRows;
        if (hasAggregates && groupByExprs.length > 0) {
            const innerQb = buildBase().select('1');
            innerQb.groupBy(groupByExprs[0]);
            for (const g of groupByExprs.slice(1))
                innerQb.addGroupBy(g);
            const [innerSql, innerParams] = innerQb.getQueryAndParameters();
            const countResult = await this.sPrdRepository.manager.query(`SELECT COUNT(*) AS cnt FROM (${innerSql}) sub`, innerParams);
            totalRows = parseInt(countResult[0].cnt, 10);
        }
        else {
            totalRows = await buildBase().getCount();
        }
        const skip = (page - 1) * pageSize;
        const startTime = Date.now();
        const rows = await dataQb.skip(skip).take(pageSize).getRawMany();
        const queryTimeMs = Date.now() - startTime;
        const stringPatternCount = filters.filter(f => ['like', 'starts_with', 'ends_with'].includes(f.operator)).length;
        const heavyQuery = stringPatternCount > 2 || select.includes('cismetro_total');
        const warning = heavyQuery
            ? 'Query potencialmente lenta. Para exportação completa, use POST /reports/jobs com type="sia-aggregated".'
            : undefined;
        return {
            columns: columnsMeta,
            rows,
            meta: {
                totalRows,
                page,
                pageSize,
                totalPages: Math.ceil(totalRows / pageSize),
                queryTimeMs,
                hasAggregates,
                ...(warning ? { warning } : {}),
            },
        };
    }
    validateSelectFields(select) {
        if (!select?.length) {
            throw new common_1.BadRequestException('Informe pelo menos um campo em "select".');
        }
        for (const fieldId of select) {
            const field = field_catalog_1.SIA_PRODUCAO_FIELDS[fieldId];
            if (!field)
                throw new common_1.BadRequestException(`Campo inválido: "${fieldId}".`);
            if (field.filterOnly) {
                throw new common_1.BadRequestException(`Campo "${fieldId}" é somente-filtro e não pode aparecer em "select".`);
            }
        }
    }
    validateFilterValues(filters) {
        for (const f of filters) {
            const field = field_catalog_1.SIA_PRODUCAO_FIELDS[f.fieldId];
            if (!field)
                throw new common_1.BadRequestException(`Campo de filtro inválido: "${f.fieldId}".`);
            if (!(0, field_catalog_1.isOperatorAllowed)(field, f.operator)) {
                throw new common_1.BadRequestException(`Operador "${f.operator}" não é permitido para "${f.fieldId}". ` +
                    `Válidos: ${field.allowedOperators.join(', ')}.`);
            }
            if (field.displayOnly) {
                throw new common_1.BadRequestException(`Campo "${f.fieldId}" é somente-exibição e não aceita filtros.`);
            }
            if (f.operator === 'between') {
                if (!Array.isArray(f.value) || f.value.length !== 2) {
                    throw new common_1.BadRequestException(`"between" em "${f.fieldId}" requer array com exatamente 2 elementos.`);
                }
            }
            else if (f.operator === 'in') {
                if (!Array.isArray(f.value) || f.value.length === 0) {
                    throw new common_1.BadRequestException(`"in" em "${f.fieldId}" requer array não-vazio.`);
                }
            }
            else {
                if (Array.isArray(f.value)) {
                    throw new common_1.BadRequestException(`Operador "${f.operator}" em "${f.fieldId}" requer valor único, não array.`);
                }
            }
        }
    }
    validateSortField(fieldId) {
        const field = field_catalog_1.SIA_PRODUCAO_FIELDS[fieldId];
        if (!field)
            throw new common_1.BadRequestException(`Campo de ordenação inválido: "${fieldId}".`);
        if (!field.sortable) {
            throw new common_1.BadRequestException(`Campo "${fieldId}" não suporta ordenação.`);
        }
    }
    attachJoins(qb, joins) {
        if (joins.has('prestador')) {
            qb.leftJoin('prestador', 'pr', 'sp.prd_uid = pr.re_cunid');
        }
        if (joins.has('cbo')) {
            qb.leftJoin('cbo', 'cb', 'sp.prd_cbo = cb.cbo');
        }
        if (joins.has('procedimento')) {
            qb.leftJoin('procedimento', 'pc', 'sp.prd_pa = pc.codigo');
        }
        if (joins.has('s_rub')) {
            qb.leftJoin('s_rub', 'sr', 'sp.prd_rub = sr.RUB_ID');
        }
        if (joins.has('cismetro')) {
            qb.leftJoin('cismetro', 'cs', 'sp.prd_pa = cs.codigo');
        }
    }
    attachFilters(qb, filters) {
        for (let i = 0; i < filters.length; i++) {
            const { fieldId, operator, value } = filters[i];
            const paramKey = `dynF${i}`;
            if (fieldId === 'procedimento_descricao') {
                const val = value;
                const pattern = operator === 'like' ? `%${val}%`
                    : operator === 'starts_with' ? `${val}%`
                        : operator === 'ends_with' ? `%${val}`
                            : val;
                qb.andWhere(`sp.prd_pa IN (SELECT codigo FROM procedimento WHERE procedimento LIKE :${paramKey})`, { [paramKey]: pattern });
                continue;
            }
            const field = field_catalog_1.SIA_PRODUCAO_FIELDS[fieldId];
            const filterExpr = field.filterExpr ?? field.sqlExpr;
            (0, field_catalog_1.applyOperator)(qb, filterExpr, operator, value, paramKey);
        }
    }
};
exports.SiaService = SiaService;
exports.SiaService = SiaService = __decorate([
    (0, common_1.Injectable)(),
    __param(0, (0, typeorm_1.InjectRepository)(s_prd_entity_1.SPrd)),
    __metadata("design:paramtypes", [typeorm_2.Repository])
], SiaService);
//# sourceMappingURL=sia.service.js.map