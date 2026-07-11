<?php
// webconfig.php

// ==========================================================
// KONFIGURASI DATABASE 1: UMUM DATA (Untuk Paket Internet)
// ==========================================================
define('DB_UMUM_HOST', 'localhost');
define('DB_UMUM_USER', 'u272457353_kevinsamsung99');
define('DB_UMUM_PASS', 'Admionkevin99');
define('DB_UMUM_NAME', 'u272457353_umumdata');

// ==========================================================
// KONFIGURASI DATABASE 2: BACKBONE (Database Baru)
// ==========================================================
define('DB_BACK_HOST', 'localhost');
define('DB_BACK_USER', 'u272457353_kevinsamsung17');
define('DB_BACK_PASS', 'Admionkevin99');
define('DB_BACK_NAME', 'u272457353_backbone');


// Nonaktifkan pelaporan error di produksi (mode aman)
// error_reporting(0);
// ini_set('display_errors', 0);

/**
 * Fungsi Generik untuk membuat koneksi ke database mana saja.
 */
function create_db_connection($host, $user, $pass, $name) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli($host, $user, $pass, $name);
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (mysqli_sql_exception $e) {
        // Catat error ke log server
        error_log("DB Connection Error ($name): " . $e->getMessage());
        return null;
    }
}

/**
 * Wrapper: Koneksi khusus ke database UMUM DATA
 * @return mysqli|null
 */
function connect_umum() {
    return create_db_connection(DB_UMUM_HOST, DB_UMUM_USER, DB_UMUM_PASS, DB_UMUM_NAME);
}

/**
 * Wrapper: Koneksi khusus ke database BACKBONE
 * @return mysqli|null
 */
function connect_backbone() {
    return create_db_connection(DB_BACK_HOST, DB_BACK_USER, DB_BACK_PASS, DB_BACK_NAME);
}

/**
 * Fungsi untuk mengambil data paket internet (Biasanya dari DB Umum)
 * @param mysqli|null $conn
 * @return array
 */
function get_internet_packages($conn) {
    $packages = [];
    if ($conn) {
        $sql = "SELECT nama_paket, kecepatan, harga, deskripsi FROM paket ORDER BY harga ASC";
        
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $packages[] = $row;
            }
            $stmt->close();
        }
    }
    return $packages;
}

/**
 * Fungsi format rupiah
 */
function format_rupiah($number) {
    return 'Rp' . number_format($number, 0, '.', ',');
}

// ----------------- LOGIKA UTAMA (CONTOH PENGGUNAAN) -----------------

// 1. Koneksi ke Database UMUM (Untuk data paket)
$conn_umum = connect_umum();
$packages = get_internet_packages($conn_umum);

// 2. Koneksi ke Database BACKBONE (Siap digunakan untuk query lain)
$conn_backbone = connect_backbone();

// Contoh pengecekan koneksi backbone (Opsional)
if ($conn_backbone) {
    // Lakukan sesuatu dengan database backbone di sini
    // $result = $conn_backbone->query("SELECT * FROM tabel_di_backbone...");
}

// Tutup koneksi setelah selesai
if ($conn_umum) {
    $conn_umum->close();
}
if ($conn_backbone) {
    $conn_backbone->close();
}
?>