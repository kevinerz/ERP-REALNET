// Template pesan notifikasi aktivasi pelanggan -- teksnya disamakan persis
// dengan sendWhatsAppNotification() di aktivasi_pelanggan.php lama. Dipisah
// jadi file sendiri (bukan digabung dengan logic kirim) supaya gampang
// diubah tanpa menyentuh kode pengiriman, dan gampang ditiru kalau modul
// lain butuh bikin template pesan sendiri.

export type ActivationMessageData = {
  id: number;
  nama: string;
  alamat: string;
  telp: string;
  pop: string | null;
  userppp: string | null;
  passwordppp: string | null;
  vlan: string | null;
  paket: { nama_paket: string; kecepatan: string; harga: number | string } | null;
};

export function buildActivationMessage(data: ActivationMessageData): string {
  const pkgName = data.paket?.nama_paket ?? "Custom";
  const pkgSpeed = data.paket?.kecepatan ?? "-";
  const pkgPrice = data.paket ? new Intl.NumberFormat("id-ID").format(Number(data.paket.harga)) : "0";
  const tglAktif = new Intl.DateTimeFormat("id-ID", { dateStyle: "short", timeStyle: "short" }).format(new Date());

  return (
    `⚡ *AKTIVASI LAYANAN BARU* ⚡\n══════════════════\n` +
    `🆔 *Tiket ID :* #${data.id}\n🏢 *POP Area :* ${data.pop ?? "-"}\n📅 *Waktu    :* ${tglAktif} WIB\n\n` +
    `👤 *CUSTOMER INFO*\n──────────────────\n🏷️ *Nama    :* ${data.nama}\n🏠 *Alamat :* ${data.alamat}\n📱 *Kontak :* ${data.telp}\n\n` +
    `📦 *SERVICE DATA*\n──────────────────\n🚀 *Paket  :* ${pkgName}\n⚡ *Speed  :* ${pkgSpeed}\n💰 *Tagihan:* Rp ${pkgPrice}/bln\n\n` +
    `🔐 *NETWORK CONFIG (PPPoE)*\n──────────────────\n👤 *User :* \`${data.userppp ?? "-"}\`\n🔑 *Pass :* \`${data.passwordppp ?? "-"}\`\n🔢 *VLAN :* \`${data.vlan ?? "-"}\`\n\n` +
    `⚠️ _Mohon teknisi melakukan konfigurasi modem sesuai data di atas._\n✅ _Status: ONLINE_`
  );
}
