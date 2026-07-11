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

$pesan = ""; // Variabel untuk menyimpan pesan status

// Proses penambahan data jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_paket = mysqli_real_escape_string($conn, $_POST["nama_paket"]);
    $kecepatan = mysqli_real_escape_string($conn, $_POST["kecepatan"]);
    $deskripsi = mysqli_real_escape_string($conn, $_POST["deskripsi"]);
    $harga = mysqli_real_escape_string($conn, $_POST["harga"]);

    $sql = "INSERT INTO jaringan_paket (nama_paket, kecepatan, deskripsi, harga) VALUES ('$nama_paket', '$kecepatan', '$deskripsi', '$harga')";

    if ($conn->query($sql) === TRUE) {
        $pesan = "<div class='alert alert-success'>Data paket berhasil ditambahkan. <a href='dashpaketku.php' class='alert-link'>Kembali ke Dashboard</a></div>";
    } else {
        $pesan = "<div class='alert alert-danger'>Error: " . $sql . "<br>" . $conn->error . "</div>";
    }
}

include('navbar.php');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Paket Internet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Tambah Paket Internet</h2>

    <?php echo $pesan; ?>

    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Form Tambah Paket</h5>
        </div>
        <div class="card-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-3">
                    <label for="nama_paket" class="form-label">Nama Paket:</label>
                    <input type="text" class="form-control" id="nama_paket" name="nama_paket" required>
                </div>
                <div class="mb-3">
                    <label for="kecepatan" class="form-label">Kecepatan:</label>
                    <input type="text" class="form-control" id="kecepatan" name="kecepatan" required>
                </div>
                <div class="mb-3">
                    <label for="deskripsi" class="form-label">Deskripsi:</label>
                    <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="harga" class="form-label">Harga:</label>
                    <input type="number" class="form-control" id="harga" name="harga" required>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Paket</button>
                <a href="dashpaketku.php" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>