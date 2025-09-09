export interface DashboardStats {
  totalProcedures: number;
  totalValue: number;
  activePrestadores: number;
  occupancyRate: number;
}

export interface ReportFilter {
  field: string;
  operator: string;
  value: string | string[];
  logicalOperator?: 'AND' | 'OR';
}

export interface ReportConfig {
  fields: string[];
  filters: ReportFilter[];
  format: 'csv' | 'excel' | 'pdf';
}

export interface PaginationParams {
  page: number;
  limit: number;
  search?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  total: number;
}
