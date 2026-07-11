<?php
require_once __DIR__ . '/config/database.php';
session_start();

$dashboard_divisi = ['Admin', 'IT', 'Manager', 'SPV Teknis', 'Finance'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['divisi'], $dashboard_divisi)) {
    header("Location: login.php");
    exit;
}
// ===================================================
// PENGATURAN KONEKSI DATABASE
// Sesuaikan dengan detail koneksi database FMS Anda
// ===================================================
$db_host = 'localhost';
$db_user = 'u272457353_kevinsamsungfm'; // User untuk database FMS
$db_pass = 'Admionkevin99';             // Password untuk database FMS
$db_name = 'u272457353_fms';             // Nama database FMS

// Membuat koneksi
$conn = getErpDbConnection();

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// Variabel untuk menyimpan pesan notifikasi
$message = '';

// --- Logika untuk memproses form ---
if (isset($_POST['submit_pengajuan'])) {
    $nama_pengaju   = $_POST['nama_pengaju'];
    $divisi_pengaju = $_POST['divisi_pengaju'];
    $nama_barang    = $_POST['nama_barang'];
    $harga_satuan   = (float)$_POST['harga_satuan'];
    $jumlah         = (int)$_POST['jumlah'];
    $total_harga    = $harga_satuan * $jumlah;
    $keterangan     = $_POST['keterangan'];

    // Query INSERT menggunakan prepared statement
    $stmt = $conn->prepare("INSERT INTO keu_pengajuan_pembelian_aset (nama_pengaju, divisi_pengaju, nama_barang, harga_satuan, jumlah, total_harga, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdiis", $nama_pengaju, $divisi_pengaju, $nama_barang, $harga_satuan, $jumlah, $total_harga, $keterangan);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success mt-3">Pengajuan aset untuk <b>' . htmlspecialchars($nama_barang) . '</b> berhasil dikirim.</div>';
    } else {
        $message = '<div class="alert alert-danger mt-3">Error: ' . $stmt->error . '</div>';
    }
    $stmt->close();
}

// Ambil 10 data pengajuan terakhir untuk ditampilkan di riwayat
$history_result = $conn->query("SELECT * FROM keu_pengajuan_pembelian_aset ORDER BY tanggal_pengajuan DESC LIMIT 10");
// INCLUDE NAVBAR
include('navbar.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pengajuan Aset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }
        .container {
            max-width: 800px;
        }
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="container my-5">
    <div class="text-center mb-4">
        <h1 class="h2"><i class="bi bi-archive-fill"></i> Form Pengajuan Aset</h1>
        <p class="text-muted">Silakan isi form di bawah ini untuk mengajukan pembelian aset baru.</p>
    </div>

    <?php echo $message; ?>

    <div class="card">
        <div class="card-body p-4 p-md-5">
            <form action="pengajuan_aset.php" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nama_pengaju" class="form-label">Nama Pengaju</label>
                        <input type="text" id="nama_pengaju" name="nama_pengaju" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="divisi_pengaju" class="form-label">Divisi Pengaju</label>
                        <input type="text" id="divisi_pengaju" name="divisi_pengaju" class="form-control" placeholder="Contoh: Gudang, Teknisi, NOC" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label for="nama_barang" class="form-label">Nama Barang</label>
                    <input type="text" id="nama_barang" name="nama_barang" class="form-control" placeholder="Contoh: Router Mikrotik RB750Gr3" required>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="harga_satuan" class="form-label">Harga Satuan (Rp)</label>
                        <input type="number" id="harga_satuan" name="harga_satuan" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="jumlah" class="form-label">Jumlah Barang</label>
                        <input type="number" id="jumlah" name="jumlah" class="form-control" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="keterangan" class="form-label">Keterangan / Spesifikasi (Opsional)</label>
                    <textarea id="keterangan" name="keterangan" class="form-control" rows="3"></textarea>
                </div>
                <div class="d-grid">
                    <button type="submit" name="submit_pengajuan" class="btn btn-primary btn-lg"><i class="bi bi-send-fill"></i> Kirim Pengajuan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="mt-5">
        <h3 class="text-center mb-3">10 Pengajuan Terakhir</h3>
        <div class="table-responsive card">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Pengaju</th>
                        <th>Barang</th>
                        <th class="text-end">Total</th>
                        <th class="text-center">Status Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history_result && $history_result->num_rows > 0): ?>
                        <?php while($row = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= date("d/m/Y", strtotime($row['tanggal_pengajuan'])) ?></td>
                                <td><?= htmlspecialchars($row['nama_pengaju']) ?></td>
                                <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                <td class="text-end">Rp <?= number_format($row['total_harga']) ?></td>
                                <td class="text-center"><span class="badge <?= $row['status_pembayaran'] == 'Sudah Bayar' ? 'bg-success' : 'bg-secondary' ?>"><?= $row['status_pembayaran'] ?></span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted p-4">Belum ada pengajuan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Menutup koneksi database
$conn->close();
?>