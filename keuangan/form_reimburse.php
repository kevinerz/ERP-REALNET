<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
$allowed_roles = ['Leader Area', 'Manager', 'SPV Teknis', 'Admin'];
if (!isset($_SESSION['username']) || !in_array($_SESSION['divisi'], $allowed_roles)) {
    echo '<div style="text-align:center; margin-top:30vh; font-size:24px; font-weight:bold;">' .
         strtoupper("Hanya Leader Area, Manager, atau SPV yang dapat mengajukan reimburse.") .
         '</div>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Form Reimburse BBM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white text-center">
                    <h4 class="mb-0">Form Reimburse BBM</h4>
                </div>
                <div class="card-body">
                    <form action="simpan_reimburse.php" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label">Nama Pengaju</label>
                            <input type="text" name="nama_pengaju" class="form-control" value="<?= htmlspecialchars($_SESSION['nama']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tujuan</label>
                            <input type="text" name="tujuan" class="form-control" placeholder="Tujuan perjalanan" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Liter BBM</label>
                            <input type="number" name="liter" class="form-control" step="0.01" placeholder="Contoh: 15.5" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Total (Rp)</label>
                            <input type="number" name="total" class="form-control" step="0.01" placeholder="Contoh: 200000" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea name="catatan" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Foto Nota BBM</label>
                            <input type="file" name="nota" class="form-control" accept="image/*" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success">Kirim Reimburse</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center small text-muted">
                    <?= htmlspecialchars($_SESSION['divisi']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
