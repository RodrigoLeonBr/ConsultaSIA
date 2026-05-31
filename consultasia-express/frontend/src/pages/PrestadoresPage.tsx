import { useEffect, useState, useCallback } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { Search } from 'lucide-react';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Table } from '../components/ui/Table';
import { Pagination } from '../components/ui/Pagination';
import { Alert } from '../components/ui/Alert';
import { getPrestadores } from '../api';
import { useServerTable } from '../hooks/useServerTable';

interface Prestador {
  cnes: string;
  nome: string;
  cnpj: string | null;
  tipo: string | null;
}

const COLUMNS: ColumnDef<Prestador, unknown>[] = [
  { accessorKey: 'cnes', header: 'CNES', cell: info => <span className="font-mono text-sm">{info.getValue<string>()}</span> },
  { accessorKey: 'nome', header: 'Nome' },
  { accessorKey: 'cnpj', header: 'CNPJ', cell: info => <span className="font-mono text-sm">{info.getValue<string>() ?? '—'}</span> },
  { accessorKey: 'tipo', header: 'Tipo', cell: info => info.getValue<string>() ?? '—' },
];

export function PrestadoresPage() {
  const [search, setSearch] = useState('');
  const [applied, setApplied] = useState('');
  const { page, pageSize, setPage, setPageSize } = useServerTable();
  const [data, setData] = useState<Prestador[]>([]);
  const [totalRows, setTotalRows] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (s: string, p: number, ps: number) => {
    setLoading(true);
    setError(null);
    try {
      const res = await getPrestadores(s, p, ps);
      setData(res.data ?? []);
      setTotalRows(res.meta?.totalRows ?? 0);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Erro ao carregar prestadores.');
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => { void load(applied, page, pageSize); }, [applied, page, pageSize, load]);

  const handleApply = () => { setApplied(search); setPage(1); };

  return (
    <div className="space-y-4">
      <div className="flex gap-3 items-end">
        <Input
          wrapperClassName="flex-1 max-w-sm"
          placeholder="Buscar por nome ou CNES..."
          value={search}
          onChange={e => setSearch(e.target.value)}
          onKeyDown={e => e.key === 'Enter' && handleApply()}
        />
        <Button onClick={handleApply} loading={loading} icon={<Search className="w-4 h-4" />}>
          Aplicar
        </Button>
      </div>

      {error && <Alert variant="error">{error}</Alert>}

      <Table columns={COLUMNS} data={data} loading={loading} />
      <Pagination page={page} pageSize={pageSize} totalRows={totalRows} onPageChange={setPage} onPageSizeChange={setPageSize} />
    </div>
  );
}
