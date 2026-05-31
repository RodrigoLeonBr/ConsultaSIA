import React, { useState, useCallback } from 'react';
import type { ColumnDef } from '@tanstack/react-table';
import { FileText } from 'lucide-react';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Table } from '../components/ui/Table';
import { Pagination } from '../components/ui/Pagination';
import { Alert } from '../components/ui/Alert';
import { useServerTable } from '../hooks/useServerTable';

interface FaturamentoRow {
  prd_uid: string;
  prestador_nome: string | null;
  tipo_financiamento: string | null;
  grupo: string | null;
  subgrupo: string | null;
  forma: string | null;
  procedimento_codigo: string;
  procedimento_nome: string | null;
  valor_unitario: string | number | null;
  qty_aprovada: string | number;
  valor_aprovado: string | number;
  qty_apresentada: string | number;
  valor_apresentado: string | number;
}

function fmt(v: string | number | null): string {
  if (v === null || v === undefined) return '—';
  return Number(v).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}
function fmtN(v: string | number | null): string {
  if (v === null || v === undefined) return '—';
  return Number(v).toLocaleString('pt-BR');
}

const COLUMNS: ColumnDef<FaturamentoRow, unknown>[] = [
  { accessorKey: 'prd_uid',           header: 'CNES',      cell: i => <span className="font-mono text-xs">{i.getValue<string>()}</span> },
  { accessorKey: 'prestador_nome',     header: 'Prestador', cell: i => <span className="text-sm">{i.getValue<string>() ?? '—'}</span> },
  { accessorKey: 'tipo_financiamento', header: 'Fin.',      cell: i => <span className="font-mono text-xs">{i.getValue<string>() ?? '—'}</span> },
  { accessorKey: 'grupo',              header: 'Grupo',     cell: i => <span className="font-mono text-xs">{i.getValue<string>() ?? '—'}</span> },
  { accessorKey: 'subgrupo',           header: 'Subgrupo',  cell: i => <span className="font-mono text-xs">{i.getValue<string>() ?? '—'}</span> },
  { accessorKey: 'procedimento_codigo',header: 'Proc.',     cell: i => <span className="font-mono text-xs">{i.getValue<string>()}</span> },
  { accessorKey: 'procedimento_nome',  header: 'Descrição', cell: i => <span className="text-xs truncate max-w-40 block">{i.getValue<string>() ?? '—'}</span> },
  { accessorKey: 'valor_unitario',     header: 'Vl Unit.',  cell: i => <span className="font-mono text-xs text-right block">{fmt(i.getValue<string | number | null>())}</span> },
  { accessorKey: 'qty_aprovada',       header: 'Qt Apr.',   cell: i => <span className="font-mono text-xs text-right block">{fmtN(i.getValue<string | number | null>())}</span> },
  { accessorKey: 'valor_aprovado',     header: 'Vl Apr.',   cell: i => <span className="font-mono text-xs text-right block">{fmt(i.getValue<string | number | null>())}</span> },
  { accessorKey: 'qty_apresentada',    header: 'Qt Apres.', cell: i => <span className="font-mono text-xs text-right block">{fmtN(i.getValue<string | number | null>())}</span> },
  { accessorKey: 'valor_apresentado',  header: 'Vl Apres.', cell: i => <span className="font-mono text-xs text-right block">{fmt(i.getValue<string | number | null>())}</span> },
];

export function FaturamentoPage() {
  const [competence, setCompetence] = useState('202301');
  const [applied, setApplied] = useState('');
  const { page, pageSize, setPage, setPageSize } = useServerTable(200);
  const [data, setData] = useState<FaturamentoRow[]>([]);
  const [totalRows, setTotalRows] = useState(0);
  const [queryTimeMs, setQueryTimeMs] = useState<number | undefined>();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async (comp: string, p: number, ps: number) => {
    setLoading(true); setError(null);
    try {
      const baseUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:3001';
      const r = await fetch(`${baseUrl}/reports/sia/faturamento-prestador?competence=${comp}&page=${p}&pageSize=${ps}`);
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      const json = await r.json();
      setData(json.data ?? []);
      setTotalRows(json.meta?.totalRows ?? 0);
      setQueryTimeMs(json.meta?.queryTimeMs);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Erro ao carregar faturamento.');
    } finally { setLoading(false); }
  }, []);

  const handleApply = (p = page, ps = pageSize) => {
    if (competence.length !== 6) { setError('Competência deve ter 6 caracteres (AAAAMM).'); return; }
    setApplied(competence);
    setPage(p); setPageSize(ps);
    void load(competence, p, ps);
  };

  return (
    <div className="space-y-4">
      <div className="flex gap-3 items-end">
        <Input
          label="Competência"
          placeholder="AAAAMM"
          value={competence}
          onChange={e => setCompetence(e.target.value)}
          maxLength={6}
          className="font-mono w-32"
        />
        <Button onClick={() => handleApply(1, pageSize)} loading={loading} icon={<FileText className="w-4 h-4" />}>
          Aplicar
        </Button>
      </div>

      {error && <Alert variant="error">{error}</Alert>}

      {applied && (
        <>
          <Table columns={COLUMNS} data={data} loading={loading} />
          <Pagination
            page={page} pageSize={pageSize} totalRows={totalRows}
            queryTimeMs={queryTimeMs}
            onPageChange={p => { setPage(p); void load(applied, p, pageSize); }}
            onPageSizeChange={ps => { setPageSize(ps); setPage(1); void load(applied, 1, ps); }}
          />
        </>
      )}
    </div>
  );
}
