import { redirect } from "next/navigation";
import { getSession } from "@/lib/auth";
import LoginForm from "./login-form";

export default async function LoginPage() {
  const session = await getSession();
  if (session) {
    redirect("/dashboard");
  }
  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-100 px-4">
      <div className="w-full max-w-sm bg-white rounded-xl shadow-lg p-8">
        <h1 className="text-xl font-bold text-slate-800 mb-1">ERP REALNET</h1>
        <p className="text-sm text-slate-500 mb-6">Masuk untuk melanjutkan</p>
        <LoginForm />
      </div>
    </div>
  );
}
