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

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $serial_number = $_POST['serial_number'];
    $mac_address = $_POST['mac_address'];
    $model = $_POST['model'];
    $merk = $_POST['merk'];
    $status = $_POST['status'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $keterangan = $_POST['keterangan'];
    $lokasi_penyimpanan = $_POST['lokasi_penyimpanan'];

    // Validasi sederhana (Anda mungkin ingin menambahkan validasi yang lebih ketat)
    if (empty($serial_number)) {
        $message = '<div class="alert alert-danger">Serial Number harus diisi.</div>';
    } else {
        // Cek apakah Serial Number sudah ada
        $stmt_check = $conn->prepare("SELECT serial_number FROM jaringan_modem WHERE serial_number = ?");
        $stmt_check->bind_param("s", $serial_number);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = '<div class="alert alert-danger">Serial Number sudah terdaftar.</div>';
        } else {
            // Siapkan query SQL untuk menyimpan data
            $stmt = $conn->prepare("INSERT INTO jaringan_modem (serial_number, mac_address, model, merk, status, tanggal_masuk, keterangan, lokasi_penyimpanan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssss", $serial_number, $mac_address, $model, $merk, $status, $tanggal_masuk, $keterangan, $lokasi_penyimpanan);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Data modem berhasil ditambahkan. <a href="dashims.php" class="alert-link">Kembali ke Dashboard</a></div>';
            } else {
                $message = '<div class="alert alert-danger">Terjadi kesalahan saat menambahkan data: ' . $stmt->error . '</div>';
            }

            $stmt->close();
        }
        $stmt_check->close();
    }
}

include('navbar.php'); // Asumsi ada file navbar.php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Modem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Tambah Data Modem</h2>

    <?php echo $message; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="serial_number" name="serial_number" required>
                </div>
                <div class="mb-3">
                    <label for="mac_address" class="form-label">MAC Address</label>
                    <input type="text" class="form-control" id="mac_address" name="mac_address">
                </div>
                <div class="mb-3">
                    <label for="model" class="form-label">Model</label>
                    <input type="text" class="form-control" id="model" name="model">
                </div>
                <div class="mb-3">
                    <label for="merk" class="form-label">Merk</label>
                    <input type="text" class="form-control" id="merk" name="merk">
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="tersedia">Tersedia</option>
                        <option value="dipasang">Dipasang</option>
                        <option value="rusak">Rusak</option>
                        <option value="dimusnahkan">Dimusnahkan</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
                    <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk">
                </div>
                <div class="mb-3">
                    <label for="keterangan" class="form-label">Keterangan</label>
                    <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="lokasi_penyimpanan" class="form-label">Lokasi Penyimpanan</label>
                    <input type="text" class="form-control" id="lokasi_penyimpanan" name="lokasi_penyimpanan">
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="dashims.php" class="btn btn-secondary ms-2">Batal</a>
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