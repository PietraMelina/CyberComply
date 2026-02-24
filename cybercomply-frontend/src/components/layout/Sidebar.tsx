"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { usePermissions } from "@/hooks/usePermissions";

type Item = { href: string; label: string; permission?: string; internalOnly?: boolean };

const items: Item[] = [
  { href: "/clients", label: "Clientes", permission: "clients.read", internalOnly: true },
  { href: "/users", label: "Users", permission: "users.read" },
  { href: "/modules", label: "Módulos", permission: "modules.read" },
  { href: "/responses", label: "Respostas", permission: "responses.read" },
  { href: "/evidences", label: "Evidências", permission: "evidences.read" },
  { href: "/audit-logs", label: "Auditoria", permission: "audit.read", internalOnly: true },
];

export function Sidebar() {
  const pathname = usePathname();
  const { can, isInternal } = usePermissions();

  const visibleItems = items.filter((item) => {
    if (item.internalOnly && !isInternal) return false;
    if (!item.permission) return true;
    return can(item.permission);
  });

  return (
    <aside className="w-64 border-r border-slate-200 bg-white p-4">
      <div className="mb-6 text-lg font-bold text-slate-900">CyberComply</div>
      <nav className="space-y-1">
        {visibleItems.map((item) => {
          const active = pathname === item.href;
          return (
            <Link
              key={item.href}
              href={item.href}
              className={`block rounded-md px-3 py-2 text-sm ${active ? "bg-slate-900 text-white" : "text-slate-700 hover:bg-slate-100"}`}
            >
              {item.label}
            </Link>
          );
        })}
      </nav>
    </aside>
  );
}
