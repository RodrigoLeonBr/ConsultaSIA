import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiEndpoints } from '../services/api';
import { DataGrid } from '../components/DataGrid';

// ─── Types ────────────────────────────────────────────────────────────────────

interface MetadataField {
    id: string;
    label: string;
    type: string;
    allowedOperators: string[];
    sortable: boolean;
    groupable: boolean;
    filterOnly: boolean;
    displayOnly: boolean;
}

interface Metadata {
    producao: {
        description: string;
        fields: MetadataField[];
    };
    limits: {
        maxSelect: number;
        maxFilters: number;
        maxPageSize: number;
    };
}

interface FilterRow {
    id: string;
    fieldId: string;
    operator: string;
    value: string;
    valueTo: string;   // para between
    valueArr: string;  // para in (comma-separated)
}

interface ResultColumn {
    fieldId: string;
    label: string;
    type: string;
    displayAlias?: string;
}

interface ResultMeta {
    totalRows: number;
    page: number;
    pageSize: number;
    totalPages: number;
    queryTimeMs: number;
    hasAggregates: boolean;
    warning?: string;
}

interface QueryResult {
    columns: ResultColumn[];
    rows: any[];
    meta: ResultMeta;
}

interface SavedView {
    name: string;
    competence: string;
    selectedFields: string[];
    filters: FilterRow[];
}

// ─── Utilitários ──────────────────────────────────────────────────────────────

const STORAGE_KEY = 'sia-dynamic-views';

function loadSavedViews(): SavedView[] {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]');
    } catch {
        return [];
    }
}

function saveSavedViews(views: SavedView[]) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(views));
}

function buildFilterValue(row: FilterRow): string | string[] {
    if (row.operator === 'between') return [row.value, row.valueTo];
    if (row.operator === 'in') {
        return row.valueArr.split(',').map(s => s.trim()).filter(Boolean);
    }
    return row.value;
}

// ─── Componente ───────────────────────────────────────────────────────────────

