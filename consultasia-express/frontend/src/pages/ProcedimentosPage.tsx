import React, { useEffect, useState, useCallback } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { Search } from 'lucide-react';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Table } from '../components/ui/Table';
import { Pagination } from '../components/ui/Pagination';
import { Alert } from '../components/ui/Alert';
import { getProcedimentos } from '../api';
import { useServerTable } from '../hooks/useServerTable';

interface Procedimento {
  codigo: string;
  descricao: string;
  valor_unitario: string | number | null;
  rubrica: string | null;
}

function formatCurrency(value: string | number | null) {
  if (value === null || value === undefined) return '—';
  const num = Number(value);
  return num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

const COLUMNS: ColumnDef<Procedimento, unknown>[] = [
  { accessorKey: 'codigo', header: 'Código', cell: info => <span className="font-mono text-sm">{info.getValue<string>()}</span> },
  { accessorKey: 'descricao', header: 'Descrição' },
  { accessorKey: 'valor_unitario', header: 'Valor Unit.', cell: info => <span className="font-mono text-sm text-right block">{formatCurrency(info.getValue<string | number | null>())}</span> },
  { accessorKey: 'rubrica', header: 'Rubrica', cell: info => <span className="font-mono text-sm">{info.getValue<string>() ?? '—'}</span> },
];

export function ProcedimentosPage() {
  const [search, setSearch] = useState('');
  const [applied, setApplied] = useState('');
  const { page, pageSize, setPage, setPageSize } = useServerTable();
  const [data, setData] = useState<Procedimento[]>([]);
  const [totalRows, setTotalRows] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (s: string, p: number, ps: number) => {
    setLoading(true); setError(null);
    try {
      const res = await getProcedimentos(s, p, ps);
      setData(res.data ?? []);
      setTotalRows(res.meta?.totalRows ?? 0);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Erro ao carregar procedimentos.');
    } finally { setLoading(false); }
  }, []);

  useEffect(() => { void load(applied, page, pageSize); }, [applied, page, pageSize, load]);
  const handleApply = () => { setApplied(search); setPage(1); };

  return (
    <div className="space-y-4">
      <div className="flex gap-3 items-end">
        <Input wrapperClassName="flex-1 max-w-sm" placeholder="Buscar por código ou descrição..." value={search} onChange={e => setSearch(e.target.value)} onKeyDown={e => e.key === 'Enter' && handleApply()} />
        <Button onClick={handleApply} loading={loading} icon={<Search className="w-4 h-4" />}>Aplicar</Button>
      </div>
      {error && <Alert variant="error">{error}</Alert>}
      <Table columns={COLUMNS} data={data} loading={loading} />
      <Pagination page={page} pageSize={pageSize} totalRows={totalRows} onPageChange={setPage} onPageSizeChange={setPageSize} />
    </div>
  );
}
