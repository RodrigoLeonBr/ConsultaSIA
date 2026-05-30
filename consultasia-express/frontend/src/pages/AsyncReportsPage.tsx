import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { createJob } from '../api';
import { useJobPolling } from '../hooks/useJobPolling';

const STATUS_COLORS: Record<string, string> = {
  queued: '#888',
  running: '#e67e22',
  done: '#27ae60',
  failed: '#c0392b',
};

export function AsyncReportsPage() {
  const navigate = useNavigate();
  const [jobType, setJobType] = useState('sia-aggregated');
  const [competence, setCompetence] = useState('202301');
  const [jobId, setJobId] = useState<number | null>(null);
  const [submitting, setSubmitting] = useState(false);
  const { job, error: pollError } = useJobPolling(jobId);

  const handleSubmit = async () => {
    setSubmitting(true);
    try {
      const j = await createJob(jobType, { competence });
      setJobId(j.id);
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div style={{ padding: 24, maxWidth: 600 }}>
      <h2 style={{ marginTop: 0 }}>Relatórios Assíncronos</h2>

      <div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', marginBottom: 16 }}>
        <select
          value={jobType}
          onChange={e => setJobType(e.target.value)}
          style={{ padding: '5px 8px' }}
        >
          <option value="sia-aggregated">SIA Agregado por CBO</option>
          <option value="sia-faturamento-prestador">Faturamento por Prestador</option>
          <option value="sia-dynamic-production">SIA Dinâmico</option>
        </select>
        <input
          value={competence}
          onChange={e => setCompetence(e.target.value)}
          maxLength={6}
          placeholder="AAAAMM"
          style={{ width: 80, padding: '4px 6px', fontFamily: 'monospace' }}
        />
        <button
          onClick={handleSubmit}
          disabled={submitting || competence.length !== 6}
          style={{ padding: '5px 16px' }}
        >
          Criar Job
        </button>
      </div>

      {pollError && (
        <div style={{ color: '#c0392b', marginBottom: 12 }}>{pollError}</div>
      )}

      {job && (
        <div style={{ border: '1px solid #ddd', borderRadius: 6, padding: 16 }}>
          <div style={{ marginBottom: 8 }}>
            <strong>Job #{job.id}</strong> — {job.type}
          </div>
          <div style={{ marginBottom: 8 }}>
            Status:{' '}
            <strong style={{ color: STATUS_COLORS[job.status] ?? '#333' }}>
              {job.status}
            </strong>
          </div>
          {job.status === 'done' && (
            <button
              onClick={() => navigate(`/job-results/${job.id}`)}
              style={{ padding: '5px 16px', background: '#27ae60', color: '#fff', border: 'none', borderRadius: 4, cursor: 'pointer' }}
            >
              Ver Resultados →
            </button>
          )}
          {job.status === 'failed' && (
            <div style={{ color: '#c0392b', fontSize: 12, marginTop: 8 }}>
              Erro: {job.errorMessage}
            </div>
          )}
          {job.status === 'running' && (
            <div style={{ color: '#e67e22', fontSize: 12 }}>Processando...</div>
          )}
          {job.status === 'queued' && (
            <div style={{ color: '#888', fontSize: 12 }}>Aguardando worker...</div>
          )}
        </div>
      )}
    </div>
  );
}
