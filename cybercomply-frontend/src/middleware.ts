import { withAuth } from "next-auth/middleware";
import { NextResponse } from "next/server";

export default withAuth(
  function middleware(req) {
    const token = req.nextauth.token;
    const path = req.nextUrl.pathname;

    if (!token) {
      return NextResponse.redirect(new URL("/login", req.url));
    }

    const role = String(token.role || "");
    const isInternal = !token.clientId;

    if (path.startsWith("/audit-logs") && !["MASTER", "GESTOR", "AUDITOR"].includes(role)) {
      return NextResponse.redirect(new URL("/modules", req.url));
    }

    if (path.startsWith("/clients") && !isInternal) {
      return NextResponse.redirect(new URL("/modules", req.url));
    }

    if (["ADMIN_CLIENTE", "TECNICO", "LEITURA"].includes(role) && !token.clientId) {
      return NextResponse.redirect(new URL("/login", req.url));
    }

    return NextResponse.next();
  },
  {
    callbacks: {
      authorized: ({ token }) => !!token,
    },
  }
);

export const config = {
  matcher: ["/((?!api|_next/static|_next/image|favicon.ico|login).*)"],
};
