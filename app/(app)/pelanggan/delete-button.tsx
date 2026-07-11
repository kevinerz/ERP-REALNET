"use client";

import { useTransition } from "react";
import { deletePelanggan } from "./actions";

export default function DeleteButton({ id }: { id: number }) {
  const [isPending, startTransition] = useTransition();

  return (
    <button
      type="button"
      disabled={isPending}
      onClick={() => {
        if (confirm("Hapus data pelanggan ini? Tindakan tidak bisa dibatalkan.")) {
          startTransition(() => deletePelanggan(id));
        }
      }}
      className="rounded-md border border-red-300 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 disabled:opacity-50"
    >
      {isPending ? "Menghapus..." : "Hapus"}
    </button>
  );
}
