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

if (isset($_GET['edit'])) {
    $odp_id = $_GET['edit'];
    $sql = "SELECT * FROM jaringan_odp WHERE ODP_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $odp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if (!$row) {
        echo "<script>alert('Data ODP tidak ditemukan.'); window.location.href='dashodp.php';</script>";
        exit;
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $odp_id = $_POST['odp_id'];
    $nama_odp = $_POST['nama_odp'];
    $lokasi = $_POST['lokasi'];
    $kapasitas_port = $_POST['kapasitas_port'];
    $kapasitas_terpakai = $_POST['kapasitas_terpakai'];
    $status = $_POST['status'];
    $tanggal_instalasi = $_POST['tanggal_instalasi'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $redaman_output = $_POST['redaman_output'];
    $nama_pop = $_POST['nama_pop'];

    $sql = "UPDATE jaringan_odp SET Nama_ODP = ?, Lokasi = ?, Kapasitas_Port = ?, Kapasitas_Terpakai = ?, Status = ?, Tanggal_Instalasi = ?, Latitude = ?, Longitude = ?, Redaman_Output = ?, Nama_POP = ? WHERE ODP_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiissssss", $nama_odp, $lokasi, $kapasitas_port, $kapasitas_terpakai, $status, $tanggal_instalasi, $latitude, $longitude, $redaman_output, $nama_pop, $odp_id);

    if ($stmt->execute()) {
        echo "<script>alert('Data ODP berhasil diperbarui.'); window.location.href='dashodp.php';</script>";
    } else {
        echo "<script>alert('Error: " . $sql . "<br>" . $conn->error . "');</script>";
    }

    $stmt->close();
}

$conn->close();

include('navbar.php');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit ODP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Edit ODP</h2>
    <form method="post" action="edit_odp.php">
        <div class="mb-3">
            <label for="odp_id" class="form-label">ODP ID</label>
            <input type="text" class="form-control" id="odp_id" name="odp_id" value="<?= htmlspecialchars($row['ODP_ID']); ?>" readonly>
        </div>
        <div class="mb-3">
            <label for="nama_odp" class="form-label">Nama ODP</label>
            <input type="text" class="form-control" id="nama_odp" name="nama_odp" value="<?= htmlspecialchars($row['Nama_ODP']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="lokasi" class="form-label">Lokasi</label>
            <textarea class="form-control" id="lokasi" name="lokasi" required><?= htmlspecialchars($row['Lokasi']); ?></textarea>
        </div>
        <div class="mb-3">
            <label for="kapasitas_port" class="form-label">Kapasitas Port</label>
            <input type="number" class="form-control" id="kapasitas_port" name="kapasitas_port" value="<?= htmlspecialchars($row['Kapasitas_Port']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="kapasitas_terpakai" class="form-label">Kapasitas Terpakai</label>
            <input type="number" class="form-control" id="kapasitas_terpakai" name="kapasitas_terpakai" value="<?= htmlspecialchars($row['Kapasitas_Terpakai']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="Aktif" <?= $row['Status'] == 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                <option value="Nonaktif" <?= $row['Status'] == 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                <option value="Dalam Perbaikan" <?= $row['Status'] == 'Dalam Perbaikan' ? 'selected' : ''; ?>>Dalam Perbaikan</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="tanggal_instalasi" class="form-label">Tanggal Instalasi</label>
            <input type="date" class="form-control" id="tanggal_instalasi" name="tanggal_instalasi" value="<?= htmlspecialchars($row['Tanggal_Instalasi']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="latitude" class="form-label">Latitude</label>
            <input type="text" class="form-control" id="latitude" name="latitude" value="<?= htmlspecialchars($row['Latitude']); ?>">
        </div>
        <div class="mb-3">
            <label for="longitude" class="form-label">Longitude</label>
            <input type="text" class="form-control" id="longitude" name="longitude" value="<?= htmlspecialchars($row['Longitude']); ?>">
        </div>
        <div class="mb-3">
            <label for="redaman_output" class="form-label">Redaman Output</label>
            <input type="text" class="form-control" id="redaman_output" name="redaman_output" value="<?= htmlspecialchars($row['Redaman_Output']); ?>">
        </div>
        <div class="mb-3">
            <label for="nama_pop" class="form-label">Nama POP</label>
            <input type="text" class="form-control" id="nama_pop" name="nama_pop" value="<?= htmlspecialchars($row['Nama_POP']); ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>