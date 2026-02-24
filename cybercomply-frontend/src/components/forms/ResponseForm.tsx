"use client";

import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
import { useMutation, useQueryClient } from "@tanstack/react-query";
import { apiTenant } from "@/lib/api/axios";
import { Button } from "@/components/ui/button";
import { useSession } from "next-auth/react";
import { useTenantStore } from "@/stores/tenant-store";

const schema = z.object({
  status: z.enum(["CONFORME", "PARCIAL", "NAO_CONFORME", "NAO_APLICA"]),
  comment: z.string().optional(),
  site_id: z.string().optional(),
});

type Values = z.infer<typeof schema>;

export function ResponseForm({ questionId, onCreated }: { questionId: number; onCreated?: () => void }) {
  const queryClient = useQueryClient();
  const { data: session } = useSession();
  const { clientId } = useTenantStore();
  const isInternal = !session?.user?.clientId;

  const { register, watch, handleSubmit, formState: { errors } } = useForm<Values>({
    resolver: zodResolver(schema),
    defaultValues: { status: "CONFORME", comment: "" },
  });

  const status = watch("status");

  const mutation = useMutation({
    mutationFn: async (values: Values) => {
      if (status === "NAO_CONFORME" && !values.comment) {
        throw new Error("Comentário obrigatório para NAO_CONFORME");
      }

      return apiTenant.post("/responses", {
        question_id: questionId,
        status: values.status,
        comment: values.comment || undefined,
        site_id: values.site_id ? Number(values.site_id) : undefined,
        client_id: isInternal ? clientId : undefined,
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ["module-responses"] });
      onCreated?.();
    },
  });

  return (
    <form onSubmit={handleSubmit((v) => mutation.mutate(v))} className="space-y-2 rounded border border-slate-200 bg-slate-50 p-3">
      <select className="w-full rounded-md border border-slate-300 px-2 py-1 text-sm" {...register("status")}>
        <option value="CONFORME">Conforme</option>
        <option value="PARCIAL">Parcial</option>
        <option value="NAO_CONFORME">Não Conforme</option>
        <option value="NAO_APLICA">Não Aplica</option>
      </select>
      <textarea
        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
        rows={3}
        placeholder="Comentário"
        {...register("comment")}
      />
      {status === "NAO_CONFORME" && !watch("comment") && (
        <p className="text-xs text-amber-700">Comentário é obrigatório para Não Conforme.</p>
      )}
      {errors.status && <p className="text-xs text-red-600">Status inválido.</p>}
      <Button type="submit" disabled={mutation.isPending}>
        {mutation.isPending ? "Salvando..." : "Salvar nova versão"}
      </Button>
      {mutation.isError && <p className="text-xs text-red-600">Falha ao salvar resposta.</p>}
    </form>
  );
}
