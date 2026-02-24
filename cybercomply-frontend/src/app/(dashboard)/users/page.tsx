"use client";

import { useQuery } from "@tanstack/react-query";
import { apiAuth } from "@/lib/api/axios";
import { DataTable } from "@/components/tables/DataTable";
import { UserForm } from "@/components/forms/UserForm";
import type { User } from "@/lib/types/api";

export default function UsersPage() {
  const query = useQuery({
    queryKey: ["users"],
    queryFn: async () => {
      const res = await apiAuth.get("/users?per_page=100");
      return res.data?.data as User[];
    },
  });

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Usuários</h1>
      <div className="grid gap-4 lg:grid-cols-2">
        <UserForm />
        <div className="rounded-lg border border-slate-200 bg-white p-4">
          <h2 className="mb-3 font-semibold">Lista</h2>
          {query.isLoading ? (
            <p>Carregando...</p>
          ) : (
            <DataTable<User>
              data={query.data ?? []}
              columns={[
                { key: "id", header: "ID" },
                { key: "email", header: "Email" },
                { key: "client_id", header: "Cliente" },
                { key: "is_active", header: "Ativo", render: (row) => (row.is_active ? "Sim" : "Não") },
                { key: "mfa_enabled", header: "MFA", render: (row) => (row.mfa_enabled ? "On" : "Off") },
              ]}
            />
          )}
        </div>
      </div>
    </div>
  );
}
