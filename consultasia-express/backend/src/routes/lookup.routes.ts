import { Router, Request, Response, NextFunction } from 'express';
import { lookupService } from '../services/lookup.service';

export const lookupRouter = Router();

function parsePagination(query: Record<string, string>) {
  const page = Math.max(1, Number(query.page ?? 1));
  const pageSize = Math.min(500, Math.max(1, Number(query.pageSize ?? 50)));
  const search = String(query.search ?? '');
  return { page, pageSize, search };
}

lookupRouter.get('/prestadores', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const { search, page, pageSize } = parsePagination(req.query as Record<string, string>);
    const result = await lookupService.listPrestadores(search, page, pageSize);
    res.json(result);
  } catch (err) { next(err); }
});

lookupRouter.get('/procedimentos', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const { search, page, pageSize } = parsePagination(req.query as Record<string, string>);
    const result = await lookupService.listProcedimentos(search, page, pageSize);
    res.json(result);
  } catch (err) { next(err); }
});

lookupRouter.get('/cbos', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const { search, page, pageSize } = parsePagination(req.query as Record<string, string>);
    const result = await lookupService.listCbos(search, page, pageSize);
    res.json(result);
  } catch (err) { next(err); }
});

lookupRouter.get('/rubricas', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const { search, page, pageSize } = parsePagination(req.query as Record<string, string>);
    const result = await lookupService.listRubricas(search, page, pageSize);
    res.json(result);
  } catch (err) { next(err); }
});
