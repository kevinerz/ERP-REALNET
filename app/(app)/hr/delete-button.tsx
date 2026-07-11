"use client";

import { useTransition } from "react";
import { deleteKaryawan } from "./actions";

export default function DeleteButton({ id }: { id: number }) {
  const [isPending, startTransition] = useTransition();

  return (
    <button
      type="button"
      disabled={isPending}
      onClick={() => {
        if (confirm("Hapus data karyawan ini? Tindakan tidak bisa dibatalkan.")) {
          startTransition(() => deleteKaryawan(id));
        }
      }}
      className="rounded-md border border-red-300 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 disabled:opacity-50"
    >
      {isPending ? "Menghapus..." : "Hapus"}
    </button>
  );
}
