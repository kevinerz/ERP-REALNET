<?php

// Koneksi ke database utama (tempat tabel tiket berada)
$host_utama = 'localhost';
$username_utama = 'u272457353_kevinsamsung';
$password_utama = 'Admionkevin99';
$database_utama = 'u272457353_tiket_helpdesk';

$conn_utama = new mysqli($host_utama, $username_utama, $password_utama, $database_utama);

if ($conn_utama->connect_error) {
    die("Koneksi ke database utama gagal: " . $conn_utama->connect_error);
}

// Koneksi ke database POP (tempat tabel POP berada)
$host_pop = 'localhost'; // Bisa berbeda jika database POP di server lain
$username_pop = 'u272457353_kevinsamsung9';
$password_pop = 'Admionkevin99';
$database_pop = 'u272457353_db_pemasangan';
$table_pop = 'pop'; // Ganti dengan nama tabel POP Anda
$kolom_pop = 'name'; // Ganti dengan nama kolom yang berisi nama POP

$conn_pop = new mysqli($host_pop, $username_pop, $password_pop, $database_pop);

if ($conn_pop->connect_error) {
    die("Koneksi ke database POP gagal: " . $conn_pop->connect_error);
}

?>