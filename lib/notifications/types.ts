// Kontrak generik untuk sistem notifikasi -- supaya modul manapun (Aktivasi,
// Gangguan, Kasbon, dst) bisa kirim notifikasi tanpa perlu tahu detail
// provider-nya (Starsender, atau provider lain di masa depan).

export type NotificationResult = {
  ok: boolean;
  /** Alasan gagal/dilewati, untuk logging -- tidak ditampilkan ke user. */
  reason?: string;
};

export interface NotificationProvider {
  /** Nama provider, untuk logging. */
  name: string;
  sendText(target: string, message: string): Promise<NotificationResult>;
}
