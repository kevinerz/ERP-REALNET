import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { Badge } from "@/components/ui/badge";
import { EmptyTableRow } from "@/components/ui/empty-state";
import { STATUS_LABELS, statusTone } from "./gangguan-helpers";

// Bangun ulang persis gangguan.php + fetch_tickets.php lama: daftar tiket
// gangguan dengan kartu statistik (total/belum dikerjakan/di proses/selesai
// -- SELALU global, tidak terpengaruh filter, sama seperti versi lama),
// filter (cari nama/status/POP), sort, dan paginasi 10/halaman. Urutan
// prioritas status lama (belum dikerjakan -> di proses -> selesai) otomatis
// didapat dari orderBy status "asc" karena urutan alfabet 3 nilai itu
// kebetulan sama dengan urutan prioritasnya.

const ALLOWED_SORT = ["nama_pelanggan", "status", "tanggal_dibuat"] as const;
type SortCol = (typeof ALLOWED_SORT)[number];
const PAGE_SIZE = 10;

function isSortCol(value: string | undefined): value is SortCol {
  return !!value && (ALLOWED_SORT as readonly string[]).includes(value);
}

export default async function GangguanPage({
  searchParams,
}: {
  searchParams: Promise<{
    cari?: string; status_filter?: string; pop_filter?: string; sort?: string; order?: string; page?: string;
  }>;
}) {
  const { cari, status_filter, pop_filter, sort, order, page: pageParam } = await searchParams;
  const query = cari?.trim() ?? "";
  const filterStatus = status_filter && STATUS_LABELS[status_filter] ? status_filter : "";
  const filterPop = pop_filter?.trim() ?? "";
  const sortCol: SortCol = isSortCol(sort) ? sort : "tanggal_dibuat";
  const sortDir: "asc" | "desc" = order === "ASC" ? "asc" : "desc";
  const page = Math.max(1, Number(pageParam) || 1);

  const where: Record<string, unknown> = {};
  if (query) where.nama_pelanggan = { contains: query };
  if (filterStatus) where.status = filterStatus;
  if (filterPop) where.pop = filterPop;

  const [statsRaw, total, rows, popRows] = await Promise.all([
    // Statistik selalu dihitung dari SELURUH data, tidak ikut filter --
    // sama seperti $stats_query di gangguan.php lama (tanpa WHERE).
    prisma.tiketGangguan.groupBy({ by: ["status"], _count: { _all: true } }),
    prisma.tiketGangguan.count({ where }),
    prisma.tiketGangguan.findMany({
      where,
      orderBy: [{ status: "asc" }, { [sortCol]: sortDir }],
      skip: (page - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
    }),
    prisma.tiketGangguan.findMany({
      where: { NOT: { pop: null } },
      distinct: ["pop"],
      select: { pop: true },
      orderBy: { pop: "asc" },
    }),
  ]);

  type StatRow = { status: string; _count: { _all: number } };
  const stats = { total: 0, "belum dikerjakan": 0, "di proses": 0, selesai: 0 } as Record<string, number>;
  (statsRaw as StatRow[]).forEach((s) => {
    stats.total += s._count._all;
    if (s.status in stats) stats[s.status] = s._count._all;
  });

  type Row = {
    id: number; nama_pelanggan: string | null; alamat: string | null; whatsapp: string | null;
    pop: string | null; keluhan: string | null; teknisi: string | null; status: string;
    tanggal_dibuat: Date | null;
  };
  const rowsPlain = (rows as Row[]).map((r) => ({
    ...r,
    tanggal_dibuat: r.tanggal_dibuat ? r.tanggal_dibuat.toISOString() : null,
  }));

  const popListRaw: (string | null)[] = popRows.map((r: { pop: string | null }) => r.pop);
  const popList = popListRaw.filter((p): p is string => Boolean(p));

  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

  function pageHref(target: number): string {
    const params = new URLSearchParams();
    if (query) params.set("cari", query);
    if (filterStatus) params.set("status_filter", filterStatus);
    if (filterPop) params.set("pop_filter", filterPop);
    if (sort) params.set("sort", sort);
    if (order) params.set("order", order);
    params.set("page", String(target));
    return `?${params.toString()}`;
  }

  function formatTanggal(iso: string | null): string {
    if (!iso) return "-";
    return new Intl.DateTimeFormat("id-ID", { day: "2-digit", month: "short", year: "numeric", hour: "2-digit", minute: "2-digit" }).format(new Date(iso));
  }

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-2">
        <div>
          <h1 className="text-2xl font-semibold text-gray-800">Gangguan</h1>
          <p className="text-sm text-gray-500">Monitoring tiket gangguan pelanggan per POP, status, dan teknisi.</p>
        </div>
        <Link href="/gangguan/new" className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
          + Tambah Tiket
        </Link>
      </div>

      <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div className="rounded-lg border border-gray-200 bg-white p-4">
          <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">Total Tiket</div>
          <div className="mt-1 text-2xl font-bold text-gray-800">{stats.total}</div>
        </div>
        <div className="rounded-lg border border-gray-200 bg-white p-4 border-l-4 border-l-red-400">
          <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">Belum Dikerjakan</div>
          <div className="mt-1 text-2xl font-bold text-red-600">{stats["belum dikerjakan"]}</div>
        </div>
        <div className="rounded-lg border border-gray-200 bg-white p-4 border-l-4 border-l-amber-400">
          <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">Dalam Proses</div>
          <div className="mt-1 text-2xl font-bold text-amber-600">{stats["di proses"]}</div>
        </div>
        <div className="rounded-lg border border-gray-200 bg-white p-4 border-l-4 border-l-green-400">
          <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">Selesai</div>
          <div className="mt-1 text-2xl font-bold text-green-600">{stats.selesai}</div>
        </div>
      </div>

      <form className="mb-4 flex flex-wrap items-end gap-2">
        <input
          type="text"
          name="cari"
          defaultValue={query}
          placeholder="Cari nama pelanggan..."
          className="w-full max-w-xs rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
        />
        <select name="status_filter" defaultValue={filterStatus} className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
          <option value="">Semua Status</option>
          {Object.entries(STATUS_LABELS).map(([val, label]) => <option key={val} value={val}>{label}</option>)}
        </select>
        <select name="pop_filter" defaultValue={filterPop} className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
          <option value="">Semua POP</option>
          {popList.map((p: string) => <option key={p} value={p}>{p.toUpperCase()}</option>)}
        </select>
        <select name="sort" defaultValue={sortCol} className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
          <option value="tanggal_dibuat">Tgl Dibuat</option>
          <option value="nama_pelanggan">Nama Pelanggan</option>
          <option value="status">Status</option>
        </select>
        <select name="order" defaultValue={order === "ASC" ? "ASC" : "DESC"} className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
          <option value="DESC">Terbaru &rarr; Lama</option>
          <option value="ASC">Terlama &rarr; Baru</option>
        </select>
        <button type="submit" className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
          Terapkan
        </button>
        {(query || filterStatus || filterPop) && (
          <Link href="/gangguan" className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
            Reset
          </Link>
        )}
      </form>

      <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
            <tr>
              <th className="px-4 py-3">Pelanggan</th>
              <th className="px-4 py-3">POP</th>
              <th className="px-4 py-3">Keluhan</th>
              <th className="px-4 py-3">Teknisi</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Tgl Dibuat</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {rowsPlain.map((row) => (
              <tr key={row.id} className="hover:bg-gray-50">
                <td className="px-4 py-3">
                  <div className="font-medium text-gray-800">{row.nama_pelanggan}</div>
                  <div className="text-xs text-gray-500">{row.whatsapp || "-"}</div>
                </td>
                <td className="px-4 py-3 text-gray-600">{(row.pop ?? "-").toUpperCase()}</td>
                <td className="px-4 py-3 max-w-[260px] truncate text-gray-600">{row.keluhan}</td>
                <td className="px-4 py-3 text-gray-600">{row.teknisi || "-"}</td>
                <td className="px-4 py-3">
                  <Badge tone={statusTone(row.status)}>{STATUS_LABELS[row.status] ?? row.status}</Badge>
                </td>
                <td className="px-4 py-3 text-xs text-gray-500">{formatTanggal(row.tanggal_dibuat)}</td>
                <td className="px-4 py-3 text-right">
                  <Link href={`/gangguan/${row.id}`} className="text-blue-600 hover:underline">
                    Detail
                  </Link>
                </td>
              </tr>
            ))}
            {rowsPlain.length === 0 && <EmptyTableRow colSpan={7} message="Tidak ada data gangguan." />}
          </tbody>
        </table>
      </div>

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
