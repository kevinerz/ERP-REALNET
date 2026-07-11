<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "u272457353_kevinsamsung99", "Admionkevin99", "u272457353_umumdata");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Filter Periode (optional)
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';
$where = "1";
if ($tanggal_awal && $tanggal_akhir) {
    $awal = $conn->real_escape_string($tanggal_awal);
    $akhir = $conn->real_escape_string($tanggal_akhir);
    $where = "tanggal BETWEEN '$awal' AND '$akhir'";
}

// Query data arsip: status_keuangan = Disetujui
$data = $conn->query("SELECT * FROM reimburse_bbm WHERE $where AND status_keuangan = 'Disetujui' ORDER BY tanggal DESC");

// Judul halaman
$title = "Arsip Reimburse BBM";

// Include header dan navbar

// Ambil divisi user jika perlu untuk validasi tombol (biasanya di navbar.php atau session)
$divisi = strtolower($_SESSION['divisi'] ?? '');
?>

<div class="container my-5">
    <h2 class="text-center mb-4">Arsip Reimburse BBM (Sudah Disetujui Finance)</h2>

    <!-- Form filter periode (sama persis dengan list_reimburse.php) -->
    <form class="row g-2 align-items-center mb-4" method="get" action="">
        <div class="col-auto">
            <label for="tanggal_awal" class="col-form-label">Periode:</label>
        </div>
        <div class="col-auto">
            <input type="date" class="form-control" name="tanggal_awal" id="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>" />
        </div>
        <div class="col-auto">
            <span class="col-form-label">s/d</span>
        </div>
        <div class="col-auto">
            <input type="date" class="form-control" name="tanggal_akhir" id="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>" />
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            <button type="submit" class="btn btn-secondary">Tampilkan</button>
            <?php
            $url_cetak = "cetak_reimburse.php";
            if ($tanggal_awal && $tanggal_akhir) {
                $url_cetak .= "?tanggal_awal=" . urlencode($tanggal_awal) . "&tanggal_akhir=" . urlencode($tanggal_akhir);
            }
            ?>
            <a href="<?= $url_cetak ?>" target="_blank" class="btn btn-success">🧾 Cetak PDF</a>
        </div>
    </form>

    <div class="mb-3 text-end">
        <a href="../list_reimburse.php" class="btn btn-outline-primary">⬅️ Kembali ke Daftar Reimburse</a>
    </div>

    <?php if ($data->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Tanggal</th>
                    <th>Tujuan</th>
                    <th>Liter</th>
                    <th>Total</th>
                    <th>Catatan</th>
                    <th>Nota</th>
                    <th class="text-center">SPV Teknis</th>
                    <th class="text-center">Manager</th>
                    <th class="text-center">Admin</th>
                    <th class="text-center">Finance</th>
                </tr>
            </thead>
            <tbody>
                <?php $no=1; while ($row = $data->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nama_pengaju']) ?></td>
                    <td><?= htmlspecialchars($row['tanggal']) ?></td>
                    <td><?= htmlspecialchars($row['tujuan']) ?></td>
                    <td><?= htmlspecialchars($row['liter']) ?></td>
                    <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                    <td style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($row['catatan'])) ?></td>
                    <td class="text-center">
                        <?php
                        $notaPath = 'keuangan/uploads/nota/' . basename($row['foto_nota']);
                        if (!empty($row['foto_nota']) && file_exists($notaPath)): ?>
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#notaModal" onclick="setNotaImage('<?= $notaPath ?>')">Lihat Nota</button>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= $row['status_spv_teknis'] == 'Disetujui' ? '✅' : '❌'; ?></td>
                    <td class="text-center"><?= $row['status_direktur'] == 'Disetujui' ? '✅' : '❌'; ?></td>
                    <td class="text-center"><?= $row['status_spv_admin'] == 'Disetujui' ? '✅' : '❌'; ?></td>
                    <td class="text-center"><?= $row['status_keuangan'] == 'Disetujui' ? '✅' : '❌'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p class="text-center text-muted mb-0">Belum ada data arsip reimburse.</p>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<?php $conn->close(); ?>
