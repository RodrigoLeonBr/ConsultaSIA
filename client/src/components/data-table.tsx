import { useState } from "react";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { 
  ChevronLeft, 
  ChevronRight, 
  Search, 
  Plus, 
  Edit, 
  Trash2,
  Filter,
  Download
} from "lucide-react";
import { cn } from "@/lib/utils";

interface Column<T> {
  key: keyof T | string;
  label: string;
  render?: (value: any, row: T) => React.ReactNode;
  sortable?: boolean;
}

interface DataTableProps<T> {
  data: T[];
  columns: Column<T>[];
  loading?: boolean;
  pagination?: {
    page: number;
    limit: number;
    total: number;
    onPageChange: (page: number) => void;
  };
  search?: {
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
  };
  actions?: {
    create?: {
      label: string;
      onClick: () => void;
    };
    edit?: {
      onClick: (item: T) => void;
    };
    delete?: {
      onClick: (item: T) => void;
    };
    export?: {
      onClick: () => void;
    };
  };
  title: string;
  emptyMessage?: string;
  testId?: string;
}

export function DataTable<T extends { id: string }>({
  data,
  columns,
  loading = false,
  pagination,
  search,
  actions,
  title,
  emptyMessage = "Nenhum registro encontrado",
  testId = "data-table"
}: DataTableProps<T>) {
  const [sortColumn, setSortColumn] = useState<string | null>(null);
  const [sortDirection, setSortDirection] = useState<'asc' | 'desc'>('asc');

  const handleSort = (columnKey: string) => {
    if (sortColumn === columnKey) {
      setSortDirection(sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
      setSortColumn(columnKey);
      setSortDirection('asc');
    }
  };

  const getValueByPath = (obj: any, path: string) => {
    return path.split('.').reduce((current, key) => current?.[key], obj);
  };

  const renderCellValue = (column: Column<T>, row: T) => {
    const value = getValueByPath(row, column.key as string);
    
    if (column.render) {
      return column.render(value, row);
    }
    
    return value;
  };

  return (
    <Card className="w-full" data-testid={testId}>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg font-semibold">{title}</CardTitle>
          <div className="flex items-center space-x-2">
            {actions?.export && (
              <Button
                variant="outline"
                size="sm"
                onClick={actions.export.onClick}
                data-testid="button-export"
              >
                <Download className="h-4 w-4 mr-2" />
                Exportar
              </Button>
            )}
            <Button
              variant="outline"
              size="sm"
              data-testid="button-filter"
            >
              <Filter className="h-4 w-4 mr-2" />
              Filtrar
            </Button>
            {actions?.create && (
              <Button
                onClick={actions.create.onClick}
                size="sm"
                data-testid="button-create"
              >
                <Plus className="h-4 w-4 mr-2" />
                {actions.create.label}
              </Button>
            )}
          </div>
        </div>
        
        {search && (
          <div className="flex items-center space-x-2">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
              <Input
                placeholder={search.placeholder || "Buscar..."}
                value={search.value}
                onChange={(e) => search.onChange(e.target.value)}
                className="pl-10"
                data-testid="input-search"
              />
            </div>
          </div>
        )}
      </CardHeader>

      <CardContent className="p-0">
        <div className="overflow-x-auto">
          <Table>
            <TableHeader>
              <TableRow>
                {columns.map((column) => (
                  <TableHead
                    key={column.key as string}
                    className={cn(
                      "text-left",
                      column.sortable && "cursor-pointer hover:bg-muted"
                    )}
                    onClick={() => column.sortable && handleSort(column.key as string)}
                  >
                    <div className="flex items-center space-x-2">
                      <span>{column.label}</span>
                      {column.sortable && sortColumn === column.key && (
                        <span className="text-xs">
                          {sortDirection === 'asc' ? '↑' : '↓'}
                        </span>
                      )}
                    </div>
                  </TableHead>
                ))}
                {(actions?.edit || actions?.delete) && (
                  <TableHead className="text-center">Ações</TableHead>
                )}
              </TableRow>
            </TableHeader>
            <TableBody>
              {loading ? (
                <TableRow>
                  <TableCell colSpan={columns.length + 1} className="text-center py-8">
                    <div className="flex items-center justify-center">
                      <div className="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
                      <span className="ml-2 text-muted-foreground">Carregando...</span>
                    </div>
                  </TableCell>
                </TableRow>
              ) : data.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={columns.length + 1} className="text-center py-8">
                    <p className="text-muted-foreground">{emptyMessage}</p>
                  </TableCell>
                </TableRow>
              ) : (
                data.map((row, index) => (
                  <TableRow 
                    key={row.id || index} 
                    className="hover:bg-muted"
                    data-testid={`table-row-${index}`}
                  >
                    {columns.map((column) => (
                      <TableCell key={column.key as string}>
                        {renderCellValue(column, row)}
                      </TableCell>
                    ))}
                    {(actions?.edit || actions?.delete) && (
                      <TableCell className="text-center">
                        <div className="flex items-center justify-center space-x-2">
                          {actions?.edit && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => actions.edit?.onClick(row)}
                              data-testid={`button-edit-${index}`}
                            >
                              <Edit className="h-4 w-4" />
                            </Button>
                          )}
                          {actions?.delete && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => actions.delete?.onClick(row)}
                              data-testid={`button-delete-${index}`}
                            >
                              <Trash2 className="h-4 w-4 text-destructive" />
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    )}
                  </TableRow>
                ))
              )}
            </TableBody>
          </Table>
        </div>

        {pagination && (
          <div className="flex items-center justify-between p-4 border-t">
            <div className="text-sm text-muted-foreground">
              Mostrando {((pagination.page - 1) * pagination.limit) + 1} a{' '}
              {Math.min(pagination.page * pagination.limit, pagination.total)} de{' '}
              {pagination.total} resultados
            </div>
            <div className="flex items-center space-x-2">
              <Button
                variant="outline"
                size="sm"
                onClick={() => pagination.onPageChange(pagination.page - 1)}
                disabled={pagination.page <= 1}
                data-testid="button-previous-page"
              >
                <ChevronLeft className="h-4 w-4" />
                Anterior
              </Button>
              
              <div className="flex items-center space-x-1">
                {Array.from({ length: Math.min(5, Math.ceil(pagination.total / pagination.limit)) }, (_, i) => {
                  const pageNum = i + 1;
                  return (
                    <Button
                      key={pageNum}
                      variant={pageNum === pagination.page ? "default" : "outline"}
                      size="sm"
                      onClick={() => pagination.onPageChange(pageNum)}
                      data-testid={`button-page-${pageNum}`}
                    >
                      {pageNum}
                    </Button>
                  );
                })}
              </div>

              <Button
                variant="outline"
                size="sm"
                onClick={() => pagination.onPageChange(pagination.page + 1)}
                disabled={pagination.page >= Math.ceil(pagination.total / pagination.limit)}
                data-testid="button-next-page"
              >
                Próximo
                <ChevronRight className="h-4 w-4" />
              </Button>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
