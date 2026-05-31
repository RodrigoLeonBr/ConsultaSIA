import { useEffect, useState, useRef, useCallback } from 'react';
import { BarChart3, Plus, X, Save, Trash2, ChevronRight } from 'lucide-react';
import type { ColumnDef } from '@tanstack/react-table';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Select } from '../components/ui/Select';
import { Card } from '../components/ui/Card';
import { Table } from '../components/ui/Table';
import { Pagination } from '../components/ui/Pagination';
import { Alert } from '../components/ui/Alert';
import { getMetadata, postProduction } from '../api';
import { useServerTable } from '../hooks/useServerTable';
import type { FieldMetadata, DynamicResult, Column } from '../types';
import { useNavigate } from 'react-router-dom';

interface Filter { fieldId: string; operator: string; value: string | string[]; }
interface SavedView { name: string; competence: string; selectedFields: string[]; filters: Filter[]; }

const STORAGE_KEY = 'sia-dynamic-views';

function loadViews(): SavedView[] {
  try { return JSON.parse(localStorage.getItem(STORAGE_KEY) ?? '[]'); } catch { return []; }
}
function saveViews(views: SavedView[]) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(views));
}

type FieldGroup = 'date' | 'lookup' | 'number' | 'currency' | 'text';
const GROUP_LABEL: Record<FieldGroup, string> = {
  date: 'Data', lookup: 'Dimensões', number: 'Quantidades', currency: 'Valores', text: 'Texto',
};

