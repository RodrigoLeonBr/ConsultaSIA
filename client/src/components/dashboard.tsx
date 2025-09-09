import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ArrowUp, ArrowDown, Stethoscope, DollarSign, Building, PieChart, Plus, ChartBar, Upload, History } from "lucide-react";
import { DashboardStats } from "@/types";
import { Skeleton } from "@/components/ui/skeleton";
import { getAuthHeaders } from "@/lib/authUtils";
import { Link } from "wouter";

function StatCard({ 
  title, 
  value, 
  change, 
  changeType, 
  icon: Icon, 
  iconBg,
  testId 
}: {
  title: string;
  value: string;
  change: string;
  changeType: 'positive' | 'negative';
  icon: any;
  iconBg: string;
  testId: string;
}) {
  return (
    <Card className="hover-elevate" data-testid={testId}>
      <CardContent className="p-6">
        <div className="flex items-center justify-between">
          <div>
            <p className="text-sm font-medium text-muted-foreground">{title}</p>
            <p className="text-3xl font-bold text-foreground">{value}</p>
            <p className={`text-sm flex items-center mt-2 ${
              changeType === 'positive' ? 'text-green-600' : 'text-red-600'
            }`}>
              {changeType === 'positive' ? <ArrowUp className="mr-1 h-3 w-3" /> : <ArrowDown className="mr-1 h-3 w-3" />}
              {change}
            </p>
          </div>
          <div className={`w-12 h-12 ${iconBg} rounded-lg flex items-center justify-center`}>
            <Icon className="text-xl h-6 w-6" />
          </div>
        </div>
      </CardContent>
    </Card>
  );
}

