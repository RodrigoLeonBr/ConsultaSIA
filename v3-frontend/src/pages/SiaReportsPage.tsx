import React, { useState, useEffect, useRef } from 'react';
import { apiEndpoints } from '../services/api';
import { DataGrid } from '../components/DataGrid';

// Schema do payload do backend
interface SiaReportRow {
    papNum: string;
    competence: string;
    providerId: string;
    procedureCode: string;
    quantityApproved: number;
    federalValue: number;
    cbo: string;
}

export function SiaReportsPage() {
    // Estado Visual / Data
    const [data, setData] = useState<SiaReportRow[]>([]);
    const [meta, setMeta] = useState<any>(null);
    const [loading, setLoading] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);

    // Pagination Params aplicados na query
    const [activePage, setActivePage] = useState<number>(1);
    const [activeLimit, setActiveLimit] = useState<number>(50);

    // Custom Filters aplicados na query (Validados)
    const [activeFilters, setActiveFilters] = useState<{ competence?: string; providerId?: string }>({});

    // Inputs controlados do formulário (que o usuário digita mas ainda não aplicou)
    const [formCompetence, setFormCompetence] = useState<string>('');
    const [formProviderId, setFormProviderId] = useState<string>('');

    // Controle de requisições pendentes (AbortController)
    const abortControllerRef = useRef<AbortController | null>(null);

    const fetchSiaData = async (page: number, limit: number, filters: any) => {
        // Cancela requests anteriores se o usuário mudar de ideia de pagina ou filtro rápido d+
        if (abortControllerRef.current) {
            abortControllerRef.current.abort();
        }
        abortControllerRef.current = new AbortController();

        setLoading(true);
        setError(null);

        try {
            const response = await apiEndpoints.getSiaReports(
                { page, limit, ...filters },
                { signal: abortControllerRef.current.signal }
            );

            setData(response.data.data);
            setMeta(response.data.meta);
        } catch (err: any) {
            if (err.name === 'CanceledError') {
                console.log('Request cancelada pelo AbortController');
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

    // Efeito disparador de fetchData reativo à página, limite e *filtros ativados*
    useEffect(() => {
        fetchSiaData(activePage, activeLimit, activeFilters);

        // Cleanup de desmontagem
        return () => {
            if (abortControllerRef.current) abortControllerRef.current.abort();
        }
    }, [activePage, activeLimit, activeFilters]);

    // Handlers
    const handleApplyFilters = (e: React.FormEvent) => {
        e.preventDefault();
        // Reseta página para a primeira sempre que novos filtros são aplicados
        setActivePage(1);

        // Empurra os filtros do formulário para o estado ATIVO da Query
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
        { key: 'papNum', header: 'Autorização (PAP)' },
        { key: 'competence', header: 'Competência' },
        { key: 'providerId', header: 'CNPJ Prestador' },
        { key: 'procedureCode', header: 'Proc. SIA' },
        { key: 'quantityApproved', header: 'Qtd. Aprovada' },
        {
            key: 'federalValue',
            header: 'Valor Fed. (R$)',
            render: (v: number) => `R$ ${v.toFixed(2).replace('.', ',')}`
        },
        { key: 'cbo', header: 'CBO' },
    ];

    return (
        <div style={{ padding: '20px', maxWidth: '1200px', margin: '0 auto', fontFamily: 'sans-serif' }}>
            <h2>Consulta SIA de Produção (Síncrono V3)</h2>
            <p style={{ color: '#555' }}>Esta tela consome paginadores nativos e filtros aplicados manualmente para proteger o banco legado.</p>

            {/* Box de Filtros - Botão Aplicar Explícito */}
            <form onSubmit={handleApplyFilters} style={{ background: '#f9f9f9', padding: '15px', borderRadius: '8px', marginBottom: '20px', display: 'flex', gap: '15px', alignItems: 'flex-end', border: '1px solid #ddd' }}>
                <div style={{ display: 'flex', flexDirection: 'column', gap: '5px' }}>
                    <label htmlFor="comp">Competência (AAAAMM)</label>
                    <input
                        id="comp"
                        type="text"
                        value={formCompetence}
                        onChange={(e) => setFormCompetence(e.target.value)}
                        placeholder="Ex: 202607"
                        maxLength={6}
                        style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px' }}
                    />
                </div>

                <div style={{ display: 'flex', flexDirection: 'column', gap: '5px' }}>
                    <label htmlFor="cnpj">CNPJ do Prestador</label>
                    <input
                        id="cnpj"
                        type="text"
                        value={formProviderId}
                        onChange={(e) => setFormProviderId(e.target.value)}
                        placeholder="Ex: 12345678000199"
                        maxLength={14}
                        style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px', width: '180px' }}
                    />
                </div>

                <button type="submit" style={{ padding: '9px 20px', backgroundColor: '#0056b3', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer', fontWeight: 'bold' }}>
                    Aplicar Filtros
                </button>

                <button type="button" onClick={handleClearFilters} style={{ padding: '9px 20px', backgroundColor: '#e2e6ea', color: '#333', border: '1px solid #ccc', borderRadius: '4px', cursor: 'pointer' }}>
                    Limpar
                </button>
            </form>

            {/* Motor de Tabela (DataGrid) */}
            <DataGrid
                columns={columns}
                data={data}
                meta={meta}
                loading={loading}
                error={error}
                onPageChange={(page) => setActivePage(page)}
                onLimitChange={(limit) => {
                    setActiveLimit(limit);
                    setActivePage(1); // Reseta a paginacao ao alterar o scale
                }}
                onRetry={() => fetchSiaData(activePage, activeLimit, activeFilters)}
            />
        </div>
    );
}
