import React, { useState, useEffect, useRef } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { apiEndpoints } from '../services/api';
import { useJobPolling } from '../hooks/useJobPolling';

const STATUS_LABEL: Record<string, string> = {
    queued: 'Na fila...',
    running: 'Processando...',
    done: 'Concluído',
    failed: 'Falha',
};

const STATUS_COLOR: Record<string, string> = {
    queued: '#6c757d',
    running: '#0056b3',
    done: '#28a745',
    failed: '#dc3545',
};

const STATUS_ICON: Record<string, string> = {
    queued: '⏳',
    running: '⏳',
    done: '✅',
    failed: '❌',
};

type JobType = 'sia-aggregated' | 'sia-faturamento-prestador' | 'sia-dynamic-production';

interface PreloadParams {
    type: JobType;
    competence?: string;
    select?: string[];
    filters?: Array<{ fieldId: string; operator: string; value: string | string[] }>;
}

export function AsyncReportsPage() {
    const navigate = useNavigate();
    const [searchParams] = useSearchParams();

    // Formulário
    const [formType, setFormType] = useState<JobType>('sia-faturamento-prestador');
    const [formCompetence, setFormCompetence] = useState('');
    const [formProviderId, setFormProviderId] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [submitError, setSubmitError] = useState<string | null>(null);

    // Parâmetros extras vindos do preload (select/filters para sia-dynamic-production)
    const [preloadedParams, setPreloadedParams] = useState<PreloadParams | null>(null);

    // Job ativo
    const [jobId, setJobId] = useState<number | null>(null);
    const navigatedRef = useRef(false);

    const { status, error: jobError } = useJobPolling(jobId, 2000);

    // ── Decodificar preload da URL ─────────────────────────────────────────────
    useEffect(() => {
        const raw = searchParams.get('preload');
        if (!raw) return;
        try {
            const decoded: PreloadParams = JSON.parse(decodeURIComponent(atob(raw)));
            setPreloadedParams(decoded);
            if (decoded.type) setFormType(decoded.type);
            if (decoded.competence) setFormCompetence(decoded.competence);
        } catch {
            // Ignora preload inválido
        }
    }, [searchParams]);

    // Ao concluir, navegar para a página de resultados
    useEffect(() => {
        if (status === 'done' && jobId && !navigatedRef.current) {
            navigatedRef.current = true;
            navigate(`/reports/results/${jobId}`);
        }
    }, [status, jobId, navigate]);

    const isJobRunning = !!jobId && status !== 'done' && status !== 'failed';

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSubmitting(true);
        setSubmitError(null);
        setJobId(null);
        navigatedRef.current = false;

        try {
            let parameters: Record<string, unknown>;

            if (formType === 'sia-dynamic-production' && preloadedParams) {
                // Usa os parâmetros completos vindos da SiaDynamicPage
                parameters = {
                    competence: formCompetence || preloadedParams.competence,
                    select: preloadedParams.select,
                    filters: preloadedParams.filters ?? [],
                };
            } else {
                parameters = {
                    competence: formCompetence || undefined,
                    providerId: formProviderId || undefined,
                };
            }

            const response = await apiEndpoints.createReportJob({
                type: formType,
                parameters,
            });
            setJobId(response.data.id);
        } catch (err: any) {
            const msg = err.response?.data?.message || err.message || 'Erro ao criar o job';
            setSubmitError(Array.isArray(msg) ? msg.join(', ') : msg);
        } finally {
            setSubmitting(false);
        }
    };

    const handleNewReport = () => {
        setJobId(null);
        navigatedRef.current = false;
        setSubmitError(null);
    };

    const isDynamic = formType === 'sia-dynamic-production';

    return (
        <div style={{ padding: '20px', maxWidth: '800px', margin: '0 auto', fontFamily: 'sans-serif' }}>
            <h2>Relatório Pesado (Assíncrono)</h2>
            <p style={{ color: '#555', marginBottom: '20px' }}>
                Gera um relatório em background via Worker. O resultado é persistido e consultável de forma paginada.
            </p>

            {/* Formulário */}
            <form
                onSubmit={handleSubmit}
                style={{
                    background: '#f9f9f9',
                    padding: '16px',
                    borderRadius: '8px',
                    border: '1px solid #ddd',
                    marginBottom: '24px',
                    opacity: isJobRunning ? 0.6 : 1,
                    pointerEvents: isJobRunning ? 'none' : 'auto',
                }}
            >
                <div style={{ marginBottom: '14px' }}>
                    <label htmlFor="async-type" style={{ fontWeight: 'bold', display: 'block', marginBottom: '4px' }}>
                        Tipo de Relatório
                    </label>
                    <select
                        id="async-type"
                        value={formType}
                        onChange={(e) => {
                            setFormType(e.target.value as JobType);
                            setPreloadedParams(null);
                        }}
                        style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px', minWidth: '320px' }}
                    >
                        <option value="sia-faturamento-prestador">SIA — Faturamento por Prestador</option>
                        <option value="sia-aggregated">SIA — Agregado por CBO</option>
                        <option value="sia-dynamic-production">SIA — Produção Dinâmica</option>
                    </select>
                </div>

                <div style={{ display: 'flex', gap: '15px', flexWrap: 'wrap', marginBottom: '16px' }}>
                    <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                        <label htmlFor="async-comp">Competência (AAAAMM)</label>
                        <input
                            id="async-comp"
                            type="text"
                            value={formCompetence}
                            onChange={(e) => setFormCompetence(e.target.value)}
                            placeholder="Ex: 202407"
                            maxLength={6}
                            style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px' }}
                        />
                    </div>

                    {!isDynamic && (
                        <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                            <label htmlFor="async-cnes">CNES do Prestador <span style={{ color: '#888', fontSize: '0.85rem' }}>(opcional)</span></label>
                            <input
                                id="async-cnes"
                                type="text"
                                value={formProviderId}
                                onChange={(e) => setFormProviderId(e.target.value)}
                                placeholder="Ex: 2058790"
                                maxLength={7}
                                style={{ padding: '8px', border: '1px solid #ccc', borderRadius: '4px', width: '120px' }}
                            />
                        </div>
                    )}
                </div>

                {/* Resumo dos parâmetros dinâmicos (somente-leitura) */}
                {isDynamic && preloadedParams && (
                    <div style={{
                        background: '#e8f4fd',
                        border: '1px solid #b8daff',
                        borderRadius: '4px',
                        padding: '10px 14px',
                        marginBottom: '14px',
                        fontSize: '0.88rem',
                    }}>
                        <strong>Parâmetros da consulta dinâmica:</strong>
                        <br />
                        <span>Colunas: </span>
                        <span style={{ fontFamily: 'monospace' }}>
                            {(preloadedParams.select ?? []).join(', ') || '—'}
                        </span>
                        {preloadedParams.filters && preloadedParams.filters.length > 0 && (
                            <>
                                <br />
                                <span>Filtros: {preloadedParams.filters.length} ativo(s)</span>
                            </>
                        )}
                        <br />
                        <span style={{ color: '#555', fontSize: '0.82rem' }}>
                            Para alterar, volte à página <a href="/reports/sia/dynamic" style={{ color: '#0056b3' }}>SIA — Dinâmico</a>.
                        </span>
                    </div>
                )}

                {isDynamic && !preloadedParams && (
                    <div style={{
                        background: '#fff3cd',
                        border: '1px solid #ffc107',
                        borderRadius: '4px',
                        padding: '10px 14px',
                        marginBottom: '14px',
                        fontSize: '0.88rem',
                        color: '#856404',
                    }}>
                        Para usar o relatório dinâmico, acesse{' '}
                        <a href="/reports/sia/dynamic" style={{ color: '#856404', fontWeight: 'bold' }}>SIA — Dinâmico</a>{' '}
                        e clique em "Gerar Relatório Completo".
                    </div>
                )}

                <button
                    type="submit"
                    disabled={submitting || (isDynamic && !preloadedParams)}
                    style={{
                        padding: '9px 24px',
                        backgroundColor: '#0056b3',
                        color: 'white',
                        border: 'none',
                        borderRadius: '4px',
                        cursor: submitting ? 'not-allowed' : 'pointer',
                        fontWeight: 'bold',
                        opacity: submitting || (isDynamic && !preloadedParams) ? 0.7 : 1,
                    }}
                >
                    {submitting ? 'Criando job...' : 'Gerar Relatório'}
                </button>
            </form>

            {/* Erro de criação */}
            {submitError && (
                <div style={{
                    padding: '12px 16px',
                    border: '1px solid #f5c6cb',
                    backgroundColor: '#f8d7da',
                    color: '#721c24',
                    borderRadius: '4px',
                    marginBottom: '16px',
                }}>
                    <strong>Erro ao criar job:</strong> {submitError}
                </div>
            )}

            {/* Painel de status do job */}
            {jobId && status && (
                <div style={{ border: '1px solid #ddd', borderRadius: '8px', padding: '20px', background: '#fff' }}>
                    <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '12px' }}>
                        <span style={{ fontWeight: 'bold', fontSize: '1rem' }}>Job #{jobId}</span>
                        <span style={{
                            padding: '4px 12px',
                            borderRadius: '12px',
                            backgroundColor: STATUS_COLOR[status] || '#6c757d',
                            color: 'white',
                            fontSize: '0.85rem',
                            fontWeight: 'bold',
                        }}>
                            {STATUS_ICON[status]} {STATUS_LABEL[status] || status}
                        </span>
                    </div>

                    {(status === 'queued' || status === 'running') && (
                        <p style={{ color: '#555', fontSize: '0.9rem', margin: 0 }}>
                            Aguardando o Worker processar... (verificação a cada 2s)
                        </p>
                    )}

                    {status === 'done' && (
                        <p style={{ color: '#155724', margin: 0 }}>
                            Processamento finalizado. Redirecionando para os resultados...
                        </p>
                    )}

                    {status === 'failed' && (
                        <>
                            <div style={{
                                padding: '10px 14px',
                                backgroundColor: '#f8d7da',
                                borderRadius: '4px',
                                color: '#721c24',
                                marginBottom: '12px',
                            }}>
                                <strong>Erro durante o processamento:</strong>
                                <br />
                                {jobError || 'Erro desconhecido.'}
                            </div>
                            <button
                                onClick={handleNewReport}
                                style={{
                                    padding: '8px 18px',
                                    background: '#6c757d',
                                    color: 'white',
                                    border: 'none',
                                    borderRadius: '4px',
                                    cursor: 'pointer',
                                }}
                            >
                                Tentar Novamente
                            </button>
                        </>
                    )}
                </div>
            )}
        </div>
    );
}
