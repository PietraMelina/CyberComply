import { NextAuthOptions } from "next-auth";
import CredentialsProvider from "next-auth/providers/credentials";

const apiBase = process.env.NEXT_PUBLIC_API_BASE_URL ?? "";

export const authOptions: NextAuthOptions = {
  session: { strategy: "jwt" },
  providers: [
    CredentialsProvider({
      name: "Credentials",
      credentials: {
        email: { label: "Email", type: "email" },
        password: { label: "Password", type: "password" },
        mfaToken: { label: "MFA Token", type: "text" },
      },
      async authorize(credentials) {
        if (!apiBase) {
          throw new Error("NEXT_PUBLIC_API_BASE_URL is not configured");
        }

        const response = await fetch(`${apiBase}/auth/login`, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            email: credentials?.email,
            password: credentials?.password,
            mfa_code: credentials?.mfaToken || undefined,
          }),
        });

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data?.message || "AUTH_FAILED");
        }

        return {
          id: data.user.id,
          email: data.user.email,
          role: data.user?.role?.name ?? data.user?.role,
          clientId: data.user.client_id,
          accessToken: data.access_token,
          refreshToken: data.refresh_token ?? data.access_token,
        };
      },
    }),
  ],
  callbacks: {
    async jwt({ token, user }) {
      if (user) {
        token.accessToken = user.accessToken;
        token.refreshToken = user.refreshToken;
        token.role = user.role;
        token.clientId = user.clientId;
        token.userId = user.id;
      }
      return token;
    },
    async session({ session, token }) {
      session.accessToken = token.accessToken;
      session.refreshToken = token.refreshToken;
      session.user.id = token.userId ?? "";
      session.user.role = token.role;
      session.user.clientId = token.clientId;
      return session;
    },
  },
  pages: {
    signIn: "/login",
    error: "/login",
  },
};
