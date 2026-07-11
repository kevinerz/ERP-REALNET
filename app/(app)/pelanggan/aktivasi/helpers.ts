// Helper murni untuk modul Antrian Aktivasi -- disamakan persis dengan logika
// saran username/password di aktivasi_pelanggan.php lama.

export function suggestUsername(nama: string, telp: string): string {
  const cleanTelp = telp.replace(/[^0-9]/g, "");
  const suffix = "@" + cleanTelp;
  const maxNameLen = Math.max(3, 30 - suffix.length);
  const cleanName = nama.toLowerCase().replace(/[^a-z0-9]/g, "");
  return cleanName.slice(0, maxNameLen) + suffix;
}

export const SUGGESTED_PASSWORD = "12345";

export function formatRupiah(value: number | string): string {
  const n = typeof value === "string" ? Number(value) : value;
  return new Intl.NumberFormat("id-ID").format(Number.isFinite(n) ? n : 0);
}
