import { useState } from "react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { Plus, X, FileText, FileSpreadsheet, FileImage } from "lucide-react";
import { ReportFilter } from "@/types";

interface ReportGeneratorProps {
  onFiltersChange: (filters: any) => void;
  onExport: (format: string) => void;
}

const availableFields = [
  { key: "prdDtcomp", label: "Data Competência" },
  { key: "prdDtreal", label: "Data Realização" },
  { key: "prdCbo", label: "CBO" },
  { key: "prdPrest", label: "Prestador" },
  { key: "prdProc", label: "Procedimento" },
  { key: "prdQtd", label: "Quantidade" },
  { key: "prdVlP", label: "Valor" },
  { key: "prdRub", label: "Rubrica" },
  { key: "prdCidpri", label: "CID Principal" },
];

const filterOperators = [
  { value: "equals", label: "Igual a" },
  { value: "contains", label: "Contém" },
  { value: "startsWith", label: "Inicia com" },
  { value: "endsWith", label: "Termina com" },
  { value: "greaterThan", label: "Maior que" },
  { value: "lessThan", label: "Menor que" },
  { value: "between", label: "Entre" },
];

export function ReportGenerator({ onFiltersChange, onExport }: ReportGeneratorProps) {
  const [selectedFields, setSelectedFields] = useState<string[]>(["prdDtcomp", "prdPrest", "prdProc", "prdVlP"]);
  const [filters, setFilters] = useState<ReportFilter[]>([]);
  const { toast } = useToast();

  const handleFieldToggle = (fieldKey: string, checked: boolean) => {
    if (checked) {
      setSelectedFields([...selectedFields, fieldKey]);
    } else {
      setSelectedFields(selectedFields.filter(f => f !== fieldKey));
    }
  };

  const addFilter = () => {
    const newFilter: ReportFilter = {
      field: "",
      operator: "equals",
      value: "",
      logicalOperator: filters.length > 0 ? "AND" : undefined,
    };
    setFilters([...filters, newFilter]);
  };

  const updateFilter = (index: number, updates: Partial<ReportFilter>) => {
    const newFilters = [...filters];
    newFilters[index] = { ...newFilters[index], ...updates };
    setFilters(newFilters);
    onFiltersChange(buildFilterObject(newFilters));
  };

  const removeFilter = (index: number) => {
    const newFilters = filters.filter((_, i) => i !== index);
    setFilters(newFilters);
    onFiltersChange(buildFilterObject(newFilters));
  };

  const buildFilterObject = (filterList: ReportFilter[]) => {
    const filterObj: any = {};
    filterList.forEach((filter, index) => {
      if (filter.field && filter.value) {
        filterObj[`filter_${index}`] = {
          field: filter.field,
          operator: filter.operator,
          value: filter.value,
          logicalOperator: filter.logicalOperator,
        };
      }
    });
    return filterObj;
  };

  const handleExport = (format: string) => {
    if (selectedFields.length === 0) {
      toast({
        title: "Erro",
        description: "Selecione pelo menos um campo para exportar",
        variant: "destructive",
      });
      return;
    }

    onExport(format);
    toast({
      title: "Exportação iniciada",
      description: `Gerando relatório em formato ${format.toUpperCase()}...`,
    });
  };

  return (
    <div className="space-y-6" data-testid="report-generator">
      {/* Step 1: Field Selection */}
      <Card className="border-border" data-testid="card-field-selection">
        <CardHeader>
          <CardTitle className="text-md font-medium">1. Seleção de Campos</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
            {availableFields.map((field) => (
              <div key={field.key} className="flex items-center space-x-2">
                <Checkbox
                  id={field.key}
                  checked={selectedFields.includes(field.key)}
                  onCheckedChange={(checked) => handleFieldToggle(field.key, checked as boolean)}
                  data-testid={`checkbox-field-${field.key}`}
                />
                <Label
                  htmlFor={field.key}
                  className="text-sm font-normal cursor-pointer"
                >
                  {field.label}
                </Label>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Step 2: Filters */}
      <Card className="border-border" data-testid="card-filters">
        <CardHeader>
          <CardTitle className="text-md font-medium">2. Filtros Avançados</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {filters.map((filter, index) => (
              <div
                key={index}
                className="flex items-center space-x-4 p-3 bg-muted rounded-md"
                data-testid={`filter-${index}`}
              >
                {index > 0 && (
                  <Select
                    value={filter.logicalOperator}
                    onValueChange={(value) => updateFilter(index, { logicalOperator: value as "AND" | "OR" })}
                  >
                    <SelectTrigger className="w-20">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="AND">E</SelectItem>
                      <SelectItem value="OR">OU</SelectItem>
                    </SelectContent>
                  </Select>
                )}
                
                <Select
                  value={filter.field}
                  onValueChange={(value) => updateFilter(index, { field: value })}
                >
                  <SelectTrigger className="w-48">
                    <SelectValue placeholder="Selecione o campo" />
                  </SelectTrigger>
                  <SelectContent>
                    {availableFields.map((field) => (
                      <SelectItem key={field.key} value={field.key}>
                        {field.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>

                <Select
                  value={filter.operator}
                  onValueChange={(value) => updateFilter(index, { operator: value })}
                >
                  <SelectTrigger className="w-36">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {filterOperators.map((operator) => (
                      <SelectItem key={operator.value} value={operator.value}>
                        {operator.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>

                {filter.operator === "between" ? (
                  <div className="flex items-center space-x-2">
                    <Input
                      placeholder="Valor inicial"
                      value={Array.isArray(filter.value) ? filter.value[0] : ""}
                      onChange={(e) => {
                        const currentValue = Array.isArray(filter.value) ? filter.value : ["", ""];
                        updateFilter(index, { value: [e.target.value, currentValue[1] || ""] });
                      }}
                      className="w-32"
                    />
                    <span>e</span>
                    <Input
                      placeholder="Valor final"
                      value={Array.isArray(filter.value) ? filter.value[1] : ""}
                      onChange={(e) => {
                        const currentValue = Array.isArray(filter.value) ? filter.value : ["", ""];
                        updateFilter(index, { value: [currentValue[0] || "", e.target.value] });
                      }}
                      className="w-32"
                    />
                  </div>
                ) : (
                  <Input
                    placeholder="Valor"
                    value={Array.isArray(filter.value) ? filter.value.join(", ") : filter.value}
                    onChange={(e) => updateFilter(index, { value: e.target.value })}
                    className="w-48"
                  />
                )}

                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => removeFilter(index)}
                  data-testid={`button-remove-filter-${index}`}
                >
                  <X className="h-4 w-4 text-destructive" />
                </Button>
              </div>
            ))}
            
            <Button
              variant="outline"
              onClick={addFilter}
              className="text-primary border-primary hover:bg-primary/10"
              data-testid="button-add-filter"
            >
              <Plus className="h-4 w-4 mr-2" />
              Adicionar Filtro
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Step 3: Export Options */}
      <Card className="border-border" data-testid="card-export-options">
        <CardHeader>
          <CardTitle className="text-md font-medium">3. Exportação</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            <div>
              <Label className="text-sm font-medium mb-2 block">Campos Selecionados:</Label>
              <div className="flex flex-wrap gap-2">
                {selectedFields.map((fieldKey) => {
                  const field = availableFields.find(f => f.key === fieldKey);
                  return field ? (
                    <Badge key={fieldKey} variant="outline" data-testid={`badge-selected-field-${fieldKey}`}>
                      {field.label}
                    </Badge>
                  ) : null;
                })}
                {selectedFields.length === 0 && (
                  <span className="text-sm text-muted-foreground">Nenhum campo selecionado</span>
                )}
              </div>
            </div>

            <div className="flex space-x-4">
              <Button
                onClick={() => handleExport("csv")}
                className="bg-green-100 text-green-800 hover:bg-green-200 border-green-200"
                data-testid="button-export-csv"
              >
                <FileText className="h-4 w-4 mr-2" />
                CSV
              </Button>
              <Button
                onClick={() => handleExport("excel")}
                className="bg-blue-100 text-blue-800 hover:bg-blue-200 border-blue-200"
                data-testid="button-export-excel"
              >
                <FileSpreadsheet className="h-4 w-4 mr-2" />
                Excel
              </Button>
              <Button
                onClick={() => handleExport("pdf")}
                className="bg-red-100 text-red-800 hover:bg-red-200 border-red-200"
                data-testid="button-export-pdf"
              >
                <FileImage className="h-4 w-4 mr-2" />
                PDF
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
