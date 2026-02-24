"use client";

import { useMemo, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { apiAuth } from "@/lib/api/axios";
import { DataTable } from "@/components/tables/DataTable";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { usePermissions } from "@/hooks/usePermissions";

type Source = "all" | "db1" | "db2";

type AuditLog = {
  id: number;
  source: "db1" | "db2";
  log_id: string;
  user_id: string;
  client_id: string | null;
  action: string;
  entity_type: string;
  entity_id: string;
  created_at: string;
};

type AuditLogDetail = AuditLog & {
  ip_address?: string;
  request_id?: string;
  before_state?: unknown;
  after_state?: unknown;
  changes_summary?: unknown;
};

type Filters = {
  source: Source;
  user_id: string;
  client_id: string;
  action: string;
  entity_type: string;
  entity_id: string;
  date_from: string;
  date_to: string;
};

type DiffItem = {
  field: string;
  before: unknown;
  after: unknown;
};

type Paginated<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
};

const defaultFilters: Filters = {
  source: "all",
  user_id: "",
  client_id: "",
  action: "",
  entity_type: "",
  entity_id: "",
  date_from: "",
  date_to: "",
};

function parseMaybeJson(value: unknown): unknown {
  if (typeof value !== "string") {
    return value;
  }

  try {
    return JSON.parse(value);
  } catch {
    return value;
  }
}

function computeDiff(beforeState: unknown, afterState: unknown): DiffItem[] {
  const beforeObj = (parseMaybeJson(beforeState) ?? {}) as Record<string, unknown>;
  const afterObj = (parseMaybeJson(afterState) ?? {}) as Record<string, unknown>;

  const rows: DiffItem[] = [];

  function walk(beforeValue: unknown, afterValue: unknown, path: string): void {
    const beforeIsObj = beforeValue !== null && typeof beforeValue === "object" && !Array.isArray(beforeValue);
    const afterIsObj = afterValue !== null && typeof afterValue === "object" && !Array.isArray(afterValue);

    if (beforeIsObj || afterIsObj) {
      const beforeRecord = (beforeValue ?? {}) as Record<string, unknown>;
      const afterRecord = (afterValue ?? {}) as Record<string, unknown>;
      const keys = new Set([...Object.keys(beforeRecord), ...Object.keys(afterRecord)]);

      for (const key of keys) {
        const nextPath = path ? `${path}.${key}` : key;
        walk(beforeRecord[key], afterRecord[key], nextPath);
      }
      return;
    }

    if (JSON.stringify(beforeValue) !== JSON.stringify(afterValue)) {
      rows.push({ field: path, before: beforeValue, after: afterValue });
    }
  }

  walk(beforeObj, afterObj, "");

  return rows;
}

