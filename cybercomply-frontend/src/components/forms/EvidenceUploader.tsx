"use client";

import { FormEvent, useRef, useState } from "react";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { apiTenant } from "@/lib/api/axios";
import { Button } from "@/components/ui/button";
import { useSession } from "next-auth/react";
import { useTenantStore } from "@/stores/tenant-store";

export function EvidenceUploader({ responseId }: { responseId?: number }) {
  const inputRef = useRef<HTMLInputElement | null>(null);
  const [selectedResponseId, setSelectedResponseId] = useState<string>(responseId ? String(responseId) : "");
  const queryClient = useQueryClient();
  const { data: session } = useSession();
  const { clientId } = useTenantStore();
  const isInternal = !session?.user?.clientId;

  const mutation = useMutation({
    mutationFn: async () => {
      const file = inputRef.current?.files?.[0];
      if (!file) throw new Error("Selecione um arquivo");
      if (!selectedResponseId) throw new Error("Informe response_id");

      const form = new FormData();
      form.append("response_id", selectedResponseId);
      form.append("file", file);
      if (isInternal && clientId) {
        form.append("client_id", clientId);
      }

      return apiTenant.post("/evidences", form, {
        headers: { "Content-Type": "multipart/form-data" },
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["evidences"] });
    },
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    mutation.mutate();
  };

  return (
    <form onSubmit={submit} className="space-y-3 rounded-lg border border-slate-200 bg-white p-4">
      <h2 className="font-semibold">Upload de Evidência</h2>
      {!responseId && (
        <input
          className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
          value={selectedResponseId}
          onChange={(e) => setSelectedResponseId(e.target.value)}
          placeholder="response_id"
        />
      )}
      <input ref={inputRef} type="file" accept="application/pdf,image/png,image/jpeg" className="block w-full text-sm" />
      <Button type="submit" disabled={mutation.isPending}>
        {mutation.isPending ? "Enviando..." : "Enviar evidência"}
      </Button>
      {mutation.isError && <p className="text-xs text-red-600">Falha no upload.</p>}
      {mutation.isSuccess && <p className="text-xs text-emerald-700">Upload concluído.</p>}
    </form>
  );
}
