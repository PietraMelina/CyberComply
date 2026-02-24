"use client";

import { FormEvent, useState } from "react";
import { signIn } from "next-auth/react";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";

export default function LoginPage() {
  const router = useRouter();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [mfaToken, setMfaToken] = useState("");
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setLoading(true);
    setError(null);

    const result = await signIn("credentials", {
      email,
      password,
      mfaToken,
      redirect: false,
    });

    setLoading(false);

    if (!result?.ok) {
      setError(result?.error || "Falha no login.");
      return;
    }

    const sessionResponse = await fetch("/api/auth/session");
    const session = await sessionResponse.json();

    if (session?.accessToken) {
      localStorage.setItem("access_token", session.accessToken);
    }
    if (session?.user?.clientId) {
      localStorage.setItem("current_client_id", session.user.clientId);
    } else {
      localStorage.removeItem("current_client_id");
    }

    router.push("/");
    router.refresh();
  }

  return (
    <div className="flex min-h-screen items-center justify-center px-4">
      <form onSubmit={onSubmit} className="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 className="mb-5 text-xl font-bold">CyberComply Login</h1>
        <div className="space-y-4">
          <div>
            <label className="mb-1 block text-sm font-medium">Email</label>
            <Input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium">Senha</label>
            <Input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required />
          </div>
          <div>
            <label className="mb-1 block text-sm font-medium">Código MFA (se aplicável)</label>
            <Input value={mfaToken} onChange={(e) => setMfaToken(e.target.value)} placeholder="123456" />
          </div>
          {error && <p className="text-sm text-red-600">{error}</p>}
          <Button type="submit" className="w-full" disabled={loading}>
            {loading ? "Entrando..." : "Entrar"}
          </Button>
        </div>
      </form>
    </div>
  );
}
