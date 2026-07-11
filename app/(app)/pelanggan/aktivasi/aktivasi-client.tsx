"use client";

import { useState, useTransition } from "react";
import { activatePelanggan, savePendingPelanggan, deleteAntrian } from "./actions";
import { suggestUsername, SUGGESTED_PASSWORD, formatRupiah } from "./helpers";

type Paket = { id_paket: number; nama_paket: string; kecepatan: string; harga: string };

type AntrianRow = {
  id: number;
  nama: string;
  telp: string;
  pop: string | null;
  alamat: string;
  paket: string;
  userppp: string | null;
  passwordppp: string | null;
  vlan: string | null;
  tanggal: string | null;
};

function formatTanggal(iso: string | null): string {
  if (!iso) return "-";
  return new Intl.DateTimeFormat("id-ID", { day: "2-digit", month: "short", year: "numeric" }).format(new Date(iso));
}

export default function AktivasiClient({ antrian, paketList }: { antrian: AntrianRow[]; paketList: Paket[] }) {
  const [openId, setOpenId] = useState<number | null>(null);
  const [isPending, startTransition] = useTransition();
  const openRow = antrian.find((r) => r.id === openId) ?? null;

  return (
    <>
      <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
            <tr>
              <th className="px-4 py-3">ID</th>
              <th className="px-4 py-3">Customer</th>
              <th className="px-4 py-3">POP / Alamat</th>
              <th className="px-4 py-3">Paket</th>
              <th className="px-4 py-3">Tgl Daftar</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {antrian.map((row) => {
              const matchedPaket = paketList.find((p) => String(p.id_paket) === row.paket);
              return (
                <tr key={row.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3 font-medium text-blue-600">#{row.id}</td>
                  <td className="px-4 py-3">
                    <div className="font-medium text-gray-800">{row.nama}</div>
                    <div className="text-xs text-gray-500">{row.telp}</div>
                  </td>
                  <td className="px-4 py-3">
                    <span className="rounded-md border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                      {(row.pop ?? "-").toUpperCase()}
                    </span>
                    <div className="mt-1 max-w-[220px] truncate text-xs text-gray-500">{row.alamat}</div>
                  </td>
                  <td className="px-4 py-3">
                    {matchedPaket ? (
                      <>
                        <div className="font-medium text-gray-800">{matchedPaket.nama_paket}</div>
                        <div className="text-xs text-green-600">{matchedPaket.kecepatan}</div>
                      </>
                    ) : (
                      <span className="text-xs font-medium text-red-600">Belum diatur</span>
                    )}
                  </td>
                  <td className="px-4 py-3 text-xs text-gray-500">{formatTanggal(row.tanggal)}</td>
                  <td className="px-4 py-3">
                    <div className="flex justify-end gap-2">
                      <button
                        type="button"
                        onClick={() => setOpenId(row.id)}
                        className="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700"
                      >
                        Proses
                      </button>
                      <button
                        type="button"
                        disabled={isPending}
                        onClick={() => {
                          if (confirm(`Hapus data antrian #${row.id} (${row.nama})?`)) {
                            startTransition(() => deleteAntrian(row.id));
                          }
                        }}
                        className="rounded-md border border-red-300 px-3 py-1.5 text-xs text-red-600 hover:bg-red-50 disabled:opacity-50"
                      >
                        Hapus
                      </button>
                    </div>
                  </td>
                </tr>
              );
            })}
            {antrian.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-10 text-center text-gray-500">
                  Tidak ada antrean aktivasi.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {openRow && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4">
          <div className="w-full max-w-lg rounded-lg bg-white shadow-xl">
            <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
              <h2 className="text-sm font-semibold text-gray-800">Proses Aktivasi #{openRow.id}</h2>
              <button type="button" onClick={() => setOpenId(null)} className="text-gray-400 hover:text-gray-600">
                ✕
              </button>
            </div>

            <form className="space-y-4 px-5 py-4">
              <div className="rounded-md bg-gray-50 px-3 py-2">
                <div className="text-sm font-semibold text-gray-800">{openRow.nama}</div>
                <div className="text-xs text-gray-500">
                  {openRow.telp} &middot; {(openRow.pop ?? "-").toUpperCase()}
                </div>
              </div>

              <div>
                <label className="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Paket Internet</label>
                <select
                  name="paket"
                  required
                  defaultValue={openRow.paket}
                  className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                >
                  <option value="" disabled>-- Pilih Paket --</option>
                  {paketList.map((p) => (
                    <option key={p.id_paket} value={p.id_paket}>
                      {p.nama_paket} [{p.kecepatan}] -- Rp {formatRupiah(p.harga)}
                    </option>
                  ))}
                </select>
                {paketList.length === 0 && (
                  <p className="mt-1 text-xs text-amber-600">
                    Belum ada data paket. Impor data paket dulu di modul Pengaturan.
                  </p>
                )}
              </div>

              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Username PPPoE</label>
                  <input
                    name="userppp"
                    required
                    maxLength={30}
                    defaultValue={openRow.userppp || suggestUsername(openRow.nama, openRow.telp)}
                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                  />
                </div>
                <div>
                  <label className="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Password PPPoE</label>
                  <input
                    name="passwordppp"
                    required
                    defaultValue={openRow.passwordppp || SUGGESTED_PASSWORD}
                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                  />
                </div>
              </div>

              <div>
                <label className="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">VLAN ID</label>
                <input
                  name="vlan"
                  type="number"
                  required
                  placeholder="Contoh: 100, 200"
                  defaultValue={openRow.vlan ?? ""}
                  className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                />
                <p className="mt-1 text-xs text-gray-400">Pastikan sesuai konfigurasi OLT / Mikrotik.</p>
              </div>

              <div className="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
                <button
                  type="submit"
                  formAction={savePendingPelanggan.bind(null, openRow.id)}
                  className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50"
                >
                  Simpan (Pending)
                </button>
                <button
                  type="submit"
                  formAction={activatePelanggan.bind(null, openRow.id)}
                  className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                  Aktivasi &amp; Kirim WA
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </>
  );
}
