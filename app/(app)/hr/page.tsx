import Link from "next/link";
import { prisma } from "@/lib/prisma";

type KaryawanRow = {
  id: number;
  nama: string;
  nik: string;
  divisi: string;
  jabatan: string | null;
  no_telp: string | null;
  status_aktif: boolean;
};

export default async function HrListPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string }>;
}) {
  const { q } = await searchParams;
  const query = q?.trim() ?? "";

  const list: KaryawanRow[] = await prisma.hrKaryawan.findMany({
    where: query
      ? {
          OR: [
            { nama: { contains: query } },
            { nik: { contains: query } },
            { divisi: { contains: query } },
          ],
        }
      : undefined,
    select: { id: true, nama: true, nik: true, divisi: true, jabatan: true, no_telp: true, status_aktif: true },
    orderBy: { nama: "asc" },
    take: 50,
  });

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-gray-800">HRIS / Karyawan</h1>
          <p className="text-sm text-gray-500">{list.length} data ditampilkan (maks. 50).</p>
        </div>
        <Link
          href="/hr/new"
          className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          + Tambah Karyawan
        </Link>
      </div>

      <form className="mb-4">
        <input
          type="text"
          name="q"
          defaultValue={query}
          placeholder="Cari nama, NIK, atau divisi..."
          className="w-full max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none sm:w-96"
        />
      </form>

      <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
            <tr>
              <th className="px-4 py-3">Nama</th>
              <th className="px-4 py-3">NIK</th>
              <th className="px-4 py-3">Divisi</th>
              <th className="px-4 py-3">Jabatan</th>
              <th className="px-4 py-3">Telepon</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {list.map((k) => (
              <tr key={k.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-medium text-gray-800">{k.nama}</td>
                <td className="px-4 py-3 text-gray-600">{k.nik}</td>
                <td className="px-4 py-3 text-gray-600">{k.divisi}</td>
                <td className="px-4 py-3 text-gray-600">{k.jabatan ?? "-"}</td>
                <td className="px-4 py-3 text-gray-600">{k.no_telp ?? "-"}</td>
                <td className="px-4 py-3">
                  <span
                    className={
                      "rounded-full px-2 py-0.5 text-xs font-medium " +
                      (k.status_aktif ? "bg-green-50 text-green-700" : "bg-gray-100 text-gray-500")
                    }
                  >
                    {k.status_aktif ? "Aktif" : "Nonaktif"}
                  </span>
                </td>
                <td className="px-4 py-3 text-right">
                  <Link href={`/hr/${k.id}`} className="text-blue-600 hover:underline">
                    Detail
                  </Link>
                </td>
              </tr>
            ))}
            {list.length === 0 && (
              <tr>
                <td colSpan={7} className="px-4 py-8 text-center text-gray-500">
                  Tidak ada data.
                </td>
              </tr>
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
