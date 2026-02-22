import { useState, useEffect, useRef } from 'react';
import axios from 'axios';

interface JobResponse {
    id: number;
    status: 'queued' | 'running' | 'done' | 'failed';
    error_message: string | null;
}

export function useJobPolling(jobId: number | null) {
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
                const { data } = await axios.get<JobResponse>(`http://localhost:3000/reports/jobs/${jobId}`);
                setStatus(data.status);

                if (data.status === 'done' || data.status === 'failed') {
                    isPolling.current = false;
                    if (data.status === 'failed') {
                        setError(data.error_message || 'Erro desconhecido durante o processamento.');
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
        }, 2000);

        return () => {
            isPolling.current = false;
            clearInterval(interval);
        };
    }, [jobId]);

    return { status, error };
}
