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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Badge } from "@/components/ui/badge";
import { useToast } from "@/hooks/use-toast";
import { apiRequest } from "@/lib/queryClient";
import { getAuthHeaders, isUnauthorizedError } from "@/lib/authUtils";
import { PaginatedResponse } from "@/types";
import type { Prestador } from "@shared/schema";

const prestadorFormSchema = z.object({
  codigo: z.string().min(1, "Código é obrigatório"),
  nomeRazaoSocial: z.string().min(1, "Nome/Razão Social é obrigatório"),
  cnpjCpf: z.string().min(11, "CNPJ/CPF deve ter pelo menos 11 caracteres"),
  tipo: z.enum(["pessoa_fisica", "pessoa_juridica"], {
    required_error: "Tipo é obrigatório",
  }),
  status: z.boolean().default(true),
});

type PrestadorFormData = z.infer<typeof prestadorFormSchema>;

export default function PrestadorPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState("");
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<Prestador | null>(null);
  const { toast } = useToast();
  const queryClient = useQueryClient();

  const form = useForm<PrestadorFormData>({
    resolver: zodResolver(prestadorFormSchema),
    defaultValues: {
      codigo: "",
      nomeRazaoSocial: "",
      cnpjCpf: "",
      tipo: "pessoa_juridica",
      status: true,
    },
  });

  const { data, isLoading } = useQuery<PaginatedResponse<Prestador>>({
    queryKey: ["/api/prestador", { page, search }],
    queryFn: async () => {
      const params = new URLSearchParams({
        page: page.toString(),
        limit: "10",
        ...(search && { search }),
      });
      const response = await fetch(`/api/prestador?${params}`, {
        headers: getAuthHeaders(),
      });
      if (!response.ok) throw new Error(`${response.status}: ${response.statusText}`);
      return response.json();
    },
  });

  const createMutation = useMutation({
    mutationFn: async (data: PrestadorFormData) => {
      const response = await apiRequest("POST", "/api/prestador", data);
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/prestador"] });
      setIsDialogOpen(false);
      form.reset();
      toast({
        title: "Sucesso",
        description: "Prestador criado com sucesso",
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
        description: "Erro ao criar prestador",
        variant: "destructive",
      });
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, data }: { id: string; data: Partial<PrestadorFormData> }) => {
      const response = await apiRequest("PUT", `/api/prestador/${id}`, data);
      return response.json();
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/prestador"] });
      setIsDialogOpen(false);
      setEditingItem(null);
      form.reset();
      toast({
        title: "Sucesso",
        description: "Prestador atualizado com sucesso",
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
        description: "Erro ao atualizar prestador",
        variant: "destructive",
      });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id: string) => {
      await apiRequest("DELETE", `/api/prestador/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["/api/prestador"] });
      toast({
        title: "Sucesso",
        description: "Prestador removido com sucesso",
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
        description: "Erro ao remover prestador",
        variant: "destructive",
      });
    },
  });

  const handleCreate = () => {
    setEditingItem(null);
    form.reset({
      codigo: "",
      nomeRazaoSocial: "",
      cnpjCpf: "",
      tipo: "pessoa_juridica",
      status: true,
    });
    setIsDialogOpen(true);
  };

  const handleEdit = (item: Prestador) => {
    setEditingItem(item);
    form.reset({
      codigo: item.codigo,
      nomeRazaoSocial: item.nomeRazaoSocial,
      cnpjCpf: item.cnpjCpf,
      tipo: item.tipo as "pessoa_fisica" | "pessoa_juridica",
      status: item.status,
    });
    setIsDialogOpen(true);
  };

  const handleDelete = (item: Prestador) => {
    if (confirm("Tem certeza que deseja remover este prestador?")) {
      deleteMutation.mutate(item.id);
    }
  };

  const onSubmit = (data: PrestadorFormData) => {
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
      key: "nomeRazaoSocial",
      label: "Nome/Razão Social",
      sortable: true,
    },
    {
      key: "cnpjCpf",
      label: "CNPJ/CPF",
    },
    {
      key: "tipo",
      label: "Tipo",
      render: (value: string) => (
        <Badge variant="outline">
          {value === "pessoa_fisica" ? "Pessoa Física" : "Pessoa Jurídica"}
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
    <div className="p-4 md:p-6 space-y-6" data-testid="prestador-page">
      <DataTable
        title="Prestadores"
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
          placeholder: "Buscar por código, nome ou CNPJ/CPF...",
        }}
        actions={{
          create: {
            label: "Novo Prestador",
            onClick: handleCreate,
          },
          edit: {
            onClick: handleEdit,
          },
          delete: {
            onClick: handleDelete,
          },
        }}
        testId="prestador-table"
      />

      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="max-w-2xl" data-testid="prestador-dialog">
          <DialogHeader>
            <DialogTitle>
              {editingItem ? "Editar Prestador" : "Novo Prestador"}
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
                  name="tipo"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Tipo</FormLabel>
                      <Select onValueChange={field.onChange} value={field.value}>
                        <FormControl>
                          <SelectTrigger data-testid="select-tipo">
                            <SelectValue placeholder="Selecione o tipo" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          <SelectItem value="pessoa_fisica">Pessoa Física</SelectItem>
                          <SelectItem value="pessoa_juridica">Pessoa Jurídica</SelectItem>
                        </SelectContent>
                      </Select>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
              <FormField
                control={form.control}
                name="nomeRazaoSocial"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Nome/Razão Social</FormLabel>
                    <FormControl>
                      <Input {...field} data-testid="input-nome-razao-social" />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              <FormField
                control={form.control}
                name="cnpjCpf"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>CNPJ/CPF</FormLabel>
                    <FormControl>
                      <Input {...field} data-testid="input-cnpj-cpf" />
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
