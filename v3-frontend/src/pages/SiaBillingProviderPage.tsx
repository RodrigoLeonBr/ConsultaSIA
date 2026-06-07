import React, { useState, useEffect, useRef } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiEndpoints } from '../services/api';
import { DataGrid } from '../components/DataGrid';

interface BillingProviderRow {
    prestadorCnes: string;
    prestadorNome: string;
    financingType: string;
    grupo: string;
    subgrupo: string;
    forma: string;
    procedureCode: string;
    procedureName: string;
    unitValue: string;
    qtyApproved: string;
    valueApproved: string;
    qtyPresented: string;
    valuePresented: string;
}

const fmt = (v: string | number) =>
    `R$ ${Number(v).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.')}`;

const columns = [
    { key: 'prestadorNome', header: 'Prestador' },
    { key: 'prestadorCnes', header: 'CNES' },
    { key: 'financingType', header: 'Tipo Fin.' },
    { key: 'grupo', header: 'Grupo' },
    { key: 'subgrupo', header: 'Subgrupo' },
    { key: 'forma', header: 'Forma' },
    { key: 'procedureCode', header: 'Procedimento' },
    { key: 'procedureName', header: 'Descrição' },
    { key: 'unitValue', header: 'Vlr Unit.', render: (v: string) => fmt(v) },
    { key: 'qtyApproved', header: 'Qtd Aprov.' },
    { key: 'valueApproved', header: 'Vlr Aprov.', render: (v: string) => fmt(v) },
    { key: 'qtyPresented', header: 'Qtd Apres.' },
    { key: 'valuePresented', header: 'Vlr Apres.', render: (v: string) => fmt(v) },
];

export function SiaBillingProviderPage() {
    const navigate = useNavigate();

    const [data, setData] = useState<BillingProviderRow[]>([]);
    const [meta, setMeta] = useState<any>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [activePage, setActivePage] = useState(1);
    const [activeLimit, setActiveLimit] = useState(50);
    const [activeFilters, setActiveFilters] = useState<{ competence?: string; providerId?: string }>({});

    const [formCompetence, setFormCompetence] = useState('');
    const [formProviderId, setFormProviderId] = useState('');

    const abortControllerRef = useRef<AbortController | null>(null);

    const fetchData = async (
        page: number,
        limit: number,
        filters: typeof activeFilters,
    ) => {
        if (!filters.competence) {
            setData([]);
            setMeta(null);
            return;
        }

        if (abortControllerRef.current) abortControllerRef.current.abort();
        abortControllerRef.current = new AbortController();

        setLoading(true);
        setError(null);

        try {
            const response = await apiEndpoints.getSiaBillingProvider(
                { page, limit, competence: filters.competence, providerId: filters.providerId },
                { signal: abortControllerRef.current.signal },
            );
            setData(response.data.data);
            setMeta(response.data.meta);
        } catch (err: any) {
            if (err.name === 'CanceledError') return;
            const msg = err.response?.data?.message || err.message || 'Erro de comunicação com a API';
            setError(Array.isArray(msg) ? msg.join(', ') : msg);
        } finally {
            if (abortControllerRef.current && !abortControllerRef.current.signal.aborted) {
                setLoading(false);
            }
        }
    };

    useEffect(() => {
        fetchData(activePage, activeLimit, activeFilters);
        return () => { abortControllerRef.current?.abort(); };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [activePage, activeLimit, activeFilters]);

    const handleApply = (e: React.FormEvent) => {
        e.preventDefault();
        setActivePage(1);
        setActiveFilters({
            competence: formCompetence || undefined,
            providerId: formProviderId || undefined,
        });
    };

    const handleClear = () => {
        setFormCompetence('');
        setFormProviderId('');
        setActivePage(1);
        setActiveFilters({});
    };

    const handleGenerateJob = () => {
        const params = new URLSearchParams();
        if (formCompetence) params.set('competence', formCompetence);
        if (formProviderId) params.set('providerId', formProviderId);
        navigate(`/reports/async?type=sia-faturamento-prestador&${params.toString()}`);
    };

    const noCompetence = !activeFilters.competence;

    return (
        <div style={{ padding: '20px', maxWidth: '1600px', margin: '0 auto', fontFamily: 'sans-serif' }}>
            <h2>Faturamento por Prestador — SIA</h2>
            <p style={{ color: '#555' }}>
                Relatório hierárquico: Prestador → Tipo Financiamento → Grupo → Subgrupo → Forma → Procedimento.
                Competência obrigatória.
            </p>

            {/* Aviso de performance */}
            <div style={{
                padding: '10px 14px',
                background: '#fff3cd',
                border: '1px solid #ffc107',
                borderRadius: '6px',
                color: '#856404',
                fontSize: '0.88rem',
                marginBottom: '16px',
            }}>
                <strong>Atenção:</strong> Esta consulta envolve agregações complexas (~2-3s por página).
                Para exportar o relatório completo sem espera, use{' '}
                <button
                    onClick={handleGenerateJob}
                    style={{
                        background: 'none',
                        border: 'none',
                        color: '#0056b3',
                        cursor: 'pointer',
                        textDecoration: 'underline',
                        padding: 0,
                        fontSize: '0.88rem',
                    }}
                >
                    Gerar Relatório Completo (assíncrono)
                </button>.
            </div>

            {/* Filtros */}
            <form
                onSubmit={handleApply}
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
                    <label htmlFor="bp-comp">
                        Competência (AAAAMM) <span style={{ color: '#dc3545' }}>*</span>
                    </label>
                    <input
                        id="bp-comp"
                        type="text"
                        value={formCompetence}
                        onChange={(e) => setFormCompetence(e.target.value)}
                        placeholder="Ex: 202301"
                        maxLength={6}
                        style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px' }}
                    />
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '5px' }}>
                    <label htmlFor="bp-cnes">
                        CNES do Prestador{' '}
                        <span style={{ color: '#888', fontSize: '0.85rem' }}>(opcional)</span>
                    </label>
                    <input
                        id="bp-cnes"
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
                    onClick={handleClear}
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

                <button
                    type="button"
                    onClick={handleGenerateJob}
                    style={{
                        padding: '9px 20px',
                        backgroundColor: '#28a745',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        fontWeight: 'bold',
                        marginLeft: 'auto',
                    }}
                >
                    Gerar Relatório Completo
                </button>
            </form>

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
                onRetry={() => fetchData(activePage, activeLimit, activeFilters)}
            />
        </div>
    );
}