export function SiaDynamicPage() {
    const navigate = useNavigate();
    const abortRef = useRef<AbortController | null>(null);

    // ── Metadados ─────────────────────────────────────────────────────────────
    const [metadata, setMetadata] = useState<Metadata | null>(null);
    const [metaError, setMetaError] = useState<string | null>(null);

    // ── Filtros / parâmetros de query ─────────────────────────────────────────
    const [competence, setCompetence] = useState('');
    const [selectedFields, setSelectedFields] = useState<string[]>([]);
    const [filters, setFilters] = useState<FilterRow[]>([]);
    const [sort, setSort] = useState<{ fieldId: string; direction: 'ASC' | 'DESC' } | null>(null);

    // ── Paginação ─────────────────────────────────────────────────────────────
    const [page, setPage] = useState(1);
    const [pageSize, setPageSize] = useState(50);

    // ── Resultado ─────────────────────────────────────────────────────────────
    const [result, setResult] = useState<QueryResult | null>(null);
    const [loading, setLoading] = useState(false);
    const [queryError, setQueryError] = useState<string | null>(null);

    // ── Views salvas ──────────────────────────────────────────────────────────
    const [savedViews, setSavedViews] = useState<SavedView[]>(() => loadSavedViews());
    const [viewName, setViewName] = useState('');

    // ─── Fetch metadados na montagem ──────────────────────────────────────────
    useEffect(() => {
        apiEndpoints.getSiaDynamicMetadata()
            .then(res => setMetadata(res.data))
            .catch(err => setMetaError(err.response?.data?.message || err.message));
    }, []);

    // ─── Campos disponíveis para cada contexto ────────────────────────────────
    const selectableFields = metadata?.producao.fields.filter(f => !f.filterOnly) ?? [];
    const filterableFields = metadata?.producao.fields.filter(f => !f.displayOnly) ?? [];
    const maxSelect = metadata?.limits.maxSelect ?? 20;
    const maxFilters = metadata?.limits.maxFilters ?? 20;

    // ─── Seleção de colunas ───────────────────────────────────────────────────
    const toggleField = (id: string) => {
        setSelectedFields(prev => {
            if (prev.includes(id)) return prev.filter(f => f !== id);
            if (prev.length >= maxSelect) return prev;
            return [...prev, id];
        });
    };

    // ─── Filtros dinâmicos ────────────────────────────────────────────────────
    const addFilter = () => {
        if (filters.length >= maxFilters) return;
        const firstField = filterableFields[0];
        if (!firstField) return;
        setFilters(prev => [...prev, {
            id: `f${Date.now()}`,
            fieldId: firstField.id,
            operator: firstField.allowedOperators[0] ?? '=',
            value: '',
            valueTo: '',
            valueArr: '',
        }]);
    };

    const removeFilter = (id: string) => setFilters(prev => prev.filter(f => f.id !== id));

    const updateFilter = (id: string, patch: Partial<FilterRow>) => {
        setFilters(prev => prev.map(f => {
            if (f.id !== id) return f;
            const updated = { ...f, ...patch };
            // Ao mudar o campo, resetar o operador para o primeiro permitido
            if (patch.fieldId && patch.fieldId !== f.fieldId) {
                const field = filterableFields.find(mf => mf.id === patch.fieldId);
                updated.operator = field?.allowedOperators[0] ?? '=';
                updated.value = '';
                updated.valueTo = '';
                updated.valueArr = '';
            }
            // Ao mudar o operador, resetar valores
            if (patch.operator && patch.operator !== f.operator) {
                updated.value = '';
                updated.valueTo = '';
                updated.valueArr = '';
            }
            return updated;
        }));
    };

    // ─── Execução da query ────────────────────────────────────────────────────
    const runQuery = useCallback(async (p: number, ps: number) => {
        if (!competence || selectedFields.length === 0) return;

        abortRef.current?.abort();
        abortRef.current = new AbortController();

        setLoading(true);
        setQueryError(null);

        const body = {
            competence,
            select: selectedFields,
            filters: filters.map(f => ({
                fieldId: f.fieldId,
                operator: f.operator,
                value: buildFilterValue(f),
            })),
            page: p,
            pageSize: ps,
            ...(sort ? { sort } : {}),
        };

        try {
            const res = await apiEndpoints.getSiaDynamicProduction(body, {
                signal: abortRef.current.signal,
            });
            setResult(res.data);
        } catch (err: any) {
            if (err.name === 'CanceledError' || err.name === 'AbortError') return;
            const msg = err.response?.data?.message || err.message || 'Erro desconhecido';
            setQueryError(Array.isArray(msg) ? msg.join(' | ') : msg);
        } finally {
            setLoading(false);
        }
    }, [competence, selectedFields, filters, sort]);

    const handleApply = () => {
        setPage(1);
        runQuery(1, pageSize);
    };

    const handlePageChange = (newPage: number) => {
        setPage(newPage);
        runQuery(newPage, pageSize);
    };

    const handleLimitChange = (newLimit: number) => {
        setPageSize(newLimit);
        setPage(1);
        runQuery(1, newLimit);
    };

    // ─── Views salvas ─────────────────────────────────────────────────────────
    const handleSaveView = () => {
        if (!viewName.trim()) return;
        const view: SavedView = { name: viewName.trim(), competence, selectedFields, filters };
        const updated = [...savedViews.filter(v => v.name !== view.name), view];
        setSavedViews(updated);
        saveSavedViews(updated);
        setViewName('');
    };

    const handleLoadView = (view: SavedView) => {
        setCompetence(view.competence);
        setSelectedFields(view.selectedFields);
        setFilters(view.filters);
        setResult(null);
    };

    const handleDeleteView = (name: string) => {
        const updated = savedViews.filter(v => v.name !== name);
        setSavedViews(updated);
        saveSavedViews(updated);
    };

    // ─── Gerar Relatório Completo (job assíncrono) ────────────────────────────
    const handleFullReport = () => {
        const params = {
            type: 'sia-dynamic-production',
            competence,
            select: selectedFields,
            filters: filters.map(f => ({
                fieldId: f.fieldId,
                operator: f.operator,
                value: buildFilterValue(f),
            })),
        };
        const encoded = btoa(encodeURIComponent(JSON.stringify(params)));
        navigate(`/reports/async?preload=${encoded}`);
    };

    // ─── Colunas do DataGrid derivadas do resultado ───────────────────────────
    const gridColumns = result?.columns.map(col => ({
        key: col.displayAlias || col.fieldId,
        header: col.label,
        render: col.type === 'currency'
            ? (val: any) => val != null ? `R$ ${Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}` : '-'
            : col.type === 'number'
            ? (val: any) => val != null ? Number(val).toLocaleString('pt-BR') : '-'
            : undefined,
    })) ?? [];

    // ─── Render ───────────────────────────────────────────────────────────────
    if (metaError) {
        return (
            <div style={{ padding: '20px', color: '#721c24', background: '#f8d7da', borderRadius: '4px' }}>
                <strong>Erro ao carregar metadados:</strong> {metaError}
            </div>
        );
    }

    if (!metadata) {
        return <div style={{ padding: '20px' }}>Carregando catálogo de campos...</div>;
    }

    const canApply = competence.length === 6 && selectedFields.length > 0;

    return (
        <div style={{ padding: '20px', maxWidth: '1200px', margin: '0 auto', fontFamily: 'sans-serif' }}>
            <h2 style={{ marginBottom: '4px' }}>Relatórios Dinâmicos SIA</h2>
            <p style={{ color: '#555', marginBottom: '20px', fontSize: '0.9rem' }}>
                {metadata.producao.description}
            </p>

            {/* ── Layout: dois painéis ── */}
            <div style={{ display: 'flex', gap: '20px', alignItems: 'flex-start' }}>

                {/* ── Painel esquerdo: configuração ── */}
                <div style={{ width: '340px', flexShrink: 0 }}>

                    {/* Competência */}
                    <section style={sectionStyle}>
                        <h3 style={sectionTitle}>Competência</h3>
                        <input
                            type="text"
                            value={competence}
                            onChange={e => setCompetence(e.target.value.replace(/\D/g, '').slice(0, 6))}
                            placeholder="AAAAMM (ex: 202301)"
                            maxLength={6}
                            style={{ ...inputStyle, width: '100%' }}
                        />
                        {competence.length > 0 && competence.length < 6 && (
                            <span style={{ color: '#dc3545', fontSize: '0.8rem' }}>6 dígitos obrigatórios</span>
                        )}
                    </section>

                    {/* Seleção de colunas */}
                    <section style={sectionStyle}>
                        <h3 style={sectionTitle}>
                            Colunas &nbsp;
                            <span style={{ color: selectedFields.length >= maxSelect ? '#dc3545' : '#666', fontSize: '0.85rem' }}>
                                ({selectedFields.length}/{maxSelect})
                            </span>
                        </h3>
                        <div style={{ maxHeight: '260px', overflowY: 'auto', display: 'flex', flexDirection: 'column', gap: '4px' }}>
                            {selectableFields.map(f => (
                                <label
                                    key={f.id}
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: '8px',
                                        cursor: selectedFields.length >= maxSelect && !selectedFields.includes(f.id)
                                            ? 'not-allowed'
                                            : 'pointer',
                                        opacity: selectedFields.length >= maxSelect && !selectedFields.includes(f.id) ? 0.5 : 1,
                                        padding: '3px 0',
                                    }}
                                >
                                    <input
                                        type="checkbox"
                                        checked={selectedFields.includes(f.id)}
                                        onChange={() => toggleField(f.id)}
                                        disabled={selectedFields.length >= maxSelect && !selectedFields.includes(f.id)}
                                    />
                                    <span style={{ fontSize: '0.87rem' }}>
                                        {f.label}
                                        {f.displayOnly && <span style={{ color: '#888', fontSize: '0.75rem' }}> (só exibição)</span>}
                                    </span>
                                </label>
                            ))}
                        </div>
                    </section>

                    {/* Ordenação */}
                    <section style={sectionStyle}>
                        <h3 style={sectionTitle}>Ordenação <span style={{ color: '#888', fontSize: '0.8rem' }}>(opcional)</span></h3>
                        <div style={{ display: 'flex', gap: '6px' }}>
                            <select
                                value={sort?.fieldId ?? ''}
                                onChange={e => {
                                    const id = e.target.value;
                                    setSort(id ? { fieldId: id, direction: sort?.direction ?? 'DESC' } : null);
                                }}
                                style={{ ...inputStyle, flex: 1 }}
                            >
                                <option value="">— sem ordenação —</option>
                                {selectableFields.filter(f => f.sortable && selectedFields.includes(f.id)).map(f => (
                                    <option key={f.id} value={f.id}>{f.label}</option>
                                ))}
                            </select>
                            {sort && (
                                <select
                                    value={sort.direction}
                                    onChange={e => setSort({ ...sort, direction: e.target.value as 'ASC' | 'DESC' })}
                                    style={{ ...inputStyle, width: '80px' }}
                                >
                                    <option value="DESC">DESC</option>
                                    <option value="ASC">ASC</option>
                                </select>
                            )}
                        </div>
                    </section>

                    {/* Views salvas */}
                    <section style={sectionStyle}>
                        <h3 style={sectionTitle}>Configurações Salvas</h3>
                        {savedViews.length > 0 && (
                            <ul style={{ listStyle: 'none', padding: 0, margin: '0 0 8px', display: 'flex', flexDirection: 'column', gap: '4px' }}>
                                {savedViews.map(v => (
                                    <li key={v.name} style={{ display: 'flex', gap: '6px', alignItems: 'center' }}>
                                        <button
                                            onClick={() => handleLoadView(v)}
                                            style={{ ...btnSmallStyle, flex: 1, textAlign: 'left' }}
                                        >
                                            {v.name}
                                        </button>
                                        <button
                                            onClick={() => handleDeleteView(v.name)}
                                            style={{ ...btnSmallStyle, background: '#dc3545', padding: '4px 8px' }}
                                        >
                                            ✕
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                        <div style={{ display: 'flex', gap: '6px' }}>
                            <input
                                type="text"
                                value={viewName}
                                onChange={e => setViewName(e.target.value)}
                                placeholder="Nome da configuração"
                                style={{ ...inputStyle, flex: 1, fontSize: '0.85rem' }}
                            />
                            <button
                                onClick={handleSaveView}
                                disabled={!viewName.trim() || !canApply}
                                style={{ ...btnSmallStyle, background: '#6c757d' }}
                            >
                                Salvar
                            </button>
                        </div>
                    </section>

                </div>

                {/* ── Painel direito: filtros + resultado ── */}
                <div style={{ flex: 1, minWidth: 0 }}>

                    {/* Construtor de filtros */}
                    <section style={sectionStyle}>
                        <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '10px' }}>
                            <h3 style={{ ...sectionTitle, marginBottom: 0 }}>
                                Filtros &nbsp;
                                <span style={{ color: '#666', fontSize: '0.85rem' }}>({filters.length}/{maxFilters})</span>
                            </h3>
                            <button
                                onClick={addFilter}
                                disabled={filters.length >= maxFilters}
                                style={btnSmallStyle}
                            >
                                + Adicionar filtro
                            </button>
                        </div>

                        {filters.length === 0 && (
                            <p style={{ color: '#888', fontSize: '0.85rem', margin: 0 }}>
                                Nenhum filtro adicionado. A competência é sempre aplicada.
                            </p>
                        )}

                        {filters.map(filterRow => {
                            const fieldMeta = filterableFields.find(f => f.id === filterRow.fieldId);
                            const ops = fieldMeta?.allowedOperators ?? [];
                            return (
                                <div key={filterRow.id} style={{
                                    display: 'flex', gap: '6px', alignItems: 'flex-start',
                                    marginBottom: '8px', flexWrap: 'wrap',
                                }}>
                                    {/* Campo */}
                                    <select
                                        value={filterRow.fieldId}
                                        onChange={e => updateFilter(filterRow.id, { fieldId: e.target.value })}
                                        style={{ ...inputStyle, minWidth: '160px' }}
                                    >
                                        {filterableFields.map(f => (
                                            <option key={f.id} value={f.id}>{f.label}</option>
                                        ))}
                                    </select>

                                    {/* Operador */}
                                    <select
                                        value={filterRow.operator}
                                        onChange={e => updateFilter(filterRow.id, { operator: e.target.value })}
                                        style={{ ...inputStyle, width: '110px' }}
                                    >
                                        {ops.map(op => (
                                            <option key={op} value={op}>{op}</option>
                                        ))}
                                    </select>

                                    {/* Valor(es) */}
                                    {filterRow.operator === 'between' ? (
                                        <>
                                            <input
                                                type="text"
                                                value={filterRow.value}
                                                onChange={e => updateFilter(filterRow.id, { value: e.target.value })}
                                                placeholder="De"
                                                style={{ ...inputStyle, width: '90px' }}
                                            />
                                            <input
                                                type="text"
                                                value={filterRow.valueTo}
                                                onChange={e => updateFilter(filterRow.id, { valueTo: e.target.value })}
                                                placeholder="Até"
                                                style={{ ...inputStyle, width: '90px' }}
                                            />
                                        </>
                                    ) : filterRow.operator === 'in' ? (
                                        <input
                                            type="text"
                                            value={filterRow.valueArr}
                                            onChange={e => updateFilter(filterRow.id, { valueArr: e.target.value })}
                                            placeholder="val1, val2, ..."
                                            style={{ ...inputStyle, flex: 1, minWidth: '140px' }}
                                        />
                                    ) : (
                                        <input
                                            type="text"
                                            value={filterRow.value}
                                            onChange={e => updateFilter(filterRow.id, { value: e.target.value })}
                                            placeholder="Valor"
                                            style={{ ...inputStyle, flex: 1, minWidth: '100px' }}
                                        />
                                    )}

                                    <button
                                        onClick={() => removeFilter(filterRow.id)}
                                        style={{ ...btnSmallStyle, background: '#dc3545', padding: '6px 10px' }}
                                    >
                                        ✕
                                    </button>
                                </div>
                            );
                        })}
                    </section>

                    {/* Botões de ação */}
                    <div style={{ display: 'flex', gap: '10px', marginBottom: '20px', alignItems: 'center' }}>
                        <button
                            onClick={handleApply}
                            disabled={!canApply || loading}
                            style={{
                                padding: '10px 28px',
                                background: canApply ? '#0056b3' : '#ccc',
                                color: 'white',
                                border: 'none',
                                borderRadius: '4px',
                                cursor: canApply ? 'pointer' : 'not-allowed',
                                fontWeight: 'bold',
                                fontSize: '0.95rem',
                            }}
                        >
                            {loading ? 'Consultando...' : 'Aplicar'}
                        </button>

                        {result && (
                            <button
                                onClick={handleFullReport}
                                disabled={!canApply}
                                style={{
                                    padding: '10px 20px',
                                    background: '#28a745',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '4px',
                                    cursor: 'pointer',
                                    fontWeight: 'bold',
                                    fontSize: '0.95rem',
                                }}
                            >
                                Gerar Relatório Completo
                            </button>
                        )}

                        {!canApply && competence.length !== 6 && (
                            <span style={{ color: '#888', fontSize: '0.85rem' }}>
                                Preencha competência (6 dígitos) e selecione ao menos uma coluna.
                            </span>
                        )}
                    </div>

                    {/* Warning de query pesada */}
                    {result?.meta.warning && (
                        <div style={{
                            padding: '10px 14px',
                            background: '#fff3cd',
                            border: '1px solid #ffc107',
                            borderRadius: '4px',
                            marginBottom: '12px',
                            fontSize: '0.88rem',
                            color: '#856404',
                        }}>
                            <strong>Aviso:</strong> {result.meta.warning}
                        </div>
                    )}

                    {/* DataGrid */}
                    {(result || loading || queryError) && (
                        <DataGrid
                            columns={gridColumns}
                            data={result?.rows ?? []}
                            meta={result ? {
                                totalRows: result.meta.totalRows,
                                page: result.meta.page,
                                pageSize: result.meta.pageSize,
                                totalPages: result.meta.totalPages,
                                queryTimeMs: result.meta.queryTimeMs,
                            } : null}
                            loading={loading}
                            error={queryError}
                            onPageChange={handlePageChange}
                            onLimitChange={handleLimitChange}
                            onRetry={handleApply}
                        />
                    )}

                    {!result && !loading && !queryError && (
                        <div style={{
                            padding: '40px',
                            textAlign: 'center',
                            color: '#888',
                            border: '1px dashed #ccc',
                            borderRadius: '4px',
                        }}>
                            Selecione colunas, configure filtros e clique em <strong>Aplicar</strong>.
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

// ─── Estilos inline compartilhados ────────────────────────────────────────────

const sectionStyle: React.CSSProperties = {
    background: '#f9f9f9',
    border: '1px solid #ddd',
    borderRadius: '6px',
    padding: '14px',
    marginBottom: '14px',
};

const sectionTitle: React.CSSProperties = {
    margin: '0 0 10px',
    fontSize: '0.95rem',
    fontWeight: 'bold',
    color: '#333',
};

const inputStyle: React.CSSProperties = {
    padding: '6px 8px',
    border: '1px solid #ccc',
    borderRadius: '4px',
    fontSize: '0.875rem',
};

const btnSmallStyle: React.CSSProperties = {
    padding: '6px 12px',
    background: '#0056b3',
    color: 'white',
    border: 'none',
    borderRadius: '4px',
    cursor: 'pointer',
    fontSize: '0.85rem',
    whiteSpace: 'nowrap',
};
