import { pool } from '../db';
import { SIA_PRODUCAO_FIELDS, LIMITS, METADATA_RESPONSE } from '../field-catalog';
import type { SiaProductionQuery } from '../validation/sia.schema';
import { AppError } from '../middleware/error-handler';

export class SiaService {

  getMetadata() {
    return METADATA_RESPONSE;
  }

  async getDynamicProduction(query: SiaProductionQuery) {
    const { competence, select, filters = [], page, pageSize, sort } = query;

    // Validate select fields
    for (const fieldId of select) {
      const field = SIA_PRODUCAO_FIELDS[fieldId];
      if (!field) throw new AppError(400, `Campo "${fieldId}" não existe no catálogo.`);
      if (field.filterOnly) throw new AppError(400, `Campo "${fieldId}" é somente-filtro e não pode aparecer em "select".`);
    }

    // Validate filter fields and operators
    for (const filter of filters) {
      const field = SIA_PRODUCAO_FIELDS[filter.fieldId];
      if (!field) throw new AppError(400, `Filtro fieldId "${filter.fieldId}" inválido.`);
      if (!field.allowedOperators.includes(filter.operator as any)) {
        throw new AppError(
          400,
          `Operador "${filter.operator}" não permitido para "${filter.fieldId}". Válidos: ${field.allowedOperators.join(', ')}.`,
        );
      }
    }

    // Determine JOINs needed (from selected + filtered fields)
    const joinsNeeded = new Set<string>();
    for (const fieldId of select) {
      const f = SIA_PRODUCAO_FIELDS[fieldId];
      if (f?.requiresJoin) joinsNeeded.add(f.requiresJoin);
    }
    for (const filter of filters) {
      const f = SIA_PRODUCAO_FIELDS[filter.fieldId];
      // procedimento_descricao uses subquery — no JOIN on main query
      if (f?.requiresJoin && filter.fieldId !== 'procedimento_descricao') {
        joinsNeeded.add(f.requiresJoin);
      }
    }

    // Determine if GROUP BY mode applies
    const hasAggregate = select.some(id => SIA_PRODUCAO_FIELDS[id]?.isAggregate);

    // Build SELECT clause
    const selectParts: string[] = [];
    const columns: object[] = [];

    for (const fieldId of select) {
      const field = SIA_PRODUCAO_FIELDS[fieldId]!;

      if (hasAggregate && field.isAggregate) {
        // Wrap with SUM if not already wrapped
        const sumExpr = field.sqlExpr.startsWith('SUM(')
          ? field.sqlExpr
          : `SUM(${field.sqlExpr})`;
        selectParts.push(`${sumExpr} AS \`${fieldId}\``);
      } else {
        selectParts.push(`${field.sqlExpr} AS \`${fieldId}\``);
      }

      columns.push({
        fieldId,
        label: field.label,
        type: field.type,
        ...(field.lookup ? { displayAlias: field.lookup.displayAlias } : {}),
      });

      // Add display companion column for lookup fields
      if (field.lookup) {
        selectParts.push(`${field.lookup.displayCol} AS \`${field.lookup.displayAlias}\``);
      }
    }

    // Build FROM + JOINs
    const joinClauses: string[] = [];
    if (joinsNeeded.has('prestador'))    joinClauses.push('LEFT JOIN prestador pr ON sp.prd_uid = pr.re_cunid');
    if (joinsNeeded.has('cbo'))          joinClauses.push('LEFT JOIN cbo cb ON sp.prd_cbo = cb.cbo');
    if (joinsNeeded.has('procedimento')) joinClauses.push('LEFT JOIN procedimento pc ON sp.prd_pa = pc.codigo');
    if (joinsNeeded.has('s_rub'))        joinClauses.push('LEFT JOIN s_rub sr ON sp.prd_rub = sr.RUB_ID');
    if (joinsNeeded.has('cismetro'))     joinClauses.push('LEFT JOIN cismetro cs ON sp.prd_pa = cs.codigo');

    const fromClause = `FROM s_prd sp${joinClauses.length ? ' ' + joinClauses.join(' ') : ''}`;

    // Build WHERE clause (always starts with competence)
    const whereParts: string[] = ['sp.prd_cmp = ?'];
    const params: unknown[] = [competence];

    for (const filter of filters) {
      const field = SIA_PRODUCAO_FIELDS[filter.fieldId]!;

      // Special case: procedimento_descricao uses filterExpr with subquery
      if (filter.fieldId === 'procedimento_descricao') {
        const val = String(filter.value);
        const likeVal =
          filter.operator === 'starts_with' ? `${val}%`
          : filter.operator === 'ends_with'  ? `%${val}`
          : `%${val}%`;
        whereParts.push(`sp.prd_pa IN (SELECT codigo FROM procedimento WHERE procedimento LIKE ?)`);
        params.push(likeVal);
        continue;
      }

      const expr = field.sqlExpr;

      if (filter.operator === 'between') {
        const arr = Array.isArray(filter.value) ? filter.value : [];
        if (arr.length !== 2) {
          throw new AppError(400, `"between" em "${filter.fieldId}" requer array com exatamente 2 elementos.`);
        }
        whereParts.push(`${expr} BETWEEN ? AND ?`);
        params.push(arr[0], arr[1]);
      } else if (filter.operator === 'in') {
        const arr = Array.isArray(filter.value) ? filter.value : [];
        if (arr.length === 0) {
          throw new AppError(400, `"in" em "${filter.fieldId}" requer array não-vazio.`);
        }
        whereParts.push(`${expr} IN (${arr.map(() => '?').join(',')})`);
        params.push(...arr);
      } else if (filter.operator === 'like') {
        whereParts.push(`${expr} LIKE ?`);
        params.push(`%${String(filter.value)}%`);
      } else if (filter.operator === 'starts_with') {
        whereParts.push(`${expr} LIKE ?`);
        params.push(`${String(filter.value)}%`);
      } else if (filter.operator === 'ends_with') {
        whereParts.push(`${expr} LIKE ?`);
        params.push(`%${String(filter.value)}`);
      } else {
        // =, >, <, >=, <=
        whereParts.push(`${expr} ${filter.operator} ?`);
        params.push(filter.value);
      }
    }

    const whereClause = `WHERE ${whereParts.join(' AND ')}`;

    // GROUP BY clause — include all non-aggregate groupable fields and their display expressions
    let groupByClause = '';
    if (hasAggregate) {
      const groupByExprs: string[] = [];
      for (const fieldId of select) {
        const field = SIA_PRODUCAO_FIELDS[fieldId]!;
        if (field.groupable && !field.isAggregate) {
          groupByExprs.push(field.sqlExpr);
          if (field.lookup) groupByExprs.push(field.lookup.displayCol);
        }
      }
      if (groupByExprs.length > 0) {
        groupByClause = `GROUP BY ${groupByExprs.join(', ')}`;
      }
    }

    // ORDER BY
    let orderByClause = '';
    if (sort) {
      const sortField = SIA_PRODUCAO_FIELDS[sort.fieldId];
      if (!sortField) throw new AppError(400, `sort.fieldId "${sort.fieldId}" inválido.`);
      if (!sortField.sortable) throw new AppError(400, `Campo "${sort.fieldId}" não é sortable.`);
      const sortExpr =
        hasAggregate && sortField.isAggregate && !sortField.sqlExpr.startsWith('SUM(')
          ? `SUM(${sortField.sqlExpr})`
          : sortField.sqlExpr;
      orderByClause = `ORDER BY ${sortExpr} ${sort.direction}`;
    }

    // Pagination
    const pageNum = page ?? 1;
    const pageSz  = pageSize ?? 50;
    const offset  = (pageNum - 1) * pageSz;

    // Count query (wrap in subquery if GROUP BY is used, to count groups)
    const countInner = `SELECT 1 ${fromClause} ${whereClause} ${groupByClause}`.trim();
    const countSql = groupByClause
      ? `SELECT COUNT(*) AS cnt FROM (${countInner}) AS _cnt`
      : `SELECT COUNT(*) AS cnt ${fromClause} ${whereClause}`;

    const dataSql = [
      `SELECT ${selectParts.join(', ')}`,
      fromClause,
      whereClause,
      groupByClause,
      orderByClause,
      `LIMIT ${pageSz} OFFSET ${offset}`,
    ].filter(Boolean).join(' ');

    const start = Date.now();

    // Use pool.execute() directly for parameterized raw SQL (drizzle sql.raw does not support params)
    const [[countRows], [dataRows]] = await Promise.all([
      pool.execute(countSql, params),
      pool.execute(dataSql, params),
    ]);

    const queryTimeMs = Date.now() - start;
    const totalRows   = Number((countRows as any)[0]?.cnt ?? 0);

    // Heavy query warning
    const likeFilterCount = filters.filter(
      f => ['like', 'starts_with', 'ends_with'].includes(f.operator),
    ).length;
    const hasCismetroTotal = select.includes('cismetro_total');
    const warning =
      likeFilterCount > 2 || hasCismetroTotal
        ? 'Query potencialmente lenta. Para exportação completa, use POST /reports/jobs com type="sia-dynamic-production".'
        : null;

    return {
      columns,
      rows: dataRows,
      meta: {
        totalRows,
        page: pageNum,
        pageSize: pageSz,
        totalPages: Math.ceil(totalRows / pageSz),
        queryTimeMs,
        hasAggregates: hasAggregate,
        warning,
      },
    };
  }

