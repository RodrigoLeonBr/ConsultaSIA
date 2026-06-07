import React, { useState, useEffect, useRef } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { apiEndpoints } from '../services/api';
import { DataGrid } from '../components/DataGrid';
import { useJobPolling } from '../hooks/useJobPolling';

interface Column {
    key: string;
    header: string;
}

function keyToHeader(key: string): string {
    return key
        .replace(/([A-Z])/g, ' $1')
        .replace(/^./, (s) => s.toUpperCase())
        .trim();
}

type ExportFormat = 'xlsx' | 'csv' | 'pdf';

const FORMAT_LABEL: Record<ExportFormat, string> = {
    xlsx: 'Excel (.xlsx)',
    csv: 'CSV (.csv)',
    pdf: 'PDF (.pdf)',
};

export function JobResultsPage() {
    const { resultId } = useParams<{ resultId: string }>();
    const navigate = useNavigate();

    // ── Dados ─────────────────────────────────────────────────────────────────
    const [data, setData] = useState<any[]>([]);
    const [columns, setColumns] = useState<Column[]>([]);
    const [meta, setMeta] = useState<any>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [activePage, setActivePage] = useState(1);
    const [activeLimit, setActiveLimit] = useState(50);
    const abortControllerRef = useRef<AbortController | null>(null);

    // ── Exportação ────────────────────────────────────────────────────────────
    const [exportFormat, setExportFormat] = useState<ExportFormat>('xlsx');
    const [exportJobId, setExportJobId] = useState<number | null>(null);
    const [exportSubmitting, setExportSubmitting] = useState(false);
    const [exportError, setExportError] = useState<string | null>(null);

    const { status: exportStatus } = useJobPolling(exportJobId, 2000);

    const fetchResults = async (page: number, limit: number) => {
        if (!resultId) return;
        if (abortControllerRef.current) abortControllerRef.current.abort();
        abortControllerRef.current = new AbortController();
        setLoading(true);
        setError(null);
        try {
            const response = await apiEndpoints.getReportResults(
                resultId, page, limit,
                { signal: abortControllerRef.current.signal },
            );
            const rows: any[] = response.data.data;
            setData(rows);
            setMeta(response.data.meta);
            if (page === 1 && rows.length > 0 && columns.length === 0) {
                setColumns(Object.keys(rows[0]).map((key) => ({ key, header: keyToHeader(key) })));
            }
        } catch (err: any) {
            if (err.name === 'CanceledError') return;
            const msg = err.response?.data?.message || err.message || 'Erro ao carregar resultados';
            setError(Array.isArray(msg) ? msg.join(', ') : msg);
        } finally {
            if (abortControllerRef.current && !abortControllerRef.current.signal.aborted) setLoading(false);
        }
    };

    useEffect(() => {
        fetchResults(activePage, activeLimit);
        return () => { abortControllerRef.current?.abort(); };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activePage, activeLimit, resultId]);

    const handleExport = async () => {
        if (!resultId) return;
        setExportSubmitting(true);
        setExportError(null);
        setExportJobId(null);
        try {
            const res = await apiEndpoints.createReportJob({
                type: 'export',
                parameters: { resultId: Number(resultId), format: exportFormat },
            });
            setExportJobId(res.data.id);
        } catch (err: any) {
            const msg = err.response?.data?.message || err.message || 'Erro ao criar job de exportação';
            setExportError(Array.isArray(msg) ? msg.join(', ') : msg);
        } finally {
            setExportSubmitting(false);
        }
    };

    const gridMeta = meta
        ? {
            totalRows: meta.totalRowsFetched,
            page: meta.page,
            pageSize: meta.limit,
            totalPages: Math.ceil(meta.totalRowsFetched / meta.limit),
        }
        : null;

    const isExportRunning = !!exportJobId && exportStatus !== 'done' && exportStatus !== 'failed';

    return (
        <div style={{ padding: '20px', maxWidth: '1400px', margin: '0 auto', fontFamily: 'sans-serif' }}>
            <div style={{ display: 'flex', alignItems: 'center', gap: '16px', marginBottom: '8px' }}>
                <button
                    onClick={() => navigate('/reports/async')}
                    style={{ padding: '6px 14px', background: '#e2e6ea', border: '1px solid #ccc', borderRadius: '4px', cursor: 'pointer' }}
                >
                    ← Novo Relatório
                </button>
                <h2 style={{ margin: 0 }}>Resultados — Job #{resultId}</h2>
            </div>
            <p style={{ color: '#555', marginBottom: '16px', fontSize: '0.9rem' }}>
                Resultado persistido. Consulta paginada sem reprocessamento.
            </p>

            {/* Painel de exportação */}
            <div style={{
                background: '#f9f9f9', border: '1px solid #ddd', borderRadius: '6px',
                padding: '12px 16px', marginBottom: '20px',
                display: 'flex', flexWrap: 'wrap', alignItems: 'center', gap: '12px',
            }}>
                <span style={{ fontWeight: 'bold', fontSize: '0.9rem' }}>Exportar:</span>

                {(['xlsx', 'csv', 'pdf'] as ExportFormat[]).map(fmt => (
                    <label key={fmt} style={{ display: 'flex', alignItems: 'center', gap: '4px', cursor: 'pointer', fontSize: '0.88rem' }}>
                        <input
                            type="radio"
                            name="export-format"
                            value={fmt}
                            checked={exportFormat === fmt}
                            onChange={() => { setExportFormat(fmt); setExportJobId(null); setExportError(null); }}
                        />
                        {FORMAT_LABEL[fmt]}
                    </label>
                ))}

                <button
                    onClick={handleExport}
                    disabled={exportSubmitting || isExportRunning}
                    style={{
                        padding: '7px 18px', background: '#0056b3', color: 'white', border: 'none',
                        borderRadius: '4px', cursor: exportSubmitting || isExportRunning ? 'not-allowed' : 'pointer',
                        fontWeight: 'bold', fontSize: '0.88rem',
                        opacity: exportSubmitting || isExportRunning ? 0.7 : 1,
                    }}
                >
                    {exportSubmitting ? 'Iniciando...' : isExportRunning ? 'Gerando arquivo...' : 'Exportar'}
                </button>

                {exportJobId && exportStatus && (
                    <span style={{ fontSize: '0.85rem', color: exportStatus === 'done' ? '#28a745' : exportStatus === 'failed' ? '#dc3545' : '#555' }}>
                        {exportStatus === 'queued' && '⏳ Na fila...'}
                        {exportStatus === 'running' && '⏳ Gerando arquivo...'}
                        {exportStatus === 'done' && '✅ Pronto!'}
                        {exportStatus === 'failed' && '❌ Falha na exportação'}
                    </span>
                )}

                {exportJobId && exportStatus === 'done' && (
                    <a
                        href={apiEndpoints.getExportDownloadUrl(exportJobId)}
                        target="_blank"
                        rel="noreferrer"
                        style={{
                            display: 'inline-block', padding: '7px 18px', background: '#28a745', color: 'white',
                            borderRadius: '4px', fontWeight: 'bold', fontSize: '0.88rem', textDecoration: 'none',
                        }}
                    >
                        Download {FORMAT_LABEL[exportFormat]}
                    </a>
                )}

                {exportError && <span style={{ color: '#dc3545', fontSize: '0.85rem' }}>{exportError}</span>}

                <span style={{ color: '#888', fontSize: '0.8rem', marginLeft: 'auto' }}>
                    Limite: 100k linhas (PDF: 5k)
                </span>
            </div>

            <DataGrid
                columns={columns}
                data={data}
                meta={gridMeta}
                loading={loading}
                error={error}
                onPageChange={(page) => setActivePage(page)}
                onLimitChange={(limit) => { setActiveLimit(limit); setActivePage(1); }}
                onRetry={() => fetchResults(activePage, activeLimit)}
            />
        </div>
    );
}
