import { useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { ReportGenerator } from "@/components/report-generator";
import { DataTable } from "@/components/data-table";
import { getAuthHeaders } from "@/lib/authUtils";
import { PaginatedResponse } from "@/types";
import { Plus, Filter, Download } from "lucide-react";

interface ConsultaProdData {
  id: string;
  prdDtcomp: string;
  prdDtreal: string;
  prdQtd: number;
  prdVlP: string;
  prdCidpri: string | null;
  cbo: {
    id: string;
    codigo: string;
    descricao: string;
  } | null;
  prestador: {
    id: string;
    codigo: string;
    nomeRazaoSocial: string;
  } | null;
  procedimento: {
    id: string;
    codigo: string;
    descricao: string;
  } | null;
  sRub: {
    id: string;
    codigo: string;
    descricao: string;
  } | null;
}

export default function RelatoriosPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [showReportGenerator, setShowReportGenerator] = useState(false);
  const [filters, setFilters] = useState<any>({});

  const { data, isLoading } = useQuery<PaginatedResponse<ConsultaProdData>>({
    queryKey: ["/api/reports/data", { page, search, filters }],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: page.toString(),
        limit: "50",
        ...(search && { search }),
        ...(Object.keys(filters).length > 0 && { filters: JSON.stringify(filters) }),
      });
      const response = await fetch(`/api/reports/data?${params}`, {
        headers: getAuthHeaders(),
      });
      if (!response.ok) throw new Error(`${response.status}: ${response.statusText}`);
      return response.json();
    },
  });

  const columns = [
    {
      key: "prdDtcomp",
      label: "Data Competência",
      render: (value: string) => new Date(value).toLocaleDateString("pt-BR"),
    },
    {
      key: "prestador.nomeRazaoSocial",
      label: "Prestador",
      render: (value: any, row: ConsultaProdData) => row.prestador?.nomeRazaoSocial || "-",
    },
    {
      key: "procedimento.descricao",
      label: "Procedimento",
      render: (value: any, row: ConsultaProdData) => (
        <div>
          <p className="font-medium">{row.procedimento?.descricao || "-"}</p>
          <p className="text-sm text-muted-foreground">{row.procedimento?.codigo}</p>
        </div>
      ),
    },
    {
      key: "prdQtd",
      label: "Quantidade",
    },
    {
      key: "prdVlP",
      label: "Valor",
      render: (value: string) => 
        `R$ ${parseFloat(value).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`,
    },
    {
      key: "status",
      label: "Status",
      render: () => (
        <Badge variant="default">
          Aprovado
        </Badge>
      ),
    },
  ];

  const handleExport = (format: string) => {
    // This would integrate with the export API
    console.log(`Exporting as ${format}`);
  };

  return (
    <div className="p-4 md:p-6 space-y-6" data-testid="relatorios-page">
      {/* Report Generator Card */}
      <Card className="hover-elevate" data-testid="card-report-generator">
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="text-lg font-semibold">Gerador de Relatórios</CardTitle>
            <Button
              onClick={() => setShowReportGenerator(!showReportGenerator)}
              data-testid="button-toggle-report-generator"
            >
              <Plus className="h-4 w-4 mr-2" />
              {showReportGenerator ? "Ocultar Gerador" : "Novo Relatório"}
            </Button>
          </div>
        </CardHeader>
        {showReportGenerator && (
          <CardContent>
            <ReportGenerator
              onFiltersChange={setFilters}
              onExport={handleExport}
            />
          </CardContent>
        )}
      </Card>

      {/* Quick Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card className="hover-elevate" data-testid="card-total-records">
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-muted-foreground">Total de Registros</p>
                <p className="text-3xl font-bold text-foreground">{data?.total.toLocaleString() || "0"}</p>
              </div>
              <div className="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <Filter className="text-blue-600 h-6 w-6" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="hover-elevate" data-testid="card-filtered-records">
          <CardContent className="p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-muted-foreground">Registros Filtrados</p>
                <p className="text-3xl font-bold text-foreground">{data?.data.length || "0"}</p>
              </div>
              <div className="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <Download className="text-green-600 h-6 w-6" />
              </div>
            </div>
          </CardContent>
        </Card>

        <Card className="hover-elevate" data-testid="card-export-options">
          <CardContent className="p-6">
            <div className="space-y-3">
              <p className="text-sm font-medium text-muted-foreground">Exportar Dados</p>
              <div className="flex space-x-2">
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => handleExport("csv")}
                  className="bg-green-50 hover:bg-green-100 text-green-700 border-green-200"
                  data-testid="button-export-csv"
                >
                  CSV
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => handleExport("excel")}
                  className="bg-blue-50 hover:bg-blue-100 text-blue-700 border-blue-200"
                  data-testid="button-export-excel"
                >
                  Excel
                </Button>
                <Button
                  size="sm"
                  variant="outline"
                  onClick={() => handleExport("pdf")}
                  className="bg-red-50 hover:bg-red-100 text-red-700 border-red-200"
                  data-testid="button-export-pdf"
                >
                  PDF
                </Button>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Data Table */}
      <DataTable
        title="Dados de Produção"
        data={data?.data || []}
        columns={columns}
        loading={isLoading}
        pagination={
          data && {
            page,
            limit: 50,
            total: data.total,
            onPageChange: setPage,
          }
        }
        search={{
          value: search,
          onChange: setSearch,
          placeholder: "Buscar nos dados de produção...",
        }}
        actions={{
          export: {
            onClick: () => handleExport("excel"),
          },
        }}
        emptyMessage="Nenhum dado de produção encontrado"
        testId="production-data-table"
      />
    </div>
  );
}
