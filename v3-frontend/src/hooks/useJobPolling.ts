import { useState, useEffect, useRef } from 'react';
import { apiEndpoints } from '../services/api';

interface JobResponse {
    id: number;
    status: 'queued' | 'running' | 'done' | 'failed';
    errorMessage: string | null;
}

export function useJobPolling(jobId: number | null, intervalMs: number = 2000) {
    const [status, setStatus] = useState<JobResponse['status'] | null>(null);
    const [error, setError] = useState<string | null>(null);
    const isPolling = useRef(false);

    useEffect(() => {
        if (!jobId) {
            setStatus(null);
            setError(null);
            return;
        }

        setStatus('queued');
        setError(null);
        isPolling.current = true;

        const checkStatus = async () => {
            try {
                const { data } = await apiEndpoints.getJobStatus(jobId);
                setStatus(data.status);

                if (data.status === 'done' || data.status === 'failed') {
                    isPolling.current = false;
                    if (data.status === 'failed') {
                        setError(data.errorMessage || 'Erro desconhecido durante o processamento.');
                    }
                }
            } catch (err: any) {
                isPolling.current = false;
                setStatus('failed');
                setError(err.message || 'Falha de rede ao consultar status.');
            }
        };

        // Primeira checagem imediata
        checkStatus();

        // Loop de Polling
        const interval = setInterval(() => {
            if (!isPolling.current) {
                clearInterval(interval);
                return;
            }
            checkStatus();
        }, intervalMs);

        return () => {
            isPolling.current = false;
            clearInterval(interval);
        };
    }, [jobId]);

    return { status, error };
}
