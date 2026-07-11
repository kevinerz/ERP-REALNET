# Deploy Next.js ERP REALNET ke Hostinger (subdomain ultima.datarealsolution.net)

Repo: https://github.com/kevinerz/ERP-REALNET (branch `main`)

Repo ini sekarang berisi dua aplikasi berdampingan: PHP lama (tetap jalan seperti biasa di public_html) dan aplikasi Next.js baru di root repo (`app/`, `lib/`, `prisma/`, dll). Keduanya tidak saling ganggu -- Next.js akan jalan sebagai proses Node terpisah di subdomain sendiri.

## 1. Yang sudah dibangun

- **Auth**: login pakai akun `hr_karyawan` yang sudah ada (username/password lama), sesi cookie ber-tanda tangan HMAC, 8 jam sama seperti sesi PHP lama.
- **Layout**: navbar + guard otomatis (redirect ke `/login` kalau belum login).
- **Dashboard** (`/dashboard`): ringkasan total pelanggan, pelanggan baru bulan ini, tiket belum selesai, tiket selesai bulan ini, karyawan aktif, mitra terdaftar.
- **Modul contoh penuh** (`/pelanggan`): daftar + cari, tambah, detail, edit, hapus -- dipakai sebagai pola untuk modul lain (HR, Keuangan, Jaringan, Tiket, Mitra) yang belum dibangun dan bisa ditambah belakangan dengan pola yang sama.

## 2. Buat Node.js App di Hostinger

Di hPanel: **Websites -> pilih domain -> Node.js** (atau menu "Website" -> "Create Node.js App" tergantung versi hPanel).

1. Application root: pilih folder terpisah, contoh `ultima_nextjs` (bukan `public_html` lama).
2. Application URL: `ultima.datarealsolution.net`.
3. Node.js version: 20.x atau lebih baru (Next.js 15.5 butuh Node >= 18.18, disarankan 20 LTS).
4. Sumber kode: hubungkan ke repo GitHub `kevinerz/ERP-REALNET`, branch `main`. Kalau opsi GitHub deploy belum tersedia di paketmu, alternatifnya: `git clone` manual lewat SSH/Terminal Hostinger ke application root, atau upload ZIP hasil `git archive`.
5. Install command: `npm install`
6. Build command: `npm run build` (sudah berisi `prisma generate && next build` -- lihat catatan Prisma di bagian 5).
7. Start command: `npm run start`

## 3. Environment Variables (isi di hPanel, bukan di file)

| Variabel | Isi |
|---|---|
| `DATABASE_URL` | `mysql://USER:PASSWORD@localhost:3306/u272457353_erprealnet` -- pakai kredensial database `erprealnet` yang sudah kamu buat, BUKAN password lama yang sama di semua database (`Admionkevin99`) |
| `AUTH_SECRET` | String acak panjang & unik, khusus aplikasi ini. Generate lewat Terminal Hostinger: `openssl rand -base64 32` |
| `NODE_ENV` | `production` |

Jangan pernah taruh kredensial ini di file `.env` yang ikut ke git -- `.gitignore` sudah memblokir `.env`, `.env.local`, dll.

## 4. Arahkan subdomain

Kalau `ultima.datarealsolution.net` belum ada sebagai subdomain di Hostinger: **Domains -> Subdomains -> Create**, lalu di pengaturan Node.js App di atas set "Application URL" ke subdomain ini. Hostinger otomatis mengarahkan proxy ke proses Node yang jalan di port internal.

## 5. Catatan penting soal Prisma

Saat development di sandbox ini, `npx prisma generate` **tidak bisa dijalankan** karena sandbox memblokir akses ke `binaries.prisma.sh` (dipakai Prisma CLI untuk unduh engine). Semua kode sudah diverifikasi dengan `tsc --noEmit` (memakai stub sementara yang tidak ikut di-commit), tapi belum pernah benar-benar dijalankan end-to-end lewat `next build` yang sesungguhnya.

Server Hostinger punya akses internet normal, jadi `npm run build` (yang menjalankan `prisma generate` lebih dulu) seharusnya berhasil di sana. Kalau build gagal di step `prisma generate`, langkah cek:

1. Pastikan `DATABASE_URL` sudah diisi sebelum build (Prisma butuh ini walau cuma untuk validasi skema).
2. Cek log build di hPanel untuk pesan error spesifik.
3. Kalau errornya soal versi Node terlalu lama, naikkan ke Node 20 LTS di pengaturan Node.js App.

## 6. Login pertama

Login pakai username/password yang sudah ada di tabel `hr_karyawan` (sama seperti login PHP lama -- password masih dicek sebagai plaintext, warisan dari `login.php` lama). Divisi yang diizinkan masuk: Admin, IT, Manager, SPV Teknis, Finance, Leader Area, Teknisi (sama seperti aturan lama). Kalau `status_aktif` karyawan tersebut `false`, login akan ditolak.

**Rekomendasi keamanan lanjutan** (belum dikerjakan, opsional): migrasi password ke hash `scrypt` -- fungsi `hashPasswordScrypt()` sudah disiapkan di `lib/password.ts`, tinggal dibuat skrip migrasi satu kali untuk hash ulang semua password yang ada.

## 7. Development lokal (opsional, kalau mau coba dulu di komputer sendiri)

```bash
npm install
cp .env.local.example .env.local   # lalu isi DATABASE_URL & AUTH_SECRET
npx prisma generate
npm run dev
```

## 8. Belum dikerjakan / langkah lanjutan

- Modul lain (HR, Keuangan, Jaringan, Tiket, Mitra, Aset) belum dibuatkan versi Next.js-nya -- ikuti pola folder `app/(app)/pelanggan/` sebagai contoh (list + actions.ts + form component).
- Rotasi password database yang sama di semua akun (`Admionkevin99`) -- lihat `LAPORAN_MIGRASI.md` poin keamanan.
- Migrasi hash password karyawan dari plaintext ke scrypt.
- Belum ada test end-to-end terhadap MySQL asli (sandbox pengembangan tidak punya server MySQL).
