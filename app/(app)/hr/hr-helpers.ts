// Helper murni (tanpa "use client"/"use server") untuk modul HRIS.
// Dipakai di form (client) maupun actions (server), jadi harus tetap generik.

/**
 * Field `tempat_tanggal_lahir` di database cuma 1 kolom teks bebas (warisan
 * dari PHP lama), formatnya "Kota, DD-MM-YYYY" -- lihat data asli hasil
 * migrasi (mis. "Kediri, 17-10-1999"). Form tambah_karyawan.php lama punya
 * 2 input terpisah (tempat lahir + tanggal lahir) yang digabung jadi 1
 * string sebelum disimpan. Kita samakan persis supaya PHP lama (yang masih
 * jalan berdampingan selama transisi) tetap bisa baca field ini dengan benar.
 */
export function combineTempatTanggalLahir(tempat: string, tanggalIso: string): string {
  const [year, month, day] = tanggalIso.split("-");
  return `${tempat.trim()}, ${day}-${month}-${year}`;
}

export function parseTempatTanggalLahir(value: string): { tempat: string; tanggalIso: string } {
  const idx = value.lastIndexOf(",");
  if (idx === -1) return { tempat: value.trim(), tanggalIso: "" };

  const tempat = value.slice(0, idx).trim();
  const tanggalPart = value.slice(idx + 1).trim();
  const match = tanggalPart.match(/^(\d{1,2})-(\d{1,2})-(\d{4})$/);
  if (!match) return { tempat, tanggalIso: "" };

  const [, day, month, year] = match;
  const tanggalIso = `${year}-${month.padStart(2, "0")}-${day.padStart(2, "0")}`;
  return { tempat, tanggalIso };
}

/** Hitung umur dari tanggal lahir (ISO yyyy-mm-dd), dibulatkan ke bawah. */
export function calculateUmur(tanggalLahirIso: string): number {
  const lahir = new Date(tanggalLahirIso);
  if (Number.isNaN(lahir.getTime())) return 0;

  const now = new Date();
  let umur = now.getFullYear() - lahir.getFullYear();
  const belumUlangTahun =
    now.getMonth() < lahir.getMonth() ||
    (now.getMonth() === lahir.getMonth() && now.getDate() < lahir.getDate());
  if (belumUlangTahun) umur -= 1;

  return Math.max(umur, 0);
}

export const DIVISI_OPTIONS = [
  "Admin",
  "IT",
  "Manager",
  "SPV Teknis",
  "Finance",
  "Leader Area",
  "Teknisi",
  "Backbone",
] as const;

export const STATUS_KEPEGAWAIAN_OPTIONS = ["Tetap", "Kontrak", "Magang"] as const;

export const TIPE_PETUGAS_OPTIONS = [
  "Lainnya",
  "NOC",
  "Teknisi Lapangan",
  "Admin Billing",
  "Sales/Marketing",
  "Manajemen",
] as const;

export const STATUS_PERNIKAHAN_OPTIONS = ["Belum Menikah", "Menikah", "Cerai Hidup", "Cerai Mati"] as const;
