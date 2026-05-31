import { Router, Request, Response, NextFunction } from 'express';
import { siaProductionSchema } from '../validation/sia.schema';
import { siaService } from '../services/sia.service';

export const siaRouter = Router();

// GET /reports/sia/metadata
siaRouter.get('/metadata', (_req: Request, res: Response): void => {
  res.json(siaService.getMetadata());
});

// GET /reports/sia?competence=202301&page=1&pageSize=50
siaRouter.get('/', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const { competence, page = '1', pageSize = '50' } = req.query as Record<string, string>;
    if (!competence || competence.length !== 6) {
      res.status(400).json({ statusCode: 400, message: 'competence obrigatório (6 chars AAAAMM)' });
      return;
    }
    const result = await siaService.getSimpleList(competence, Number(page), Number(pageSize));
    res.json(result);
  } catch (err) {
    next(err);
  }
});

// GET /reports/sia/faturamento-prestador?competence=AAAAMM&page=1&pageSize=200
siaRouter.get('/faturamento-prestador', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const { competence, page = '1', pageSize = '200' } = req.query as Record<string, string>;
    if (!competence || competence.length !== 6) {
      res.status(400).json({ statusCode: 400, message: 'competence obrigatório (6 chars AAAAMM)' });
      return;
    }
    const p = Math.max(1, Number(page));
    const ps = Math.min(500, Math.max(1, Number(pageSize)));
    const offset = (p - 1) * ps;
    const start = Date.now();
    const { pool } = await import('../db');
    const [[countRows], [rows]] = await Promise.all([
      pool.execute(
        `SELECT COUNT(*) AS cnt FROM (
          SELECT sp.prd_uid FROM s_prd sp WHERE sp.prd_cmp = ?
          GROUP BY sp.prd_uid, sp.prd_rub, sp.grupo, sp.subgrupo, sp.forma, sp.prd_pa
        ) AS sub`,
        [competence]
      ) as Promise<[{ cnt: number }[], unknown]>,
      pool.execute(
        `SELECT sp.prd_uid, pr.re_cnome AS prestador_nome,
                sp.prd_rub AS tipo_financiamento,
                sp.grupo, sp.subgrupo, sp.forma,
                sp.prd_pa AS procedimento_codigo, pc.procedimento AS procedimento_nome,
                CAST(pc.PA_TOTAL AS DECIMAL(15,2)) AS valor_unitario,
                SUM(CAST(sp.PRD_QT_A AS UNSIGNED)) AS qty_aprovada,
                SUM(CAST(sp.PRD_VL_A AS DECIMAL(15,2))) AS valor_aprovado,
                SUM(CAST(sp.PRD_QT_P AS UNSIGNED)) AS qty_apresentada,
                SUM(CAST(sp.PRD_QT_P AS UNSIGNED) * CAST(pc.PA_TOTAL AS DECIMAL(15,2))) AS valor_apresentado
         FROM s_prd sp
         LEFT JOIN prestador pr ON sp.prd_uid = pr.re_cunid
         LEFT JOIN procedimento pc ON sp.prd_pa = pc.codigo
         WHERE sp.prd_cmp = ?
         GROUP BY sp.prd_uid, pr.re_cnome, sp.prd_rub, sp.grupo, sp.subgrupo, sp.forma, sp.prd_pa, pc.procedimento, pc.PA_TOTAL
         ORDER BY pr.re_cnome, sp.prd_rub, sp.grupo, sp.subgrupo, sp.forma
         LIMIT ? OFFSET ?`,
        [competence, ps, offset]
      ) as Promise<[Record<string, unknown>[], unknown]>,
    ]);
    const totalRows = Number((countRows as { cnt: number }[])[0]?.cnt ?? 0);
    res.json({
      data: rows,
      meta: { totalRows, page: p, pageSize: ps, totalPages: Math.ceil(totalRows / ps), queryTimeMs: Date.now() - start },
    });
  } catch (err) { next(err); }
});

// POST /reports/sia/production
siaRouter.post('/production', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const parsed = siaProductionSchema.safeParse(req.body);
    if (!parsed.success) {
      res.status(400).json({
        statusCode: 400,
        message: parsed.error.issues.map((e) => e.message),
        error: 'Bad Request',
      });
      return;
    }
    const result = await siaService.getDynamicProduction(parsed.data);
    res.json(result);
  } catch (err) {
    next(err);
  }
});
