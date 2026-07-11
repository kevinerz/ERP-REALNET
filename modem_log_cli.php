<?php
require_once __DIR__ . '/config/database.php';
$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

$conn = getErpDbConnection();
$conn->set_charset("utf8mb4");

$q = $conn->query("
    SELECT waktu, serial_number, aksi, nama_karyawan 
    FROM jaringan_modem_log 
    ORDER BY id_log DESC 
    LIMIT 30
");

while($r = $q->fetch_assoc()){
    echo "<div class='log-db'>[{$r['waktu']}] {$r['aksi']} | {$r['serial_number']} | {$r['nama_karyawan']}</div>";
}
?>
