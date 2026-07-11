<?php
require_once __DIR__ . '/config/database.php';
// Konfigurasi database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u272457353_kevinsamsung');
define('DB_PASSWORD', 'Admionkevin99');
define('DB_NAME', 'u272457353_tiket_helpdesk');

// Koneksi ke database
$conn = getErpDbConnection();

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>