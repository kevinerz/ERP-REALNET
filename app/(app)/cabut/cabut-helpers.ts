// Konstanta modul CABUT -- disamakan persis dengan cabut.php lama:
// hanya 3 POP yang diizinkan (PascalCase, sesuai <option> lama), dan hanya
// 2 status yang ada di database lama (tidak ada status lain seperti modul
// Gangguan).

export const ALLOWED_POP = ["Rajeg", "Mauk", "Kemeri"] as const;
export type CabutPop = (typeof ALLOWED_POP)[number];

export function isAllowedPop(value: string | undefined | null): value is CabutPop {
  return !!value && (ALLOWED_POP as readonly string[]).includes(value);
}

export const STATUS_LIST = ["belum selesai", "selesai"] as const;
export type CabutStatus = (typeof STATUS_LIST)[number];

export function isCabutStatus(value: string | undefined | null): value is CabutStatus {
  return !!value && (STATUS_LIST as readonly string[]).includes(value);
}

export type BadgeTone = "green" | "amber";
export function statusTone(status: string): BadgeTone {
  return status === "selesai" ? "green" : "amber";
}

/** Sama seperti waLink() di cabut.php: normalisasi 0 di depan jadi 62. */
export function waLink(wa: string): string {
  const digits = wa.replace(/\D+/g, "");
  const normalized = digits.startsWith("0") ? "62" + digits.slice(1) : digits;
  return `https://wa.me/${normalized}`;
}
