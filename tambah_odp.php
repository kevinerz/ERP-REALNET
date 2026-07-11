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

    $sql = "INSERT INTO jaringan_odp (ODP_ID, Nama_ODP, Lokasi, Kapasitas_Port, Kapasitas_Terpakai, Status, Tanggal_Instalasi, Latitude, Longitude, Redaman_Output, Nama_POP) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssiissssss", $odp_id, $nama_odp, $lokasi, $kapasitas_port, $kapasitas_terpakai, $status, $tanggal_instalasi, $latitude, $longitude, $redaman_output, $nama_pop);

    if ($stmt->execute()) {
        echo "<script>alert('Data ODP berhasil ditambahkan.'); window.location.href='dashodp.php';</script>";
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
    <title>Tambah ODP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Tambah ODP</h2>
    <form method="post" action="tambah_odp.php">
        <div class="mb-3">
            <label for="odp_id" class="form-label">ODP ID</label>
            <input type="text" class="form-control" id="odp_id" name="odp_id" required>
        </div>
        <div class="mb-3">
            <label for="nama_odp" class="form-label">Nama ODP</label>
            <input type="text" class="form-control" id="nama_odp" name="nama_odp" required>
        </div>
        <div class="mb-3">
            <label for="lokasi" class="form-label">Lokasi</label>
            <textarea class="form-control" id="lokasi" name="lokasi" required></textarea>
        </div>
        <div class="mb-3">
            <label for="kapasitas_port" class="form-label">Kapasitas Port</label>
            <input type="number" class="form-control" id="kapasitas_port" name="kapasitas_port" required>
        </div>
        <div class="mb-3">
            <label for="kapasitas_terpakai" class="form-label">Kapasitas Terpakai</label>
            <input type="number" class="form-control" id="kapasitas_terpakai" name="kapasitas_terpakai" required>
        </div>
        <div class="mb-3">
            <label for="status" class="form-label">Status</label>
            <select class="form-select" id="status" name="status" required>
                <option value="Aktif">Aktif</option>
                <option value="Nonaktif">Nonaktif</option>
                <option value="Dalam Perbaikan">Dalam Perbaikan</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="tanggal_instalasi" class="form-label">Tanggal Instalasi</label>
            <input type="date" class="form-control" id="tanggal_instalasi" name="tanggal_instalasi" required>
        </div>
        <div class="mb-3">
            <label for="latitude" class="form-label">Latitude</label>
            <input type="text" class="form-control" id="latitude" name="latitude" readonly>
        </div>
        <div class="mb-3">
            <label for="longitude" class="form-label">Longitude</label>
            <input type="text" class="form-control" id="longitude" name="longitude" readonly>
        </div>
        <div class="mb-3">
            <label for="redaman_output" class="form-label">Redaman Output</label>
            <input type="text" class="form-control" id="redaman_output" name="redaman_output">
        </div>
        <div class="mb-3">
            <label for="nama_pop" class="form-label">Nama POP</label>
            <input type="text" class="form-control" id="nama_pop" name="nama_pop" required>
        </div>
        <button type="button" class="btn btn-info mb-3" onclick="getLocation()">Ambil Lokasi Otomatis</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
</div>

<script>
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition);
    } else {
        alert("Geolocation tidak didukung oleh browser ini.");
    }
}

function showPosition(position) {
    document.getElementById("latitude").value = position.coords.latitude;
    document.getElementById("longitude").value = position.coords.longitude;
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>