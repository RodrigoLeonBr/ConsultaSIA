import React from 'react';

interface Column {
    key: string;
    header: string;
    render?: (val: any, row: any) => React.ReactNode;
}

interface DataGridProps {
    columns: Column[];
    data: any[];
    meta: {
        totalRows: number;
        page: number;
        pageSize: number;
        totalPages: number;
        queryTimeMs?: number;
    } | null;
    loading: boolean;
    error: string | null;
    onPageChange: (newPage: number) => void;
    onLimitChange: (newLimit: number) => void;
    onRetry?: () => void;
}

export function DataGrid({
    columns,
    data,
    meta,
    loading,
    error,
    onPageChange,
    onLimitChange,
    onRetry,
}: DataGridProps) {

    if (error) {
        return (
            <div className="error-state" style={{ padding: '20px', border: '1px solid #f5c6cb', backgroundColor: '#f8d7da', color: '#721c24', borderRadius: '4px' }}>
                <h3>Erro ao carregar dados</h3>
                <p>{error}</p>
                {onRetry && <button onClick={onRetry} style={{ padding: '8px 16px', background: '#dc3545', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}>Tentar Novamente</button>}
            </div>
        );
    }

    return (
        <div className="data-grid-container" style={{ display: 'flex', flexDirection: 'column', gap: '10px' }}>

            {/* Controles Acima da Tabela */}
            <div className="grid-controls" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                <div>
                    <label>Linhas por página: </label>
                    <select
                        value={meta?.pageSize || 50}
                        onChange={(e) => onLimitChange(Number(e.target.value))}
                        disabled={loading}
                    >
                        <option value={50}>50</option>
                        <option value={100}>100</option>
                        <option value={200}>200</option>
                    </select>
                </div>
                {meta?.queryTimeMs !== undefined && (
                    <div style={{ fontSize: '0.85rem', color: '#666' }}>
                        Query Time: <strong>{meta.queryTimeMs}ms</strong>
                    </div>
                )}
            </div>

            {/* Tabela de Dados */}
            <div style={{ overflowX: 'auto', border: '1px solid #ddd', minHeight: '300px', position: 'relative' }}>

                {loading && (
                    <div style={{ position: 'absolute', top: 0, left: 0, right: 0, bottom: 0, background: 'rgba(255,255,255,0.7)', display: 'flex', justifyContent: 'center', alignItems: 'center', zIndex: 10 }}>
                        <span>Carregando dados... (Skeletons ocultos)</span>
                    </div>
                )}

                {data.length === 0 && !loading && !error && (
                    <div style={{ padding: '40px', textAlign: 'center', color: '#666' }}>
                        Nenhum resultado encontrado para os filtros atuais.
                    </div>
                )}

                <table style={{ width: '100%', borderCollapse: 'collapse', textAlign: 'left' }}>
                    <thead style={{ backgroundColor: '#f4f4f4', borderBottom: '2px solid #ccc' }}>
                        <tr>
                            {columns.map((col) => (
                                <th key={col.key} style={{ padding: '12px 8px' }}>{col.header}</th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {data.map((row, idx) => (
                            <tr key={idx} style={{ borderBottom: '1px solid #eee' }}>
                                {columns.map((col) => (
                                    <td key={col.key} style={{ padding: '10px 8px' }}>
                                        {col.render ? col.render(row[col.key], row) : row[col.key]}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {/* Paginação */}
            {meta && (
                <div className="pagination" style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '10px 0' }}>
                    <div>
                        Mostrando página <strong>{meta.page}</strong> de <strong>{meta.totalPages}</strong> (Total: {meta.totalRows} registros)
                    </div>
                    <div style={{ display: 'flex', gap: '5px' }}>
                        <button
                            onClick={() => onPageChange(meta.page - 1)}
                            disabled={meta.page <= 1 || loading}
                            style={{ padding: '6px 12px' }}
                        >
                            Anterior
                        </button>
                        <button
                            onClick={() => onPageChange(meta.page + 1)}
                            disabled={meta.page >= meta.totalPages || loading}
                            style={{ padding: '6px 12px' }}
                        >
                            Próxima
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
