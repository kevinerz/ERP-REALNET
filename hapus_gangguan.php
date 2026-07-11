<?php
require_once __DIR__ . '/config/database.php';
// Konfigurasi database
$servername = "localhost";
$username = "u272457353_kevinsamsung";
$password = "Admionkevin99";
$database = "u272457353_tiket_helpdesk";

// Koneksi ke database
$conn = getErpDbConnection();

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Periksa apakah ada parameter 'id' dalam URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = $_GET['id'];

    // Query untuk menghapus tiket berdasarkan ID
    $query = "DELETE FROM tiket_gangguan WHERE id = ?";
    
    // Menggunakan prepared statement untuk menghindari SQL injection
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $id); // "i" untuk integer
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Jika tiket berhasil dihapus, redirect ke halaman utama atau halaman data gangguan
            header("Location: gangguan.php?status=success");
            exit;
        } else {
            // Jika tidak ada baris yang terhapus (ID tidak ditemukan)
            echo "Data gangguan tidak ditemukan atau sudah dihapus.";
        }
    } else {
        echo "Gagal menyiapkan query.";
    }
} else {
    echo "ID tidak valid.";
}

$conn->close();
?>
