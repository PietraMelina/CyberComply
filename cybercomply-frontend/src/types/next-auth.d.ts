import { DefaultSession } from "next-auth";

declare module "next-auth" {
  interface Session {
    accessToken?: string;
    refreshToken?: string;
    user: DefaultSession["user"] & {
      id: string;
      role?: string;
      clientId?: string | null;
    };
  }

  interface User {
    id: string;
    email: string;
    role?: string;
    clientId?: string | null;
    accessToken?: string;
    refreshToken?: string;
  }
}

declare module "next-auth/jwt" {
  interface JWT {
    accessToken?: string;
    refreshToken?: string;
    role?: string;
    clientId?: string | null;
    userId?: string;
  }
}
