"use client";

import { useState } from "react";
import { EmptyTableRow } from "@/components/ui/empty-state";
import { Badge } from "@/components/ui/badge";
import { formatRupiah } from "../aktivasi/helpers";

type Paket = { id_paket: number; nama_paket: string; kecepatan: string; harga: string };

type Row = {
  id: number; nama: string; alamat: string; pop: string | null; status: string | null; paket: string;
  userppp: string | null; passwordppp: string | null; vlan: string | null; modem: string;
  tanggal: string | null; ktp: string | null; telp: string; email: string | null; marketing: string | null;
  url_maps: string; teknisi: string | null; dropcore: string; sn: string | null; odp: string | null;
  last_updated_by: string | null;
};

function formatTanggal(iso: string | null): string {
  if (!iso) return "-";
  return new Intl.DateTimeFormat("id-ID", { day: "2-digit", month: "short", year: "numeric" }).format(new Date(iso));
}

function mapsUrl(raw: string): string | null {
  const v = raw.trim();
  if (!v) return null;
  return /^https?:\/\//i.test(v) ? v : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(v)}`;
}

export default function SelesaiPsbClient({ rows, paketList }: { rows: Row[]; paketList: Paket[] }) {
  const [openId, setOpenId] = useState<number | null>(null);
  const openRow = rows.find((r) => r.id === openId) ?? null;
  const openPaket = openRow ? paketList.find((p) => String(p.id_paket) === openRow.paket) : undefined;

  return (
    <>
      <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
            <tr>
              <th className="px-4 py-3">Pelanggan</th>
              <th className="px-4 py-3">POP</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Paket</th>
              <th className="px-4 py-3">PPPoE / VLAN</th>
              <th className="px-4 py-3">Tgl. Selesai</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {rows.map((row) => {
              const matched = paketList.find((p) => String(p.id_paket) === row.paket);
              const isOn = row.status === "on";
              return (
                <tr key={row.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <div className="font-medium text-gray-800">{row.nama}</div>
                    <div className="max-w-[220px] truncate text-xs text-gray-500">{row.alamat}</div>
                  </td>
                  <td className="px-4 py-3 text-gray-600">{(row.pop ?? "-").toUpperCase()}</td>
                  <td className="px-4 py-3">
                    <Badge tone={isOn ? "blue" : "green"}>{isOn ? "Aktif (ON)" : "Selesai"}</Badge>
                  </td>
                  <td className="px-4 py-3">
                    {matched ? (
                      <>
                        <div className="font-medium text-green-700">{matched.nama_paket}</div>
                        <div className="text-xs text-gray-500">{matched.kecepatan}</div>
                      </>
                    ) : (
                      <span className="text-xs text-gray-400">-</span>
                    )}
                  </td>
                  <td className="px-4 py-3 font-mono text-xs">
                    <div>{row.userppp || "-"}</div>
                    <div className="text-gray-500">VLAN: {row.vlan || "-"}</div>
                  </td>
                  <td className="px-4 py-3 text-xs text-gray-500">{formatTanggal(row.tanggal)}</td>
                  <td className="px-4 py-3">
                    <div className="flex flex-col items-end gap-1">
                      <button
                        type="button"
                        onClick={() => setOpenId(row.id)}
                        className="rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                      >
                        Detail
                      </button>
                      <span
                        title="Modul BBM/Reimburse belum dibangun -- cetak reimburse teknisi akan aktif setelah itu jadi."
                        className="cursor-not-allowed rounded-md bg-gray-100 px-3 py-1 text-[11px] text-gray-400"
                      >
                        Cetak Reimburse (segera)
                      </span>
                    </div>
                  </td>
                </tr>
              );
            })}
            {rows.length === 0 && <EmptyTableRow colSpan={7} message="Data tidak ditemukan." />}
          </tbody>
        </table>
      </div>

      {openRow && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" onClick={() => setOpenId(null)}>
          <div className="w-full max-w-2xl rounded-lg bg-white shadow-xl" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
              <h2 className="text-sm font-semibold text-gray-800">Detail Pelanggan: {openRow.nama}</h2>
              <button type="button" onClick={() => setOpenId(null)} className="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div className="grid grid-cols-1 gap-6 px-5 py-4 sm:grid-cols-2">
              <div>
                <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-600">Informasi Teknis</h3>
                <dl className="space-y-1.5 text-sm">
                  <DetailRow label="Username PPPoE" value={openRow.userppp} mono />
                  <DetailRow label="Password PPPoE" value={openRow.passwordppp} mono />
                  <DetailRow label="Modem" value={openRow.modem} mono />
                  <DetailRow label="ODP" value={openRow.odp} />
                  <DetailRow label="Dropcore" value={openRow.dropcore} />
                  <DetailRow label="Teknisi PJ" value={openRow.teknisi} />
                  <DetailRow label="Paket" value={openPaket ? `${openPaket.nama_paket} (${openPaket.kecepatan})` : "-"} />
                </dl>
              </div>
              <div>
                <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-green-600">Kontak & Lokasi</h3>
                <dl className="space-y-1.5 text-sm">
                  <DetailRow label="KTP" value={openRow.ktp} />
                  <DetailRow label="Telepon" value={openRow.telp} />
                  <DetailRow label="Email" value={openRow.email} />
                  <DetailRow label="Marketing" value={openRow.marketing} />
                </dl>
                <div className="mt-3 rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">
                  {openRow.alamat || "-"}
                </div>
                {mapsUrl(openRow.url_maps) ? (
                  <a
                    href={mapsUrl(openRow.url_maps)!}
                    target="_blank"
                    rel="noreferrer"
                    className="mt-2 block rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-center text-xs font-medium text-blue-700 hover:bg-blue-100"
                  >
                    Buka Google Maps
                  </a>
                ) : (
                  <div className="mt-2 rounded-md border border-gray-200 px-3 py-2 text-center text-xs text-gray-400">
                    Belum ada link maps.
                  </div>
                )}
              </div>
            </div>
            <div className="flex items-center justify-end border-t border-gray-100 px-5 py-3">
              <button type="button" onClick={() => setOpenId(null)} className="rounded-md border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50">
                Tutup
              </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}

function DetailRow({ label, value, mono }: { label: string; value?: string | null; mono?: boolean }) {
  return (
    <div className="flex items-start justify-between gap-3 border-b border-gray-50 py-1">
      <dt className="shrink-0 text-xs text-gray-400">{label}</dt>
      <dd className={`text-right text-xs font-medium text-gray-700 ${mono ? "font-mono" : ""}`}>{value || "-"}</dd>
    </div>
  );
}
