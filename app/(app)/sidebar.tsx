"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { NAV_GROUPS } from "./nav-items";
import { IconX } from "./icons";

function SidebarContent({ pathname, onNavigate }: { pathname: string; onNavigate?: () => void }) {
  return (
    <div className="flex h-full flex-col">
      <div className="flex h-16 shrink-0 items-center gap-2 border-b border-slate-800 px-5">
        <div className="flex h-8 w-8 items-center justify-center rounded-md bg-blue-600 text-sm font-bold text-white">
          ER
        </div>
        <div className="leading-tight">
          <div className="text-sm font-semibold text-white">ERP REALNET</div>
          <div className="text-[11px] text-slate-400">Internal System</div>
        </div>
      </div>

      <nav className="flex-1 space-y-6 overflow-y-auto px-3 py-5">
        {NAV_GROUPS.map((group) => (
          <div key={group.title}>
            <div className="mb-2 px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
              {group.title}
            </div>
            <div className="space-y-0.5">
              {group.items.map((item) => {
                const active = pathname === item.href || pathname.startsWith(item.href + "/");
                const Icon = item.icon;

                if (item.external) {
                  return (
                    <a
                      key={item.href}
                      href={item.href}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium text-slate-300 transition hover:bg-slate-800 hover:text-white"
                    >
                      <Icon className="h-[18px] w-[18px]" />
                      {item.label}
                    </a>
                  );
                }

                if (item.comingSoon) {
                  return (
                    <div
                      key={item.href}
                      className="flex cursor-not-allowed items-center justify-between rounded-md px-2.5 py-2 text-sm text-slate-500"
                      title="Modul ini belum dibangun"
                    >
                      <span className="flex items-center gap-2.5">
                        <Icon className="h-[18px] w-[18px] opacity-60" />
                        {item.label}
                      </span>
                      <span className="rounded-full bg-slate-800 px-2 py-0.5 text-[10px] font-medium text-slate-400">
                        Segera
                      </span>
                    </div>
                  );
                }

                return (
                  <Link
                    key={item.href}
                    href={item.href}
                    onClick={onNavigate}
                    className={
                      "flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium transition " +
                      (active
                        ? "bg-blue-600 text-white shadow-sm"
                        : "text-slate-300 hover:bg-slate-800 hover:text-white")
                    }
                  >
                    <Icon className="h-[18px] w-[18px]" />
                    {item.label}
                  </Link>
                );
              })}
            </div>
          </div>
        ))}
      </nav>

      <div className="border-t border-slate-800 px-5 py-4 text-[11px] text-slate-500">
        ERP REALNET &copy; {new Date().getFullYear()}
      </div>
    </div>
  );
}

export default function Sidebar({
  mobileOpen,
  onClose,
}: {
  mobileOpen: boolean;
  onClose: () => void;
}) {
  const pathname = usePathname();

  return (
    <>
      {/* Sidebar tetap, selalu tampil di layar >= lg */}
      <aside className="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:flex lg:w-64 lg:flex-col lg:bg-slate-900">
        <SidebarContent pathname={pathname} />
      </aside>

      {/* Sidebar slide-over untuk layar kecil */}
      {mobileOpen && (
        <div className="fixed inset-0 z-40 lg:hidden">
          <div className="fixed inset-0 bg-slate-900/60" onClick={onClose} />
          <div className="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-slate-900">
            <button
              type="button"
              onClick={onClose}
              className="absolute -right-10 top-3 flex h-8 w-8 items-center justify-center rounded-md text-white"
              aria-label="Tutup menu"
            >
              <IconX className="h-5 w-5" />
            </button>
            <SidebarContent pathname={pathname} onNavigate={onClose} />
          </div>
        </div>
      )}
    </>
  );
}
