<?php
require_once 'templates/header.php';

// --- Logika untuk memproses form ---
if (isset($_POST['submit_pengajuan'])) {
    $nama_pengaju   = $_POST['nama_pengaju'];
    $divisi_pengaju = $_POST['divisi_pengaju'];
    $nama_barang    = $_POST['nama_barang'];
    $harga_satuan   = (float)$_POST['harga_satuan'];
    $jumlah         = (int)$_POST['jumlah'];
    $total_harga    = $harga_satuan * $jumlah;
    $keterangan     = $_POST['keterangan'];

    $stmt = $conn->prepare("INSERT INTO pengajuan_aset (nama_pengaju, divisi_pengaju, nama_barang, harga_satuan, jumlah, total_harga, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdiis", $nama_pengaju, $divisi_pengaju, $nama_barang, $harga_satuan, $jumlah, $total_harga, $keterangan);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Pengajuan aset berhasil dikirim.</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
    }
}

// --- Logika untuk konfirmasi penerimaan barang ---
if (isset($_GET['action']) && $_GET['action'] == 'terima_barang' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $tanggal_sekarang = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE pengajuan_aset SET status_penerimaan = 'Sudah Diterima', tanggal_penerimaan = ? WHERE id = ?");
    $stmt->bind_param("si", $tanggal_sekarang, $id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-info'>Status barang berhasil diperbarui menjadi 'Sudah Diterima'.</div>";
    }
}

// Ambil semua data pengajuan untuk ditampilkan
$result = $conn->query("SELECT * FROM pengajuan_aset ORDER BY tanggal_pengajuan DESC");
?>

<h1><i class="bi bi-file-earmark-plus-fill"></i> Pengajuan Aset</h1>
<p>Gunakan halaman ini untuk mengajukan pembelian aset baru dan mengonfirmasi penerimaan barang.</p>

<div class="form-card">
    <h2>Form Pengajuan Aset Baru</h2>
    <form action="pengajuan_aset.php" method="POST">
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Nama Pengaju</label>
                <input type="text" name="nama_pengaju" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Divisi Pengaju</label>
                <input type="text" name="divisi_pengaju" class="form-control" placeholder="Contoh: Gudang, Teknisi, NOC" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Nama Barang</label>
            <input type="text" name="nama_barang" class="form-control" required>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Harga Satuan (Rp)</label>
                <input type="number" name="harga_satuan" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">Jumlah Barang</label>
                <input type="number" name="jumlah" class="form-control" required>
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Keterangan/Spesifikasi</label>
            <textarea name="keterangan" class="form-control" rows="2"></textarea>
        </div>
        <button type="submit" name="submit_pengajuan" class="btn btn-primary">Kirim Pengajuan</button>
    </form>
</div>

<div class="table-container mt-4">
    <h2>Riwayat Pengajuan Anda</h2>
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Tgl Pengajuan</th>
                    <th>Pengaju</th>
                    <th>Barang</th>
                    <th class="text-end">Total Harga</th>
                    <th class="text-center">Status Bayar</th>
                    <th class="text-center">Status Barang</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= date("d M Y", strtotime($row['tanggal_pengajuan'])) ?></td>
                        <td><b><?= htmlspecialchars($row['nama_pengaju']) ?></b><br><small><?= htmlspecialchars($row['divisi_pengaju']) ?></small></td>
                        <td><b><?= htmlspecialchars($row['nama_barang']) ?></b><br><small><?= $row['jumlah'] ?> unit @ Rp <?= number_format($row['harga_satuan']) ?></small></td>
                        <td class="text-end">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                        <td class="text-center"><span class="badge <?= $row['status_pembayaran'] == 'Sudah Bayar' ? 'bg-success' : 'bg-secondary' ?>"><?= $row['status_pembayaran'] ?></span></td>
                        <td class="text-center"><span class="badge <?= $row['status_penerimaan'] == 'Sudah Diterima' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $row['status_penerimaan'] ?></span></td>
                        <td class="text-center">
                            <?php if ($row['status_pembayaran'] == 'Sudah Bayar' && $row['status_penerimaan'] == 'Belum Diterima'): ?>
                                <a href="pengajuan_aset.php?action=terima_barang&id=<?= $row['id'] ?>" class="btn btn-info btn-sm" onclick="return confirm('Apakah Anda yakin barang sudah diterima?')">Konfirmasi Terima</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>