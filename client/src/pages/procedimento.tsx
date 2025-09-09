import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { DataTable } from "@/components/data-table";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";
import { getAuthHeaders, isUnauthorizedError } from "@/lib/authUtils";
import { PaginatedResponse } from "@/types";
import type { Procedimento } from "@shared/schema";

const procedimentoFormSchema = z.object({
  codigo: z.string().min(1, "Código é obrigatório"),
  descricao: z.string().min(1, "Descrição é obrigatória"),
  valor: z.string().min(1, "Valor é obrigatório"),
  complexidade: z.enum(["baixa", "media", "alta"], {
    required_error: "Complexidade é obrigatória",
  }),
  status: z.boolean().default(true),
});

type ProcedimentoFormData = z.infer<typeof procedimentoFormSchema>;

export default function ProcedimentoPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<Procedimento | null>(null);
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const form = useForm<ProcedimentoFormData>({
    resolver: zodResolver(procedimentoFormSchema),
    defaultValues: {
      codigo: "",
      descricao: "",
      valor: "",
      complexidade: "media",
      status: true,
    },
  });

  const { data, isLoading } = useQuery<PaginatedResponse<Procedimento>>({
    queryKey: ["/api/procedimento", { page, search }],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: page.toString(),
        limit: "10",
        ...(search && { search }),
      });
      const response = await fetch(`/api/procedimento?${params}`, {
        headers: getAuthHeaders(),
      });
      if (!response.ok) throw new Error(`${response.status}: ${response.statusText}`);
      return response.json();
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: ProcedimentoFormData) => {
      const response = await apiRequest("POST", "/api/procedimento", data);
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/procedimento"] });
      setIsDialogOpen(false);
      form.reset();
      toast({
        title: "Sucesso",
        description: "Procedimento criado com sucesso",
      });
    },
    onError: (error) => {
      if (isUnauthorizedError(error)) {
        toast({
          title: "Erro de autorização",
          description: "Você precisa estar logado para realizar esta ação",
          variant: "destructive",
        });
        return;
      }
      toast({
        title: "Erro",
        description: "Erro ao criar procedimento",
        variant: "destructive",
      });
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, data }: { id: string; data: Partial<ProcedimentoFormData> }) => {
      const response = await apiRequest("PUT", `/api/procedimento/${id}`, data);
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/procedimento"] });
      setIsDialogOpen(false);
      setEditingItem(null);
      form.reset();
      toast({
        title: "Sucesso",
        description: "Procedimento atualizado com sucesso",
      });
    },
    onError: (error) => {
      if (isUnauthorizedError(error)) {
        toast({
          title: "Erro de autorização",
          description: "Você precisa estar logado para realizar esta ação",
          variant: "destructive",
        });
        return;
      }
      toast({
        title: "Erro",
        description: "Erro ao atualizar procedimento",
        variant: "destructive",
      });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: string) => {
      await apiRequest("DELETE", `/api/procedimento/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/procedimento"] });
      toast({
        title: "Sucesso",
        description: "Procedimento removido com sucesso",
      });
    },
    onError: (error) => {
      if (isUnauthorizedError(error)) {
        toast({
          title: "Erro de autorização",
          description: "Você precisa estar logado para realizar esta ação",
          variant: "destructive",
        });
        return;
      }
      toast({
        title: "Erro",
        description: "Erro ao remover procedimento",
        variant: "destructive",
      });
    },
  });

  const handleCreate = () => {
    setEditingItem(null);
    form.reset({
      codigo: "",
      descricao: "",
      valor: "",
      complexidade: "media",
      status: true,
    });
    setIsDialogOpen(true);
  };

  const handleEdit = (item: Procedimento) => {
    setEditingItem(item);
    form.reset({
      codigo: item.codigo,
      descricao: item.descricao,
      valor: item.valor.toString(),
      complexidade: item.complexidade as "baixa" | "media" | "alta",
      status: item.status,
    });
    setIsDialogOpen(true);
  };

  const handleDelete = (item: Procedimento) => {
    if (confirm("Tem certeza que deseja remover este procedimento?")) {
      deleteMutation.mutate(item.id);
    }
  };

  const onSubmit = (data: ProcedimentoFormData) => {
    if (editingItem) {
      updateMutation.mutate({ id: editingItem.id, data });
    } else {
      createMutation.mutate(data);
    }
  };

  const getComplexityBadgeVariant = (complexidade: string) => {
    switch (complexidade) {
      case "baixa":
        return "default";
      case "media":
        return "secondary";
      case "alta":
        return "destructive";
      default:
        return "outline";
    }
  };

  const columns = [
    {
      key: "codigo",
      label: "Código",
      sortable: true,
    },
    {
      key: "descricao",
      label: "Descrição",
      sortable: true,
    },
    {
      key: "valor",
      label: "Valor",
      render: (value: string) => 
        `R$ ${parseFloat(value).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`,
    },
    {
      key: "complexidade",
      label: "Complexidade",
      render: (value: string) => (
        <Badge variant={getComplexityBadgeVariant(value)}>
          {value.charAt(0).toUpperCase() + value.slice(1)}
        </Badge>
      ),
    },
    {
      key: "status",
      label: "Status",
      render: (value: boolean) => (
        <Badge variant={value ? "default" : "secondary"}>
          {value ? "Ativo" : "Inativo"}
        </Badge>
      ),
    },
  ];

  return (
    <div className="p-4 md:p-6 space-y-6" data-testid="procedimento-page">
      <DataTable
        title="Procedimentos"
        data={data?.data || []}
        columns={columns}
        loading={isLoading}
        pagination={
          data && {
            page,
            limit: 10,
            total: data.total,
            onPageChange: setPage,
          }
        }
        search={{
          value: search,
          onChange: setSearch,
          placeholder: "Buscar por código ou descrição...",
        }}
        actions={{
          create: {
            label: "Novo Procedimento",
            onClick: handleCreate,
          },
          edit: {
            onClick: handleEdit,
          },
          delete: {
            onClick: handleDelete,
          },
        }}
        testId="procedimento-table"
      />

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-2xl" data-testid="procedimento-dialog">
          <DialogHeader>
            <DialogTitle>
              {editingItem ? "Editar Procedimento" : "Novo Procedimento"}
            </DialogTitle>
          </DialogHeader>
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="codigo"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Código</FormLabel>
                      <FormControl>
                        <Input {...field} data-testid="input-codigo" />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                <FormField
                  control={form.control}
                  name="valor"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Valor (R$)</FormLabel>
                      <FormControl>
                        <Input
                          {...field}
                          type="number"
                          step="0.01"
                          data-testid="input-valor"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
              <FormField
                control={form.control}
                name="descricao"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Descrição</FormLabel>
                    <FormControl>
                      <Textarea {...field} data-testid="textarea-descricao" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="complexidade"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Complexidade</FormLabel>
                    <Select onValueChange={field.onChange} value={field.value}>
                      <FormControl>
                        <SelectTrigger data-testid="select-complexidade">
                          <SelectValue placeholder="Selecione a complexidade" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="baixa">Baixa</SelectItem>
                        <SelectItem value="media">Média</SelectItem>
                        <SelectItem value="alta">Alta</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="status"
                render={({ field }) => (
                  <FormItem className="flex items-center justify-between">
                    <FormLabel>Ativo</FormLabel>
                    <FormControl>
                      <Switch
                        checked={field.value}
                        onCheckedChange={field.onChange}
                        data-testid="switch-status"
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <div className="flex justify-end space-x-2">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setIsDialogOpen(false)}
                  data-testid="button-cancel"
                >
                  Cancelar
                </Button>
                <Button
                  type="submit"
                  disabled={createMutation.isPending || updateMutation.isPending}
                  data-testid="button-submit"
                >
                  {createMutation.isPending || updateMutation.isPending
                    ? "Salvando..."
                    : editingItem
                    ? "Atualizar"
                    : "Criar"}
                </Button>
              </div>
            </form>
          </Form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
