<?php
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

$sql = "SELECT * FROM Aset";
$result = $conn->query($sql);

include('navbar.php');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Aset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Dashboard Aset</h2>
    <a href="tambah_aset.php" class="btn btn-primary mb-4">Tambah Aset</a>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Data Aset</h5>
        </div>
        <div class="card-body">
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Nama Aset</th>
                                <th>Jenis Aset</th>
                                <th>Merk</th>
                                <th>Model</th>
                                <th>Foto</th>
                                <th>Serial Number</th>
                                <th>Tanggal Pembelian</th>
                                <th>Harga Pembelian</th>
                                <th>Kondisi</th>
                                <th>Lokasi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['Nama_Aset']); ?></td>
                                    <td><?= htmlspecialchars($row['Jenis_Aset']); ?></td>
                                    <td><?= htmlspecialchars($row['Merk']); ?></td>
                                    <td><?= htmlspecialchars($row['Model']); ?></td>
                                    <td>
                                        <?php if (!empty($row['Foto'])): ?>
                                            <img src="uploads/<?= htmlspecialchars($row['Foto']); ?>" alt="Foto Aset" style="max-width: 100px;">
                                        <?php else: ?>
                                            Tidak Ada Foto
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['Serial_Number']); ?></td>
                                    <td><?= htmlspecialchars($row['Tanggal_Pembelian']); ?></td>
                                    <td><?= number_format($row['Harga_Pembelian'], 2, ',', '.'); ?></td>
                                    <td><?= htmlspecialchars($row['Kondisi']); ?></td>
                                    <td><?= htmlspecialchars($row['Lokasi']); ?></td>
                                    <td>
                                        <a href="edit_aset.php?edit=<?= $row['ID_Aset']; ?>" class="btn btn-warning btn-sm">Edit</a>
                                        <a href="hapus_aset.php?id=<?= $row['ID_Aset']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus data ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">Belum ada data aset.</p>
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