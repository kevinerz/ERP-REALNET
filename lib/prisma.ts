import { PrismaMariaDb } from "@prisma/adapter-mariadb";
import { PrismaClient } from "../generated/prisma/client";

// Satu koneksi Prisma untuk seluruh aplikasi. Menggunakan driver adapter
// (@prisma/adapter-mariadb) -- tidak butuh binary engine terpisah, cukup
// npm install biasa. Cocok untuk hosting seperti Hostinger.
//
// DATABASE_URL contoh: mysql://user:password@127.0.0.1:3306/u272457353_erprealnet
//
// CATATAN PENTING: pakai "127.0.0.1", JANGAN "localhost". Di banyak hosting
// (termasuk Hostinger Node.js App), "localhost" bisa ter-resolve ke IPv6 (::1)
// dulu oleh Node -- kalau MySQL/MariaDB di server itu cuma dengar di IPv4,
// koneksi akan menggantung lama (bukan langsung gagal) sebelum akhirnya
// timeout. Gejalanya: tombol login macet di "Memproses..." tanpa pesan error.
// "127.0.0.1" memaksa IPv4 langsung, menghindari masalah ini sepenuhnya.

function sanitizeDatabaseUrl(raw: string): string {
  let value = raw.trim();

  // Kalau nilainya ke-copy-paste termasuk "DATABASE_URL=" di depan (kesalahan
  // umum saat isi Environment Variables di hPanel: value field diisi baris
  // penuh dari .env, bukan cuma nilainya), buang prefix itu.
  value = value.replace(/^DATABASE_URL\s*=\s*/i, "");

  // Buang tanda kutip pembungkus (" atau ') kalau ikut ke-paste.
  if (
    (value.startsWith('"') && value.endsWith('"')) ||
    (value.startsWith("'") && value.endsWith("'"))
  ) {
    value = value.slice(1, -1);
  }

  return value.trim();
}

function parseDatabaseUrl(url: string) {
  const parsed = new URL(sanitizeDatabaseUrl(url));
  return {
    host: parsed.hostname,
    port: parsed.port ? parseInt(parsed.port, 10) : 3306,
    user: decodeURIComponent(parsed.username),
    password: decodeURIComponent(parsed.password),
    database: parsed.pathname.replace(/^\//, ""),
  };
}

const globalForPrisma = globalThis as unknown as { prisma?: PrismaClient };

function createPrismaClient() {
  const databaseUrl = process.env.DATABASE_URL;
  if (!databaseUrl) {
    throw new Error(
      "DATABASE_URL belum diset. Isi di .env.local (dev) atau Environment Variables Hostinger (produksi)."
    );
  }
  const adapter = new PrismaMariaDb({
    ...parseDatabaseUrl(databaseUrl),
    connectionLimit: 5,
    // Batas waktu koneksi 10 detik -- kalau database tidak bisa dijangkau,
    // request akan gagal cepat dengan error jelas, bukan menggantung tanpa
    // batas waktu (yang sebelumnya bikin tombol login macet di "Memproses...").
    connectTimeout: 10000,
  });
  return new PrismaClient({ adapter });
}

export const prisma = globalForPrisma.prisma ?? createPrismaClient();

if (process.env.NODE_ENV !== "production") {
  globalForPrisma.prisma = prisma;
}
