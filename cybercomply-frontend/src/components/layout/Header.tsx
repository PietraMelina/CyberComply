"use client";

import { signOut, useSession } from "next-auth/react";
import { Button } from "@/components/ui/button";
import { useEffect } from "react";
import { useTenantStore } from "@/stores/tenant-store";
import { Input } from "@/components/ui/input";

export function Header() {
  const { data } = useSession();
  const { clientId, setClientId, initFromStorage } = useTenantStore();
  const isInternal = !data?.user?.clientId;

  useEffect(() => {
    initFromStorage();
  }, [initFromStorage]);

  return (
    <header className="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-3">
      <div className="flex items-center gap-3">
        <div className="text-sm text-slate-600">{data?.user?.email}</div>
        {isInternal && (
          <Input
            className="w-48"
            placeholder="client_id ativo"
            value={clientId}
            onChange={(e) => setClientId(e.target.value)}
          />
        )}
      </div>
      <div className="flex items-center gap-3">
        <span className="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">
          {data?.user?.role || "-"}
        </span>
        <Button
          variant="outline"
          onClick={() => {
            localStorage.removeItem("access_token");
            localStorage.removeItem("current_client_id");
            signOut({ callbackUrl: "/login" });
          }}
        >
          Sair
        </Button>
      </div>
    </header>
  );
}
