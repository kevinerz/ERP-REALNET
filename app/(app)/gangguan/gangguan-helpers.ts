// Helper murni untuk modul Gangguan -- status & label disamakan persis
// dengan gangguan.php / edit_gangguan.php lama.

export const STATUS_OPTIONS = [
  { value: "belum dikerjakan", label: "Belum Dikerjakan" },
  { value: "di proses", label: "Di Proses" },
  { value: "selesai", label: "Selesai" },
] as const;

export const STATUS_LABELS: Record<string, string> = {
  "belum dikerjakan": "Belum Dikerjakan",
  "di proses": "Di Proses",
  selesai: "Selesai",
};

export type BadgeTone = "red" | "amber" | "green" | "gray";

export function statusTone(status: string | null): BadgeTone {
  if (status === "belum dikerjakan") return "red";
  if (status === "di proses") return "amber";
  if (status === "selesai") return "green";
  return "gray";
}
