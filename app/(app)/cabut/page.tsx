import Link from "next/link";
import { prisma } from "@/lib/prisma";
import { Badge } from "@/components/ui/badge";
import { EmptyTableRow } from "@/components/ui/empty-state";
import { createCabutTiket } from "./actions";
import CabutForm from "./cabut-form";
import StatusButton from "./status-button";
import { ALLOWED_POP, STATUS_LIST, isAllowedPop, isCabutStatus, statusTone, waLink } from "./cabut-helpers";

// Bangun ulang persis cabut.php: form tambah tiket + filter (cari/POP/status)
// + tabel, TANPA paginasi (sama seperti versi lama yang fetchAll() semua
// hasil query). Bedanya dengan modul Gangguan: statistik (total/belum
// selesai/selesai + distribusi per POP) di sini DIHITUNG DARI HASIL YANG
// SUDAH DIFILTER (persis seperti cabut.php yang menghitung $totalTiket dkk
// dengan looping array $tickets setelah query WHERE diterapkan) -- bukan
// statistik global seperti di Gangguan.

export default async function CabutPage({
  searchParams,
}: {
  searchParams: Promise<{ q?: string; pop?: string; status?: string }>;
}) {
  const { q, pop, status } = await searchParams;
  const keyword = q?.trim() ?? "";
  const filterPop = isAllowedPop(pop) ? pop : "";
  const filterStatus = isCabutStatus(status) ? status : "";

  const where: Record<string, unknown> = {};
  if (keyword) {
    where.OR = [
      { pop: { contains: keyword } },
      { nama: { contains: keyword } },
      { alamat: { contains: keyword } },
      { wa: { contains: keyword } },
      { alasan: { contains: keyword } },
      { sn_modem: { contains: keyword } },
    ];
  }
  if (filterPop) where.pop = filterPop;
  if (filterStatus) where.status = filterStatus;

  type Ticket = {
    id: number;
    pop: string;
    nama: string;
    alamat: string;
    wa: string;
    alasan: string;
    sn_modem: string;
    status: string;
    created_at: Date | null;
  };

  const tickets: Ticket[] = await prisma.tiketCabutModem.findMany({
    where,
    orderBy: { created_at: "desc" },
  });

  let totalSelesai = 0;
  let totalBelum = 0;
  const perPop: Record<string, number> = {};
  for (const t of tickets) {
    if (t.status === "selesai") totalSelesai++;
    else totalBelum++;
    perPop[t.pop] = (perPop[t.pop] ?? 0) + 1;
  }
  const totalTiket = tickets.length;

  const ticketsPlain = tickets.map((t: Ticket) => ({
    ...t,
    created_at: t.created_at ? t.created_at.toISOString() : null,
  }));

  function formatTanggal(iso: string | null): string {
    if (!iso) return "-";
    return new Intl.DateTimeFormat("id-ID", { day: "2-digit", month: "2-digit", year: "numeric" }).format(new Date(iso));
  }

  return (
    <div>
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-gray-800">Cabut (Tiket Cabut Modem)</h1>
        <p className="text-sm text-gray-500">
          Update status hanya manual via tombol &mdash; tidak ada auto-trigger. Statistik di bawah mengikuti filter yang sedang aktif.
        </p>
      </div>

      <div className="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div className="rounded-lg border border-gray-200 bg-white p-4 border-l-4 border-l-blue-400">
          <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">Total Tiket</div>
          <div className="mt-1 text-2xl font-bold text-gray-800">{totalTiket}</div>
        </div>
        <div className="rounded-lg border border-gray-200 bg-white p-4 border-l-4 border-l-amber-400">
          <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">Belum Selesai</div>
          <div className="mt-1 text-2xl font-bold text-amber-600">{totalBelum}</div>
        </div>
        <div className="rounded-lg border border-gray-200 bg-white p-4 border-l-4 border-l-green-400">
          <div className="text-xs font-semibold uppercase tracking-wide text-gray-400">Sudah Selesai</div>
          <div className="mt-1 text-2xl font-bold text-green-600">{totalSelesai}</div>
        </div>
      </div>

      {Object.keys(perPop).length > 0 && (
        <div className="mb-6 rounded-lg border border-gray-200 bg-white p-4">
          <div className="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
            Distribusi Tiket per POP
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
            {Object.entries(perPop).map(([popName, cnt]) => (
              <div key={popName} className="rounded-md bg-gray-50 p-3 text-center">
                <div className="text-sm font-medium text-gray-700">{popName}</div>
                <div className="text-xl font-bold text-blue-600">{cnt}</div>
                <div className="text-xs text-gray-400">tiket</div>
              </div>
            ))}
          </div>
        </div>
      )}

      <div className="mb-6 rounded-lg border border-gray-200 bg-white p-6">
        <h2 className="mb-4 text-sm font-semibold text-gray-700">Tambah Tiket Cabut Modem</h2>
        <CabutForm action={createCabutTiket} />
      </div>

      <form className="mb-4 flex flex-wrap items-end gap-2">
        <input
          type="text"
          name="q"
          defaultValue={keyword}
          placeholder="Cari nama/WA/SN/alamat..."
          className="w-full max-w-xs rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
        />
        <select name="pop" defaultValue={filterPop} className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
          <option value="">Semua POP</option>
          {ALLOWED_POP.map((p) => (
            <option key={p} value={p}>{p}</option>
          ))}
        </select>
        <select name="status" defaultValue={filterStatus} className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
          <option value="">Semua Status</option>
          {STATUS_LIST.map((s) => (
            <option key={s} value={s}>{s === "selesai" ? "Selesai" : "Belum Selesai"}</option>
          ))}
        </select>
        <button type="submit" className="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
          Cari
        </button>
        {(keyword || filterPop || filterStatus) && (
          <Link href="/cabut" className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50">
            Reset
          </Link>
        )}
      </form>

      <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table className="min-w-full divide-y divide-gray-200 text-sm">
          <thead className="bg-gray-50 text-left text-xs font-medium uppercase text-gray-500">
            <tr>
              <th className="px-4 py-3">POP</th>
              <th className="px-4 py-3">Nama</th>
              <th className="px-4 py-3">Alamat</th>
              <th className="px-4 py-3">No. WA</th>
              <th className="px-4 py-3">SN Modem</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Dibuat</th>
              <th className="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {ticketsPlain.map((t) => (
              <tr key={t.id} className="hover:bg-gray-50">
                <td className="px-4 py-3">
                  <Badge tone="blue">{t.pop}</Badge>
                </td>
                <td className="px-4 py-3 font-medium text-gray-800">{t.nama}</td>
                <td className="px-4 py-3 max-w-[220px] truncate text-gray-600">{t.alamat}</td>
                <td className="px-4 py-3">
                  <a href={waLink(t.wa)} target="_blank" rel="noreferrer" className="text-green-600 hover:underline">
                    {t.wa}
                  </a>
                </td>
                <td className="px-4 py-3 font-mono text-xs">{t.sn_modem}</td>
                <td className="px-4 py-3">
                  <Badge tone={statusTone(t.status)}>{t.status === "selesai" ? "Selesai" : "Belum Selesai"}</Badge>
                </td>
                <td className="px-4 py-3 text-xs text-gray-500">{formatTanggal(t.created_at)}</td>
                <td className="px-4 py-3 text-right">
                  <StatusButton id={t.id} nama={t.nama} status={t.status} />
                </td>
              </tr>
            ))}
            {ticketsPlain.length === 0 && <EmptyTableRow colSpan={8} message="Belum ada tiket." />}
          </tbody>
        </table>
      </div>
    </div>
  );
}
