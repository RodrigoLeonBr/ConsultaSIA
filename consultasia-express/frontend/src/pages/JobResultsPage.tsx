import { useEffect, useState, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import type { ColumnDef } from '@tanstack/react-table';
import { Download, ArrowLeft } from 'lucide-react';
import { Button } from '../components/ui/Button';
import { Card } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import { Table } from '../components/ui/Table';
import { Pagination } from '../components/ui/Pagination';
import { Alert } from '../components/ui/Alert';
import { getJobResults, createJob, getJob } from '../api';
import { useServerTable } from '../hooks/useServerTable';
import type { Column } from '../types';

const STATUS_LABEL: Record<string, string> = {
  queued: 'Na fila', running: 'Executando', done: 'Concluído', failed: 'Falhou',
};

export function JobResultsPage() {
  const { jobId } = useParams<{ jobId: string }>();
  const navigate = useNavigate();
  const [jobStatus, setJobStatus] = useState<string>('done');
  const [columns, setColumns] = useState<Column[]>([]);
  const [data, setData] = useState<Record<string, unknown>[]>([]);
  const [totalRows, setTotalRows] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [exportStatus, setExportStatus] = useState('');
  const { page, pageSize, setPage, setPageSize } = useServerTable(200);

  const load = useCallback(async (p: number, ps: number) => {
    if (!jobId) return;
    setLoading(true); setError(null);
    try {
      const res = await getJobResults(Number(jobId), p, ps);
      setColumns((res.columns as Column[]) ?? []);
      setData(res.data ?? []);
      setTotalRows(res.meta?.totalRowsFetched ?? 0);
      const j = await getJob(Number(jobId));
      setJobStatus(j.status);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Erro ao carregar resultados.');
    } finally { setLoading(false); }
  }, [jobId]);

  useEffect(() => { void load(1, 200); }, [load]);

  const handleExport = async (format: 'xlsx' | 'csv' | 'pdf') => {
    if (!jobId) return;
    setExportStatus('Criando exportação...');
    try {
      const j = await createJob('export', { resultId: Number(jobId), format });
      const poll = setInterval(async () => {
        const st = await getJob(j.id);
        if (st.status === 'done') {
          clearInterval(poll);
          setExportStatus('');
          window.location.href = `${import.meta.env.VITE_API_URL ?? 'http://localhost:3001'}/reports/jobs/${j.id}/download`;
        } else if (st.status === 'failed') {
          clearInterval(poll);
          setExportStatus(`Falha: ${st.errorMessage}`);
        }
      }, 2000);
    } catch (err: unknown) {
      setExportStatus(err instanceof Error ? err.message : 'Erro ao exportar.');
    }
  };

  const tableColumns: ColumnDef<Record<string, unknown>, unknown>[] = columns.map(col => ({
    id: col.fieldId,
    accessorFn: (row: Record<string, unknown>) => col.displayAlias ? (row[col.displayAlias] ?? row[col.fieldId]) : row[col.fieldId],
    header: col.label,
    cell: (info: { getValue: () => unknown }) => {
      const v = info.getValue();
      const isNum = col.type === 'number' || col.type === 'currency';
      if (isNum && v !== null && v !== undefined) {
        const formatted = col.type === 'currency'
          ? Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
          : Number(v).toLocaleString('pt-BR');
        return <span className="font-mono text-sm text-right block">{formatted}</span>;
      }
      return <span className="text-sm">{String(v ?? '—')}</span>;
    },
  }));

  return (
    <div className="space-y-4">
      {/* Header card */}
      <Card>
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Button variant="ghost" size="sm" onClick={() => navigate('/async-reports')} icon={<ArrowLeft className="w-4 h-4" />}>
              Voltar
            </Button>
            <h3 className="text-sm font-semibold text-slate-800">Job #{jobId}</h3>
            <Badge variant={jobStatus as 'queued' | 'running' | 'done' | 'failed'}>
              {STATUS_LABEL[jobStatus] ?? jobStatus}
            </Badge>
            <span className="text-xs text-slate-500">{totalRows.toLocaleString('pt-BR')} registros</span>
          </div>
          <div className="flex items-center gap-2">
            {exportStatus ? (
              <span className="text-xs text-[#ca8a04]">{exportStatus}</span>
            ) : (
              <>
                <Button variant="secondary" size="sm" onClick={() => handleExport('xlsx')} icon={<Download className="w-3 h-3" />}>XLSX</Button>
                <Button variant="secondary" size="sm" onClick={() => handleExport('csv')} icon={<Download className="w-3 h-3" />}>CSV</Button>
                <Button variant="secondary" size="sm" onClick={() => handleExport('pdf')} icon={<Download className="w-3 h-3" />}>PDF</Button>
              </>
            )}
          </div>
        </div>
      </Card>

      {error && <Alert variant="error">{error}</Alert>}

      <Table columns={tableColumns} data={data} loading={loading} />
      <Pagination
        page={page} pageSize={pageSize} totalRows={totalRows}
        onPageChange={p => { setPage(p); void load(p, pageSize); }}
        onPageSizeChange={ps => { setPageSize(ps); setPage(1); void load(1, ps); }}
      />
    </div>
  );
}
