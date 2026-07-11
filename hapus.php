<?php
// Konfigurasi database
$servername = "localhost";
$username = "u272457353_kevinsamsung9";
$password = "Admionkevin99";
$database = "u272457353_db_pemasangan";

// Buat koneksi ke database
$conn = new mysqli($servername, $username, $password, $database);

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Periksa apakah parameter ID dikirim melalui GET
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // Pastikan ID adalah angka

    // Query untuk menghapus data berdasarkan ID
    $sql = "DELETE FROM pemasangan WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "<script>alert('Data berhasil dihapus!'); window.location.href='pemasangan.php';</script>";
    } else {
        echo "<script>alert('Gagal menghapus data!'); window.location.href='pemasangan.php';</script>";
    }

    $stmt->close();
} else {
    echo "<script>alert('ID tidak ditemukan!'); window.location.href='pemasangan.php';</script>";
}

$conn->close();
?>