  async getSimpleList(competence: string, page = 1, pageSize = 50) {
    if (competence.length !== 6) {
      throw new AppError(400, 'competence deve ter exatamente 6 caracteres (AAAAMM)');
    }
    const offset = (page - 1) * pageSize;

    const start = Date.now();

    const [[countRows], [dataRows]] = await Promise.all([
      pool.execute(
        'SELECT COUNT(*) AS cnt FROM s_prd sp WHERE sp.prd_cmp = ?',
        [competence],
      ),
      pool.execute(
        `SELECT sp.prd_uid, sp.prd_cmp, sp.prd_pa, sp.grupo, sp.subgrupo, sp.forma,
                CAST(sp.PRD_QT_P AS UNSIGNED) AS PRD_QT_P,
                CAST(sp.PRD_VL_P AS DECIMAL(15,2)) AS PRD_VL_P,
                CAST(sp.PRD_QT_A AS UNSIGNED) AS PRD_QT_A,
                CAST(sp.PRD_VL_A AS DECIMAL(15,2)) AS PRD_VL_A
         FROM s_prd sp
         WHERE sp.prd_cmp = ?
         LIMIT ? OFFSET ?`,
        [competence, pageSize, offset],
      ),
    ]);

    const totalRows = Number((countRows as any)[0]?.cnt ?? 0);
    return {
      data: dataRows,
      meta: {
        totalRows,
        page,
        pageSize,
        totalPages: Math.ceil(totalRows / pageSize),
        queryTimeMs: Date.now() - start,
      },
    };
  }
}

export const siaService = new SiaService();
