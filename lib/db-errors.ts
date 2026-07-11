// Helper error database bersama -- dipakai semua server action yang nulis ke
// database (createX/updateX) supaya pesan error unique-constraint konsisten,
// tidak perlu tulis ulang pengecekan `err.code === "P2002"` di tiap modul.

type PrismaKnownError = { code?: string; meta?: { target?: string[] } };

/**
 * Kalau error-nya pelanggaran UNIQUE KEY (kode P2002 Prisma), kembalikan
 * pesan yang jelas menyebut field mana yang bentrok (pakai `fieldLabels`
 * untuk terjemahan nama kolom -> label yang enak dibaca). Kalau bukan error
 * unique constraint, kembalikan null (biar pemanggil tangani sendiri).
 */
export function formatUniqueConstraintError(
  err: unknown,
  fieldLabels: Record<string, string>
): string | null {
  const e = err as PrismaKnownError;
  if (e?.code !== "P2002") return null;

  // MySQL/MariaDB kadang mengembalikan target sebagai nama index gabungan
  // (mis. "hr_karyawan.uniq_username"), bukan nama kolom polos -- jadi
  // dicocokkan pakai "includes" per kata kunci, bukan pencarian exact key.
  const targetText = (e.meta?.target ?? []).join(" ").toLowerCase();
  const matched = Object.entries(fieldLabels).filter(([key]) => targetText.includes(key.toLowerCase()));
  const labelText = matched.length > 0 ? matched.map(([, label]) => label).join(" / ") : "data";

  return `Data tidak bisa disimpan: ${labelText} sudah dipakai data lain.`;
}