export default function AuditLogsPage() {
  const { can } = usePermissions();

  const [draftFilters, setDraftFilters] = useState<Filters>(defaultFilters);
  const [filters, setFilters] = useState<Filters>(defaultFilters);
  const [page, setPage] = useState(1);
  const [selectedLog, setSelectedLog] = useState<{ id: number; source: Source } | null>(null);

  const params = useMemo(() => {
    const obj: Record<string, string | number> = {
      page,
      per_page: 20,
      source: filters.source,
    };

    for (const [key, value] of Object.entries(filters)) {
      if (value && key !== "source") {
        obj[key] = value;
      }
    }

    return obj;
  }, [filters, page]);

  const listQuery = useQuery({
    queryKey: ["audit-logs", params],
    queryFn: async () => {
      const response = await apiAuth.get("/audit-logs", { params });
      return response.data as Paginated<AuditLog>;
    },
    enabled: can("audit.read"),
  });

  const detailQuery = useQuery({
    queryKey: ["audit-log-detail", selectedLog?.id, selectedLog?.source],
    queryFn: async () => {
      const response = await apiAuth.get(`/audit-logs/${selectedLog?.id}`, {
        params: { source: selectedLog?.source ?? "all" },
      });
      return response.data as AuditLogDetail;
    },
    enabled: can("audit.read") && !!selectedLog,
  });

  if (!can("audit.read")) {
    return <div className="rounded bg-white p-4">Acesso negado.</div>;
  }

  const data = listQuery.data?.data ?? [];
  const pagination = listQuery.data;

  const exportCsv = async () => {
    const response = await apiAuth.get("/audit-logs/export", {
      params,
      responseType: "blob",
    });

    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement("a");
    link.href = url;
    link.setAttribute("download", `audit-logs-${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  };

  const detail = detailQuery.data;
  const beforeState = parseMaybeJson(detail?.before_state);
  const afterState = parseMaybeJson(detail?.after_state);
  const summary = parseMaybeJson(detail?.changes_summary);
  const computedDiff = computeDiff(beforeState, afterState);

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Logs de Auditoria</h1>

      <div className="rounded-lg border border-slate-200 bg-white p-4">
        <div className="grid gap-2 md:grid-cols-4">
          <select
            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm"
            value={draftFilters.source}
            onChange={(e) => setDraftFilters((prev) => ({ ...prev, source: e.target.value as Source }))}
          >
            <option value="all">Fonte: todas</option>
            <option value="db1">Fonte: db1</option>
            <option value="db2">Fonte: db2</option>
          </select>
          <Input
            value={draftFilters.user_id}
            onChange={(e) => setDraftFilters((prev) => ({ ...prev, user_id: e.target.value }))}
            placeholder="user_id"
          />
          <Input
            value={draftFilters.client_id}
            onChange={(e) => setDraftFilters((prev) => ({ ...prev, client_id: e.target.value }))}
            placeholder="client_id"
          />
          <Input
            value={draftFilters.action}
            onChange={(e) => setDraftFilters((prev) => ({ ...prev, action: e.target.value }))}
            placeholder="action"
          />
          <Input
            value={draftFilters.entity_type}
            onChange={(e) => setDraftFilters((prev) => ({ ...prev, entity_type: e.target.value }))}
            placeholder="entity_type"
          />
          <Input
            value={draftFilters.entity_id}
            onChange={(e) => setDraftFilters((prev) => ({ ...prev, entity_id: e.target.value }))}
            placeholder="entity_id"
          />
          <Input
            type="date"
            value={draftFilters.date_from}
            onChange={(e) => setDraftFilters((prev) => ({ ...prev, date_from: e.target.value }))}
          />
          <Input
            type="date"
            value={draftFilters.date_to}
            onChange={(e) => setDraftFilters((prev) => ({ ...prev, date_to: e.target.value }))}
          />
        </div>

        <div className="mt-3 flex items-center gap-2">
          <Button
            onClick={() => {
              setPage(1);
              setFilters(draftFilters);
            }}
          >
            Aplicar filtros
          </Button>
          <Button
            variant="outline"
            onClick={() => {
              setDraftFilters(defaultFilters);
              setFilters(defaultFilters);
              setPage(1);
            }}
          >
            Limpar
          </Button>
          <Button variant="outline" onClick={exportCsv}>
            Exportar CSV
          </Button>
        </div>
      </div>

      {listQuery.isLoading ? (
        <div>Carregando...</div>
      ) : (
        <>
          <DataTable<AuditLog>
            data={data}
            columns={[
              { key: "id", header: "ID" },
              { key: "source", header: "Fonte" },
              { key: "user_id", header: "Usuário" },
              { key: "client_id", header: "Cliente" },
              { key: "action", header: "Ação" },
              { key: "entity_type", header: "Entidade" },
              { key: "entity_id", header: "Entidade ID" },
              {
                key: "created_at",
                header: "Data",
                render: (row) => new Date(row.created_at).toLocaleString("pt-PT"),
              },
              {
                key: "details",
                header: "Detalhe",
                render: (row) => (
                  <Button variant="outline" onClick={() => setSelectedLog({ id: row.id, source: row.source })}>
                    Ver
                  </Button>
                ),
              },
            ]}
          />

          <div className="flex items-center justify-between rounded-lg border border-slate-200 bg-white p-3 text-sm">
            <div>
              Página {pagination?.current_page ?? 1} de {pagination?.last_page ?? 1} | Total: {pagination?.total ?? 0}
            </div>
            <div className="flex gap-2">
              <Button variant="outline" disabled={(pagination?.current_page ?? 1) <= 1} onClick={() => setPage((p) => p - 1)}>
                Anterior
              </Button>
              <Button
                variant="outline"
                disabled={(pagination?.current_page ?? 1) >= (pagination?.last_page ?? 1)}
                onClick={() => setPage((p) => p + 1)}
              >
                Próxima
              </Button>
            </div>
          </div>
        </>
      )}

      <div className="rounded-lg border border-slate-200 bg-white p-4">
        <h2 className="mb-3 font-semibold">Detalhe do Log</h2>
        {!selectedLog && <p className="text-sm text-slate-500">Selecione um item na tabela para ver o detalhe.</p>}
        {selectedLog && detailQuery.isLoading && <p className="text-sm">Carregando detalhe...</p>}
        {selectedLog && !detailQuery.isLoading && !detail && <p className="text-sm text-red-600">Não foi possível carregar o detalhe.</p>}

        {detail && (
          <div className="space-y-4 text-sm">
            <div className="grid gap-2 md:grid-cols-3">
              <div><span className="font-semibold">ID:</span> {detail.id}</div>
              <div><span className="font-semibold">Fonte:</span> {detail.source}</div>
              <div><span className="font-semibold">Log ID:</span> {detail.log_id}</div>
              <div><span className="font-semibold">Usuário:</span> {detail.user_id}</div>
              <div><span className="font-semibold">Cliente:</span> {detail.client_id || "-"}</div>
              <div><span className="font-semibold">Ação:</span> {detail.action}</div>
              <div><span className="font-semibold">Entidade:</span> {detail.entity_type}</div>
              <div><span className="font-semibold">Entidade ID:</span> {detail.entity_id}</div>
              <div><span className="font-semibold">IP:</span> {detail.ip_address || "-"}</div>
            </div>

            <div className="grid gap-3 md:grid-cols-2">
              <div>
                <p className="mb-1 font-semibold">Before</p>
                <pre className="max-h-64 overflow-auto rounded bg-slate-950 p-3 text-xs text-slate-100">{JSON.stringify(beforeState, null, 2)}</pre>
              </div>
              <div>
                <p className="mb-1 font-semibold">After</p>
                <pre className="max-h-64 overflow-auto rounded bg-slate-950 p-3 text-xs text-slate-100">{JSON.stringify(afterState, null, 2)}</pre>
              </div>
            </div>

            <div>
              <p className="mb-1 font-semibold">changes_summary (backend)</p>
              <pre className="max-h-64 overflow-auto rounded bg-slate-950 p-3 text-xs text-slate-100">{JSON.stringify(summary, null, 2)}</pre>
            </div>

            <div>
              <p className="mb-2 font-semibold">Diff calculado</p>
              {computedDiff.length === 0 ? (
                <p className="text-slate-500">Sem diferenças detectadas.</p>
              ) : (
                <div className="space-y-2">
                  {computedDiff.map((item) => (
                    <div key={item.field} className="rounded border border-slate-200 p-2">
                      <p className="font-medium">{item.field}</p>
                      <p className="text-xs text-slate-600">de: {JSON.stringify(item.before)}</p>
                      <p className="text-xs text-slate-600">para: {JSON.stringify(item.after)}</p>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
