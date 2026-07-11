// Mapping nama POP -> ID grup WhatsApp. Dipisah dari logic pengiriman supaya
// modul lain (mis. Gangguan/Tiket per area) bisa pakai mapping yang sama tanpa
// duplikasi. Bukan kredensial rahasia (cuma ID grup), aman disimpan di kode --
// sama seperti sebelumnya di aktivasi_pelanggan.php.
//
// Untuk nambah/ubah POP baru, cukup edit object di bawah -- tidak perlu ubah
// kode di tempat lain.
export const POP_WHATSAPP_GROUPS: Record<string, string> = {
  rajeg: "6281293958590-1587210420@g.us",
  kemeri: "6287770366015-1628875457@g.us",
  kelapa: "120363423157487069@g.us",
  panggang: "120363405472722137@g.us",
  muncung: "120363424548647899@g.us",
  mauk: "120363419348224895@g.us",
};

export function getWhatsAppGroupForPop(pop: string | null | undefined): string | null {
  if (!pop) return null;
  return POP_WHATSAPP_GROUPS[pop.trim().toLowerCase()] ?? null;
}
