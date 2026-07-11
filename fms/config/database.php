<?php
// Mengatur zona waktu default untuk semua operasi tanggal dan waktu
date_default_timezone_set('Asia/Jakarta');

// Dimigrasikan: KONEKSI 1 (fms), 2 (umumdata/bbm), dan 3 (db_pemasangan)
// dulu 3 database terpisah, sekarang sudah digabung jadi satu database
// `erprealnet`. KONEKSI 4 (market) DIBIARKAN terpisah -- belum ada
// skema/dump untuk database `u272457353_market`.

require_once __DIR__ . '/../../config/database.php';

// ===================================================
// KONEKSI 1: UNTUK FMS (Sistem Manajemen Keuangan Utama)
// ===================================================
$conn = getErpDbConnection();

// ===================================================
// KONEKSI 2: UNTUK REIMBURSE BBM (dulu Database Umumdata)
// ===================================================
$conn_bbm = getErpDbConnection();

// ===================================================
// KONEKSI 3: UNTUK FEE PASANG (dulu Database db_pemasangan)
// ===================================================
$conn_pasang = getErpDbConnection();

// ===================================================
// KONEKSI 4: UNTUK MARKET (Database u272457353_market)
// Belum dikonsolidasi -- dibiarkan seperti semula.
// ===================================================
$servername = "localhost";
$username   = "u272457353_kevinsamsungku";
$password   = "Admionkevin99";
$dbname     = "u272457353_market";

$conn_market = new mysqli($servername, $username, $password, $dbname);

if ($conn_market->connect_error) {
    error_log("Koneksi ke database MARKET gagal: " . $conn_market->connect_error);
    http_response_code(500);
    echo "Sistem sedang mengalami gangguan. Mohon coba beberapa saat lagi.";
    exit;
}
