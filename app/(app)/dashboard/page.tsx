import { prisma } from "@/lib/prisma";
import { getSession } from "@/lib/auth";
import { IconUsers, IconTicket, IconCheck, IconBriefcase, IconHandshake } from "../icons";

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
  {
    key: "totalPelanggan",
    label: "Total Pelanggan",
    icon: IconUsers,
    tone: "bg-brand-50 text-brand-700",
  },
  {
    key: "pelangganBulanIni",
    label: "Pelanggan Baru Bulan Ini",
    icon: IconUsers,
    tone: "bg-emerald-50 text-emerald-700",
  },
  {
    key: "tiketTerbuka",
    label: "Tiket Belum Selesai",
    icon: IconTicket,
    tone: "bg-amber-50 text-amber-700",
  },
  {
    key: "tiketSelesaiBulanIni",
    label: "Tiket Selesai Bulan Ini",
    icon: IconCheck,
    tone: "bg-emerald-50 text-emerald-700",
  },
  {
    key: "karyawanAktif",
    label: "Karyawan Aktif",
    icon: IconBriefcase,
    tone: "bg-brand-50 text-brand-700",
  },
  {
    key: "totalMitra",
    label: "Mitra Resmi Terdaftar",
    icon: IconHandshake,
    tone: "bg-gray-100 text-gray-700",
  },
] as const;

function greeting(): string {
  const hour = new Date().getHours();
  if (hour < 11) return "Selamat pagi";
  if (hour < 15) return "Selamat siang";
  if (hour < 19) return "Selamat sore";
  return "Selamat malam";
}

export default async function DashboardPage() {
  const [stats, session] = await Promise.all([getStats(), getSession()]);
  const today = new Intl.DateTimeFormat("id-ID", { weekday: "long", day: "numeric", month: "long", year: "numeric" }).format(new Date());

  return (
    <div>
      <div className="mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-slate-900 to-brand-950 p-6 text-white shadow-elevated-lg sm:p-8">
        <p className="text-sm font-medium text-slate-300">{today}</p>
        <h1 className="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">
          {greeting()}{session ? `, ${session.nama.split(" ")[0]}` : ""}
        </h1>
        <p className="mt-2 max-w-lg text-sm text-slate-400">
          Ringkasan kondisi ERP REALNET saat ini -- pelanggan, tiket, dan tim yang aktif.
        </p>
      </div>

      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {CARDS.map((card) => {
          const Icon = card.icon;
          return (
            <div
              key={card.key}
              className="rounded-2xl border border-gray-100 bg-white p-5 shadow-elevated transition hover:shadow-elevated-lg"
            >
              <div className={`inline-flex h-10 w-10 items-center justify-center rounded-xl ${card.tone}`}>
                <Icon className="h-5 w-5" />
              </div>
              <div className="mt-4 text-sm font-medium text-gray-500">{card.label}</div>
              <div className="mt-1 text-3xl font-bold tracking-tight text-gray-900">{stats[card.key]}</div>
            </div>
          );
        })}
      </div>
    </div>
  );
}
