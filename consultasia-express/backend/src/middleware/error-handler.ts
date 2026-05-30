import { Request, Response, NextFunction } from 'express';
import { ZodError } from 'zod';

export class AppError extends Error {
  constructor(public readonly statusCode: number, message: string) {
    super(message);
    this.name = 'AppError';
  }
}

export function errorHandler(err: unknown, req: Request, res: Response, _next: NextFunction): void {
  if (err instanceof ZodError) {
    res.status(400).json({
      statusCode: 400,
      message: err.issues.map((e: { message: string }) => e.message),
      error: 'Bad Request',
    });
    return;
  }
  if (err instanceof AppError) {
    res.status(err.statusCode).json({
      statusCode: err.statusCode,
      message: err.message,
    });
    return;
  }
  console.error('[Unhandled Error]', err);
  res.status(500).json({ statusCode: 500, message: 'Internal server error' });
}
