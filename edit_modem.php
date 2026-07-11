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
$id_modem = null;
$modem_data = null;

// Ambil ID dari parameter GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_modem = $_GET['id'];

    // Ambil data modem berdasarkan ID
    $stmt_select = $conn->prepare("SELECT * FROM jaringan_modem WHERE id_modem = ?");
    $stmt_select->bind_param("i", $id_modem);
    $stmt_select->execute();
    $result = $stmt_select->get_result();

    if ($result->num_rows == 1) {
        $modem_data = $result->fetch_assoc();
    } else {
        $message = '<div class="alert alert-danger">Data modem tidak ditemukan. <a href="dashims.php" class="alert-link">Kembali ke Dashboard</a></div>';
    }
    $stmt_select->close();
} else {
    $message = '<div class="alert alert-danger">ID modem tidak valid. <a href="dashims.php" class="alert-link">Kembali ke Dashboard</a></div>';
}

// Proses form update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id_modem_update'])) {
    $id_modem_update = $_POST['id_modem_update'];
    $serial_number = $_POST['serial_number'];
    $model = $_POST['model'];
    $merk = $_POST['merk'];
    $status = $_POST['status'];
    $tanggal_masuk = $_POST['tanggal_masuk'];
    $lokasi_penyimpanan = $_POST['lokasi_penyimpanan'];

    // Validasi sederhana
    if (empty($serial_number)) {
        $message = '<div class="alert alert-danger">Serial Number harus diisi.</div>';
    } elseif (!in_array($status, ['tersedia', 'dipasang', 'rusak', 'cabutan'])) {
        $message = '<div class="alert alert-danger">Status tidak valid.</div>';
    } else {
        // Cek apakah Serial Number sudah ada untuk modem lain
        $stmt_check = $conn->prepare("SELECT serial_number FROM jaringan_modem WHERE serial_number = ? AND id_modem != ?");
        $stmt_check->bind_param("si", $serial_number, $id_modem_update);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = '<div class="alert alert-danger">Serial Number sudah terdaftar untuk modem lain.</div>';
        } else {
            // Update data modem
            $stmt_update = $conn->prepare("UPDATE jaringan_modem SET serial_number=?, model=?, merk=?, status=?, tanggal_masuk=?, lokasi_penyimpanan=? WHERE id_modem=?");
            $stmt_update->bind_param("ssssssi", $serial_number, $model, $merk, $status, $tanggal_masuk, $lokasi_penyimpanan, $id_modem_update);

            if ($stmt_update->execute()) {
                $message = '<div class="alert alert-success">Data modem berhasil diperbarui. <a href="dashims.php" class="alert-link">Kembali ke Dashboard</a></div>';
            } else {
                $message = '<div class="alert alert-danger">Terjadi kesalahan saat memperbarui data: ' . $stmt_update->error . '</div>';
            }

            $stmt_update->close();
        }
        $stmt_check->close();
    }
}

include('navbar.php'); // Optional jika kamu pakai include navbar
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Modem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Edit Data Modem</h2>

    <?php echo $message; ?>

    <?php if ($modem_data): ?>
        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="id_modem_update" value="<?= htmlspecialchars($modem_data['id_modem']); ?>">

                    <div class="mb-3">
                        <label for="serial_number" class="form-label">Serial Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="serial_number" name="serial_number" value="<?= htmlspecialchars($modem_data['serial_number']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="model" class="form-label">Model</label>
                        <input type="text" class="form-control" id="model" name="model" value="<?= htmlspecialchars($modem_data['model'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="merk" class="form-label">Merk</label>
                        <input type="text" class="form-control" id="merk" name="merk" value="<?= htmlspecialchars($modem_data['merk'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="tersedia" <?= $modem_data['status'] == 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                            <option value="dipasang" <?= $modem_data['status'] == 'dipasang' ? 'selected' : '' ?>>Dipasang</option>
                            <option value="rusak" <?= $modem_data['status'] == 'rusak' ? 'selected' : '' ?>>Rusak</option>
                            <option value="cabutan" <?= $modem_data['status'] == 'cabutan' ? 'selected' : '' ?>>Cabutan</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="tanggal_masuk" class="form-label">Tanggal Masuk</label>
                        <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk" value="<?= htmlspecialchars($modem_data['tanggal_masuk'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="lokasi_penyimpanan" class="form-label">Lokasi Penyimpanan</label>
                        <input type="text" class="form-control" id="lokasi_penyimpanan" name="lokasi_penyimpanan" value="<?= htmlspecialchars($modem_data['lokasi_penyimpanan'] ?? ''); ?>">
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
