// Titik masuk tunggal untuk kirim notifikasi dari modul manapun.
//
// Kenapa dipusatkan di sini (bukan tiap modul bikin fetch() sendiri ke API
// WhatsApp seperti sebelumnya):
//  - Ganti provider (mis. dari Starsender ke provider lain) cukup di 1 tempat.
//  - Setiap modul baru (Gangguan, Kasbon, dst) tinggal `import { sendWhatsApp }
//    from "@/lib/notifications"` tanpa perlu tahu detail token/endpoint API.
//  - Konsisten: semua kirim WA lewat jalur & error-handling yang sama.
//
// HANYA dipakai dari server (server action / route handler), jangan pernah
// diimpor dari komponen client (butuh env var rahasia).

import { starsenderProvider } from "./providers/starsender";
import { getWhatsAppGroupForPop } from "./pop-groups";
import type { NotificationResult } from "./types";

export { getWhatsAppGroupForPop };

/** Kirim pesan teks WhatsApp ke target (nomor atau ID grup) apa adanya. */
export async function sendWhatsApp(target: string, message: string): Promise<NotificationResult> {
  const result = await starsenderProvider.sendText(target, message);
  if (!result.ok) {
    console.warn(`[notifikasi:${starsenderProvider.name}] gagal kirim -- ${result.reason}`);
  }
  return result;
}

/** Kirim pesan teks WhatsApp ke grup POP tertentu. Diam-diam dilewati (return ok:false) kalau POP tidak dikenal. */
export async function sendWhatsAppToPop(pop: string | null | undefined, message: string): Promise<NotificationResult> {
  const groupId = getWhatsAppGroupForPop(pop);
  if (!groupId) {
    return { ok: false, reason: `Tidak ada grup WhatsApp terdaftar untuk POP "${pop ?? "-"}"` };
  }
  return sendWhatsApp(groupId, message);
}
