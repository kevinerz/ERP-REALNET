"use client";

import { useMemo, useState } from "react";
import { EmptyTableRow } from "@/components/ui/empty-state";
import { Badge } from "@/components/ui/badge";
import { formatRupiah } from "../aktivasi/helpers";

type Paket = { id_paket: number; nama_paket: string; kecepatan: string; harga: string };

type Row = {
  id: number; nama: string; telp: string; email: string | null; ktp: string | null; marketing: string | null;
  pop: string | null; odp: string | null; alamat: string; url_maps: string; paket: string;
  userppp: string | null; passwordppp: string | null; vlan: string | null; sn: string | null;
  dropcore: string; teknisi: string | null; tanggal: string | null; last_updated_by: string | null;
};

function formatTanggal(iso: string | null): string {
  if (!iso) return "-";
  return new Intl.DateTimeFormat("id-ID", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }).format(new Date(iso));
}

function mapsUrl(raw: string): string | null {
  const v = raw.trim();
  if (!v) return null;
  return /^https?:\/\//i.test(v) ? v : `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(v)}`;
}

export default function ProsesPsbClient({ rows, paketList }: { rows: Row[]; paketList: Paket[] }) {
  const [search, setSearch] = useState("");
  const [openId, setOpenId] = useState<number | null>(null);

  const filtered = useMemo(() => {
    const q = search.trim().toLowerCase();
    if (!q) return rows;
    const terms = q.split(/\s+/).filter(Boolean);
    return rows.filter((r) => {
      const matched = paketList.find((p) => String(p.id_paket) === r.paket);
      const hay = [
        r.nama, r.userppp, r.passwordppp, r.vlan, r.pop, r.email, r.telp,
        matched?.nama_paket, r.teknisi, r.last_updated_by, r.odp, r.sn,
      ].filter(Boolean).join(" ").toLowerCase();
      return terms.every((t) => hay.includes(t));
    });
  }, [rows, paketList, search]);

  const openRow = rows.find((r) => r.id === openId) ?? null;
  const openPaket = openRow ? paketList.find((p) => String(p.id_paket) === openRow.paket) : undefined;

  return (
    <>
      <div className="mb-3">
        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Cari nama, PPPoE, VLAN, email, telepon..."
          className="w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none sm:w-80"
        />
        {search && <span className="ml-2 text-xs text-gray-500">{filtered.length} / {rows.length} ditemukan</span>}
      </div>

      <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
            <tr>
              <th className="px-4 py-3">Pelanggan</th>
              <th className="px-4 py-3">Paket</th>
              <th className="px-4 py-3">Data PPPoE</th>
              <th className="px-4 py-3">VLAN</th>
              <th className="px-4 py-3">Tgl. Aktivasi</th>
              <th className="px-4 py-3">Petugas</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {filtered.map((row) => {
              const matched = paketList.find((p) => String(p.id_paket) === row.paket);
              return (
                <tr key={row.id} className="hover:bg-gray-50">
                  <td className="px-4 py-3">
                    <div className="font-medium text-gray-800">{row.nama}</div>
                    <Badge tone="amber">POP {(row.pop ?? "-").toUpperCase()}</Badge>
                  </td>
                  <td className="px-4 py-3">
                    {matched ? (
                      <>
                        <div className="font-medium text-gray-800">{matched.nama_paket}</div>
                        <div className="text-xs text-green-600">{matched.kecepatan} &middot; Rp {formatRupiah(matched.harga)}</div>
                      </>
                    ) : (
                      <span className="text-xs font-medium text-red-600">Paket tidak ditemukan</span>
                    )}
                  </td>
                  <td className="px-4 py-3 font-mono text-xs">
                    <div className="text-blue-600">{row.userppp || "-"}</div>
                    <div className="text-gray-500">{row.passwordppp || "-"}</div>
                  </td>
                  <td className="px-4 py-3">
                    <span className="rounded-md bg-cyan-50 px-2 py-0.5 font-mono text-xs font-semibold text-cyan-700">{row.vlan || "-"}</span>
                  </td>
                  <td className="px-4 py-3 text-xs text-gray-500">{formatTanggal(row.tanggal)}</td>
                  <td className="px-4 py-3 text-xs text-gray-500">{row.last_updated_by || "-"}</td>
                  <td className="px-4 py-3 text-right">
                    <button
                      type="button"
                      onClick={() => setOpenId(row.id)}
                      className="rounded-md border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                    >
                      Detail
                    </button>
                  </td>
                </tr>
              );
            })}
            {filtered.length === 0 && <EmptyTableRow colSpan={7} message={rows.length === 0 ? "Tidak ada data proses aktivasi." : "Tidak ada hasil pencarian."} />}
          </tbody>
        </table>
      </div>

      {openRow && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" onClick={() => setOpenId(null)}>
          <div className="w-full max-w-2xl rounded-lg bg-white shadow-xl" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between border-b border-gray-200 px-5 py-4">
              <h2 className="text-sm font-semibold text-gray-800">
                {openRow.nama} <span className="ml-1 text-xs font-normal text-gray-500">POP {(openRow.pop ?? "-").toUpperCase()}</span>
              </h2>
              <button type="button" onClick={() => setOpenId(null)} className="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <div className="grid grid-cols-1 gap-6 px-5 py-4 sm:grid-cols-2">
              <div>
                <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-600">Data Teknis</h3>
                <dl className="space-y-1.5 text-sm">
                  <DetailRow label="User PPPoE" value={openRow.userppp} mono />
                  <DetailRow label="Password" value={openRow.passwordppp} mono />
                  <DetailRow label="VLAN" value={openRow.vlan} mono />
                  <DetailRow label="POP" value={openRow.pop?.toUpperCase()} />
                  <DetailRow label="ODP" value={openRow.odp} />
                  <DetailRow label="SN Modem" value={openRow.sn} mono />
                  <DetailRow label="Dropcore" value={openRow.dropcore} />
                  <DetailRow label="Teknisi" value={openRow.teknisi} />
                  <DetailRow label="Paket" value={openPaket ? `${openPaket.nama_paket} (${openPaket.kecepatan})` : "-"} />
                </dl>
              </div>
              <div>
                <h3 className="mb-2 text-xs font-semibold uppercase tracking-wide text-green-600">Kontak & Lokasi</h3>
                <dl className="space-y-1.5 text-sm">
                  <DetailRow label="No. KTP" value={openRow.ktp} />
                  <DetailRow label="Telepon/WA" value={openRow.telp} />
                  <DetailRow label="Email" value={openRow.email} />
                  <DetailRow label="Sales" value={openRow.marketing} />
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
                    Tidak ada data peta
                  </div>
                )}
              </div>
            </div>
            <div className="flex items-center justify-between border-t border-gray-100 px-5 py-3 text-xs text-gray-500">
              <span>Diperbarui oleh: <strong className="text-gray-700">{openRow.last_updated_by || "-"}</strong></span>
              <button type="button" onClick={() => setOpenId(null)} className="rounded-md border border-gray-300 px-3 py-1.5 text-gray-600 hover:bg-gray-50">
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
