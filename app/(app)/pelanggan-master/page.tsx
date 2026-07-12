import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { Badge } from "@/components/ui/badge";
import { EmptyTableRow } from "@/components/ui/empty-state";

// Master Data Pelanggan: daftar pelanggan ringan (nama/telp/alamat/username)
// hasil import dari billing lama MixRadius + otomatis bertambah tiap ada
// pelanggan baru lewat form Pelanggan/Instalasi (PSB). Dipakai sebagai
// sumber dropdown/autocomplete di form Gangguan & Cabut (lihat
// components/pelanggan-picker.tsx).

const PAGE_SIZE = 30;

function sumberLabel(sumber: string): string {
  if (sumber === "mixradius_import") return "Import MixRadius";
  if (sumber === "psb") return "PSB";
  return "Manual";
}

function sumberTone(sumber: string): "blue" | "green" | "gray" {
  if (sumber === "mixradius_import") return "gray";
  if (sumber === "psb") return "green";
  return "blue";
}

export default async function PelangganMasterPage({
  searchParams,
}: {
  searchParams: Promise<{ cari?: string; page?: string }>;
}) {
  const { cari, page: pageParam } = await searchParams;
  const query = cari?.trim() ?? "";
  const page = Math.max(1, Number(pageParam) || 1);

  const where = query
    ? {
        OR: [
          { nama: { contains: query } },
          { telp: { contains: query } },
          { username: { contains: query } },
        ],
      }
    : {};

  type Row = {
    id: number;
    nama: string;
    telp: string;
    alamat: string | null;
    username: string | null;
    sumber: string;
  };

  const [total, rows]: [number, Row[]] = await Promise.all([
    prisma.pelangganMaster.count({ where }),
    prisma.pelangganMaster.findMany({
      where,
      orderBy: { nama: "asc" },
      skip: (page - 1) * PAGE_SIZE,
      take: PAGE_SIZE,
    }),
  ]);

  const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));

  function pageHref(target: number): string {
    const params = new URLSearchParams();
    if (query) params.set("cari", query);
    params.set("page", String(target));
    return `?${params.toString()}`;
  }

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-2">
        <div>
          <h1 className="text-2xl font-semibold text-gray-800">Master Data Pelanggan</h1>
          <p className="text-sm text-gray-500">
            Sumber dropdown/autocomplete pelanggan di form Gangguan, Cabut, dll. Total {total} pelanggan.
          </p>
        </div>
        <Link
          href="/pelanggan-master/new"
          className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          + Tambah Manual
        </Link>
      </div>

      <form className="mb-4 flex flex-wrap items-end gap-2">
        <input
          type="text"
          name="cari"
          defaultValue={query}
          placeholder="Cari nama, no. HP, atau username..."
          className="w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
        />
        <button type="submit" className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
          Cari
        </button>
        {query && (
          <Link href="/pelanggan-master" className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
            Reset
          </Link>
        )}
      </form>

      <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
            <tr>
              <th className="px-4 py-3">Nama</th>
              <th className="px-4 py-3">No. HP</th>
              <th className="px-4 py-3">Alamat</th>
              <th className="px-4 py-3">Username</th>
              <th className="px-4 py-3">Sumber</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {rows.map((r) => (
              <tr key={r.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-medium text-gray-800">{r.nama}</td>
                <td className="px-4 py-3 text-gray-600">{r.telp}</td>
                <td className="px-4 py-3 max-w-[260px] truncate text-gray-600">{r.alamat || "-"}</td>
                <td className="px-4 py-3 text-xs text-gray-500">{r.username || "-"}</td>
                <td className="px-4 py-3">
                  <Badge tone={sumberTone(r.sumber)}>{sumberLabel(r.sumber)}</Badge>
                </td>
                <td className="px-4 py-3 text-right">
                  <Link href={`/pelanggan-master/${r.id}`} className="text-blue-600 hover:underline">
                    Detail
                  </Link>
                </td>
              </tr>
            ))}
            {rows.length === 0 && <EmptyTableRow colSpan={6} message="Tidak ada data pelanggan." />}
          </tbody>
        </table>
      </div>

      <div className="mt-4 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
        <span>Halaman {page} dari {totalPages} &middot; menampilkan {rows.length} dari {total} hasil.</span>
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
