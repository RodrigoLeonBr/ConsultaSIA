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
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";
import { getAuthHeaders, isUnauthorizedError } from "@/lib/authUtils";
import { PaginatedResponse } from "@/types";
import type { SRub } from "@shared/schema";

const srubFormSchema = z.object({
  codigo: z.string().min(1, "Código é obrigatório"),
  descricao: z.string().min(1, "Descrição é obrigatória"),
  tipoFinanciamento: z.string().min(1, "Tipo de financiamento é obrigatório"),
  status: z.boolean().default(true),
});

type SRubFormData = z.infer<typeof srubFormSchema>;

export default function SRubPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<SRub | null>(null);
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const form = useForm<SRubFormData>({
    resolver: zodResolver(srubFormSchema),
    defaultValues: {
      codigo: "",
      descricao: "",
      tipoFinanciamento: "",
      status: true,
    },
  });

  const { data, isLoading } = useQuery<PaginatedResponse<SRub>>({
    queryKey: ["/api/srub", { page, search }],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: page.toString(),
        limit: "10",
        ...(search && { search }),
      });
      const response = await fetch(`/api/srub?${params}`, {
        headers: getAuthHeaders(),
      });
      if (!response.ok) throw new Error(`${response.status}: ${response.statusText}`);
      return response.json();
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: SRubFormData) => {
      const response = await apiRequest("POST", "/api/srub", data);
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/srub"] });
      setIsDialogOpen(false);
      form.reset();
      toast({
        title: "Sucesso",
        description: "Financiamento criado com sucesso",
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
        description: "Erro ao criar financiamento",
        variant: "destructive",
      });
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, data }: { id: string; data: Partial<SRubFormData> }) => {
      const response = await apiRequest("PUT", `/api/srub/${id}`, data);
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/srub"] });
      setIsDialogOpen(false);
      setEditingItem(null);
      form.reset();
      toast({
        title: "Sucesso",
        description: "Financiamento atualizado com sucesso",
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
        description: "Erro ao atualizar financiamento",
        variant: "destructive",
      });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: string) => {
      await apiRequest("DELETE", `/api/srub/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/srub"] });
      toast({
        title: "Sucesso",
        description: "Financiamento removido com sucesso",
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
        description: "Erro ao remover financiamento",
        variant: "destructive",
      });
    },
  });

  const handleCreate = () => {
    setEditingItem(null);
    form.reset({
      codigo: "",
      descricao: "",
      tipoFinanciamento: "",
      status: true,
    });
    setIsDialogOpen(true);
  };

  const handleEdit = (item: SRub) => {
    setEditingItem(item);
    form.reset({
      codigo: item.codigo,
      descricao: item.descricao,
      tipoFinanciamento: item.tipoFinanciamento,
      status: item.status,
    });
    setIsDialogOpen(true);
  };

  const handleDelete = (item: SRub) => {
    if (confirm("Tem certeza que deseja remover este financiamento?")) {
      deleteMutation.mutate(item.id);
    }
  };

  const onSubmit = (data: SRubFormData) => {
    if (editingItem) {
      updateMutation.mutate({ id: editingItem.id, data });
    } else {
      createMutation.mutate(data);
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
      key: "tipoFinanciamento",
      label: "Tipo de Financiamento",
      sortable: true,
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
    {
      key: "createdAt",
      label: "Criado em",
      render: (value: string) => new Date(value).toLocaleDateString("pt-BR"),
    },
  ];

  return (
    <div className="p-4 md:p-6 space-y-6" data-testid="srub-page">
      <DataTable
        title="S_RUB (Financiamentos)"
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
            label: "Novo Financiamento",
            onClick: handleCreate,
          },
          edit: {
            onClick: handleEdit,
          },
          delete: {
            onClick: handleDelete,
          },
        }}
        testId="srub-table"
      />

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-2xl" data-testid="srub-dialog">
          <DialogHeader>
            <DialogTitle>
              {editingItem ? "Editar Financiamento" : "Novo Financiamento"}
            </DialogTitle>
          </DialogHeader>
          <Form {...form}>
            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-4">
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
                name="tipoFinanciamento"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Tipo de Financiamento</FormLabel>
                    <FormControl>
                      <Input {...field} data-testid="input-tipo-financiamento" />
                    </FormControl>
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
