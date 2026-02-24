"use client";

import { useMemo, useState } from "react";
import { useParams } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { apiTenant } from "@/lib/api/axios";
import { ResponseForm } from "@/components/forms/ResponseForm";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useSession } from "next-auth/react";
import { useTenantStore } from "@/stores/tenant-store";
import type { Module, Question, ResponseItem } from "@/lib/types/api";

export default function ModuleDetailPage() {
  const params = useParams<{ id: string }>();
  const moduleId = Number(params.id);
  const queryClient = useQueryClient();
  const { data: session } = useSession();
  const { clientId } = useTenantStore();
  const isInternal = !session?.user?.clientId;

  const [newQuestionText, setNewQuestionText] = useState("");
  const [newQuestionOrder, setNewQuestionOrder] = useState("1");

  const moduleQuery = useQuery({
    queryKey: ["module", moduleId, clientId, session?.user?.clientId],
    queryFn: async () => {
      const params = isInternal && clientId ? { client_id: clientId } : undefined;
      const res = await apiTenant.get(`/modules/${moduleId}`, { params });
      return res.data as Module & { questions: Question[] };
    },
    enabled: Number.isFinite(moduleId),
  });

  const responsesQuery = useQuery({
    queryKey: ["module-responses", moduleId, clientId, session?.user?.clientId],
    queryFn: async () => {
      const params = isInternal && clientId ? { client_id: clientId } : undefined;
      const res = await apiTenant.get(`/modules/${moduleId}/responses`, { params });
      return (res.data?.data ?? []) as ResponseItem[];
    },
    enabled: Number.isFinite(moduleId),
  });

  const createQuestion = useMutation({
    mutationFn: async () => {
      const params = {
        question_text: newQuestionText,
        order_index: Number(newQuestionOrder),
        weight: 1,
        client_id: isInternal ? clientId : undefined,
      };
      return apiTenant.post(`/modules/${moduleId}/questions`, params);
    },
    onSuccess: () => {
      setNewQuestionText("");
      setNewQuestionOrder("1");
      queryClient.invalidateQueries({ queryKey: ["module", moduleId] });
    },
  });

  const currentResponseByQuestion = useMemo(() => {
    const map = new Map<number, ResponseItem>();
    for (const item of responsesQuery.data ?? []) {
      if (item.is_current && !map.has(item.question_id)) {
        map.set(item.question_id, item);
      }
    }
    return map;
  }, [responsesQuery.data]);

  if (moduleQuery.isLoading) return <div>Carregando módulo...</div>;
  if (!moduleQuery.data) return <div>Módulo não encontrado.</div>;

  const moduleData = moduleQuery.data;

  return (
    <div className="space-y-4">
      <div>
        <h1 className="text-2xl font-bold">{moduleData.code} - {moduleData.name}</h1>
        <p className="text-sm text-slate-600">Versão {moduleData.version}</p>
      </div>

      <div className="rounded-lg border border-slate-200 bg-white p-4">
        <h2 className="mb-3 font-semibold">Nova Pergunta</h2>
        <div className="flex flex-col gap-2 md:flex-row">
          <Input value={newQuestionText} onChange={(e) => setNewQuestionText(e.target.value)} placeholder="Texto da pergunta" />
          <Input value={newQuestionOrder} onChange={(e) => setNewQuestionOrder(e.target.value)} placeholder="Ordem" />
          <Button onClick={() => createQuestion.mutate()} disabled={createQuestion.isPending || !newQuestionText}>
            {createQuestion.isPending ? "Criando..." : "Adicionar"}
          </Button>
        </div>
      </div>

      <div className="space-y-3">
        {(moduleData.questions ?? []).map((q) => {
          const current = currentResponseByQuestion.get(q.id);
          return (
            <div key={q.id} className="rounded-lg border border-slate-200 bg-white p-4">
              <div className="mb-3">
                <p className="font-medium">{q.order_index}. {q.question_text}</p>
                {current && (
                  <p className="text-xs text-slate-600">
                    Atual: {current.status} (v{current.version})
                  </p>
                )}
              </div>
              <ResponseForm questionId={q.id} onCreated={() => queryClient.invalidateQueries({ queryKey: ["module-responses", moduleId] })} />
            </div>
          );
        })}
      </div>
    </div>
  );
}
