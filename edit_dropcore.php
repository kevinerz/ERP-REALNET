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
$id_dropcore = null;
$dropcore_data = null;

// Ambil ID dari parameter GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_dropcore = $_GET['id'];

    // Ambil data dropcore berdasarkan ID
    $stmt_select = $conn->prepare("SELECT * FROM jaringan_kabel_dropcore WHERE id_kabel_dropcore = ?");
    $stmt_select->bind_param("i", $id_dropcore);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows == 1) {
        $dropcore_data = $result->fetch_assoc();
    } else {
        $message = '<div class="alert alert-danger">Data kabel dropcore tidak ditemukan. <a href="dashims.php" class="alert-link">Kembali ke Dashboard</a></div>';
    }
    $stmt_select->close();
} else {
    $message = '<div class="alert alert-danger">ID kabel dropcore tidak valid. <a href="dashims.php" class="alert-link">Kembali ke Dashboard</a></div>';
}

// Proses form update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_kabel_dropcore_update'])) {
    $id_kabel_dropcore_update = $_POST['id_kabel_dropcore_update'];
    $kode_kabel = $_POST['kode_kabel'];
    $panjang_meter = $_POST['panjang_meter'];
    $jenis = $_POST['jenis'];
    $status = $_POST['status'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $keterangan = $_POST['keterangan'];
    $lokasi_penyimpanan = $_POST['lokasi_penyimpanan'];

    // Validasi sederhana
    if (empty($kode_kabel)) {
        $message = '<div class="alert alert-danger">Kode Kabel harus diisi.</div>';
    } else {
        // Cek apakah Kode Kabel sudah ada untuk kabel dropcore lain
        $stmt_check = $conn->prepare("SELECT kode_kabel FROM jaringan_kabel_dropcore WHERE kode_kabel = ? AND id_kabel_dropcore != ?");
        $stmt_check->bind_param("si", $kode_kabel, $id_kabel_dropcore_update);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = '<div class="alert alert-danger">Kode Kabel sudah terdaftar untuk kabel dropcore lain.</div>';
        } else {
            // Siapkan query SQL untuk update data
            $stmt_update = $conn->prepare("UPDATE jaringan_kabel_dropcore SET kode_kabel=?, panjang_meter=?, jenis=?, status=?, tanggal_masuk=?, keterangan=?, lokasi_penyimpanan=? WHERE id_kabel_dropcore=?");
            $stmt_update->bind_param("sdsssssi", $kode_kabel, $panjang_meter, $jenis, $status, $tanggal_masuk, $keterangan, $lokasi_penyimpanan, $id_kabel_dropcore_update);

            if ($stmt_update->execute()) {
                $message = '<div class="alert alert-success">Data kabel dropcore berhasil diperbarui. <a href="dashims.php" class="alert-link">Kembali ke Dashboard</a></div>';
            } else {
                $message = '<div class="alert alert-danger">Terjadi kesalahan saat memperbarui data: ' . $stmt_update->error . '</div>';
            }

            $stmt_update->close();
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
    <title>Edit Kabel Dropcore</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Edit Data Kabel Dropcore</h2>

    <?php echo $message; ?>

    <?php if ($dropcore_data): ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="id_kabel_dropcore_update" value="<?= htmlspecialchars($dropcore_data['id_kabel_dropcore']); ?>">
                    <div class="mb-3">
                        <label for="kode_kabel" class="form-label">Kode Kabel <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="kode_kabel" name="kode_kabel" value="<?= htmlspecialchars($dropcore_data['kode_kabel']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="panjang_meter" class="form-label">Panjang (Meter)</label>
                        <input type="number" class="form-control" id="panjang_meter" name="panjang_meter" step="0.01" value="<?= htmlspecialchars($dropcore_data['panjang_meter']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="jenis" class="form-label">Jenis</label>
                        <input type="text" class="form-control" id="jenis" name="jenis" value="<?= htmlspecialchars($dropcore_data['jenis']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="tersedia" <?php if ($dropcore_data['status'] == 'tersedia') echo 'selected'; ?>>Tersedia</option>
                            <option value="digunakan" <?php if ($dropcore_data['status'] == 'digunakan') echo 'selected'; ?>>Digunakan</option>
                            <option value="rusak" <?php if ($dropcore_data['status'] == 'rusak') echo 'selected'; ?>>Rusak</option>
                            <option value="dimusnahkan" <?php if ($dropcore_data['status'] == 'dimusnahkan') echo 'selected'; ?>>Dimusnahkan</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
                        <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk" value="<?= htmlspecialchars($dropcore_data['tanggal_masuk']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="keterangan" class="form-label">Keterangan</label>
                        <textarea class="form-control" id="keterangan" name="keterangan" rows="3"><?= htmlspecialchars($dropcore_data['keterangan']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="lokasi_penyimpanan" class="form-label">Lokasi Penyimpanan</label>
                        <input type="text" class="form-control" id="lokasi_penyimpanan" name="lokasi_penyimpanan" value="<?= htmlspecialchars($dropcore_data['lokasi_penyimpanan']); ?>">
                    </div>
                    <button type="submit" class="btn btn-success">Simpan Perubahan</button>
                    <a href="dashims.php" class="btn btn-secondary ms-2">Batal</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>