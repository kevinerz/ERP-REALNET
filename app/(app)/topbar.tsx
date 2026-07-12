"use client";

import { usePathname } from "next/navigation";
import { useState, useRef, useEffect } from "react";
import { logoutAction } from "@/app/actions/auth";
import type { SessionPayload } from "@/lib/session";
import { findActiveLabel } from "./nav-items";
import { IconMenu, IconChevronDown, IconLogOut } from "./icons";

function initials(name: string): string {
  const parts = name.trim().split(/\s+/);
  const first = parts[0]?.[0] ?? "";
  const last = parts.length > 1 ? parts[parts.length - 1][0] : "";
  return (first + last).toUpperCase();
}

export default function Topbar({
  session,
  onMenuClick,
}: {
  session: SessionPayload;
  onMenuClick: () => void;
}) {
  const pathname = usePathname();
  const title = findActiveLabel(pathname);
  const [menuOpen, setMenuOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    function handleClickOutside(e: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setMenuOpen(false);
      }
    }
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-4 border-b border-gray-100 bg-white/95 px-4 shadow-sm backdrop-blur-sm sm:px-6">
      <button
        type="button"
        onClick={onMenuClick}
        className="flex h-9 w-9 items-center justify-center rounded-xl text-gray-500 hover:bg-gray-100 lg:hidden"
        aria-label="Buka menu"
      >
        <IconMenu className="h-5 w-5" />
      </button>

      <h1 className="flex-1 truncate text-base font-bold tracking-tight text-gray-800">{title}</h1>

      <div className="relative" ref={menuRef}>
        <button
          type="button"
          onClick={() => setMenuOpen((v) => !v)}
          className="flex items-center gap-2 rounded-xl px-2 py-1.5 hover:bg-gray-100"
        >
          <div className="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-brand-400 to-brand-600 text-xs font-bold text-white shadow-sm">
            {initials(session.nama)}
          </div>
          <div className="hidden text-left leading-tight sm:block">
            <div className="text-sm font-semibold text-gray-800">{session.nama}</div>
            <div className="text-xs text-gray-500">{session.divisi}</div>
          </div>
          <IconChevronDown className="h-4 w-4 text-gray-400" />
        </button>

        {menuOpen && (
          <div className="absolute right-0 mt-2 w-48 rounded-xl border border-gray-200 bg-white py-1 shadow-elevated-lg">
            <div className="border-b border-gray-100 px-3 py-2 sm:hidden">
              <div className="text-sm font-semibold text-gray-800">{session.nama}</div>
              <div className="text-xs text-gray-500">{session.divisi}</div>
            </div>
            <form action={() => logoutAction()}>
              <button
                type="submit"
                className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm font-medium text-gray-700 hover:bg-gray-50"
              >
                <IconLogOut className="h-4 w-4" />
                Keluar
              </button>
            </form>
          </div>
        )}
      </div>
    </header>
  );
}
