<?php
$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $database);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}
?>
