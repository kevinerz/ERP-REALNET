<?php
/**
 * db_config.php
 * File Konfigurasi Database dan API
 * Versi Final & Robust
 */

// ===========================================
// KONFIGURASI DATABASE
// ===========================================

// --- Konfigurasi DB Pemasangan ---
define('DB_HOST_PEMASANGAN', 'localhost');
define('DB_USER_PEMASANGAN', 'u272457353_kevinsamsung9');
define('DB_PASS_PEMASANGAN', 'Admionkevin99');
define('DB_NAME_PEMASANGAN', 'u272457353_db_pemasangan');

// --- Konfigurasi DB Umum ---
define('DB_HOST_UMUM', 'localhost');
define('DB_USER_UMUM', 'u272457353_kevinsamsung99');
define('DB_PASS_UMUM', 'Admionkevin99');
define('DB_NAME_UMUM', 'u272457353_umumdata');

// ===========================================
// KONFIGURASI API (WhatsApp Notifikasi)
// ===========================================
define('STARSENDER_API_URL', 'https://api.starsender.online/api/send');
define('STARSENDER_API_TOKEN', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124');

// ===========================================
// FUNGSI KONEKSI DATABASE
// ===========================================

/**
 * Mendapatkan koneksi ke database berdasarkan tipe.
 * Menggunakan die() dengan pesan user-friendly saat koneksi gagal.
 *
 * @param string $dbType 'pemasangan' atau 'umum'
 * @return mysqli|null Objek koneksi mysqli
 */
function getDbConnection($dbType) {
    
    $host = '';
    $user = '';
    $pass = '';
    $name = '';
    $dbNameDisplay = '';

    if ($dbType === 'pemasangan') {
        $host = DB_HOST_PEMASANGAN;
        $user = DB_USER_PEMASANGAN;
        $pass = DB_PASS_PEMASANGAN;
        $name = DB_NAME_PEMASANGAN;
        $dbNameDisplay = 'Pemasangan';
    } elseif ($dbType === 'umum') {
        $host = DB_HOST_UMUM;
        $user = DB_USER_UMUM;
        $pass = DB_PASS_UMUM;
        $name = DB_NAME_UMUM;
        $dbNameDisplay = 'Umum';
    } else {
        // Log error developer jika tipe DB tidak valid
        error_log("Invalid database type specified: $dbType");
        return null;
    }

    $conn = new mysqli($host, $user, $pass, $name);

    if ($conn->connect_error) {
        // Logging error di server log (lebih aman di production)
        error_log("Database connection failed for $dbNameDisplay: " . $conn->connect_error);
        
        // Pesan yang ramah pengguna
        die("Koneksi database **$dbNameDisplay** gagal. Mohon hubungi Administrator."); 
    }
    
    // Set character set agar mendukung semua karakter, termasuk emoji
    $conn->set_charset("utf8mb4"); 
    
    return $conn;
}
?>