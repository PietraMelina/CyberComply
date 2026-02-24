import { create } from "zustand";

type TenantState = {
  clientId: string;
  setClientId: (clientId: string) => void;
  initFromStorage: () => void;
};

export const useTenantStore = create<TenantState>((set) => ({
  clientId: "",
  setClientId: (clientId) => {
    if (typeof window !== "undefined") {
      if (clientId) {
        localStorage.setItem("current_client_id", clientId);
      } else {
        localStorage.removeItem("current_client_id");
      }
    }
    set({ clientId });
  },
  initFromStorage: () => {
    if (typeof window === "undefined") return;
    set({ clientId: localStorage.getItem("current_client_id") || "" });
  },
}));
