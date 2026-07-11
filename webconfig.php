<?php
// webconfig.php
// Dimigrasikan: connect_umum() dulu konek ke u272457353_umumdata, sekarang
// ke erprealnet. connect_backbone() DIBIARKAN seperti semula karena
// database `u272457353_backbone` belum ada skema/dump-nya (belum
// dikonsolidasi -- lihat MAPPING_DATABASE.md).

require_once __DIR__ . '/config/database.php';

// ==========================================================
// KONFIGURASI DATABASE 2: BACKBONE (belum dikonsolidasi)
// ==========================================================
define('DB_BACK_HOST', 'localhost');
define('DB_BACK_USER', 'u272457353_kevinsamsung17');
define('DB_BACK_PASS', 'Admionkevin99');
define('DB_BACK_NAME', 'u272457353_backbone');

/**
 * Fungsi Generik untuk membuat koneksi ke database mana saja (dipertahankan
 * untuk connect_backbone(), yang belum ikut dikonsolidasi).
 */
function create_db_connection($host, $user, $pass, $name) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli($host, $user, $pass, $name);
        $conn->set_charset("utf8mb4");
        return $conn;
    } catch (mysqli_sql_exception $e) {
        error_log("DB Connection Error ($name): " . $e->getMessage());
        return null;
    }
}

/**
 * Wrapper: Koneksi khusus ke database UMUM DATA (sekarang erprealnet)
 * @return mysqli|null
 */
function connect_umum() {
    return getErpDbConnection();
}

/**
 * Wrapper: Koneksi khusus ke database BACKBONE (belum dikonsolidasi)
 * @return mysqli|null
 */
function connect_backbone() {
    return create_db_connection(DB_BACK_HOST, DB_BACK_USER, DB_BACK_PASS, DB_BACK_NAME);
}

/**
 * Fungsi untuk mengambil data paket internet (tabel jaringan_paket)
 * @param mysqli|null $conn
 * @return array
 */
function get_internet_packages($conn) {
    $packages = [];
    if ($conn) {
        $sql = "SELECT nama_paket, kecepatan, harga, deskripsi FROM jaringan_paket ORDER BY harga ASC";

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

// 1. Koneksi ke Database UMUM (Untuk data paket) -- sekarang erprealnet
$conn_umum = connect_umum();
$packages = get_internet_packages($conn_umum);

// 2. Koneksi ke Database BACKBONE (belum dikonsolidasi)
$conn_backbone = connect_backbone();

if ($conn_backbone) {
    // Lakukan sesuatu dengan database backbone di sini
}

// Tutup koneksi setelah selesai
if ($conn_backbone) {
    $conn_backbone->close();
}
