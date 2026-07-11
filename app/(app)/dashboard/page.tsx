import { prisma } from "@/lib/prisma";

function monthRange(date = new Date()) {
  const start = new Date(date.getFullYear(), date.getMonth(), 1);
  const end = new Date(date.getFullYear(), date.getMonth() + 1, 1);
  return { start, end };
}

async function getStats() {
  const { start, end } = monthRange();

  const [
    totalPelanggan,
    pelangganBulanIni,
    tiketTerbuka,
    tiketSelesaiBulanIni,
    karyawanAktif,
    totalMitra,
  ] = await Promise.all([
    prisma.pelangganInstalasi.count(),
    prisma.pelangganInstalasi.count({ where: { tanggal: { gte: start, lt: end } } }),
    prisma.tiketGangguan.count({ where: { tanggal_selesai: null } }),
    prisma.tiketGangguan.count({ where: { tanggal_selesai: { gte: start, lt: end } } }),
    prisma.hrKaryawan.count({ where: { status_aktif: true } }),
    prisma.mitraResmi.count(),
  ]);

  return { totalPelanggan, pelangganBulanIni, tiketTerbuka, tiketSelesaiBulanIni, karyawanAktif, totalMitra };
}

const CARDS = [
  { key: "totalPelanggan", label: "Total Pelanggan", accent: "text-blue-700" },
  { key: "pelangganBulanIni", label: "Pelanggan Baru Bulan Ini", accent: "text-emerald-700" },
  { key: "tiketTerbuka", label: "Tiket Belum Selesai", accent: "text-amber-700" },
  { key: "tiketSelesaiBulanIni", label: "Tiket Selesai Bulan Ini", accent: "text-emerald-700" },
  { key: "karyawanAktif", label: "Karyawan Aktif", accent: "text-blue-700" },
  { key: "totalMitra", label: "Mitra Resmi Terdaftar", accent: "text-gray-700" },
] as const;

export default async function DashboardPage() {
  const stats = await getStats();

  return (
    <div>
      <h1 className="mb-1 text-2xl font-semibold text-gray-800">Dashboard</h1>
      <p className="mb-6 text-sm text-gray-500">Ringkasan kondisi ERP REALNET saat ini.</p>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {CARDS.map((card) => (
          <div key={card.key} className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <div className="text-sm text-gray-500">{card.label}</div>
            <div className={`mt-2 text-3xl font-semibold ${card.accent}`}>{stats[card.key]}</div>
          </div>
        ))}
      </div>
    </div>
  );
}
