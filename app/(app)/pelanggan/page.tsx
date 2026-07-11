import Link from "next/link";
import { prisma } from "@/lib/prisma";

type PelangganRow = {
  id: number;
  nama: string;
  telp: string;
  paket: string;
  pop: string | null;
  odp: string | null;
  status: string | null;
};

export default async function PelangganListPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string }>;
}) {
  const { q } = await searchParams;
  const query = q?.trim() ?? "";

  const list = await prisma.pelangganInstalasi.findMany({
    where: query
      ? {
          OR: [
            { nama: { contains: query } },
            { telp: { contains: query } },
            { user: { contains: query } },
          ],
        }
      : undefined,
    orderBy: { id: "desc" },
    take: 50,
  });

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-semibold text-gray-800">Pelanggan / Instalasi</h1>
          <p className="text-sm text-gray-500">{list.length} data ditampilkan (maks. 50).</p>
        </div>
        <Link
          href="/pelanggan/new"
          className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
        >
          + Tambah Pelanggan
        </Link>
      </div>

      <form className="mb-4">
        <input
          type="text"
          name="q"
          defaultValue={query}
          placeholder="Cari nama, telepon, atau username..."
          className="w-full max-w-md rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none sm:w-96"
        />
      </form>

      <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
            <tr>
              <th className="px-4 py-3">Nama</th>
              <th className="px-4 py-3">Telepon</th>
              <th className="px-4 py-3">Paket</th>
              <th className="px-4 py-3">POP / ODP</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {list.map((p: PelangganRow) => (
              <tr key={p.id} className="hover:bg-gray-50">
                <td className="px-4 py-3 font-medium text-gray-800">{p.nama}</td>
                <td className="px-4 py-3 text-gray-600">{p.telp}</td>
                <td className="px-4 py-3 text-gray-600">{p.paket}</td>
                <td className="px-4 py-3 text-gray-600">{[p.pop, p.odp].filter(Boolean).join(" / ") || "-"}</td>
                <td className="px-4 py-3 text-gray-600">{p.status ?? "-"}</td>
                <td className="px-4 py-3 text-right">
                  <Link href={`/pelanggan/${p.id}`} className="text-blue-600 hover:underline">
                    Detail
                  </Link>
                </td>
              </tr>
            ))}
            {list.length === 0 && (
              <tr>
                <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
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
