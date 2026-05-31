import React from 'react';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';

interface PaginationProps {
  page: number;
  pageSize: number;
  totalRows: number;
  onPageChange: (page: number) => void;
  onPageSizeChange: (size: number) => void;
  queryTimeMs?: number;
  className?: string;
}

const PAGE_SIZE_OPTIONS = [50, 100, 200, 500];

export function Pagination({ page, pageSize, totalRows, onPageChange, onPageSizeChange, queryTimeMs, className = '' }: PaginationProps) {
  const totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
  const from = totalRows === 0 ? 0 : (page - 1) * pageSize + 1;
  const to = Math.min(page * pageSize, totalRows);

  return (
    <div className={`flex items-center justify-between mt-3 text-sm text-slate-600 ${className}`}>
      <div className="flex items-center gap-3">
        <span className="text-xs">
          {from}–{to} de {totalRows.toLocaleString('pt-BR')} registros
          {queryTimeMs !== undefined && ` · ${queryTimeMs}ms`}
        </span>
        <select
          value={pageSize}
          onChange={e => onPageSizeChange(Number(e.target.value))}
          className="border border-slate-300 rounded px-2 py-1 text-xs bg-white"
        >
          {PAGE_SIZE_OPTIONS.map(n => (
            <option key={n} value={n}>{n}/pág</option>
          ))}
        </select>
      </div>
      <div className="flex items-center gap-1">
        <button onClick={() => onPageChange(1)} disabled={page <= 1} className="p-1 rounded hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">
          <ChevronsLeft className="w-4 h-4" />
        </button>
        <button onClick={() => onPageChange(page - 1)} disabled={page <= 1} className="p-1 rounded hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">
          <ChevronLeft className="w-4 h-4" />
        </button>
        <span className="px-3 text-xs font-medium">Pág {page} / {totalPages}</span>
        <button onClick={() => onPageChange(page + 1)} disabled={page >= totalPages} className="p-1 rounded hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">
          <ChevronRight className="w-4 h-4" />
        </button>
        <button onClick={() => onPageChange(totalPages)} disabled={page >= totalPages} className="p-1 rounded hover:bg-slate-100 disabled:opacity-40 disabled:cursor-not-allowed">
          <ChevronsRight className="w-4 h-4" />
        </button>
      </div>
    </div>
  );
}
