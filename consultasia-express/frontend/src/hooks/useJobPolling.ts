import { useEffect, useState, useRef } from 'react';
import { getJob } from '../api';
import type { Job } from '../types';

export function useJobPolling(jobId: number | null) {
  const [job, setJob] = useState<Job | null>(null);
  const [error, setError] = useState<string | null>(null);
  const timerRef = useRef<number | undefined>(undefined);

  useEffect(() => {
    if (!jobId) return;
    let active = true;

    const poll = async () => {
      try {
        const j = await getJob(jobId);
        if (active) {
          setJob(j);
          if (j.status !== 'done' && j.status !== 'failed') {
            timerRef.current = setTimeout(poll, 2000);
          }
        }
      } catch (err: unknown) {
        if (active) {
          setError(err instanceof Error ? err.message : 'Erro ao buscar status do job.');
        }
      }
    };

    void poll();
    return () => {
      active = false;
      if (timerRef.current) clearTimeout(timerRef.current);
    };
  }, [jobId]);

  return { job, error };
}
