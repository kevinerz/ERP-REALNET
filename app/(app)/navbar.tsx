"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { useTransition } from "react";
import { logoutAction } from "@/app/actions/auth";
import type { SessionPayload } from "@/lib/session";

const NAV_ITEMS = [
  { href: "/dashboard", label: "Dashboard" },
  { href: "/pelanggan", label: "Pelanggan" },
];

export default function Navbar({ session }: { session: SessionPayload }) {
  const pathname = usePathname();
  const [isPending, startTransition] = useTransition();

  return (
    <nav className="border-b border-gray-200 bg-white">
      <div className="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
        <div className="flex items-center gap-6">
          <span className="text-lg font-semibold text-blue-700">ERP REALNET</span>
          <div className="flex gap-1">
            {NAV_ITEMS.map((item) => {
              const active = pathname === item.href || pathname.startsWith(item.href + "/");
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={
                    "rounded-md px-3 py-2 text-sm font-medium transition " +
                    (active ? "bg-blue-50 text-blue-700" : "text-gray-600 hover:bg-gray-100")
                  }
                >
                  {item.label}
                </Link>
              );
            })}
          </div>
        </div>
        <div className="flex items-center gap-3 text-sm">
          <div className="text-right leading-tight">
            <div className="font-medium text-gray-800">{session.nama}</div>
            <div className="text-xs text-gray-500">{session.divisi}</div>
          </div>
          <form action={() => startTransition(() => logoutAction())}>
            <button
              type="submit"
              disabled={isPending}
              className="rounded-md border border-gray-300 px-3 py-1.5 text-gray-600 hover:bg-gray-100 disabled:opacity-50"
            >
              Keluar
            </button>
          </form>
        </div>
      </div>
    </nav>
  );
}
