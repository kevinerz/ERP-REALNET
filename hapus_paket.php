<?php
require_once __DIR__ . '/config/database.php';
// Konfigurasi database
$servername = "localhost";
$username = "u272457353_kevinsamsung99";
$password = "Admionkevin99";
$database = "u272457353_umumdata";

// Koneksi ke database
$conn = getErpDbConnection();

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Pastikan ID paket ada di URL
if (isset($_GET["id"])) {
    $id_paket = mysqli_real_escape_string($conn, $_GET["id"]);

    // Query untuk menghapus data
    $sql = "DELETE FROM jaringan_paket WHERE id_paket=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_paket);

    if ($stmt->execute()) {
        // Redirect kembali ke dashpaket.php setelah berhasil dihapus
        header("Location: dashpaket.php");
        exit();
    } else {
        echo "<div class='alert alert-danger'>Error deleting record: " . $stmt->error . "</div>";
    }
    $stmt->close();
} else {
    echo "<div class='alert alert-warning'>ID paket tidak ditemukan.</div>";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Paket Internet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center mb-4">Hapus Paket Internet</h2>
        <p class="text-center">Sedang memproses penghapusan data...</p>
        <div class="text-center">
            <a href="dashpaket.php" class="btn btn-secondary">Kembali ke Dashboard</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>