function TopProcedures() {
  const procedures = [
    {
      name: "Consulta Médica - Atenção Básica",
      code: "03.01.01.007-2",
      count: "8,432",
      percentage: "34%",
      color: "bg-blue-500"
    },
    {
      name: "Exame Radiológico",
      code: "02.05.02.007-0", 
      count: "5,621",
      percentage: "23%",
      color: "bg-green-500"
    },
    {
      name: "Procedimento Cirúrgico",
      code: "04.03.02.018-6",
      count: "3,247", 
      percentage: "13%",
      color: "bg-yellow-500"
    },
    {
      name: "Exames Laboratoriais",
      code: "02.02.03.012-4",
      count: "2,891",
      percentage: "12%", 
      color: "bg-red-500"
    }
  ];

  return (
    <Card className="hover-elevate" data-testid="card-top-procedures">
      <CardHeader>
        <CardTitle className="text-lg font-semibold">Principais Procedimentos</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {procedures.map((procedure, index) => (
            <div key={index} className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <div className={`w-2 h-2 ${procedure.color} rounded-full`}></div>
                <div>
                  <p className="text-sm font-medium text-foreground">{procedure.name}</p>
                  <p className="text-xs text-muted-foreground">Código: {procedure.code}</p>
                </div>
              </div>
              <div className="text-right">
                <p className="text-sm font-medium text-foreground">{procedure.count}</p>
                <p className="text-xs text-muted-foreground">{procedure.percentage}</p>
              </div>
            </div>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}

function RecentActivity() {
  const activities = [
    {
      icon: Plus,
      iconBg: "bg-blue-100",
      iconColor: "text-blue-600",
      title: "Novo prestador cadastrado",
      description: "Hospital São José - CNPJ: 12.345.678/0001-90",
      time: "Há 2 horas"
    },
    {
      icon: Upload,
      iconBg: "bg-green-100", 
      iconColor: "text-green-600",
      title: "Relatório exportado",
      description: "Produção Outubro 2024 - Formato Excel",
      time: "Há 4 horas"
    },
    {
      icon: History,
      iconBg: "bg-yellow-100",
      iconColor: "text-yellow-600", 
      title: "Procedimento atualizado",
      description: "Valor alterado: R$ 85,50 → R$ 89,25",
      time: "Ontem às 16:30"
    }
  ];

  return (
    <Card className="lg:col-span-2 hover-elevate" data-testid="card-recent-activity">
      <CardHeader>
        <CardTitle className="text-lg font-semibold">Atividade Recente</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-4">
          {activities.map((activity, index) => (
            <div key={index} className="flex items-start space-x-3">
              <div className={`w-8 h-8 ${activity.iconBg} rounded-full flex items-center justify-center flex-shrink-0`}>
                <activity.icon className={`${activity.iconColor} h-4 w-4`} />
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-foreground">{activity.title}</p>
                <p className="text-sm text-muted-foreground">{activity.description}</p>
                <p className="text-xs text-muted-foreground mt-1">{activity.time}</p>
              </div>
            </div>
          ))}
        </div>
        <div className="mt-4">
          <Button variant="ghost" className="text-primary h-auto p-0" data-testid="button-view-all-activities">
            Ver todas as atividades
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}

function QuickActions() {
  const actions = [
    {
      icon: ChartBar,
      iconBg: "bg-blue-100",
      iconColor: "text-blue-600",
      title: "Gerar Relatório",
      description: "Criar novo relatório dinâmico",
      href: "/relatorios",
      testId: "button-generate-report"
    },
    {
      icon: Plus,
      iconBg: "bg-green-100",
      iconColor: "text-green-600", 
      title: "Novo Procedimento",
      description: "Cadastrar procedimento",
      href: "/procedimento",
      testId: "button-new-procedure"
    },
    {
      icon: Upload,
      iconBg: "bg-purple-100",
      iconColor: "text-purple-600",
      title: "Importar Dados", 
      description: "Upload de arquivo CSV/Excel",
      href: "#",
      testId: "button-import-data"
    },
    {
      icon: History,
      iconBg: "bg-orange-100",
      iconColor: "text-orange-600",
      title: "Log de Auditoria",
      description: "Visualizar atividades do sistema", 
      href: "#",
      testId: "button-audit-log"
    }
  ];

  return (
    <Card className="hover-elevate" data-testid="card-quick-actions">
      <CardHeader>
        <CardTitle className="text-lg font-semibold">Ações Rápidas</CardTitle>
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          {actions.map((action, index) => (
            <Link key={index} href={action.href}>
              <Button
                variant="outline"
                className="w-full justify-start p-3 h-auto hover-elevate"
                data-testid={action.testId}
              >
                <div className="flex items-center space-x-3">
                  <div className={`w-8 h-8 ${action.iconBg} rounded-lg flex items-center justify-center`}>
                    <action.icon className={`${action.iconColor} h-4 w-4`} />
                  </div>
                  <div className="text-left">
                    <p className="text-sm font-medium text-foreground">{action.title}</p>
                    <p className="text-xs text-muted-foreground">{action.description}</p>
                  </div>
                </div>
              </Button>
            </Link>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}

export default function Dashboard() {
  const { data: stats, isLoading: statsLoading } = useQuery<DashboardStats>({
    queryKey: ["/api/dashboard/stats"],
    meta: {
      headers: getAuthHeaders(),
    },
  });

  if (statsLoading) {
    return (
      <div className="p-4 md:p-6 space-y-6">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {[1, 2, 3, 4].map((i) => (
            <Card key={i}>
              <CardContent className="p-6">
                <Skeleton className="h-4 w-32 mb-2" />
                <Skeleton className="h-8 w-24 mb-2" />
                <Skeleton className="h-4 w-28" />
              </CardContent>
            </Card>
          ))}
        </div>
      </div>
    );
  }

  return (
    <div className="p-4 md:p-6 space-y-6" data-testid="dashboard-main">
      {/* Statistics Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard
          title="Total de Procedimentos"
          value={stats?.totalProcedures?.toLocaleString() || "0"}
          change="12.5% vs mês anterior"
          changeType="positive"
          icon={Stethoscope}
          iconBg="bg-blue-100 text-blue-600"
          testId="stat-total-procedures"
        />
        <StatCard
          title="Valor Total"
          value={`R$ ${(stats?.totalValue || 0).toLocaleString('pt-BR', { minimumFractionDigits: 2 })}`}
          change="8.2% vs mês anterior"
          changeType="positive"
          icon={DollarSign}
          iconBg="bg-green-100 text-green-600"
          testId="stat-total-value"
        />
        <StatCard
          title="Prestadores Ativos"
          value={stats?.activePrestadores?.toString() || "0"}
          change="2.1% vs mês anterior"
          changeType="negative"
          icon={Building}
          iconBg="bg-purple-100 text-purple-600"
          testId="stat-active-providers"
        />
        <StatCard
          title="Taxa de Ocupação"
          value={`${stats?.occupancyRate || 0}%`}
          change="3.7% vs mês anterior"
          changeType="positive"
          icon={PieChart}
          iconBg="bg-yellow-100 text-yellow-600"
          testId="stat-occupancy-rate"
        />
      </div>

      {/* Charts Section */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Production Chart */}
        <Card className="hover-elevate" data-testid="card-production-chart">
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle className="text-lg font-semibold">Produção Mensal</CardTitle>
              <div className="flex space-x-2">
                <Button variant="ghost" size="sm" className="text-muted-foreground">6M</Button>
                <Button variant="default" size="sm">1A</Button>
                <Button variant="ghost" size="sm" className="text-muted-foreground">Tudo</Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="h-64 bg-muted rounded-lg flex items-center justify-center">
              <p className="text-muted-foreground">Gráfico de Produção Mensal</p>
            </div>
          </CardContent>
        </Card>

        <TopProcedures />
      </div>

      {/* Recent Activity & Quick Actions */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <RecentActivity />
        <QuickActions />
      </div>
    </div>
  );
}
