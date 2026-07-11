<?php
// koneksi.php
// File ini mengatur semua koneksi database RealNet (3 database terpisah)

date_default_timezone_set('Asia/Jakarta'); 

/* ======================================================
   1. DATABASE TIKET HELPDESK
   ------------------------------------------------------
   Tabel berada di:
   - Database: u272457353_tiket_helpdesk
   - Tabel:    tiket
   ====================================================== */
$host_utama     = 'localhost';
$username_utama = 'u272457353_kevinsamsung';
$password_utama = 'Admionkevin99';
$database_utama = 'u272457353_tiket_helpdesk';

$conn_utama = new mysqli($host_utama, $username_utama, $password_utama, $database_utama);
$conn_utama->set_charset("utf8mb4");

if ($conn_utama->connect_error) {
    error_log("Koneksi ke database tiket_helpdesk gagal: " . $conn_utama->connect_error);
    die("Koneksi database tiket gagal.");
}


/* ======================================================
   2. DATABASE POP / PEMASANGAN
   ------------------------------------------------------
   Untuk mengambil daftar POP dari tabel `pop`
   - Database: u272457353_db_pemasangan
   - Tabel:    pop
   - Kolom:    name
   ====================================================== */
$host_pop     = 'localhost';
$username_pop = 'u272457353_kevinsamsung9';
$password_pop = 'Admionkevin99';
$database_pop = 'u272457353_db_pemasangan';

$table_pop = 'pop';
$kolom_pop = 'name';

$conn_pop = new mysqli($host_pop, $username_pop, $password_pop, $database_pop);
$conn_pop->set_charset("utf8mb4");

if ($conn_pop->connect_error) {
    error_log("Koneksi ke database POP gagal: " . $conn_pop->connect_error);
    // Tidak perlu die() agar tiket tetap bisa berjalan
}


/* ======================================================
   3. DATABASE UMUMDATA (karyawan)
   ------------------------------------------------------
   Tabel karyawan berisi fcm_token teknisi
   - Database: u272457353_umumdata
   - Tabel:    karyawan
   ====================================================== */
$host_umum     = 'localhost';
$username_umum = 'u272457353_kevinsamsung99';
$password_umum = 'Admionkevin99';
$database_umum = 'u272457353_umumdata';

$conn_umum = new mysqli($host_umum, $username_umum, $password_umum, $database_umum);
$conn_umum->set_charset("utf8mb4");

if ($conn_umum->connect_error) {
    error_log("Koneksi ke database umumdata gagal: " . $conn_umum->connect_error);
    die("Koneksi database karyawan gagal.");
}

?>
