# Laporan Migrasi ERP REALNET -> Database `erprealnet`

Tanggal: 11 Juli 2026
Commit: `d846100` (baseline) -> `6d8a292` (migrasi) di https://github.com/kevinerz/ERP-REALNET

## Yang sudah dikerjakan

1. **Baseline dulu, baru migrasi.** Commit pertama adalah kondisi ASLI (sebelum diubah sama sekali) supaya ada titik rollback aman kalau ada yang salah. `git revert` atau `git checkout d846100 -- <file>` bisa dipakai kapan saja untuk kembali ke versi lama per file.
2. **.gitignore & .htaccess ditambahkan** -- menutup celah kritis dari audit awal: dump `.sql` dan log `.txt` tidak lagi bisa diunduh publik, dan tidak ikut ke GitHub.
3. **Koneksi database dikonsolidasi** ke satu file `config/database.php` yang baca kredensial dari `.env` (bukan hardcode). Semua file yang tadinya konek sendiri-sendiri (93 titik koneksi di 77 file, + 16 file config/koneksi bersama) sekarang lewat 2 fungsi: `getErpDbConnection()` (mysqli) dan `getErpDbPdo()` (PDO).
4. **406 baris query di 131+ file** direname dari nama tabel lama ke nama tabel baru (prefix per domain: `pelanggan_`, `jaringan_`, `hr_`, `keu_`, `aset_`, `tiket_`, `mitra_`).
5. **Kasus khusus juga ditangani**: `dashboard.php`/`dashboard2.php` yang bangun nama tabel dari variabel, dan `tiket/koneksi.php` yang expose `$table_pop` ke banyak file lain.
6. **Semua 150 file yang diubah lolos validasi syntax** (pakai php-parser, karena sandbox ini tidak punya PHP CLI). Tidak ada 1 pun error syntax.
7. Hasil migrasi sudah disinkronkan balik ke folder project asli.

## Yang SENGAJA tidak disentuh

Database berikut belum ada skema/dump-nya, jadi dibiarkan seperti semula (koneksinya masih pakai kredensial lama langsung di kode):

- **`dapel`** -- ternyata ini sumber data pelanggan utama (dipakai `tiket/index.php` untuk ambil nama/telepon/alamat). Jangan disentuh sampai ada keputusan lebih lanjut.
- **`market`** -- database TERPISAH dari `mitra` (folder `market/` dan `mitra/` adalah 2 sistem mitra yang berbeda!). Dipakai di `market/*.php` dan sebagian `market1daftar.php`, `daftarku.php`, `fms/config/database.php`.
- **`backbone`**, **`cabut`**, **`crm`** -- direferensikan di kode (`webconfig.php`, `cabut/config.php`, `update_pemasangan.php`) tapi tidak ada dump-nya sama sekali.

## PENTING -- yang perlu kamu lakukan sekarang

1. **Isi file `.env` di server** (salin dari `.env.example`) dengan kredensial database `erprealnet` yang asli + API key WhatsApp/Tripay/Google Maps. Tanpa ini, situs akan mati total (koneksi database akan gagal).
2. **Semua akun database (db_pemasangan, umumdata, mitra, fms, tiket_helpdesk, cabut, market, backbone) memakai password yang SAMA PERSIS** (`Admionkevin99`) -- ini temuan lama yang belum diperbaiki, sangat disarankan untuk mengganti password akun `erprealnet` jadi unik & kuat saat mengisi `.env`.
3. **Belum diuji ke MySQL asli** -- sandbox ini tidak punya server MySQL, jadi semua perubahan baru divalidasi dari sisi syntax (dijamin tidak ada typo/error PHP), BUKAN dari sisi "query-nya jalan dan hasilnya benar". Sangat disarankan untuk uji coba di staging/database duplikat dulu sebelum dipakai penuh di server produksi, terutama untuk alur inti (aktivasi pelanggan, tiket gangguan, kasbon).
4. Beberapa file (mis. `daftar1.php`, `login.php`, dll) masih menyimpan sisa variabel kredensial lama yang **sudah tidak dipakai** (dead code, bukan bug) -- bisa dibersihkan belakangan, tidak mendesak.

## File pendukung di repo

- `Audit_dan_Rencana_Migrasi_ERP_REALNET.docx` -- audit awal
- `MAPPING_DATABASE.md` -- peta tabel lama -> baru
- `consolidated_schema.sql` -- DDL skema `erprealnet`
- `Checklist_Migrasi_Query_PHP.xlsx` -- checklist detail (sebelum migrasi dieksekusi)
- `.env.example` -- template kredensial
