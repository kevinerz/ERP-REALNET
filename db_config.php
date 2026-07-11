<?php
/**
 * db_config.php
 * Dimigrasikan: dulu 'pemasangan' dan 'umum' adalah 2 database berbeda
 * dengan kredensial masing-masing. Sekarang keduanya sudah digabung jadi
 * satu database `erprealnet`, jadi getDbConnection() mengembalikan koneksi
 * yang sama untuk kedua tipe. Nama & signature fungsi dipertahankan supaya
 * pemanggil lama (prosesaktivasi.php, selesai_aktivasi.php,
 * aktivasi_pelanggan1.php) tidak perlu diubah.
 */

require_once __DIR__ . '/config/database.php';

// ===========================================
// KONFIGURASI API (WhatsApp Notifikasi)
// ===========================================
define('STARSENDER_API_URL', 'https://api.starsender.online/api/send');
define('STARSENDER_API_TOKEN', env('WA_API_TOKEN_CUSTOMER', ''));

/**
 * Mendapatkan koneksi ke database berdasarkan tipe.
 * 'pemasangan' dan 'umum' sekarang menunjuk ke database yang sama
 * (erprealnet), dipertahankan sebagai parameter untuk kompatibilitas kode lama.
 *
 * @param string $dbType 'pemasangan' atau 'umum'
 * @return mysqli|null
 */
function getDbConnection($dbType) {
    if ($dbType === 'pemasangan' || $dbType === 'umum') {
        return getErpDbConnection();
    }
    error_log("Invalid database type specified: $dbType");
    return null;
}
