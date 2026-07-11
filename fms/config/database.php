<?php
// Mengatur zona waktu default untuk semua operasi tanggal dan waktu
date_default_timezone_set('Asia/Jakarta');

// ===================================================
// KONEKSI 1: UNTUK FMS (Sistem Manajemen Keuangan Utama)
// ===================================================
$db_host_fms = 'localhost';
$db_user_fms = 'u272457353_kevinsamsungfm';
$db_pass_fms = 'Admionkevin99';
$db_name_fms = 'u272457353_fms';

$conn = new mysqli($db_host_fms, $db_user_fms, $db_pass_fms, $db_name_fms);

if ($conn->connect_error) {
    error_log("Koneksi ke database FMS gagal: " . $conn->connect_error);
    http_response_code(500);
    echo "Sistem sedang mengalami gangguan. Mohon coba beberapa saat lagi.";
    exit;
}

// ===================================================
// KONEKSI 2: UNTUK REIMBURSE BBM (Dari Database Umumdata)
// ===================================================
$db_host_bbm = "localhost";
$db_user_bbm = "u272457353_kevinsamsung99";
$db_pass_bbm = "Admionkevin99";
$db_name_bbm = "u272457353_umumdata";

$conn_bbm = new mysqli($db_host_bbm, $db_user_bbm, $db_pass_bbm, $db_name_bbm);

if ($conn_bbm->connect_error) {
    error_log("Koneksi ke database Reimburse BBM gagal: " . $conn_bbm->connect_error);
    http_response_code(500);
    echo "Sistem sedang mengalami gangguan. Mohon coba beberapa saat lagi.";
    exit;
}

// ===================================================
// KONEKSI 3: UNTUK FEE PASANG (Dari Database db_pemasangan)
// ===================================================
$db_host_pasang = 'localhost';
$db_user_pasang = 'u272457353_kevinsamsung9';
$db_pass_pasang = 'Admionkevin99';
$db_name_pasang = 'u272457353_db_pemasangan';

$conn_pasang = new mysqli($db_host_pasang, $db_user_pasang, $db_pass_pasang, $db_name_pasang);

if ($conn_pasang->connect_error) {
    error_log("Koneksi ke database Pemasangan gagal: " . $conn_pasang->connect_error);
    http_response_code(500);
    echo "Sistem sedang mengalami gangguan. Mohon coba beberapa saat lagi.";
    exit;
}

// ===================================================
// KONEKSI 4: UNTUK MARKET (Database u272457353_market)
// ===================================================
$servername = "localhost";
$username   = "u272457353_kevinsamsungku";
$password   = "Admionkevin99";
$dbname     = "u272457353_market";

// Variabel koneksi baru: $conn_market
$conn_market = new mysqli($servername, $username, $password, $dbname);

if ($conn_market->connect_error) {
    error_log("Koneksi ke database MARKET gagal: " . $conn_market->connect_error);
    http_response_code(500);
    echo "Sistem sedang mengalami gangguan. Mohon coba beberapa saat lagi.";
    exit;
}
