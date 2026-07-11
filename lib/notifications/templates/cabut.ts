// Template pesan notifikasi tiket CABUT modem -- disamakan persis dengan
// pesan yang dirakit langsung di dalam cabut.php lama (ACTION: CREATE TICKET
// dan ACTION: UPDATE STATUS), supaya isi notifikasi WA tidak berubah untuk
// tim lapangan yang sudah terbiasa dengan formatnya.

export type CabutMessageData = {
  id: number;
  pop: string;
  nama: string;
  alamat: string;
  wa: string;
  alasan: string;
  sn_modem: string;
  status: string;
  created_at: string;
};

function formatWaktu(iso: string): string {
  return new Intl.DateTimeFormat("id-ID", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
    second: "2-digit",
  }).format(new Date(iso));
}

/** Sama seperti pesan "TIKET CABUT MODEM BARU" di cabut.php (ACTION: create). */
export function buildCabutTicketMessage(t: CabutMessageData): string {
  const lines = [
    "📋 TIKET CABUT MODEM BARU",
    "",
    `Nama: ${t.nama}`,
    `POP: ${t.pop}`,
    `Alamat: ${t.alamat}`,
    `No. WA: ${t.wa}`,
    `Alasan: ${t.alasan}`,
    `SN: ${t.sn_modem}`,
    "Status: BELUM SELESAI",
    `Waktu: ${formatWaktu(t.created_at)}`,
  ];
  return lines.join("\n");
}

/** Sama seperti pesan "UPDATE STATUS TIKET" di cabut.php (ACTION: updateStatus). */
export function buildCabutStatusChangeMessage(
  t: Pick<CabutMessageData, "nama" | "sn_modem">,
  oldStatus: string,
  newStatus: string
): string {
  const lines = [
    "🔄 UPDATE STATUS TIKET",
    "",
    `Nama: ${t.nama}`,
    `SN: ${t.sn_modem}`,
    `Status Lama: ${oldStatus.toUpperCase()}`,
    `Status Baru: ${newStatus.toUpperCase()}`,
    `Waktu: ${formatWaktu(new Date().toISOString())}`,
  ];
  return lines.join("\n");
}
