import { z } from 'zod';
import { SIA_PRODUCAO_FIELDS, LIMITS } from '../field-catalog';

const validFieldIds = Object.keys(SIA_PRODUCAO_FIELDS);

const filterItemSchema = z.object({
  fieldId: z.string().refine(id => validFieldIds.includes(id), { message: 'fieldId inválido no catálogo' }),
  operator: z.enum(['=', '>', '<', '>=', '<=', 'between', 'in', 'like', 'starts_with', 'ends_with']),
  value: z.union([z.string(), z.array(z.string()).min(1)]),
});

export const siaProductionSchema = z.object({
  competence: z.string().length(6, 'competence deve ter exatamente 6 caracteres (AAAAMM)'),
  select: z.array(z.string()).min(1).max(LIMITS.maxSelect),
  filters: z.array(filterItemSchema).max(LIMITS.maxFilters).optional().default([]),
  page: z.number().int().min(1).optional().default(1),
  pageSize: z.number().int().min(1).max(LIMITS.maxPageSize).optional().default(50),
  sort: z.object({
    fieldId: z.string(),
    direction: z.enum(['ASC', 'DESC']),
  }).optional(),
});

export type SiaProductionQuery = z.infer<typeof siaProductionSchema>;
export type FilterItem = z.infer<typeof filterItemSchema>;
