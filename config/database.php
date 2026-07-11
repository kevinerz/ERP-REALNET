<?php
/**
 * config/database.php
 * SATU titik koneksi database untuk seluruh aplikasi -- menggantikan puluhan
 * file koneksi terpisah (config.php, db.php, db_config.php, api/koneksi.php,
 * cabut/config.php, kasbon/koneksi.php, dst) yang tadinya masing-masing
 * menyimpan kredensial sendiri ke database yang berbeda-beda.
 *
 * Semua kredensial dibaca dari .env (lihat .env.example), TIDAK ada lagi
 * password yang ditulis langsung di kode.
 *
 * CATATAN DESAIN: setiap panggilan getErpDbConnection()/getErpDbPdo()
 * mengembalikan KONEKSI BARU (bukan singleton/cache). Ini SENGAJA --
 * banyak file lama memanggil beberapa variabel koneksi berbeda
 * (mis. $conn_utama, $conn_pop, $conn_umum) lalu men-close() salah satunya
 * di tengah/akhir skrip. Kalau semua variabel itu berbagi 1 objek koneksi
 * yang sama (singleton), close() salah satu akan ikut mematikan yang lain.
 * Dengan tiap panggilan bikin koneksi baru, perilaku lama (independen per
 * variabel) tetap terjaga meskipun sekarang semuanya menunjuk ke database
 * yang sama (erprealnet).
 */

declare(strict_types=1);

require_once __DIR__ . '/env.php';

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_NAME', env('DB_NAME', 'u272457353_erprealnet'));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));

/**
 * Koneksi mysqli baru ke database erprealnet.
 */
function getErpDbConnection(): mysqli
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        error_log('Koneksi database erprealnet gagal: ' . $conn->connect_error);
        die('Koneksi database gagal. Mohon hubungi Administrator.');
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

/**
 * Koneksi PDO baru ke database erprealnet, untuk kode lama yang pakai PDO.
 */
function getErpDbPdo(): PDO
{
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        error_log('Koneksi database (PDO) erprealnet gagal: ' . $e->getMessage());
        die('Koneksi database gagal. Mohon hubungi Administrator.');
    }
    return $pdo;
}
