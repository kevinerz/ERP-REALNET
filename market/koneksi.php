<?php
$servername = "localhost";
$username = "u272457353_kevinsamsungku";
$password = "Admionkevin99";
$dbname = "u272457353_market";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
