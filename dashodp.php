<?php
session_start();

$dashboard_divisi = ['Admin', 'IT', 'Manager', 'SPV Teknis', 'Finance'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['divisi'], $dashboard_divisi)) {
    header("Location: login.php");
    exit;
}
// Konfigurasi database
$servername = "localhost";
$username = "u272457353_kevinsamsung99";
$password = "Admionkevin99";
$database = "u272457353_umumdata";

// Koneksi ke database
$conn = new mysqli($servername, $username, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Menampilkan data ODP
$sql = "SELECT * FROM ODP";
$result = $conn->query($sql);

// Include the navbar
include('navbar.php');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard ODP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Dashboard ODP</h2>
    <a href="tambah_odp.php" class="btn btn-primary mb-4">Tambah ODP</a>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Data ODP</h5>
        </div>
        <div class="card-body">
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>ODP ID</th>
                                <th>Nama ODP</th>
                                <th>Lokasi</th>
                                <th>Kapasitas Port</th>
                                <th>Kapasitas Terpakai</th>
                                <th>Status</th>
                                <th>Tanggal Instalasi</th>
                                <th>Latitude</th>
                                <th>Longitude</th>
                                <th>Redaman Output</th>
                                <th>Nama POP</th> <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['ODP_ID']); ?></td>
                                    <td><?= htmlspecialchars($row['Nama_ODP']); ?></td>
                                    <td><?= htmlspecialchars($row['Lokasi']); ?></td>
                                    <td><?= htmlspecialchars($row['Kapasitas_Port']); ?></td>
                                    <td><?= htmlspecialchars($row['Kapasitas_Terpakai']); ?></td>
                                    <td><?= htmlspecialchars($row['Status']); ?></td>
                                    <td><?= htmlspecialchars($row['Tanggal_Instalasi']); ?></td>
                                    <td><?= htmlspecialchars($row['Latitude']); ?></td>
                                    <td><?= htmlspecialchars($row['Longitude']); ?></td>
                                    <td><?= htmlspecialchars($row['Redaman_Output']); ?></td>
                                    <td><?= htmlspecialchars($row['Nama_POP']); ?></td> <td>
                                        <a href="edit_odp.php?edit=<?= $row['ODP_ID']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="hapus_odp.php?id=<?= $row['ODP_ID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">Belum ada data ODP.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>