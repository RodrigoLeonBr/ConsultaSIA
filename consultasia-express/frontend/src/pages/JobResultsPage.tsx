import { useEffect, useState, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import { getJobResults, createJob, getJob } from '../api';
import { DataGrid } from '../components/DataGrid';
import type { Column } from '../types';

export function JobResultsPage() {
  const { jobId } = useParams<{ jobId: string }>();
  const [data, setData] = useState<Record<string, unknown>[]>([]);
  const [columns, setColumns] = useState<Column[]>([]);
  const [totalRows, setTotalRows] = useState(0);
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(200);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [exportStatus, setExportStatus] = useState('');

  const load = useCallback(async (p: number, ps: number) => {
    if (!jobId) return;
    setLoading(true);
    setError(null);
    try {
      const result = await getJobResults(Number(jobId), p, ps);
      setColumns((result.columns as Column[]) ?? []);
      setData(result.data);
      setTotalRows(result.meta.totalRowsFetched);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Erro ao carregar resultados.');
    } finally {
      setLoading(false);
    }
  }, [jobId]);

  useEffect(() => {
    void load(1, 200);
  }, [load]);

  const handleExport = async (format: 'xlsx' | 'csv' | 'pdf') => {
    if (!jobId) return;
    setExportStatus('Criando job de exportação...');
    try {
      const j = await createJob('export', { resultId: Number(jobId), format });
      setExportStatus('Aguardando geração do arquivo...');
      const poll = setInterval(async () => {
        const status = await getJob(j.id);
        if (status.status === 'done') {
          clearInterval(poll);
          setExportStatus('');
          window.location.href = `${import.meta.env.VITE_API_URL ?? 'http://localhost:3001'}/reports/jobs/${j.id}/download`;
        } else if (status.status === 'failed') {
          clearInterval(poll);
          setExportStatus(`Falha na exportação: ${status.errorMessage}`);
        }
      }, 2000);
    } catch (err: unknown) {
      setExportStatus(err instanceof Error ? err.message : 'Erro ao exportar.');
    }
  };

  return (
    <div style={{ padding: 24, maxWidth: 1400 }}>
      <h2 style={{ marginTop: 0 }}>Resultados — Job #{jobId}</h2>

      <div style={{ display: 'flex', gap: 8, marginBottom: 16, flexWrap: 'wrap', alignItems: 'center' }}>
        <button onClick={() => handleExport('xlsx')} style={{ padding: '5px 12px' }}>
          Exportar XLSX
        </button>
        <button onClick={() => handleExport('csv')} style={{ padding: '5px 12px' }}>
          Exportar CSV
        </button>
        <button onClick={() => handleExport('pdf')} style={{ padding: '5px 12px' }}>
          Exportar PDF
        </button>
        {exportStatus && (
          <span style={{ color: '#e67e22', fontSize: 12 }}>{exportStatus}</span>
        )}
      </div>

      {error && (
        <div style={{ color: '#c0392b', background: '#fdecea', padding: '8px 12px', borderRadius: 4, marginBottom: 12 }}>
          {error}
        </div>
      )}

      <DataGrid
        columns={columns}
        rows={data}
        page={page}
        pageSize={pageSize}
        totalRows={totalRows}
        onPageChange={p => { setPage(p); void load(p, pageSize); }}
        onPageSizeChange={ps => { setPageSize(ps); setPage(1); void load(1, ps); }}
        loading={loading}
      />
    </div>
  );
}
