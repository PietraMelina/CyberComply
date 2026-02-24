"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiAuth } from "@/lib/api/axios";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useSession } from "next-auth/react";
import { useTenantStore } from "@/stores/tenant-store";
import type { Client } from "@/lib/types/api";

const schema = z.object({
  email: z.string().email(),
  password: z.string().min(10),
  role_id: z.string().min(1),
  client_id: z.string().optional(),
  accepted_terms_version: z.string().min(1),
  company_code: z.string().length(4),
});

type FormValues = z.infer<typeof schema>;

export function UserForm() {
  const queryClient = useQueryClient();
  const { data: session } = useSession();
  const { clientId } = useTenantStore();
  const isInternal = !session?.user?.clientId;

  const { register, handleSubmit, setValue, formState: { errors } } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: {
      accepted_terms_version: "v1",
      company_code: "CYBR",
      client_id: clientId || session?.user?.clientId || "",
    },
  });

  useEffect(() => {
    const cid = clientId || session?.user?.clientId || "";
    setValue("client_id", cid);
  }, [clientId, session?.user?.clientId, setValue]);

  const roles = [
    { id: 1, name: "MASTER", type: "INTERNAL" },
    { id: 2, name: "GESTOR", type: "INTERNAL" },
    { id: 3, name: "AUDITOR", type: "INTERNAL" },
    { id: 4, name: "ADMIN_CLIENTE", type: "CLIENT" },
    { id: 5, name: "TECNICO", type: "CLIENT" },
    { id: 6, name: "LEITURA", type: "CLIENT" },
  ] as const;

  const clientsQuery = useQuery({
    queryKey: ["clients", "compact"],
    queryFn: async (): Promise<Client[]> => {
      const res = await apiAuth.get("/clients?per_page=200");
      return res.data?.data ?? [];
    },
    enabled: isInternal,
  });

  const mutation = useMutation({
    mutationFn: async (values: FormValues) => {
      const payload = {
        ...values,
        role_id: Number(values.role_id),
        client_id: isInternal ? values.client_id || undefined : session?.user?.clientId,
      };
      return apiAuth.post("/users", payload);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["users"] });
    },
  });

  return (
    <form onSubmit={handleSubmit((v) => mutation.mutate(v))} className="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
      <h2 className="font-semibold">Novo Usuário</h2>
      <div>
        <Input placeholder="email@empresa.com" {...register("email")} />
        {errors.email && <p className="mt-1 text-xs text-red-600">Email inválido</p>}
      </div>
      <div>
        <Input type="password" placeholder="Senha forte" {...register("password")} />
        {errors.password && <p className="mt-1 text-xs text-red-600">Senha mínima de 10 caracteres</p>}
      </div>
      <div>
        <select className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" {...register("role_id")}>
          <option value="">Selecione role</option>
          {roles.map((role) => (
            <option key={role.id} value={role.id}>{role.name}</option>
          ))}
        </select>
      </div>
      {isInternal && (
        <div>
          <select className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm" {...register("client_id")}>
            <option value="">Cliente (opcional para role interna)</option>
            {(clientsQuery.data ?? []).map((client) => (
              <option key={client.id} value={client.id}>{client.id} - {client.name}</option>
            ))}
          </select>
        </div>
      )}
      <Button type="submit" disabled={mutation.isPending}>
        {mutation.isPending ? "Criando..." : "Criar Usuário"}
      </Button>
      {mutation.isError && <p className="text-xs text-red-600">Falha ao criar usuário.</p>}
      {mutation.isSuccess && <p className="text-xs text-emerald-700">Usuário criado.</p>}
    </form>
  );
}
