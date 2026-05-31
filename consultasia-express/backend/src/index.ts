import 'dotenv/config';
import express from 'express';
import cors from 'cors';
import { timingMiddleware } from './middleware/timing';
import { errorHandler } from './middleware/error-handler';
import { siaRouter } from './routes/sia.routes';
import { reportsRouter } from './routes/reports.routes';

const isWorker = process.env.RUN_WORKER === 'true';

async function main() {
  if (isWorker) {
    // Worker mode: start polling loop only, no HTTP server
    const { workerService } = await import('./services/worker.service');
    console.log('[Worker] Starting polling loop...');
    workerService.start();
    return;
  }

  const app = express();
  const PORT = Number(process.env.PORT ?? 3001);

  app.use(cors({
    origin: process.env.CORS_ORIGIN ?? 'http://localhost:5174',
    methods: ['GET', 'POST'],
  }));
  app.use(express.json());
  app.use(timingMiddleware);

  app.get('/health', (_req, res) => {
    res.json({ status: 'ok', ts: new Date().toISOString() });
  });

  // API routes
  app.use('/reports/sia', siaRouter);
  app.use('/reports', reportsRouter);

  // Global error handler (must be last)
  app.use(errorHandler as express.ErrorRequestHandler);

  app.listen(PORT, () => {
    console.log(`[API] Listening on http://localhost:${PORT}`);
  });
}

main().catch(err => {
  console.error('[Fatal]', err);
  process.exit(1);
});
