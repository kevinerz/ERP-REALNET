<?php
// koneksi.php
// Dimigrasikan: 3 database (tiket_helpdesk, db_pemasangan, umumdata) yang
// dulu terpisah sekarang sudah digabung jadi satu database `erprealnet`.
// Variabel $conn_utama / $conn_pop / $conn_umum dipertahankan (dipakai
// file lain) tapi sekarang semuanya menunjuk ke koneksi yang sama.

require_once __DIR__ . '/../config/database.php';

date_default_timezone_set('Asia/Jakarta');

/* Database TIKET (tabel: tiket_gangguan) */
$conn_utama = getErpDbConnection();

/* Database POP (tabel: jaringan_pop, kolom: name) */
$table_pop = 'jaringan_pop';
$kolom_pop = 'name';
$conn_pop  = getErpDbConnection();

/* Database UMUM (tabel: hr_karyawan, untuk fcm_token teknisi) */
$conn_umum = getErpDbConnection();
