"use client";

import { useSession } from "next-auth/react";
import { PERMISSIONS } from "@/lib/constants/roles";

export function usePermissions() {
  const { data: session } = useSession();
  const role = session?.user?.role || "";

  const can = (permission: string): boolean => {
    if (!role) return false;

    const userPerms = PERMISSIONS[role] ?? [];
    if (userPerms.includes("*")) return true;
    if (userPerms.includes(permission)) return true;

    return userPerms.some((p) => {
      if (!p.endsWith(".*")) return false;
      const base = p.slice(0, -2);
      return permission.startsWith(base + ".") || permission === base;
    });
  };

  return {
    role,
    can,
    isInternal: !session?.user?.clientId,
  };
}
