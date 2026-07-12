"use client";

import { useRef, useState, useTransition } from "react";
import { searchPelanggan } from "@/app/(app)/pelanggan-master/actions";
import type { PelangganMasterHit } from "@/lib/pelanggan-master";
import { inputBaseClass } from "@/components/ui/form-field";

// Komponen cari-pelanggan reusable, dipakai di form Gangguan & Cabut (dan
// modul lain nanti kalau perlu) untuk isi otomatis nama/telp/alamat dari
// Master Data Pelanggan -- menggantikan cara lama di tiket/index.php yang
// dump SELURUH tabel pelanggan ke halaman lalu filter di JS. Di sini
// pencarian jalan lewat server action (searchPelanggan), dibatasi 15 hasil,
// debounce 300ms supaya tidak query tiap ketikan huruf.

export default function PelangganPicker({
  onPick,
  placeholder = "Cari nama atau no. HP pelanggan dari master...",
}: {
  onPick: (hit: PelangganMasterHit) => void;
  placeholder?: string;
}) {
  const [query, setQuery] = useState("");
  const [results, setResults] = useState<PelangganMasterHit[]>([]);
  const [open, setOpen] = useState(false);
  const [isPending, startTransition] = useTransition();
  const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  function handleChange(value: string) {
    setQuery(value);
    if (debounceRef.current) clearTimeout(debounceRef.current);

    if (value.trim().length < 2) {
      setResults([]);
      setOpen(false);
      return;
    }

    debounceRef.current = setTimeout(() => {
      startTransition(async () => {
        const hits = await searchPelanggan(value);
        setResults(hits);
        setOpen(true);
      });
    }, 300);
  }

  return (
    <div className="relative">
      <input
        type="text"
        value={query}
        onChange={(e) => handleChange(e.target.value)}
        onFocus={() => results.length > 0 && setOpen(true)}
        onBlur={() => {
          // Delay supaya klik hasil sempat ke-handle dulu sebelum dropdown ditutup.
          setTimeout(() => setOpen(false), 150);
        }}
        placeholder={placeholder}
        className={inputBaseClass}
        autoComplete="off"
      />
      {isPending && <div className="mt-1 text-xs text-gray-400">Mencari...</div>}
      {open && results.length > 0 && (
        <div className="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-md border border-gray-200 bg-white shadow-lg">
          {results.map((r) => (
            <button
              key={r.id}
              type="button"
              onMouseDown={(e) => {
                // onMouseDown (bukan onClick) supaya jalan SEBELUM input blur.
                e.preventDefault();
                onPick(r);
                setQuery(r.nama);
                setOpen(false);
              }}
              className="block w-full border-b border-gray-100 px-3 py-2 text-left text-sm hover:bg-blue-50 last:border-b-0"
            >
              <div className="font-medium text-gray-800">{r.nama}</div>
              <div className="text-xs text-gray-500">
                {r.telp}
                {r.alamat ? ` · ${r.alamat}` : ""}
              </div>
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
