"use client";

import Link from "next/link";
import { useQuery } from "@tanstack/react-query";
import { apiTenant } from "@/lib/api/axios";
import { DataTable } from "@/components/tables/DataTable";
import { useSession } from "next-auth/react";
import { useTenantStore } from "@/stores/tenant-store";
import type { Module } from "@/lib/types/api";

export default function ModulesPage() {
  const { data: session } = useSession();
  const { clientId } = useTenantStore();
  const isInternal = !session?.user?.clientId;

  const { data, isLoading } = useQuery({
    queryKey: ["modules", clientId, session?.user?.clientId],
    queryFn: async () => {
      const params = isInternal && clientId ? { client_id: clientId } : undefined;
      const response = await apiTenant.get("/modules", { params });
      return (response.data?.data ?? []) as Module[];
    },
  });

  if (isLoading) return <div>Carregando...</div>;

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Módulos</h1>
      <DataTable<Module>
        data={data ?? []}
        columns={[
          { key: "id", header: "#" },
          { key: "code", header: "Código" },
          { key: "name", header: "Nome" },
          { key: "version", header: "Versão" },
          { key: "is_active", header: "Estado", render: (row) => (row.is_active ? "Ativo" : "Inativo") },
          {
            key: "actions",
            header: "Ações",
            render: (row) => (
              <Link className="text-sm font-medium text-blue-700 hover:underline" href={`/modules/${row.id}`}>
                Abrir
              </Link>
            ),
          },
        ]}
      />
    </div>
  );
}
