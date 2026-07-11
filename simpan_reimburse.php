<?php
$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$nama_pengaju = $_POST['nama_pengaju'];
$tanggal      = $_POST['tanggal'];
$tujuan       = $_POST['tujuan'];
$liter        = $_POST['liter'];
$total        = $_POST['total'];
$catatan      = $_POST['catatan'];

$sql = "INSERT INTO reimburse_bbm 
(nama_pengaju, tanggal, tujuan, liter, total, catatan) 
VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssdds", $nama_pengaju, $tanggal, $tujuan, $liter, $total, $catatan);

if ($stmt->execute()) {
    echo "Reimburse berhasil dikirim. <a href='form_reimburse.php'>Kembali</a>";
} else {
    echo "Gagal menyimpan: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
