"use client";

import { useState } from "react";
import { useMutation } from "@tanstack/react-query";
import { apiTenant } from "@/lib/api/axios";
import { EvidenceUploader } from "@/components/forms/EvidenceUploader";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useSession } from "next-auth/react";
import { useTenantStore } from "@/stores/tenant-store";
import type { Evidence } from "@/lib/types/api";

export default function EvidencesPage() {
  const { data: session } = useSession();
  const { clientId } = useTenantStore();
  const isInternal = !session?.user?.clientId;

  const [responseId, setResponseId] = useState("");
  const [items, setItems] = useState<Evidence[]>([]);

  const listMutation = useMutation({
    mutationFn: async () => {
      if (!responseId) throw new Error("Informe response_id");
      const params = isInternal && clientId ? { client_id: clientId } : undefined;
      const res = await apiTenant.get(`/responses/${responseId}/evidences`, { params });
      return res.data as Evidence[];
    },
    onSuccess: (data) => setItems(data),
  });

  const download = async (token: string) => {
    const params = isInternal && clientId ? { client_id: clientId } : undefined;
    const res = await apiTenant.get(`/evidences/${token}`, {
      params,
      responseType: "blob",
    });

    const url = URL.createObjectURL(new Blob([res.data]));
    const a = document.createElement("a");
    a.href = url;
    a.download = `evidence-${token}`;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
  };

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Evidências</h1>
      <EvidenceUploader />
      <div className="rounded-lg border border-slate-200 bg-white p-4">
        <h2 className="mb-3 font-semibold">Listar por resposta</h2>
        <div className="mb-3 flex gap-2">
          <Input value={responseId} onChange={(e) => setResponseId(e.target.value)} placeholder="response_id" />
          <Button onClick={() => listMutation.mutate()} disabled={listMutation.isPending}>
            {listMutation.isPending ? "Buscando..." : "Buscar"}
          </Button>
        </div>
        <div className="space-y-2">
          {items.map((evidence) => (
            <div key={evidence.id} className="flex items-center justify-between rounded border border-slate-200 p-2 text-sm">
              <div>
                <p className="font-medium">{evidence.original_filename}</p>
                <p className="text-xs text-slate-600">{evidence.internal_token}</p>
              </div>
              <Button variant="outline" onClick={() => download(evidence.internal_token)}>
                Download
              </Button>
            </div>
          ))}
          {items.length === 0 && <p className="text-sm text-slate-500">Sem evidências carregadas.</p>}
        </div>
      </div>
    </div>
  );
}
