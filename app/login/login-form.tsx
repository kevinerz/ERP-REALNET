"use client";

import { useActionState } from "react";
import { loginAction, type LoginState } from "@/app/actions/auth";
import { Button } from "@/components/ui/button";

const initialState: LoginState = null;

export default function LoginForm() {
  const [state, formAction, pending] = useActionState(loginAction, initialState);

  return (
    <form action={formAction} className="space-y-4">
      {state?.error && (
        <div className="rounded-xl border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
          {state.error}
        </div>
      )}
      <div>
        <label htmlFor="username" className="mb-1 block text-sm font-semibold text-slate-700">
          Username
        </label>
        <input
          id="username"
          name="username"
          type="text"
          required
          autoFocus
          className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
        />
      </div>
      <div>
        <label htmlFor="password" className="mb-1 block text-sm font-semibold text-slate-700">
          Password
        </label>
        <input
          id="password"
          name="password"
          type="password"
          required
          className="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
        />
      </div>
      <Button
        type="submit"
        isLoading={pending}
        loadingText="Memproses..."
        className="w-full"
      >
        Masuk
      </Button>
    </form>
  );
}
