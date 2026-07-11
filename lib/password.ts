import crypto from "crypto";

// PERINGATAN KEAMANAN (diwariskan dari login.php lama):
// Password karyawan di database saat ini disimpan PLAIN TEXT, bukan hash.
// Fungsi di bawah ini SENGAJA mendukung dua mode supaya:
//  1. Login tetap jalan untuk akun lama (plain text apa adanya).
//  2. Kalau suatu saat migrasi ke bcrypt/scrypt hash, tidak perlu ubah
//     kode pemanggilnya -- cukup simpan password baru dalam format hash.
//
// TODO PRIORITAS: ganti proses "ganti password" supaya menyimpan hash
// (mis. Node crypto.scrypt) alih-alih plain text, lalu hapus cabang
// plain-text di bawah ini setelah semua akun lama di-reset.

const SCRYPT_PREFIX = "scrypt$";

export function verifyPassword(inputPassword: string, storedPassword: string): boolean {
  if (storedPassword.startsWith(SCRYPT_PREFIX)) {
    const [, saltHex, hashHex] = storedPassword.split("$");
    const salt = Buffer.from(saltHex, "hex");
    const hash = crypto.scryptSync(inputPassword, salt, 64);
    return crypto.timingSafeEqual(hash, Buffer.from(hashHex, "hex"));
  }
  // Fallback: akun lama, password masih plain text di database.
  return inputPassword === storedPassword;
}

export function hashPasswordScrypt(plainPassword: string): string {
  const salt = crypto.randomBytes(16);
  const hash = crypto.scryptSync(plainPassword, salt, 64);
  return `${SCRYPT_PREFIX}${salt.toString("hex")}$${hash.toString("hex")}`;
}
