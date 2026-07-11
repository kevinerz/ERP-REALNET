<?php
session_start();
// Cek apakah user sudah login atau belum
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'templates/header.php';

// --- Logika untuk menandai sudah bayar (tidak berubah) ---
if (isset($_GET['action']) && $_GET['action'] == 'bayar' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $tanggal_sekarang = date('Y-m-d H:i:s');
    $stmt = $conn->prepare("UPDATE keu_pengajuan_pembelian_aset SET status_pembayaran = 'Sudah Bayar', tanggal_pembayaran = ? WHERE id = ?");
    $stmt->bind_param("si", $tanggal_sekarang, $id);
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Status pembayaran berhasil diperbarui.</div>";
    }
}

// Ambil semua data pengajuan, utamakan yang belum bayar (tidak berubah)
$result = $conn->query("SELECT * FROM keu_pengajuan_pembelian_aset ORDER BY FIELD(status_pembayaran, 'Belum Bayar', 'Sudah Bayar'), tanggal_pengajuan DESC");
?>

<h1><i data-lucide="landmark"></i> Pembayaran Aset</h1>
<p>Halaman ini menampilkan semua pengajuan aset untuk diproses pembayarannya.</p>

<div class="table-container mt-4">
    <h2>Daftar Pengajuan Aset</h2>
    <div class="table-responsive">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>Tgl Pengajuan</th>
                    <th>Pengaju</th>
                    <th>Barang</th>
                    <th>Keterangan</th> <th class="text-end">Total Harga</th>
                    <th class="text-center">Status Bayar</th>
                    <th class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="<?= $row['status_pembayaran'] == 'Belum Bayar' ? 'table-warning' : '' ?>">
                        <td><?= date("d M Y", strtotime($row['tanggal_pengajuan'])) ?></td>
                        <td><b><?= htmlspecialchars($row['nama_pengaju']) ?></b><br><small><?= htmlspecialchars($row['divisi_pengaju']) ?></small></td>
                        
                        <td>
                            <b><?= htmlspecialchars($row['nama_barang']) ?></b>
                            <br>
                            <small><?= $row['jumlah'] ?> unit @ Rp <?= number_format($row['harga_satuan']) ?></small>
                        </td>
                        <td>
                            <?= nl2br(htmlspecialchars($row['keterangan'])) ?>
                        </td>
                        <td class="text-end">Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                        <td class="text-center"><span class="badge <?= $row['status_pembayaran'] == 'Sudah Bayar' ? 'bg-success' : 'bg-danger' ?>"><?= $row['status_pembayaran'] ?></span></td>
                        <td class="text-center">
                            <?php if ($row['status_pembayaran'] == 'Belum Bayar'): ?>
                                <a href="pembayaran_aset.php?action=bayar&id=<?= $row['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Anda yakin akan memproses pembayaran ini?')">Tandai Sudah Bayar</a>
                            <?php else: ?>
                                <a href="cetak_pembayaran_aset.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm" target="_blank">
                                    <i data-lucide="printer"></i> Cetak Bukti
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'templates/footer.php'; ?>