import { requireSession } from "@/lib/auth";
import AppShell from "./app-shell";

export default async function AppLayout({ children }: { children: React.ReactNode }) {
  const session = await requireSession();

  return <AppShell session={session}>{children}</AppShell>;
}
