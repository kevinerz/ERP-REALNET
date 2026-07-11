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
    $kode_kabel = $_POST['kode_kabel'];
    $panjang_meter = $_POST['panjang_meter'];
    $jumlah_core = $_POST['jumlah_core'];
    $merk = $_POST['merk'];
    $status = $_POST['status'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $keterangan = $_POST['keterangan'];
    $lokasi_penyimpanan = $_POST['lokasi_penyimpanan'];

    // Validasi sederhana
    if (empty($kode_kabel)) {
        $message = '<div class="alert alert-danger">Kode Kabel harus diisi.</div>';
    } else {
        // Cek apakah Kode Kabel sudah ada
        $stmt_check = $conn->prepare("SELECT kode_kabel FROM jaringan_kabel_adss WHERE kode_kabel = ?");
        $stmt_check->bind_param("s", $kode_kabel);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = '<div class="alert alert-danger">Kode Kabel sudah terdaftar.</div>';
        } else {
            // Siapkan query SQL untuk menyimpan data
            $stmt = $conn->prepare("INSERT INTO jaringan_kabel_adss (kode_kabel, panjang_meter, jumlah_core, merk, status, tanggal_masuk, keterangan, lokasi_penyimpanan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdsssss", $kode_kabel, $panjang_meter, $jumlah_core, $merk, $status, $tanggal_masuk, $keterangan, $lokasi_penyimpanan);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Data kabel ADSS berhasil ditambahkan. <a href="dashims.php" class="alert-link">Kembali ke Dashboard</a></div>';
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
    <title>Tambah Kabel ADSS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Tambah Data Kabel ADSS</h2>

    <?php echo $message; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="kode_kabel" class="form-label">Kode Kabel <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="kode_kabel" name="kode_kabel" required>
                </div>
                <div class="mb-3">
                    <label for="panjang_meter" class="form-label">Panjang (Meter)</label>
                    <input type="number" class="form-control" id="panjang_meter" name="panjang_meter" step="0.01">
                </div>
                <div class="mb-3">
                    <label for="jumlah_core" class="form-label">Jumlah Core</label>
                    <input type="number" class="form-control" id="jumlah_core" name="jumlah_core" min="1">
                </div>
                <div class="mb-3">
                    <label for="merk" class="form-label">Merk</label>
                    <input type="text" class="form-control" id="merk" name="merk">
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="tersedia">Tersedia</option>
                        <option value="terpasang">Terpasang</option>
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
                <button type="submit" class="btn btn-info text-white">Simpan</button>
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