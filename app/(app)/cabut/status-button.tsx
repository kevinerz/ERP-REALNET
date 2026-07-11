"use client";

import { useTransition } from "react";
import { updateCabutStatus } from "./actions";

export default function StatusButton({
  id,
  nama,
  status,
}: {
  id: number;
  nama: string;
  status: string;
}) {
  const [isPending, startTransition] = useTransition();
  const nextStatus = status === "selesai" ? "belum selesai" : "selesai";
  const nextLabel = nextStatus === "selesai" ? "SELESAI" : "BELUM SELESAI";

  return (
    <button
      type="button"
      disabled={isPending}
      onClick={() => {
        if (confirm(`Ubah status tiket "${nama}" menjadi ${nextLabel}?`)) {
          startTransition(() => updateCabutStatus(id, nextStatus));
        }
      }}
      className="rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100 disabled:opacity-50"
    >
      {isPending ? "Menyimpan..." : "Ubah"}
    </button>
  );
}
