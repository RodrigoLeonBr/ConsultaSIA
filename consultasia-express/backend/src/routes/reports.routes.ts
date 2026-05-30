import { Router, Request, Response, NextFunction } from 'express';
import { createJobSchema } from '../validation/job.schema';
import { reportsService } from '../services/reports.service';
import path from 'path';
import fs from 'fs';

export const reportsRouter = Router();

// POST /reports/jobs
reportsRouter.post('/jobs', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const parsed = createJobSchema.safeParse(req.body);
    if (!parsed.success) {
      res.status(400).json({
        statusCode: 400,
        message: parsed.error.issues.map((e: { message: string }) => e.message),
        error: 'Bad Request',
      });
      return;
    }
    const job = await reportsService.createJob(parsed.data.type, parsed.data.parameters);
    res.status(202).json(job);
  } catch (err) {
    next(err);
  }
});

// GET /reports/jobs/:id
reportsRouter.get('/jobs/:id', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const job = await reportsService.getJob(Number(req.params.id));
    res.json(job);
  } catch (err) {
    next(err);
  }
});

// GET /reports/jobs/:id/results?page=1&limit=200
reportsRouter.get('/jobs/:id/results', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const { page = '1', limit = '200' } = req.query as Record<string, string>;
    const result = await reportsService.getJobResults(Number(req.params.id), Number(page), Number(limit));
    res.json(result);
  } catch (err) {
    next(err);
  }
});

// GET /reports/jobs/:id/download
reportsRouter.get('/jobs/:id/download', async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const filePath = await reportsService.getDownloadPath(Number(req.params.id));
    if (!fs.existsSync(filePath)) {
      res.status(404).json({ statusCode: 404, message: 'Arquivo não encontrado no sistema.' });
      return;
    }
    res.download(filePath, path.basename(filePath));
  } catch (err) {
    next(err);
  }
});
