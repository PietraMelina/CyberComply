export const PERMISSIONS: Record<string, string[]> = {
  MASTER: ["*"],
  GESTOR: ["clients.*", "users.read", "audit.read", "reports.*"],
  AUDITOR: ["clients.read", "modules.read", "responses.read", "evidences.read", "audit.read"],
  ADMIN_CLIENTE: ["tenant.*", "users.manage", "modules.*", "responses.*", "evidences.*"],
  TECNICO: ["modules.read", "responses.write", "evidences.write"],
  LEITURA: ["modules.read", "responses.read", "evidences.read"],
};
