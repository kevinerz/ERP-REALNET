import Link from "next/link";
import { prisma } from "@/lib/prisma";
import SelesaiPsbClient from "./selesai-psb-client";

// Bangun ulang persis selesai_aktivasi.php lama: monitoring pelanggan dengan
// status 'on' (aktif online) atau 'selesai' (instalasi selesai). Read-only +
// filter (nama/POP/status) + sort + paginasi server-side, sama seperti versi
// lama -- tidak ada aksi ubah data di halaman ini juga, jadi tidak perlu
// actions.ts. Tombol "Cetak Reimburse Teknisi" di versi lama (ke
// cetak_reimburse_teknisi.php) belum ada modulnya di Next.js -- ditandai
// "Segera" dulu, akan aktif kalau modul BBM/Reimburse sudah dibangun.

const ALLOWED_SORT = ["tanggal", "nama", "pop", "status"] as const;
type SortCol = (typeof ALLOWED_SORT)[number];
const PAGE_SIZE = 30;

function isSortCol(value: string | undefined): value is SortCol {
  return !!value && (ALLOWED_SORT as readonly string[]).includes(value);
}

const STATUS_LABELS: Record<string, string> = { on: "Aktif (ON)", selesai: "Selesai" };

export default async function SelesaiPsbPage({
  searchParams,
}: {
  searchParams: Promise<{ cari?: string; pop?: string; status?: string; sort?: string; page?: string }>;
}) {
  const { cari, pop, status, sort, page: pageParam } = await searchParams;
  const query = cari?.trim() ?? "";
  const filterPop = pop?.trim() ?? "";
  const filterStatus = status && STATUS_LABELS[status] ? status : "";
  const sortCol: SortCol = isSortCol(sort) ? sort : "tanggal";
  const sortDir: "asc" | "desc" = sortCol === "nama" || sortCol === "pop" ? "asc" : "desc";
  const page = Math.max(1, Number(pageParam) || 1);

  const where: Record<string, unknown> = { status: { in: ["on", "selesai"] } };
  if (query) where.nama = { contains: query };
  if (filterPop) where.pop = filterPop;
  if (filterStatus) where.status = filterStatus;

  const [statusCounts, rows, paketList, popRows] = await Promise.all([
    prisma.pelangganInstalasi.groupBy({ by: ["status"], where, _count: { _all: true } }),
    prisma.pelangganInstalasi.findMany({
      where,
      orderBy: [{ status: "asc" }, { [sortCol]: sortDir }],
      skip: (page - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
      select: {
        id: true, nama: true, alamat: true, pop: true, status: true, paket: true,
        userppp: true, passwordppp: true, vlan: true, modem: true, tanggal: true,
        ktp: true, telp: true, email: true, marketing: true, url_maps: true,
        teknisi: true, dropcore: true, sn: true, odp: true, last_updated_by: true,
      },
    }),
    prisma.jaringanPaket.findMany({ orderBy: { id_paket: "asc" } }),
    prisma.pelangganInstalasi.findMany({
      where: { status: { in: ["on", "selesai"] }, NOT: { pop: null } },
      distinct: ["pop"],
      select: { pop: true },
      orderBy: { pop: "asc" },
    }),
  ]);

  type PaketRow = { id_paket: number; nama_paket: string; kecepatan: string; harga: { toString(): string } };
  const paketListPlain = paketList.map((p: PaketRow) => ({
    id_paket: p.id_paket, nama_paket: p.nama_paket, kecepatan: p.kecepatan, harga: p.harga.toString(),
  }));

  type Row = {
    id: number; nama: string; alamat: string; pop: string | null; status: string | null; paket: string;
    userppp: string | null; passwordppp: string | null; vlan: string | null; modem: string;
    tanggal: Date | null; ktp: string | null; telp: string; email: string | null; marketing: string | null;
    url_maps: string; teknisi: string | null; dropcore: string; sn: string | null; odp: string | null;
    last_updated_by: string | null;
  };
  const rowsPlain = rows.map((r: Row) => ({ ...r, tanggal: r.tanggal ? r.tanggal.toISOString() : null }));

  const popListRaw: (string | null)[] = popRows.map((r: { pop: string | null }) => r.pop);
  const popList = popListRaw.filter((p): p is string => Boolean(p));

  type CountRow = { status: string | null; _count: { _all: number } };
  const counts: Record<string, number> = { on: 0, selesai: 0 };
  let total = 0;
  (statusCounts as CountRow[]).forEach((c) => {
    total += c._count._all;
    if (c.status && c.status in counts) counts[c.status] = c._count._all;
  });

  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

  function pageHref(target: number): string {
    const params = new URLSearchParams();
    if (query) params.set("cari", query);
    if (filterPop) params.set("pop", filterPop);
    if (filterStatus) params.set("status", filterStatus);
    if (sort) params.set("sort", sort);
    params.set("page", String(target));
    return `?${params.toString()}`;
  }

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-2">
        <div>
          <h1 className="text-2xl font-semibold text-gray-800">Selesai PSB</h1>
          <p className="text-sm text-gray-500">
            {total} pelanggan pada filter saat ini &middot; Aktif (ON): {counts.on} &middot; Selesai: {counts.selesai}
          </p>
        </div>
        <Link href="/pelanggan/proses-psb" className="text-sm text-blue-600 hover:underline">
          &larr; Lihat proses PSB
        </Link>
      </div>

      <form className="mb-4 flex flex-wrap items-end gap-2">
        <input
          type="text"
          name="cari"
          defaultValue={query}
          placeholder="Cari nama pelanggan..."
          className="w-full max-w-xs rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
        />
        <select name="pop" defaultValue={filterPop} className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
          <option value="">Semua POP</option>
          {popList.map((p: string) => <option key={p} value={p}>{p.toUpperCase()}</option>)}
        </select>
        <select name="status" defaultValue={filterStatus} className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
          <option value="">Semua Status</option>
          {Object.entries(STATUS_LABELS).map(([val, label]) => <option key={val} value={val}>{label}</option>)}
        </select>
        <select name="sort" defaultValue={sortCol} className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
          <option value="tanggal">Tgl. Pemasangan</option>
          <option value="nama">Nama</option>
          <option value="pop">POP</option>
          <option value="status">Status</option>
        </select>
        <button type="submit" className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
          Terapkan
        </button>
        {(query || filterPop || filterStatus) && (
          <Link href="/pelanggan/selesai-psb" className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
            Reset
          </Link>
        )}
      </form>

      <SelesaiPsbClient rows={rowsPlain} paketList={paketListPlain} />

      <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
        <span>Halaman {page} dari {totalPages} &middot; menampilkan {rowsPlain.length} dari {total} hasil.</span>
        <div className="flex gap-1">
          {page > 1 && (
            <Link href={pageHref(page - 1)} className="rounded-md border border-gray-300 px-3 py-1 hover:bg-gray-50">&larr; Prev</Link>
          )}
          {page < totalPages && (
            <Link href={pageHref(page + 1)} className="rounded-md border border-gray-300 px-3 py-1 hover:bg-gray-50">Next &rarr;</Link>
          )}
        </div>
      </div>
    </div>
  );
}
