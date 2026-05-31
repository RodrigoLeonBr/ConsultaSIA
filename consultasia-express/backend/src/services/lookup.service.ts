import { pool } from '../db';

type LookupMeta = { totalRows: number; page: number; pageSize: number };

export class LookupService {

  async listPrestadores(search: string, page: number, pageSize: number) {
    const like = `%${search}%`;
    const offset = (page - 1) * pageSize;
    const [[countRows], [rows]] = await Promise.all([
      pool.execute(
        `SELECT COUNT(*) AS cnt FROM prestador WHERE re_cnome LIKE ? OR re_cunid LIKE ?`,
        [like, like]
      ) as Promise<[{ cnt: number }[], unknown]>,
      pool.execute(
        `SELECT re_cunid AS cnes, re_cnome AS nome, cnpj, tipouni AS tipo
         FROM prestador
         WHERE re_cnome LIKE ? OR re_cunid LIKE ?
         ORDER BY re_cnome
         LIMIT ? OFFSET ?`,
        [like, like, pageSize, offset]
      ) as Promise<[Record<string, unknown>[], unknown]>,
    ]);
    return {
      data: rows,
      meta: { totalRows: Number(countRows[0]?.cnt ?? 0), page, pageSize } as LookupMeta,
    };
  }

  async listProcedimentos(search: string, page: number, pageSize: number) {
    const like = `%${search}%`;
    const offset = (page - 1) * pageSize;
    const [[countRows], [rows]] = await Promise.all([
      pool.execute(
        `SELECT COUNT(*) AS cnt FROM procedimento WHERE procedimento LIKE ? OR codigo LIKE ?`,
        [like, like]
      ) as Promise<[{ cnt: number }[], unknown]>,
      pool.execute(
        `SELECT codigo, procedimento AS descricao,
                CAST(PA_TOTAL AS DECIMAL(15,2)) AS valor_unitario,
                pa_rub AS rubrica
         FROM procedimento
         WHERE procedimento LIKE ? OR codigo LIKE ?
         ORDER BY codigo
         LIMIT ? OFFSET ?`,
        [like, like, pageSize, offset]
      ) as Promise<[Record<string, unknown>[], unknown]>,
    ]);
    return {
      data: rows,
      meta: { totalRows: Number(countRows[0]?.cnt ?? 0), page, pageSize } as LookupMeta,
    };
  }

  async listCbos(search: string, page: number, pageSize: number) {
    const like = `%${search}%`;
    const offset = (page - 1) * pageSize;
    const [[countRows], [rows]] = await Promise.all([
      pool.execute(
        `SELECT COUNT(*) AS cnt FROM cbo WHERE ds_cbo LIKE ? OR cbo LIKE ?`,
        [like, like]
      ) as Promise<[{ cnt: number }[], unknown]>,
      pool.execute(
        `SELECT cbo AS codigo, ds_cbo AS descricao
         FROM cbo
         WHERE ds_cbo LIKE ? OR cbo LIKE ?
         ORDER BY cbo
         LIMIT ? OFFSET ?`,
        [like, like, pageSize, offset]
      ) as Promise<[Record<string, unknown>[], unknown]>,
    ]);
    return {
      data: rows,
      meta: { totalRows: Number(countRows[0]?.cnt ?? 0), page, pageSize } as LookupMeta,
    };
  }

  async listRubricas(search: string, page: number, pageSize: number) {
    const like = `%${search}%`;
    const offset = (page - 1) * pageSize;
    const [[countRows], [rows]] = await Promise.all([
      pool.execute(
        `SELECT COUNT(*) AS cnt FROM s_rub WHERE RUB_DC LIKE ? OR RUB_ID LIKE ?`,
        [like, like]
      ) as Promise<[{ cnt: number }[], unknown]>,
      pool.execute(
        `SELECT RUB_ID AS id, RUB_DC AS descricao
         FROM s_rub
         WHERE RUB_DC LIKE ? OR RUB_ID LIKE ?
         ORDER BY RUB_ID
         LIMIT ? OFFSET ?`,
        [like, like, pageSize, offset]
      ) as Promise<[Record<string, unknown>[], unknown]>,
    ]);
    return {
      data: rows,
      meta: { totalRows: Number(countRows[0]?.cnt ?? 0), page, pageSize } as LookupMeta,
    };
  }
}

export const lookupService = new LookupService();
