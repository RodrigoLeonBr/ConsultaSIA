import { useEffect, useState } from 'react';
import { Building2, Stethoscope, Clock, AlertTriangle, BarChart3 } from 'lucide-react';
import { StatCard } from '../components/ui/StatCard';
import { Card } from '../components/ui/Card';
import { Badge } from '../components/ui/Badge';
import { Table } from '../components/ui/Table';
import { Button } from '../components/ui/Button';
import { useNavigate } from 'react-router-dom';
import type { ColumnDef } from '@tanstack/react-table';

interface RecentJob {
  id: number;
  type: string;
  status: string;
  competence: string | null;
  createdAt: string;
}

const STATUS_LABEL: Record<string, string> = {
  queued: 'Na fila',
  running: 'Executando',
  done: 'Concluído',
  failed: 'Falhou',
};

function formatDate(iso: string) {
  try {
    return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
  } catch {
    return iso;
  }
}

const TYPE_LABELS: Record<string, string> = {
  'sia-aggregated': 'SIA Agregado',
  'sia-faturamento-prestador': 'Faturamento',
  'sia-dynamic-production': 'SIA Dinâmico',
  'export': 'Exportação',
};

export function DashboardPage() {
  const navigate = useNavigate();
  const [jobs, setJobs] = useState<RecentJob[]>([]);
  const [stats, setStats] = useState({ prestadores: 0, procedimentos: 0, jobsHoje: 0, jobsFalhos: 0 });
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const baseUrl = import.meta.env.VITE_API_URL ?? 'http://localhost:3001';

    async function load() {
      setLoading(true);
      try {
        const [prestRes, procRes, jobsRes] = await Promise.allSettled([
          fetch(`${baseUrl}/api/prestadores?pageSize=1`).then(r => r.json()),
          fetch(`${baseUrl}/api/procedimentos?pageSize=1`).then(r => r.json()),
          fetch(`${baseUrl}/api/jobs/recent`).then(r => r.json()),
        ]);

        const prestTotal = prestRes.status === 'fulfilled' ? (prestRes.value?.meta?.totalRows ?? 0) : 0;
        const procTotal = procRes.status === 'fulfilled' ? (procRes.value?.meta?.totalRows ?? 0) : 0;

        let recentJobs: RecentJob[] = [];
        let jobsHoje = 0;
        let jobsFalhos = 0;

        if (jobsRes.status === 'fulfilled' && Array.isArray(jobsRes.value?.data)) {
          recentJobs = jobsRes.value.data as RecentJob[];
          const today = new Date().toISOString().slice(0, 10);
          jobsHoje = recentJobs.filter((j: RecentJob) => j.createdAt?.slice(0, 10) === today).length;
          jobsFalhos = recentJobs.filter((j: RecentJob) => j.status === 'failed').length;
        }

        setStats({ prestadores: prestTotal, procedimentos: procTotal, jobsHoje, jobsFalhos });
        setJobs(recentJobs);
      } finally {
        setLoading(false);
      }
    }

    void load();
  }, []);

  const columns: ColumnDef<RecentJob, unknown>[] = [
    {
      accessorKey: 'id',
      header: '#',
      cell: info => <span className="font-mono text-xs text-slate-500">#{info.getValue<number>()}</span>,
    },
    {
      accessorKey: 'type',
      header: 'Tipo',
      cell: info => TYPE_LABELS[info.getValue<string>()] ?? info.getValue<string>(),
    },
    {
      accessorKey: 'status',
      header: 'Status',
      cell: info => {
        const s = info.getValue<string>();
        return (
          <Badge variant={s as 'queued' | 'running' | 'done' | 'failed'}>
            {STATUS_LABEL[s] ?? s}
          </Badge>
        );
      },
    },
    {
      accessorKey: 'competence',
      header: 'Competência',
      cell: info => <span className="font-mono text-xs">{info.getValue<string>() ?? '—'}</span>,
    },
    {
      accessorKey: 'createdAt',
      header: 'Criado em',
      cell: info => formatDate(info.getValue<string>()),
    },
    {
      id: 'actions',
      header: '',
      cell: info =>
        info.row.original.status === 'done' ? (
          <Button
            size="sm"
            variant="ghost"
            onClick={() => navigate(`/job-results/${info.row.original.id}`)}
          >
            Ver →
          </Button>
        ) : null,
    },
  ];

  return (
    <div className="space-y-6">
      {/* Stats */}
      <div className="grid grid-cols-4 gap-4">
        <StatCard label="Prestadores" value={stats.prestadores} icon={<Building2 />} color="primary" />
        <StatCard label="Procedimentos" value={stats.procedimentos} icon={<Stethoscope />} />
        <StatCard label="Jobs Hoje" value={stats.jobsHoje} icon={<Clock />} color="success" />
        <StatCard label="Jobs com Erro" value={stats.jobsFalhos} icon={<AlertTriangle />} color="danger" />
      </div>

      {/* Quick actions */}
      <div className="flex gap-3">
        <Button onClick={() => navigate('/sia-dinamico')} icon={<BarChart3 className="w-4 h-4" />}>
          Novo Relatório SIA
        </Button>
        <Button
          variant="secondary"
          onClick={() => navigate('/async-reports')}
          icon={<Clock className="w-4 h-4" />}
        >
          Relatório Assíncrono
        </Button>
      </div>

      {/* Recent jobs table */}
      <Card title="Jobs Recentes">
        <Table
          columns={columns}
          data={jobs}
          loading={loading}
          emptyMessage="Nenhum job encontrado. Crie um relatório assíncrono para começar."
        />
      </Card>
    </div>
  );
}
