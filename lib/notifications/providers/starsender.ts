import type { NotificationProvider, NotificationResult } from "../types";

// Provider WhatsApp lewat Starsender -- dipindah dari yang tadinya nempel
// langsung di modul Aktivasi Pelanggan (aktivasi_pelanggan.php lama & versi
// awal Next.js-nya). Sekarang jadi provider generik: modul manapun yang mau
// kirim WhatsApp tinggal panggil `sendWhatsApp(target, message)` dari
// `lib/notifications`, tidak perlu tahu ini pakai Starsender.

const STARSENDER_API_URL = "https://api.starsender.online/api/send";

export const starsenderProvider: NotificationProvider = {
  name: "starsender",

  async sendText(target: string, message: string): Promise<NotificationResult> {
    const token = process.env.STARSENDER_API_TOKEN;
    if (!token) {
      return { ok: false, reason: "STARSENDER_API_TOKEN belum diset di environment variables." };
    }

    try {
      const res = await fetch(STARSENDER_API_URL, {
        method: "POST",
        headers: { "Content-Type": "application/json", Authorization: token },
        body: JSON.stringify({ messageType: "text", to: target, body: message }),
        signal: AbortSignal.timeout(10000),
      });
      if (!res.ok) {
        return { ok: false, reason: `Starsender merespons status ${res.status}` };
      }
      return { ok: true };
    } catch (err) {
      return { ok: false, reason: err instanceof Error ? err.message : "Unknown error" };
    }
  },
};
