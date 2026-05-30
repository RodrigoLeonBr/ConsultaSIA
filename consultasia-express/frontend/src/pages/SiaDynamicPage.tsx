import { useEffect, useState, useRef } from 'react';
import { getMetadata, postProduction } from '../api';
import type { FieldMetadata, DynamicResult } from '../types';
import { DataGrid } from '../components/DataGrid';

export function SiaDynamicPage() {
  const [fields, setFields] = useState<FieldMetadata[]>([]);
  const [competence, setCompetence] = useState('202301');
  const [selectedFields, setSelectedFields] = useState<string[]>(['prd_uid', 'PRD_QT_A', 'PRD_VL_A']);
  const [result, setResult] = useState<DynamicResult | null>(null);
  const [page, setPage] = useState(1);
  const [pageSize, setPageSize] = useState(50);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const abortRef = useRef<AbortController | null>(null);

  useEffect(() => {
    getMetadata()
      .then(m => setFields(m.producao.fields.filter(f => !f.filterOnly && !f.displayOnly)))
      .catch(err => setError(String(err.message)));
  }, []);

  const handleApply = async (p = page, ps = pageSize) => {
    if (abortRef.current) abortRef.current.abort();
    abortRef.current = new AbortController();
    setLoading(true);
    setError(null);
    try {
      const r = await postProduction(
        { competence, select: selectedFields, page: p, pageSize: ps },
        abortRef.current.signal
      );
      setResult(r);
      setPage(p);
      setPageSize(ps);
    } catch (err: unknown) {
      if (err instanceof Error && err.name !== 'CanceledError') {
        const msg = (err as any).response?.data?.message;
        setError(Array.isArray(msg) ? msg.join(', ') : String(err.message));
      }
    } finally {
      setLoading(false);
    }
  };

  const toggleField = (id: string) =>
    setSelectedFields(prev =>
      prev.includes(id) ? prev.filter(f => f !== id) : [...prev, id]
    );

  return (
    <div style={{ padding: 24, maxWidth: 1400 }}>
      <h2 style={{ marginTop: 0 }}>Relatório Dinâmico SIA</h2>

      <div style={{ display: 'flex', gap: 12, alignItems: 'center', marginBottom: 16, flexWrap: 'wrap' }}>
        <label style={{ display: 'flex', alignItems: 'center', gap: 6 }}>
          Competência:
          <input
            value={competence}
            onChange={e => setCompetence(e.target.value)}
            maxLength={6}
            placeholder="AAAAMM"
            style={{ width: 80, padding: '4px 6px', fontFamily: 'monospace' }}
          />
        </label>
        <button
          onClick={() => handleApply(1, pageSize)}
          disabled={loading || selectedFields.length === 0}
          style={{ padding: '5px 16px', cursor: 'pointer' }}
        >
          Aplicar
        </button>
        {loading && (
          <button onClick={() => abortRef.current?.abort()} style={{ padding: '5px 12px' }}>
            Cancelar
          </button>
        )}
      </div>

      <div style={{ marginBottom: 16 }}>
        <div style={{ fontWeight: 'bold', marginBottom: 8 }}>Campos ({selectedFields.length} selecionados):</div>
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 8 }}>
          {fields.map(f => (
            <label key={f.id} style={{ display: 'flex', alignItems: 'center', gap: 4, userSelect: 'none', cursor: 'pointer' }}>
              <input
                type="checkbox"
                checked={selectedFields.includes(f.id)}
                onChange={() => toggleField(f.id)}
              />
              <span style={{ fontSize: 12 }}>{f.label}</span>
            </label>
          ))}
        </div>
      </div>

      {error && (
        <div style={{ color: '#c0392b', background: '#fdecea', padding: '8px 12px', borderRadius: 4, marginBottom: 12 }}>
          {error}
        </div>
      )}

      {result?.meta.warning && (
        <div style={{ color: '#e67e22', background: '#fef9e7', padding: '8px 12px', borderRadius: 4, marginBottom: 12 }}>
          ⚠️ {result.meta.warning}
        </div>
      )}

      {result && (
        <DataGrid
          columns={result.columns}
          rows={result.rows}
          page={result.meta.page}
          pageSize={result.meta.pageSize}
          totalRows={result.meta.totalRows}
          onPageChange={p => handleApply(p, pageSize)}
          onPageSizeChange={ps => handleApply(1, ps)}
          queryTimeMs={result.meta.queryTimeMs}
          loading={loading}
        />
      )}
    </div>
  );
}
