import React, { useState, useEffect, useRef } from 'react';
import { apiEndpoints } from '../services/api';
import { DataGrid } from '../components/DataGrid';

// Reflete os campos de SPrd (s_prd) retornados pelo endpoint
interface SiaReportRow {
    id: number;
    prestadorCnes: string;
    competence: string;
    procedureCode: string;
    cbo: string;
    quantityPresented: number;
    quantityApproved: number;
    valuePresented: string;  // decimal vem como string do TypeORM
    valueApproved: string;
    financingType: string;
    grupo: string;
    subgrupo: string;
    forma: string;
}

const formatDecimal = (v: string | number) =>
    `R$ ${Number(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;

export function SiaReportsPage() {
    const [data, setData] = useState<SiaReportRow[]>([]);
    const [meta, setMeta] = useState<any>(null);
    const [loading, setLoading] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);

    const [activePage, setActivePage] = useState<number>(1);
    const [activeLimit, setActiveLimit] = useState<number>(50);

    // Filtros validados — só disparam fetch quando o usuário clicar em "Aplicar"
    const [activeFilters, setActiveFilters] = useState<{ competence?: string; providerId?: string }>({});

    // Inputs controlados (não disparam fetch no onChange)
    const [formCompetence, setFormCompetence] = useState<string>('');
    const [formProviderId, setFormProviderId] = useState<string>('');

    const abortControllerRef = useRef<AbortController | null>(null);

    const fetchSiaData = async (page: number, limit: number, filters: typeof activeFilters) => {
        // Guard obrigatório — sem competência não faz request (proteção de full table scan)
        if (!filters.competence) {
            setData([]);
            setMeta(null);
            return;
        }

        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
        }
        abortControllerRef.current = new AbortController();

        setLoading(true);
        setError(null);

        try {
            const response = await apiEndpoints.getSiaReports(
                { page, limit, ...filters },
                { signal: abortControllerRef.current.signal },
            );

            setData(response.data.data);
            setMeta(response.data.meta);
        } catch (err: any) {
            if (err.name === 'CanceledError') {
                return;
            }
            const msg = err.response?.data?.message || err.message || 'Erro de comunicação com a API';
            setError(Array.isArray(msg) ? msg.join(', ') : msg);
        } finally {
            if (abortControllerRef.current && !abortControllerRef.current.signal.aborted) {
                setLoading(false);
            }
        }
    };

    useEffect(() => {
        fetchSiaData(activePage, activeLimit, activeFilters);
        return () => { abortControllerRef.current?.abort(); };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activePage, activeLimit, activeFilters]);

    const handleApplyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        setActivePage(1);
        setActiveFilters({
            competence: formCompetence || undefined,
            providerId: formProviderId || undefined,
        });
    };

    const handleClearFilters = () => {
        setFormCompetence('');
        setFormProviderId('');
        setActivePage(1);
        setActiveFilters({});
    };

    const columns = [
        { key: 'prestadorCnes', header: 'CNES Prestador' },
        { key: 'competence', header: 'Competência' },
        { key: 'procedureCode', header: 'Proc. SIA' },
        { key: 'grupo', header: 'Grupo' },
        { key: 'subgrupo', header: 'Subgrupo' },
        { key: 'financingType', header: 'Tipo Fin.' },
        { key: 'quantityApproved', header: 'Qtd Aprovada' },
        {
            key: 'valueApproved',
            header: 'Valor Aprovado',
            render: (v: string) => formatDecimal(v),
        },
        { key: 'cbo', header: 'CBO' },
    ];

    const noCompetence = !activeFilters.competence;

    return (
        <div style={{ padding: '20px', maxWidth: '1400px', margin: '0 auto', fontFamily: 'sans-serif' }}>
            <h2>Consulta SIA de Produção (Síncrono V3)</h2>
            <p style={{ color: '#555' }}>
                Tabela <code>s_prd</code> — consulta paginada, server-side. Competência obrigatória.
            </p>

            {/* Filtros — disparo exclusivo via botão "Aplicar" */}
            <form
                onSubmit={handleApplyFilters}
                style={{
                    background: '#f9f9f9',
                    padding: '15px',
                    borderRadius: '8px',
                    marginBottom: '20px',
                    display: 'flex',
                    gap: '15px',
                    alignItems: 'flex-end',
                    border: '1px solid #ddd',
                    flexWrap: 'wrap',
                }}
            >
                <div style={{ display: 'flex', flexDirection: 'column', gap: '5px' }}>
                    <label htmlFor="comp">
                        Competência (AAAAMM) <span style={{ color: '#dc3545' }}>*</span>
                    </label>
                    <input
                        id="comp"
                        type="text"
                        value={formCompetence}
                        onChange={(e) => setFormCompetence(e.target.value)}
                        placeholder="Ex: 202301"
                        maxLength={6}
                        style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px' }}
                    />
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '5px' }}>
                    <label htmlFor="cnes">
                        CNES do Prestador <span style={{ color: '#888', fontSize: '0.85rem' }}>(opcional)</span>
                    </label>
                    <input
                        id="cnes"
                        type="text"
                        value={formProviderId}
                        onChange={(e) => setFormProviderId(e.target.value)}
                        placeholder="Ex: 2058790"
                        maxLength={7}
                        style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px', width: '120px' }}
                    />
                </div>

                <button
                    type="submit"
                    style={{
                        padding: '9px 20px',
                        backgroundColor: '#0056b3',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        fontWeight: 'bold',
                    }}
                >
                    Aplicar Filtros
                </button>

                <button
                    type="button"
                    onClick={handleClearFilters}
                    style={{
                        padding: '9px 20px',
                        backgroundColor: '#e2e6ea',
                        color: '#333',
                        border: '1px solid #ccc',
                        borderRadius: '4px',
                        cursor: 'pointer',
                    }}
                >
                    Limpar
                </button>
            </form>

            {/* Aviso quando nenhuma competência foi aplicada */}
            {noCompetence && !loading && (
                <div style={{
                    padding: '16px',
                    background: '#fff3cd',
                    border: '1px solid #ffc107',
                    borderRadius: '6px',
                    color: '#856404',
                    marginBottom: '16px',
                }}>
                    Informe a <strong>competência</strong> e clique em "Aplicar Filtros" para iniciar a consulta.
                </div>
            )}

            <DataGrid
                columns={columns}
                data={data}
                meta={meta}
                loading={loading}
                error={error}
                onPageChange={(page) => setActivePage(page)}
                onLimitChange={(limit) => {
                    setActiveLimit(limit);
                    setActivePage(1);
                }}
                onRetry={() => fetchSiaData(activePage, activeLimit, activeFilters)}
            />
        </div>
    );
}
