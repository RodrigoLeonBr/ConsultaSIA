import { z } from 'zod';

const jobTypeEnum = z.enum(['sia-aggregated', 'sia-faturamento-prestador', 'sia-dynamic-production', 'export']);

export const createJobSchema = z.object({
  type: jobTypeEnum,
  parameters: z.object({
    competence: z.string().length(6).optional(),
    select: z.array(z.string()).optional(),
    filters: z.array(z.any()).optional(),
    resultId: z.number().int().optional(),
    format: z.enum(['xlsx', 'csv', 'pdf']).optional(),
  }),
});

export type CreateJobDto = z.infer<typeof createJobSchema>;
