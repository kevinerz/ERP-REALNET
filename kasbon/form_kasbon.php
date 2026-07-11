<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    echo "<div class='text-center mt-5 text-danger'>Silakan login terlebih dahulu.</div>";
    exit;
}

$username = $_SESSION['username'];
$divisi   = $_SESSION['divisi'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Kasbon</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Form Pengajuan Kasbon</h4>
        </div>
        <div class="card-body">
            <form action="proses_kasbon.php" method="post">
                <div class="mb-3">
                    <label class="form-label">Nama Pengaju</label>
                    <input type="text" class="form-control" name="nama_pengaju"
                           value="<?= htmlspecialchars($username); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Divisi</label>
                    <input type="text" class="form-control" name="divisi_pengaju"
                           value="<?= htmlspecialchars($divisi); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" class="form-control" name="tanggal" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah Kasbon (Rp)</label>
                    <input type="number" class="form-control" name="jumlah" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Keperluan</label>
                    <textarea class="form-control" name="keperluan" rows="3" required></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Ajukan Kasbon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
