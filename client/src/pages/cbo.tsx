import { useState } from "react";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { DataTable } from "@/components/data-table";
import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from "@/components/ui/dialog";
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";
import { getAuthHeaders, isUnauthorizedError } from "@/lib/authUtils";
import { PaginatedResponse } from "@/types";
import type { CBO, InsertCBO } from "@shared/schema";

const cboFormSchema = z.object({
  codigo: z.string().min(1, "Código é obrigatório"),
  descricao: z.string().min(1, "Descrição é obrigatória"),
  status: z.boolean().default(true),
});

type CBOFormData = z.infer<typeof cboFormSchema>;

export default function CBOPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<CBO | null>(null);
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const form = useForm<CBOFormData>({
    resolver: zodResolver(cboFormSchema),
    defaultValues: {
      codigo: "",
      descricao: "",
      status: true,
    },
  });

  const { data, isLoading } = useQuery<PaginatedResponse<CBO>>({
    queryKey: ["/api/cbo", { page, search }],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: page.toString(),
        limit: "10",
        ...(search && { search }),
      });
      const response = await fetch(`/api/cbo?${params}`, {
        headers: getAuthHeaders(),
      });
      if (!response.ok) throw new Error(`${response.status}: ${response.statusText}`);
      return response.json();
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: CBOFormData) => {
      const response = await apiRequest("POST", "/api/cbo", data);
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/cbo"] });
      setIsDialogOpen(false);
      form.reset();
      toast({
        title: "Sucesso",
        description: "CBO criado com sucesso",
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
        description: "Erro ao criar CBO",
        variant: "destructive",
      });
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, data }: { id: string; data: Partial<CBOFormData> }) => {
      const response = await apiRequest("PUT", `/api/cbo/${id}`, data);
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/cbo"] });
      setIsDialogOpen(false);
      setEditingItem(null);
      form.reset();
      toast({
        title: "Sucesso",
        description: "CBO atualizado com sucesso",
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
        description: "Erro ao atualizar CBO",
        variant: "destructive",
      });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: string) => {
      await apiRequest("DELETE", `/api/cbo/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/cbo"] });
      toast({
        title: "Sucesso",
        description: "CBO removido com sucesso",
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
        description: "Erro ao remover CBO",
        variant: "destructive",
      });
    },
  });

  const handleCreate = () => {
    setEditingItem(null);
    form.reset({
      codigo: "",
      descricao: "",
      status: true,
    });
    setIsDialogOpen(true);
  };

  const handleEdit = (item: CBO) => {
    setEditingItem(item);
    form.reset({
      codigo: item.codigo,
      descricao: item.descricao,
      status: item.status,
    });
    setIsDialogOpen(true);
  };

  const handleDelete = (item: CBO) => {
    if (confirm("Tem certeza que deseja remover este CBO?")) {
      deleteMutation.mutate(item.id);
    }
  };

  const onSubmit = (data: CBOFormData) => {
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
    <div className="p-4 md:p-6 space-y-6" data-testid="cbo-page">
      <DataTable
        title="CBO (Ocupações)"
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
            label: "Nova Ocupação",
            onClick: handleCreate,
          },
          edit: {
            onClick: handleEdit,
          },
          delete: {
            onClick: handleDelete,
          },
        }}
        testId="cbo-table"
      />

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent data-testid="cbo-dialog">
          <DialogHeader>
            <DialogTitle>
              {editingItem ? "Editar CBO" : "Nova CBO"}
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
                      <Input {...field} data-testid="input-descricao" />
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
