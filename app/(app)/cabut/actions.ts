"use server";

import { prisma } from "@/lib/prisma";
import { requireSession } from "@/lib/auth";
import { revalidatePath } from "next/cache";
import { sendWhatsAppToPop } from "@/lib/notifications";
import { buildCabutTicketMessage, buildCabutStatusChangeMessage } from "@/lib/notifications/templates/cabut";
import { ALLOWED_POP, isAllowedPop, isCabutStatus } from "./cabut-helpers";

export type CabutFormState = {
  error?: string;
  success?: string;
  fieldErrors?: Record<string, string>;
};

const REQUIRED_FIELDS = ["nama", "alamat", "wa", "alasan", "sn_modem"] as const;

// Sama seperti ACTION: CREATE TICKET di cabut.php -- validasi field wajib,
// insert dengan status hardcoded 'belum selesai', lalu kirim notifikasi WA
// ke grup POP terkait. Tidak redirect ke halaman lain (form + tabel ada di
// satu halaman yang sama, persis seperti cabut.php).
export async function createCabutTiket(
  _prev: CabutFormState,
  formData: FormData
): Promise<CabutFormState> {
  await requireSession();

  const get = (key: string) => (formData.get(key)?.toString().trim() ?? "");

  const popRaw = get("pop");
  const pop = isAllowedPop(popRaw) ? popRaw : ALLOWED_POP[0];
  const nama = get("nama");
  const alamat = get("alamat");
  const wa = get("wa").replace(/\s+/g, "");
  const alasan = get("alasan");
  const sn_modem = get("sn_modem");

  const fieldErrors: Record<string, string> = {};
  for (const field of REQUIRED_FIELDS) {
    const value = field === "wa" ? wa : get(field);
    if (!value) fieldErrors[field] = "Wajib diisi";
  }
  if (Object.keys(fieldErrors).length > 0) {
    return { error: "Harap lengkapi semua field.", fieldErrors };
  }

  let created;
  try {
    created = await prisma.tiketCabutModem.create({
      data: { pop, nama, alamat, wa, alasan, sn_modem, status: "belum selesai" },
    });
  } catch (err) {
    console.error("createCabutTiket error:", err);
    return { error: "Gagal menyimpan tiket. Coba lagi." };
  }

  const message = buildCabutTicketMessage({
    id: created.id,
    pop,
    nama,
    alamat,
    wa,
    alasan,
    sn_modem,
    status: "belum selesai",
    created_at: created.created_at ? created.created_at.toISOString() : new Date().toISOString(),
  });
  await sendWhatsAppToPop(pop, message);

  revalidatePath("/cabut");
  return { success: "Tiket berhasil dibuat dengan status BELUM SELESAI." };
}

// Sama seperti ACTION: UPDATE STATUS di cabut.php -- HANYA manual lewat
// tombol, tidak ada auto-trigger dari proses lain. Kalau status baru sama
// dengan status lama, tidak melakukan apa-apa (no-op), sama seperti
// pengecekan `if ($oldStatus === $newStatus)` di versi lama.
export async function updateCabutStatus(id: number, newStatus: string): Promise<void> {
  await requireSession();
  if (!isCabutStatus(newStatus)) return;

  const existing = await prisma.tiketCabutModem.findUnique({ where: { id } });
  if (!existing) return;
  if (existing.status === newStatus) return;

  await prisma.tiketCabutModem.update({ where: { id }, data: { status: newStatus } });

  const message = buildCabutStatusChangeMessage(
    { nama: existing.nama, sn_modem: existing.sn_modem },
    existing.status,
    newStatus
  );
  await sendWhatsAppToPop(existing.pop, message);

  revalidatePath("/cabut");
}
