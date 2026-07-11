<?php
// Konfigurasi database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u272457353_kevinsamsung');
define('DB_PASSWORD', 'Admionkevin99');
define('DB_NAME', 'u272457353_tiket_helpdesk');

// Koneksi ke database
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>