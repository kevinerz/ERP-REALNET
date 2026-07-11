import Link from "next/link";
import { prisma } from "@/lib/prisma";
import ProsesPsbClient from "./proses-psb-client";

// Bangun ulang persis prosesaktivasi.php lama: monitoring pelanggan dengan
// status='aktivasi' (sudah diaktivasi NOC, menunggu jadwal instalasi teknisi
// fisik di lapangan). Halaman lama murni read-only (filter POP + sort + cari
// client-side, tanpa aksi ubah data) -- jadi modul ini juga read-only, tidak
// perlu actions.ts.

const ALLOWED_SORT = ["tanggal", "nama", "pop", "vlan"] as const;
type SortCol = (typeof ALLOWED_SORT)[number];

function isSortCol(value: string | undefined): value is SortCol {
  return !!value && (ALLOWED_SORT as readonly string[]).includes(value);
}

export default async function ProsesPsbPage({
  searchParams,
}: {
  searchParams: Promise<{ pop?: string; sort?: string; dir?: string }>;
}) {
  const { pop, sort, dir } = await searchParams;
  const filterPop = pop?.trim() ?? "";
  const sortCol: SortCol = isSortCol(sort) ? sort : "tanggal";
  const sortDir: "asc" | "desc" = dir === "asc" ? "asc" : "desc";

  const where: Record<string, unknown> = { status: "aktivasi" };
  if (filterPop) where.pop = filterPop;

  const [rows, paketList, popRows] = await Promise.all([
    prisma.pelangganInstalasi.findMany({
      where,
      orderBy: { [sortCol]: sortDir },
      select: {
        id: true,
        nama: true,
        telp: true,
        email: true,
        ktp: true,
        marketing: true,
        pop: true,
        odp: true,
        alamat: true,
        url_maps: true,
        paket: true,
        userppp: true,
        passwordppp: true,
        vlan: true,
        sn: true,
        dropcore: true,
        teknisi: true,
        tanggal: true,
        last_updated_by: true,
      },
    }),
    prisma.jaringanPaket.findMany({ orderBy: { id_paket: "asc" } }),
    prisma.pelangganInstalasi.findMany({
      where: { status: "aktivasi", NOT: { pop: null } },
      distinct: ["pop"],
      select: { pop: true },
      orderBy: { pop: "asc" },
    }),
  ]);

  type PaketRow = { id_paket: number; nama_paket: string; kecepatan: string; harga: { toString(): string } };
  const paketListPlain = paketList.map((p: PaketRow) => ({
    id_paket: p.id_paket,
    nama_paket: p.nama_paket,
    kecepatan: p.kecepatan,
    harga: p.harga.toString(),
  }));

  type Row = {
    id: number; nama: string; telp: string; email: string | null; ktp: string | null; marketing: string | null;
    pop: string | null; odp: string | null; alamat: string; url_maps: string; paket: string;
    userppp: string | null; passwordppp: string | null; vlan: string | null; sn: string | null;
    dropcore: string; teknisi: string | null; tanggal: Date | null; last_updated_by: string | null;
  };
  const rowsPlain = rows.map((r: Row) => ({ ...r, tanggal: r.tanggal ? r.tanggal.toISOString() : null }));

  const popListRaw: (string | null)[] = popRows.map((r: { pop: string | null }) => r.pop);
  const popList = popListRaw.filter((p): p is string => Boolean(p));

  function sortHref(col: SortCol): string {
    const nextDir = sortCol === col && sortDir === "desc" ? "asc" : "desc";
    const params = new URLSearchParams();
    params.set("sort", col);
    params.set("dir", nextDir);
    if (filterPop) params.set("pop", filterPop);
    return `?${params.toString()}`;
  }

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-2">
        <div>
          <h1 className="text-2xl font-semibold text-gray-800">Proses PSB</h1>
          <p className="text-sm text-gray-500">
            Pelanggan sudah diaktivasi NOC, menunggu instalasi teknisi di lapangan. {rowsPlain.length} dalam proses.
          </p>
        </div>
        <Link href="/pelanggan/aktivasi" className="text-sm text-blue-600 hover:underline">
          &larr; Lihat antrian aktivasi
        </Link>
      </div>

      <form className="mb-4 flex flex-wrap items-center gap-2">
        <input type="hidden" name="sort" value={sortCol} />
        <input type="hidden" name="dir" value={sortDir} />
        <select
          name="pop"
          defaultValue={filterPop}
          className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
        >
          <option value="">Semua POP Area</option>
          {popList.map((p: string) => (
            <option key={p} value={p}>{p.toUpperCase()}</option>
          ))}
        </select>
        <button type="submit" className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
          Filter
        </button>
        {filterPop && (
          <Link href={`/pelanggan/proses-psb?sort=${sortCol}&dir=${sortDir}`} className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
            Reset
          </Link>
        )}
      </form>

      <div className="mb-3 flex flex-wrap gap-3 text-xs text-gray-500">
        <span>Urutkan:</span>
        {ALLOWED_SORT.map((col) => (
          <Link
            key={col}
            href={sortHref(col)}
            className={sortCol === col ? "font-semibold text-blue-600" : "hover:text-blue-600"}
          >
            {col === "tanggal" ? "Tgl. Aktivasi" : col === "nama" ? "Nama" : col === "pop" ? "POP" : "VLAN"}
            {sortCol === col && (sortDir === "desc" ? " ↓" : " ↑")}
          </Link>
        ))}
      </div>

      <ProsesPsbClient rows={rowsPlain} paketList={paketListPlain} />
    </div>
  );
}
