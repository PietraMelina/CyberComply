"use client";

import { useQuery } from "@tanstack/react-query";
import { apiAuth } from "@/lib/api/axios";
import { DataTable } from "@/components/tables/DataTable";
import { usePermissions } from "@/hooks/usePermissions";

type Client = {
  id: string;
  name: string;
  type: string;
  is_active: boolean;
};

export default function ClientsPage() {
  const { can, isInternal } = usePermissions();

  const { data, isLoading } = useQuery({
    queryKey: ["clients"],
    queryFn: async () => {
      const response = await apiAuth.get("/clients");
      return response.data?.data ?? [];
    },
    enabled: isInternal,
  });

  if (!isInternal) return <div className="rounded bg-white p-4">Acesso negado.</div>;
  if (isLoading) return <div>Carregando...</div>;

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Clientes</h1>
      {can("clients.read") && (
        <DataTable<Client>
          data={data ?? []}
          columns={[
            { key: "id", header: "ID" },
            { key: "name", header: "Nome" },
            { key: "type", header: "Tipo" },
            { key: "is_active", header: "Estado", render: (row) => (row.is_active ? "Ativo" : "Inativo") },
          ]}
        />
      )}
    </div>
  );
}
