import { prisma } from "@/lib/prisma";

// Master Data Pelanggan -- query & upsert bersama, dipakai lintas modul
// (Gangguan, Cabut, Pelanggan/Instalasi, dan halaman Master Pelanggan itu
// sendiri). Lihat catatan lengkap di prisma/schema.prisma pada model
// PelangganMaster untuk latar belakangnya (menggantikan query langsung ke
// database billing terpisah "u272457353_dapel" di tiket/index.php lama).

export type PelangganMasterHit = {
  id: number;
  nama: string;
  telp: string;
  alamat: string | null;
};

/** Cari pelanggan by nama ATAU nomor telepon, dibatasi 15 hasil. */
export async function searchPelangganMasterRows(query: string): Promise<PelangganMasterHit[]> {
  const q = query.trim();
  if (q.length < 2) return [];

  return prisma.pelangganMaster.findMany({
    where: {
      OR: [{ nama: { contains: q } }, { telp: { contains: q } }],
    },
    select: { id: true, nama: true, telp: true, alamat: true },
    orderBy: { nama: "asc" },
    take: 15,
  });
}

/**
 * Simpan/perbarui satu entri di Master Data Pelanggan, dicocokkan by nomor
 * telepon (kunci paling stabil dibanding nama/username yang sering beda
 * ejaan). Kalau telp kosong, tidak melakukan apa-apa -- tanpa nomor telepon
 * entri tidak berguna untuk pencarian.
 *
 * Dipanggil otomatis tiap ada pelanggan baru/diedit lewat form
 * Pelanggan/Instalasi (PSB) -- lihat app/(app)/pelanggan/actions.ts.
 */
export async function upsertPelangganMaster(input: {
  nama: string;
  telp: string;
  alamat?: string | null;
  username?: string | null;
  pop?: string | null;
  sumber: string;
}): Promise<void> {
  const telp = input.telp.trim();
  const nama = input.nama.trim();
  if (!telp || !nama) return;

  const existing = await prisma.pelangganMaster.findFirst({ where: { telp } });

  if (existing) {
    await prisma.pelangganMaster.update({
      where: { id: existing.id },
      data: {
        nama,
        alamat: input.alamat ?? existing.alamat,
        username: input.username ?? existing.username,
        pop: input.pop ?? existing.pop,
      },
    });
  } else {
    await prisma.pelangganMaster.create({
      data: {
        nama,
        telp,
        alamat: input.alamat ?? null,
        username: input.username ?? null,
        pop: input.pop ?? null,
        sumber: input.sumber,
      },
    });
  }
}
