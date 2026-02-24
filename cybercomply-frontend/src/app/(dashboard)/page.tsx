import { getServerSession } from "next-auth";
import { redirect } from "next/navigation";
import { authOptions } from "@/lib/auth/auth.config";

export default async function DashboardHome() {
  const session = await getServerSession(authOptions);

  if (!session) {
    redirect("/login");
  }

  if (session.user.clientId) {
    redirect("/modules");
  }

  redirect("/clients");
}
