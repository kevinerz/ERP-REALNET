// Badge status kecil (Aktif/Nonaktif, Segera, dll) -- dipakai di berbagai
// modul supaya warna & bentuknya konsisten.

const TONE_CLASSES = {
  green: "bg-green-50 text-green-700",
  gray: "bg-gray-100 text-gray-500",
  red: "bg-red-50 text-red-700",
  blue: "bg-blue-50 text-blue-700",
  amber: "bg-amber-50 text-amber-700",
} as const;

export type BadgeTone = keyof typeof TONE_CLASSES;

export function Badge({ children, tone = "gray" }: { children: React.ReactNode; tone?: BadgeTone }) {
  return (
    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${TONE_CLASSES[tone]}`}>
      {children}
    </span>
  );
}
