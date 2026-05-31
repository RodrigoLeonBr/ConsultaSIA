import React from 'react';
import { useReactTable, getCoreRowModel, flexRender, type ColumnDef } from '@tanstack/react-table';
import { Spinner } from './Spinner';

interface TableProps<T extends object> {
  columns: ColumnDef<T, unknown>[];
  data: T[];
  loading?: boolean;
  emptyMessage?: string;
}

export function Table<T extends object>({ columns, data, loading, emptyMessage = 'Nenhum registro encontrado.' }: TableProps<T>) {
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    manualPagination: true,
  });

  return (
    <div className="relative overflow-x-auto rounded-md border border-slate-200">
      {loading && (
        <div className="absolute inset-0 bg-white/70 flex items-center justify-center z-10">
          <Spinner size="lg" />
        </div>
      )}
      <table className="w-full text-sm text-left text-slate-700">
        <thead className="text-xs text-slate-500 uppercase tracking-wide bg-slate-50 border-b border-slate-200">
          {table.getHeaderGroups().map(hg => (
            <tr key={hg.id}>
              {hg.headers.map(h => (
                <th key={h.id} className="px-4 py-3 font-semibold whitespace-nowrap">
                  {flexRender(h.column.columnDef.header, h.getContext())}
                </th>
              ))}
            </tr>
          ))}
        </thead>
        <tbody>
          {table.getRowModel().rows.length === 0 && !loading ? (
            <tr>
              <td colSpan={columns.length} className="px-4 py-8 text-center text-slate-400">
                {emptyMessage}
              </td>
            </tr>
          ) : (
            table.getRowModel().rows.map((row, i) => (
              <tr key={row.id} className={`border-b border-slate-100 hover:bg-slate-50 transition-colors ${i % 2 === 1 ? 'bg-slate-50/50' : 'bg-white'}`}>
                {row.getVisibleCells().map(cell => (
                  <td key={cell.id} className="px-4 py-2.5 whitespace-nowrap">
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </td>
                ))}
              </tr>
            ))
          )}
        </tbody>
      </table>
    </div>
  );
}
