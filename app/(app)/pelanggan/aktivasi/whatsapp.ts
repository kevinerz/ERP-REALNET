// Util server-only untuk kirim notifikasi WhatsApp saat aktivasi -- HANYA
// diimpor dari actions.ts (file "use server"), jangan pernah diimpor dari
// komponen client. Menyamakan logika sendWhatsAppNotification() di
// aktivasi_pelanggan.php lama, tapi token API dipindah ke env var (di PHP
// lama tokennya hardcode langsung di kode -- risiko keamanan yang sudah
// diperbaiki di sini).

const STARSENDER_API_URL = "https://api.starsender.online/api/send";

// Mapping POP -> ID grup WhatsApp, sama persis dengan yang ada di PHP lama.
// Ini bukan kredensial rahasia (cuma ID grup), jadi aman tetap di kode.
const POP_GROUP_IDS: Record<string, string> = {
  rajeg: "6281293958590-1587210420@g.us",
  kemeri: "6287770366015-1628875457@g.us",
  kelapa: "120363423157487069@g.us",
  panggang: "120363405472722137@g.us",
  muncung: "120363424548647899@g.us",
  mauk: "120363419348224895@g.us",
};

type CustomerData = {
  id: number;
  nama: string;
  alamat: string;
  userppp: string | null;
  passwordppp: string | null;
  vlan: string | null;
  telp: string;
  pop: string | null;
};

type PaketData = { nama_paket: string; kecepatan: string; harga: number | string } | null;

export async function sendActivationWhatsApp(customer: CustomerData, paket: PaketData): Promise<boolean> {
  const groupId = POP_GROUP_IDS[(customer.pop ?? "").trim().toLowerCase()];
  if (!groupId) return false;

  const token = process.env.STARSENDER_API_TOKEN;
  if (!token) {
    console.warn("STARSENDER_API_TOKEN belum diset -- notifikasi WhatsApp aktivasi dilewati.");
    return false;
  }

  const pkgName = paket?.nama_paket ?? "Custom";
  const pkgSpeed = paket?.kecepatan ?? "-";
  const pkgPrice = paket ? new Intl.NumberFormat("id-ID").format(Number(paket.harga)) : "0";
  const tglAktif = new Intl.DateTimeFormat("id-ID", { dateStyle: "short", timeStyle: "short" }).format(new Date());

  const message =
    `⚡ *AKTIVASI LAYANAN BARU* ⚡\n══════════════════\n` +
    `🆔 *Tiket ID :* #${customer.id}\n🏢 *POP Area :* ${customer.pop ?? "-"}\n📅 *Waktu    :* ${tglAktif} WIB\n\n` +
    `👤 *CUSTOMER INFO*\n──────────────────\n🏷️ *Nama    :* ${customer.nama}\n🏠 *Alamat :* ${customer.alamat}\n📱 *Kontak :* ${customer.telp}\n\n` +
    `📦 *SERVICE DATA*\n──────────────────\n🚀 *Paket  :* ${pkgName}\n⚡ *Speed  :* ${pkgSpeed}\n💰 *Tagihan:* Rp ${pkgPrice}/bln\n\n` +
    `🔐 *NETWORK CONFIG (PPPoE)*\n──────────────────\n👤 *User :* \`${customer.userppp ?? "-"}\`\n🔑 *Pass :* \`${customer.passwordppp ?? "-"}\`\n🔢 *VLAN :* \`${customer.vlan ?? "-"}\`\n\n` +
    `⚠️ _Mohon teknisi melakukan konfigurasi modem sesuai data di atas._\n✅ _Status: ONLINE_`;

  try {
    const res = await fetch(STARSENDER_API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: token },
      body: JSON.stringify({ messageType: "text", to: groupId, body: message }),
      signal: AbortSignal.timeout(10000),
    });
    return res.ok;
  } catch (err) {
    console.error("Gagal kirim notifikasi WhatsApp aktivasi:", err);
    return false;
  }
}