export function SiaDynamicPage() {
  const navigate = useNavigate();
  const [fields, setFields] = useState<FieldMetadata[]>([]);
  const [competence, setCompetence] = useState('202301');
  const [selectedFields, setSelectedFields] = useState<string[]>(['prd_uid', 'PRD_QT_A', 'PRD_VL_A']);
  const [filters, setFilters] = useState<Filter[]>([]);
  const [sortField, setSortField] = useState('');
  const [sortDir, setSortDir] = useState<'ASC' | 'DESC'>('DESC');
  const [result, setResult] = useState<DynamicResult | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [views, setViews] = useState<SavedView[]>(loadViews);
  const [viewName, setViewName] = useState('');
  const { pageSize, setPage, setPageSize } = useServerTable();
  const abortRef = useRef<AbortController | null>(null);

  // Load metadata once
  useEffect(() => {
    getMetadata().then(m => setFields(m.producao.fields.filter(f => !f.displayOnly))).catch(() => {});
  }, []);

  const selectableFields = fields.filter(f => !f.filterOnly);
  const filterableFields = fields;

  // Group fields for display
  const grouped = selectableFields.reduce<Record<string, FieldMetadata[]>>((acc, f) => {
    const g = f.isAggregate ? (f.type === 'currency' ? 'currency' : 'number') : f.type as FieldGroup;
    (acc[g] ??= []).push(f);
    return acc;
  }, {});

  const toggleField = (id: string) =>
    setSelectedFields(prev => prev.includes(id) ? prev.filter(f => f !== id) : [...prev, id]);

  const addFilter = () => {
    const first = filterableFields[0];
    if (!first) return;
    setFilters(prev => [...prev, { fieldId: first.id, operator: first.allowedOperators[0] ?? '=', value: '' }]);
  };

  const updateFilter = (idx: number, patch: Partial<Filter>) =>
    setFilters(prev => prev.map((f, i) => i === idx ? { ...f, ...patch } : f));

  const removeFilter = (idx: number) =>
    setFilters(prev => prev.filter((_, i) => i !== idx));

  const handleApply = useCallback(async (p = 1, ps = pageSize) => {
    if (abortRef.current) abortRef.current.abort();
    abortRef.current = new AbortController();
    setLoading(true); setError(null);
    try {
      const r = await postProduction({
        competence,
        select: selectedFields,
        filters,
        page: p,
        pageSize: ps,
        ...(sortField ? { sort: { fieldId: sortField, direction: sortDir } } : {}),
      }, abortRef.current.signal);
      setResult(r);
      setPage(p); setPageSize(ps);
    } catch (err: unknown) {
      if (err instanceof Error && err.name !== 'CanceledError') {
        const msg = (err as unknown as { response?: { data?: { message?: unknown } } }).response?.data?.message;
        setError(Array.isArray(msg) ? (msg as string[]).join(', ') : String((err as Error).message));
      }
    } finally { setLoading(false); }
  }, [competence, selectedFields, filters, sortField, sortDir, pageSize, setPage, setPageSize]);

  const saveView = () => {
    if (!viewName.trim()) return;
    const newViews = [...views, { name: viewName.trim(), competence, selectedFields, filters }];
    setViews(newViews); saveViews(newViews); setViewName('');
  };
  const loadView = (v: SavedView) => {
    setCompetence(v.competence); setSelectedFields(v.selectedFields); setFilters(v.filters);
  };
  const deleteView = (idx: number) => {
    const updated = views.filter((_, i) => i !== idx);
    setViews(updated); saveViews(updated);
  };

  // Build TanStack columns from result metadata
  const columns: ColumnDef<Record<string, unknown>, unknown>[] = result?.columns.map((col: Column) => ({
    id: col.fieldId,
    accessorFn: (row: Record<string, unknown>) => col.displayAlias ? (row[col.displayAlias] ?? row[col.fieldId]) : row[col.fieldId],
    header: col.label,
    cell: (info: { getValue: () => unknown }) => {
      const v = info.getValue();
      const isNum = col.type === 'number' || col.type === 'currency';
      if (isNum && v !== null && v !== undefined) {
        const num = Number(v);
        const formatted = col.type === 'currency'
          ? num.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
          : num.toLocaleString('pt-BR');
        return <span className="font-mono text-sm text-right block">{formatted}</span>;
      }
      return <span className="text-sm">{String(v ?? '—')}</span>;
    },
  })) ?? [];

  return (
    <div className="flex gap-5 h-full min-h-0">
      {/* Left panel — config */}
      <div className="w-72 shrink-0 flex flex-col gap-4 overflow-y-auto">
        {/* Competência */}
        <Card title="Competência">
          <Input
            placeholder="AAAAMM"
            value={competence}
            onChange={e => setCompetence(e.target.value)}
            className="font-mono"
            maxLength={6}
          />
        </Card>

        {/* Field selector */}
        <Card title={`Campos (${selectedFields.length})`}>
          <div className="space-y-3">
            {Object.entries(grouped).map(([group, gFields]) => (
              <div key={group}>
                <p className="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">{GROUP_LABEL[group as FieldGroup] ?? group}</p>
                <div className="space-y-1">
                  {gFields.map(f => (
                    <label key={f.id} className="flex items-center gap-2 cursor-pointer group">
                      <input
                        type="checkbox"
                        checked={selectedFields.includes(f.id)}
                        onChange={() => toggleField(f.id)}
                        className="rounded border-slate-300 text-[#0F52BA]"
                      />
                      <span className="text-sm text-slate-700 group-hover:text-slate-900">{f.label}</span>
                    </label>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </Card>

        {/* Sort */}
        <Card title="Ordenação">
          <div className="space-y-2">
            <Select value={sortField} onChange={e => setSortField(e.target.value)}>
              <option value="">Sem ordenação</option>
              {selectableFields.filter(f => f.sortable).map(f => (
                <option key={f.id} value={f.id}>{f.label}</option>
              ))}
            </Select>
            <Select value={sortDir} onChange={e => setSortDir(e.target.value as 'ASC' | 'DESC')}>
              <option value="DESC">Decrescente</option>
              <option value="ASC">Crescente</option>
            </Select>
          </div>
        </Card>

        {/* Saved Views */}
        <Card title="Visões Salvas">
          <div className="space-y-2">
            {views.map((v, i) => (
              <div key={i} className="flex items-center gap-1">
                <button onClick={() => loadView(v)} className="flex-1 text-left text-sm text-[#0F52BA] hover:underline truncate">{v.name}</button>
                <button onClick={() => deleteView(i)} className="p-1 text-slate-400 hover:text-red-500"><Trash2 className="w-3 h-3" /></button>
              </div>
            ))}
            <div className="flex gap-1 mt-2">
              <input
                value={viewName}
                onChange={e => setViewName(e.target.value)}
                placeholder="Nome da visão..."
                className="flex-1 border border-slate-300 rounded px-2 py-1 text-xs"
                onKeyDown={e => e.key === 'Enter' && saveView()}
              />
              <button onClick={saveView} className="p-1.5 rounded bg-slate-100 hover:bg-slate-200 text-slate-600"><Save className="w-3 h-3" /></button>
            </div>
          </div>
        </Card>
      </div>

      {/* Right panel — filters + results */}
      <div className="flex-1 min-w-0 flex flex-col gap-4 overflow-y-auto">
        {/* Filters */}
        <Card title="Filtros" action={
          <button onClick={addFilter} className="flex items-center gap-1 text-xs text-[#0F52BA] hover:underline">
            <Plus className="w-3 h-3" /> Adicionar
          </button>
        }>
          {filters.length === 0 ? (
            <p className="text-sm text-slate-400">Nenhum filtro. Clique em "Adicionar" para filtrar.</p>
          ) : (
            <div className="space-y-2">
              {filters.map((filter, idx) => {
                const fieldMeta = filterableFields.find(f => f.id === filter.fieldId);
                const ops = fieldMeta?.allowedOperators ?? [];
                return (
                  <div key={idx} className="flex gap-2 items-center">
                    <select
                      value={filter.fieldId}
                      onChange={e => updateFilter(idx, { fieldId: e.target.value, operator: filterableFields.find(f => f.id === e.target.value)?.allowedOperators[0] ?? '=', value: '' })}
                      className="border border-slate-300 rounded px-2 py-1.5 text-sm bg-white flex-shrink-0"
                    >
                      {filterableFields.map(f => <option key={f.id} value={f.id}>{f.label}</option>)}
                    </select>
                    <select
                      value={filter.operator}
                      onChange={e => updateFilter(idx, { operator: e.target.value, value: '' })}
                      className="border border-slate-300 rounded px-2 py-1.5 text-sm bg-white w-28 flex-shrink-0"
                    >
                      {ops.map(op => <option key={op} value={op}>{op}</option>)}
                    </select>
                    {filter.operator === 'between' ? (
                      <div className="flex gap-1 flex-1">
                        <input
                          value={Array.isArray(filter.value) ? filter.value[0] ?? '' : ''}
                          onChange={e => updateFilter(idx, { value: [e.target.value, Array.isArray(filter.value) ? filter.value[1] ?? '' : ''] })}
                          placeholder="De"
                          className="border border-slate-300 rounded px-2 py-1.5 text-sm flex-1"
                        />
                        <input
                          value={Array.isArray(filter.value) ? filter.value[1] ?? '' : ''}
                          onChange={e => updateFilter(idx, { value: [Array.isArray(filter.value) ? filter.value[0] ?? '' : '', e.target.value] })}
                          placeholder="Até"
                          className="border border-slate-300 rounded px-2 py-1.5 text-sm flex-1"
                        />
                      </div>
                    ) : (
                      <input
                        value={typeof filter.value === 'string' ? filter.value : (filter.value as string[]).join(', ')}
                        onChange={e => updateFilter(idx, { value: filter.operator === 'in' ? e.target.value.split(',').map(s => s.trim()) : e.target.value })}
                        placeholder={filter.operator === 'in' ? 'valor1, valor2, ...' : 'Valor'}
                        className="border border-slate-300 rounded px-2 py-1.5 text-sm flex-1"
                      />
                    )}
                    <button onClick={() => removeFilter(idx)} className="p-1 text-slate-400 hover:text-red-500 flex-shrink-0"><X className="w-4 h-4" /></button>
                  </div>
                );
              })}
            </div>
          )}
        </Card>

        {/* Actions */}
        <div className="flex gap-3">
          <Button onClick={() => handleApply(1, pageSize)} loading={loading} icon={<BarChart3 className="w-4 h-4" />}>
            Aplicar
          </Button>
          {loading && <Button variant="secondary" onClick={() => abortRef.current?.abort()}>Cancelar</Button>}
          <Button
            variant="ghost"
            icon={<ChevronRight className="w-4 h-4" />}
            onClick={() => navigate('/async-reports')}
          >
            Relatório Pesado
          </Button>
        </div>

        {/* Error / Warning */}
        {error && <Alert variant="error">{error}</Alert>}
        {result?.meta.warning && <Alert variant="warning">{result.meta.warning}</Alert>}

        {/* Results */}
        {result && (
          <>
            <Table columns={columns} data={result.rows as Record<string, unknown>[]} loading={loading} />
            <Pagination
              page={result.meta.page}
              pageSize={result.meta.pageSize}
              totalRows={result.meta.totalRows}
              queryTimeMs={result.meta.queryTimeMs}
              onPageChange={p => handleApply(p, pageSize)}
              onPageSizeChange={ps => handleApply(1, ps)}
            />
          </>
        )}
      </div>
    </div>
  );
}
