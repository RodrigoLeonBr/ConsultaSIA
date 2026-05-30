export interface FieldMetadata {
  id: string;
  label: string;
  type: string;
  allowedOperators: string[];
  sortable: boolean;
  groupable: boolean;
  isAggregate: boolean;
  filterOnly: boolean;
  displayOnly: boolean;
}

export interface MetadataResponse {
  producao: {
    description: string;
    fields: FieldMetadata[];
  };
  limits: {
    maxSelect: number;
    maxFilters: number;
    maxPageSize: number;
  };
}

export interface Column {
  fieldId: string;
  label: string;
  type: string;
  displayAlias?: string;
}

export interface QueryMeta {
  totalRows: number;
  page: number;
  pageSize: number;
  totalPages: number;
  queryTimeMs: number;
  hasAggregates: boolean;
  warning: string | null;
}

export interface DynamicResult {
  columns: Column[];
  rows: Record<string, unknown>[];
  meta: QueryMeta;
}

export interface Job {
  id: number;
  status: 'queued' | 'running' | 'done' | 'failed';
  type: string;
  parameters: unknown;
  createdAt: string;
  startedAt?: string | null;
  completedAt?: string | null;
  errorMessage?: string | null;
}

export interface JobResults {
  columns: Column[];
  data: Record<string, unknown>[];
  meta: {
    page: number;
    limit: number;
    totalRowsFetched: number;
  };
}
