# Mapping Database Lama → Skema Konsolidasi (`erp_realnet`)

File terkait: `consolidated_schema.sql` (siap dijalankan via `mysql -u root -p < consolidated_schema.sql`, atau import lewat phpMyAdmin).

Ini adalah **rancangan skema saja** — belum memindahkan data. Struktur tabel (nama kolom, tipe data) dipertahankan sama persis dari database asli supaya kode PHP lama tetap kompatibel; yang dirapikan hanya nama tabel (diberi prefix per domain) dan lokasinya (semua jadi 1 database).

## Peta tabel lama → baru

| Database Lama | Tabel Lama | Tabel Baru | Domain |
|---|---|---|---|
| db_pemasangan | `pemasangan` | `pelanggan_instalasi` | Pelanggan |
| db_pemasangan | `pemasangan_fee_marketing_status` | `pelanggan_fee_marketing` | Pelanggan |
| db_pemasangan | `pemasangan_fee_teknisi_status` | `pelanggan_fee_teknisi` | Pelanggan |
| db_pemasangan | `pop` | `jaringan_pop` | Jaringan |
| umumdata | `ODP` | `jaringan_odp` | Jaringan |
| umumdata | `paket` | `jaringan_paket` | Jaringan |
| umumdata | `kabel_adss` | `jaringan_kabel_adss` | Jaringan |
| umumdata | `kabel_dropcore` | `jaringan_kabel_dropcore` | Jaringan |
| umumdata | `modem` | `jaringan_modem` | Jaringan |
| umumdata | `modem_log` | `jaringan_modem_log` | Jaringan |
| umumdata | `modem_logging` | `jaringan_modem_logging` | Jaringan |
| umumdata | `perangkat_lain` | `jaringan_perangkat_lain` | Jaringan |
| umumdata | `karyawan` | `hr_karyawan` | HR |
| umumdata | `cuti` | `hr_cuti` | HR |
| umumdata | `jadwal_libur` | `hr_jadwal_libur` | HR |
| umumdata | `struktur_organisasi` | `hr_struktur_organisasi` | HR |
| fms | `slip_gaji` | `hr_slip_gaji` | HR |
| umumdata | `Aset` | `aset_master` | Aset |
| umumdata | `aset_spv` | `aset_spv` | Aset |
| umumdata | `kasbon` | `keu_kasbon` | Keuangan |
| umumdata | `reimburse_bbm` | `keu_reimburse_bbm` | Keuangan |
| fms | `biaya_listrik` | `keu_biaya_listrik` | Keuangan |
| fms | `pemasukan` | `keu_pemasukan` | Keuangan |
| fms | `pengeluaran` | `keu_pengeluaran` | Keuangan |
| fms | `pembayaran_kontribusi` | `keu_pembayaran_kontribusi` | Keuangan |
| fms | `pembayaran_sewa` | `keu_pembayaran_sewa` | Keuangan |
| fms | `pembayaran_upstream` | `keu_pembayaran_upstream` | Keuangan |
| fms | `pengajuan_aset` | `keu_pengajuan_pembelian_aset` | Keuangan |
| tiket_helpdesk | `tiket` | `tiket_gangguan` | Tiket |
| tiket_helpdesk | `tiket_ai` | `tiket_ai` | Tiket |
| mitra | `mitra_resmi` | `mitra_resmi` | Mitra |
| mitra | `v_mitra_statistics` (view) | `mitra_statistics` (view) | Mitra |

**Total: 31 tabel + 1 view**, cocok dengan jumlah asli (4+16+2+8+2 tabel + 1 view di 5 database lama).

## Yang TIDAK ikut digabung (sesuai keputusanmu)

- **`dapel`** (45 tabel: radius, voucher, payment gateway) — dibiarkan terpisah karena memang sistem billing hotspot yang berbeda dunia dari ERP karyawan/pelanggan.
  ⚠️ **Temuan penting**: modul `tiket/index.php` di kode saat ini query **langsung** ke `dapel.tbl_customers` untuk ambil nama/telepon/alamat pelanggan. Artinya `dapel` sebenarnya adalah sumber data pelanggan utama, bukan sistem yang sepenuhnya lepas. Supaya konsisten dengan keputusan "dibiarkan terpisah", jangan salin data pelanggan ke `erp_realnet` — tetap akses `dapel` sebagai satu-satunya sumber kebenaran data pelanggan (nanti di fase migrasi Next.js, ini sebaiknya lewat satu fungsi/API khusus, bukan koneksi mysqli manual seperti sekarang).
- **`backbone`, `cabut`, `crm`** — direferensikan di kode tapi tidak ada file dump-nya di folder ini. Dilewati dulu sesuai arahanmu; perlu di-export menyusul dari server produksi.
- **`app13194radius`** (3 tabel: tbl_appconfig, tbl_language, tbl_users) — dicek dengan grep, **tidak direferensikan oleh file PHP manapun**. Kemungkinan sisa instalasi lama. Diabaikan.

## Perubahan yang ditambahkan (tidak ada di database asli)

- 9 **FOREIGN KEY** baru (mis. `hr_cuti.id_karyawan → hr_karyawan.id`, `pelanggan_fee_marketing.pemasangan_id → pelanggan_instalasi.id`) — supaya integritas relasi dijaga otomatis oleh database.
- `UNIQUE KEY` pada `hr_karyawan.username` dan `hr_karyawan.nik` — sebelumnya tidak ada, artinya secara teori bisa ada 2 akun username sama persis.
- Beberapa index tambahan untuk kolom yang sering dipakai filter (status, telp).
- Beberapa kolom (`aset_spv.pemilik_username`, `hr_slip_gaji.karyawan_nik`, `pelanggan_instalasi.pop/odp/paket`) masih berupa teks bebas, bukan foreign key ber-ID — sengaja tidak dipaksa jadi FK di level SQL karena datanya belum tentu 100% konsisten (rawan gagal saat import). Rapikan ini nanti di level aplikasi (Prisma/Next.js), bukan sekarang.

## Belum termasuk di tahap ini

- **Migrasi data asli** dari 5 database lama ke struktur baru (baru rancangan skema/DDL, tabel masih kosong). Kalau sudah oke dengan strukturnya, langkah berikutnya adalah bikin script `INSERT ... SELECT` per tabel dari database lama ke `erp_realnet`.
- Kredensial 1 user/password baru untuk `erp_realnet` (rekomendasi: 1 kredensial saja, bukan 1 per database seperti sekarang).

## Cara pakai file `consolidated_schema.sql`

1. Backup dulu 5 database lama (kamu sudah punya file dump-nya).
2. Buat database baru: jalankan file ini di server MySQL Hostinger (lewat phpMyAdmin → Import, atau `mysql -u <user> -p < consolidated_schema.sql`).
3. Setelah tabel baru terbentuk dan dicek strukturnya benar, baru kita buat script migrasi data dari dump lama ke sini.
