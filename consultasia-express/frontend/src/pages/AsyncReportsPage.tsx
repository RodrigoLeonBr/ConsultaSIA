import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { Clock, ArrowRight } from 'lucide-react';
import { Button } from '../components/ui/Button';
import { Input } from '../components/ui/Input';
import { Select } from '../components/ui/Select';
import { Card } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import { Alert } from '../components/ui/Alert';
import { createJob } from '../api';
import { useJobPolling } from '../hooks/useJobPolling';
import type { Job } from '../types';

const JOB_TYPES = [
  { value: 'sia-aggregated',           label: 'SIA Agregado por CBO' },
  { value: 'sia-faturamento-prestador',label: 'Faturamento por Prestador' },
  { value: 'sia-dynamic-production',   label: 'SIA Dinâmico (Personalizado)' },
];

const STATUS_LABEL: Record<string, string> = {
  queued: 'Na fila', running: 'Executando', done: 'Concluído', failed: 'Falhou',
};

export function AsyncReportsPage() {
  const navigate = useNavigate();
  const [jobType, setJobType] = useState('sia-aggregated');
  const [competence, setCompetence] = useState('202301');
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);
  const [createdJob, setCreatedJob] = useState<Job | null>(null);
  const { job, error: pollError } = useJobPolling(createdJob?.id ?? null);

  const displayJob = job ?? createdJob;

  const handleCreate = async () => {
    if (competence.length !== 6) { setSubmitError('Competência deve ter 6 caracteres.'); return; }
    setSubmitting(true); setSubmitError(null);
    try {
      const j = await createJob(jobType, { competence });
      setCreatedJob(j);
    } catch (err: unknown) {
      setSubmitError(err instanceof Error ? err.message : 'Erro ao criar job.');
    } finally { setSubmitting(false); }
  };

  return (
    <div className="max-w-lg space-y-5">
      <Card title="Criar Relatório Assíncrono">
        <div className="space-y-4">
          <Select label="Tipo de Relatório" value={jobType} onChange={e => setJobType(e.target.value)}>
            {JOB_TYPES.map(t => <option key={t.value} value={t.value}>{t.label}</option>)}
          </Select>
          <Input label="Competência" placeholder="AAAAMM" value={competence} onChange={e => setCompetence(e.target.value)} maxLength={6} className="font-mono w-32" />
          {submitError && <Alert variant="error">{submitError}</Alert>}
          <Button onClick={handleCreate} loading={submitting} icon={<Clock className="w-4 h-4" />} className="w-full justify-center">
            Criar Job
          </Button>
        </div>
      </Card>

      {displayJob && (
        <Card title={`Job #${displayJob.id}`}>
          <div className="space-y-3">
            <div className="flex items-center gap-3">
              <span className="text-sm text-slate-600">Status:</span>
              <Badge variant={displayJob.status as 'queued' | 'running' | 'done' | 'failed'}>
                {STATUS_LABEL[displayJob.status] ?? displayJob.status}
              </Badge>
              {displayJob.status === 'running' && <span className="text-xs text-slate-400 animate-pulse">Aguardando worker...</span>}
            </div>
            <p className="text-sm text-slate-600">Tipo: <span className="font-medium">{JOB_TYPES.find(t => t.value === displayJob.type)?.label ?? displayJob.type}</span></p>

            {pollError && <Alert variant="error">{pollError}</Alert>}

            {displayJob.status === 'done' && (
              <Button onClick={() => navigate(`/job-results/${displayJob.id}`)} icon={<ArrowRight className="w-4 h-4" />}>
                Ver Resultados
              </Button>
            )}
            {displayJob.status === 'failed' && (
              <Alert variant="error">{displayJob.errorMessage ?? 'Erro desconhecido.'}</Alert>
            )}
          </div>
        </Card>
      )}
    </div>
  );
}
