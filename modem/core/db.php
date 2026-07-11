<?php
$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

$conn = new mysqli($servername, $username, $password, $database);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
