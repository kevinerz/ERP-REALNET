import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import LoginForm from "./login-form";

export default async function LoginPage() {
  const session = await getSession();
  if (session) {
    redirect("/dashboard");
  }
  return (
    <div className="flex min-h-screen items-center justify-center bg-gradient-to-br from-slate-950 via-slate-900 to-brand-950 px-4">
      <div className="w-full max-w-sm">
        <div className="mb-6 flex flex-col items-center text-center">
          <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 text-lg font-bold text-white shadow-elevated-lg">
            ER
          </div>
          <h1 className="mt-4 text-xl font-bold tracking-tight text-white">ERP REALNET</h1>
          <p className="mt-1 text-sm text-slate-400">Masuk untuk melanjutkan ke sistem internal</p>
        </div>

        <div className="rounded-2xl bg-white p-8 shadow-elevated-lg">
          <LoginForm />
        </div>
      </div>
    </div>
  );
}
