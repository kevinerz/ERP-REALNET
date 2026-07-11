import Link from "next/link";
import { prisma } from "@/lib/prisma";
import AktivasiClient from "./aktivasi-client";

export default async function AktivasiPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; pop?: string }>;
}) {
  const { q, pop } = await searchParams;
  const query = q?.trim() ?? "";
  const filterPop = pop?.trim() ?? "";

  const where: Record<string, unknown> = { status: "belum diproses" };
  if (filterPop) where.pop = filterPop;
  if (query) {
    where.OR = [
      { nama: { contains: query } },
      { telp: { contains: query } },
      { alamat: { contains: query } },
    ];
  }

  const [antrian, paketList, popRows] = await Promise.all([
    prisma.pelangganInstalasi.findMany({
      where,
      orderBy: { id: "desc" },
      select: {
        id: true,
        nama: true,
        telp: true,
        pop: true,
        alamat: true,
        paket: true,
        userppp: true,
        passwordppp: true,
        vlan: true,
        tanggal: true,
      },
    }),
    prisma.jaringanPaket.findMany({ orderBy: { harga: "asc" } }),
    prisma.pelangganInstalasi.findMany({
      where: { status: "belum diproses", NOT: { pop: null } },
      distinct: ["pop"],
      select: { pop: true },
      orderBy: { pop: "asc" },
    }),
  ]);

  const popListRaw: (string | null)[] = popRows.map((r: { pop: string | null }) => r.pop);
  const popList = popListRaw.filter((p): p is string => Boolean(p));

  // Komponen client di bawah cuma boleh terima props yang bisa diserialisasi
  // (bukan instance Decimal/Date dari Prisma langsung) -- ubah dulu jadi
  // string/plain value sebelum dioper.
  type PaketRow = { id_paket: number; nama_paket: string; kecepatan: string; harga: { toString(): string } };
  const paketListPlain = paketList.map((p: PaketRow) => ({
    id_paket: p.id_paket,
    nama_paket: p.nama_paket,
    kecepatan: p.kecepatan,
    harga: p.harga.toString(),
  }));
  type AntrianRow = {
    id: number; nama: string; telp: string; pop: string | null; alamat: string; paket: string;
    userppp: string | null; passwordppp: string | null; vlan: string | null; tanggal: Date | null;
  };
  const antrianPlain = antrian.map((r: AntrianRow) => ({
    ...r,
    tanggal: r.tanggal ? r.tanggal.toISOString() : null,
  }));

  return (
    <div>
      <div className="mb-6 flex flex-wrap items-center justify-between gap-2">
        <div>
          <h1 className="text-2xl font-semibold text-gray-800">Antrian Aktivasi</h1>
          <p className="text-sm text-gray-500">
            Pendaftaran baru yang belum diproses teknisi/NOC. {antrian.length} dalam antrian.
          </p>
        </div>
        <Link href="/pelanggan" className="text-sm text-blue-600 hover:underline">
          Lihat semua data pelanggan →
        </Link>
      </div>

      <form className="mb-4 flex flex-wrap gap-2">
        <input
          type="text"
          name="q"
          defaultValue={query}
          placeholder="Cari nama, no. HP, atau alamat..."
          className="w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none sm:w-72"
        />
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
        {(query || filterPop) && (
          <Link href="/pelanggan/aktivasi" className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
            Reset
          </Link>
        )}
      </form>

      <AktivasiClient antrian={antrianPlain} paketList={paketListPlain} />
    </div>
  );
}
