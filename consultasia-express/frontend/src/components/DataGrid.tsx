import type { Column } from '../types';

interface DataGridProps {
  columns: Column[];
  rows: Record<string, unknown>[];
  page: number;
  pageSize: number;
  totalRows: number;
  onPageChange: (page: number) => void;
  onPageSizeChange: (size: number) => void;
  loading?: boolean;
  queryTimeMs?: number;
}

export function DataGrid({
  columns, rows, page, pageSize, totalRows,
  onPageChange, onPageSizeChange, loading, queryTimeMs,
}: DataGridProps) {
  const totalPages = Math.ceil(totalRows / pageSize);

  return (
    <div style={{ fontFamily: 'monospace', fontSize: 13 }}>
      <div style={{ color: '#666', marginBottom: 8, fontSize: 12 }}>
        {queryTimeMs !== undefined && `Tempo: ${queryTimeMs}ms — `}
        {totalRows.toLocaleString('pt-BR')} registros
        {loading && ' — Carregando...'}
      </div>
      <div style={{ overflowX: 'auto' }}>
        <table style={{ borderCollapse: 'collapse', width: '100%', minWidth: 400 }}>
          <thead>
            <tr>
              {columns.map(col => (
                <th
                  key={col.fieldId}
                  style={{
                    border: '1px solid #ccc',
                    padding: '6px 10px',
                    background: '#f0f4f8',
                    textAlign: 'left',
                    whiteSpace: 'nowrap',
                    fontSize: 12,
                    fontWeight: 'bold',
                  }}
                >
                  {col.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody>
            {rows.length === 0 && !loading && (
              <tr>
                <td colSpan={columns.length} style={{ padding: 16, textAlign: 'center', color: '#888' }}>
                  Nenhum resultado encontrado.
                </td>
              </tr>
            )}
            {rows.map((row, i) => (
              <tr key={i} style={{ background: i % 2 === 0 ? '#fff' : '#fafafa' }}>
                {columns.map(col => {
                  const value = col.displayAlias
                    ? (row[col.displayAlias] ?? row[col.fieldId])
                    : row[col.fieldId];
                  return (
                    <td
                      key={col.fieldId}
                      style={{
                        border: '1px solid #eee',
                        padding: '4px 10px',
                        whiteSpace: 'nowrap',
                        maxWidth: 300,
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                      }}
                    >
                      {String(value ?? '')}
                    </td>
                  );
                })}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      <div style={{ marginTop: 10, display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' }}>
        <button
          onClick={() => onPageChange(1)}
          disabled={page <= 1}
          style={{ padding: '2px 8px' }}
        >
          «
        </button>
        <button
          onClick={() => onPageChange(page - 1)}
          disabled={page <= 1}
          style={{ padding: '2px 8px' }}
        >
          ‹
        </button>
        <span style={{ fontSize: 12 }}>Página {page} de {totalPages || 1}</span>
        <button
          onClick={() => onPageChange(page + 1)}
          disabled={page >= totalPages}
          style={{ padding: '2px 8px' }}
        >
          ›
        </button>
        <button
          onClick={() => onPageChange(totalPages)}
          disabled={page >= totalPages}
          style={{ padding: '2px 8px' }}
        >
          »
        </button>
        <select
          value={pageSize}
          onChange={e => onPageSizeChange(Number(e.target.value))}
          style={{ marginLeft: 8 }}
        >
          {[50, 100, 200, 500].map(n => (
            <option key={n} value={n}>{n}/página</option>
          ))}
        </select>
      </div>
    </div>
  );
}
