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
