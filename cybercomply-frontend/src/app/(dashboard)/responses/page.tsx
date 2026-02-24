"use client";

import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { apiTenant } from "@/lib/api/axios";
import { DataTable } from "@/components/tables/DataTable";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useSession } from "next-auth/react";
import { useTenantStore } from "@/stores/tenant-store";
import type { ResponseItem } from "@/lib/types/api";

export default function ResponsesPage() {
  const { data: session } = useSession();
  const { clientId } = useTenantStore();
  const isInternal = !session?.user?.clientId;

  const [responseId, setResponseId] = useState("");
  const [history, setHistory] = useState<ResponseItem[]>([]);

  const historyMutation = useMutation({
    mutationFn: async () => {
      if (!responseId) throw new Error("Informe o response_id");
      const params = isInternal && clientId ? { client_id: clientId } : undefined;
      const res = await apiTenant.get(`/responses/${responseId}/history`, { params });
      return res.data as ResponseItem[];
    },
    onSuccess: (data) => setHistory(data),
  });

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Histórico de Respostas</h1>
      <div className="flex gap-2 rounded-lg border border-slate-200 bg-white p-4">
        <Input value={responseId} onChange={(e) => setResponseId(e.target.value)} placeholder="response_id" />
        <Button onClick={() => historyMutation.mutate()} disabled={historyMutation.isPending}>
          {historyMutation.isPending ? "Buscando..." : "Buscar histórico"}
        </Button>
      </div>
      <DataTable<ResponseItem>
        data={history}
        columns={[
          { key: "id", header: "ID" },
          { key: "version", header: "Versão" },
          { key: "status", header: "Status" },
          { key: "is_current", header: "Atual", render: (row) => (row.is_current ? "Sim" : "Não") },
          { key: "answered_at", header: "Respondida em", render: (row) => new Date(row.answered_at).toLocaleString("pt-PT") },
        ]}
      />
    </div>
  );
}
