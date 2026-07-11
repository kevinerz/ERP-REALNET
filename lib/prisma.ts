import { PrismaMariaDb } from "@prisma/adapter-mariadb";
import { PrismaClient } from "../generated/prisma/client";

// Satu koneksi Prisma untuk seluruh aplikasi. Menggunakan driver adapter
// (@prisma/adapter-mariadb) -- tidak butuh binary engine terpisah, cukup
// npm install biasa. Cocok untuk hosting seperti Hostinger.
//
// DATABASE_URL contoh: mysql://user:password@localhost:3306/u272457353_erprealnet

function parseDatabaseUrl(url: string) {
  const parsed = new URL(url);
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
  });
  return new PrismaClient({ adapter });
}

export const prisma = globalForPrisma.prisma ?? createPrismaClient();

if (process.env.NODE_ENV !== "production") {
  globalForPrisma.prisma = prisma;
}
