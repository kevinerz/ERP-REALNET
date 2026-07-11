<?php
session_start();
// Cek apakah user sudah login atau belum
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Memuat header FMS (otomatis memuat koneksi DB, termasuk $conn_bbm untuk umumdata)
require_once 'templates/header.php';

// =========================================================================
// MENGAMBIL DATA ASLI DARI SESSION (MENGGANTIKAN SIMULASI)
// =========================================================================
$nama_pengguna_login = $_SESSION['nama'];
$divisi_login        = $_SESSION['divisi'];
$id_karyawan_login   = $_SESSION['user_id'];
// =========================================================================


// Daftar divisi yang memiliki hak untuk approval dan melihat semua kasbon
$approver_divisi = ['Leader Area', 'SPV Teknis', 'Manager', 'Admin', 'Finance'];

// Logika untuk mengambil data kasbon berdasarkan role/divisi yang login
// Semua query sekarang menggunakan koneksi $conn_bbm
if (in_array($divisi_login, $approver_divisi)) {
    // Jika user adalah approver, tampilkan semua kasbon
    $stmt = $conn_bbm->prepare("
        SELECT k.*, u.nama, u.divisi 
        FROM keu_kasbon k 
        JOIN hr_karyawan u ON k.id_karyawan = u.id 
        ORDER BY k.tanggal_dibuat DESC
    ");
} else {
    // Jika bukan, tampilkan hanya kasbon milik sendiri
    $stmt = $conn_bbm->prepare("
        SELECT k.*, u.nama, u.divisi 
        FROM keu_kasbon k 
        JOIN hr_karyawan u ON k.id_karyawan = u.id 
        WHERE k.id_karyawan = ? 
        ORDER BY k.tanggal_dibuat DESC
    ");
    $stmt->bind_param("i", $id_karyawan_login);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<h1><i data-lucide="wallet"></i> Daftar Pengajuan Kasbon</h1>
<p class="text-muted">Menampilkan data sebagai: <b><?= htmlspecialchars($nama_pengguna_login) ?></b> (Divisi: <?= htmlspecialchars($divisi_login) ?>)</p>

<div class="form-card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <a href="../kasbon/form_kasbon.php" class="btn btn-primary"><i data-lucide="plus-circle" class="me-2"></i>Ajukan Kasbon Baru</a>
        <div>
            <?php if (in_array($divisi_login, $approver_divisi)): ?>
                <a href="approval_kasbon.php" class="btn btn-primary"><i data-lucide="check-square" class="me-2"></i>Halaman Approval</a>
            <?php endif; ?>
             <a href="https://datarealsolution.net/cetak_kasbon.php" class="btn btn-outline-danger" target="_blank"><i data-lucide="printer" class="me-2"></i>Cetak Laporan</a>
        </div>
    </div>
</div>

<div class="table-container mt-4">
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr class="text-center">
                    <th>No</th>
                    <th>Nama</th>
                    <th>Divisi</th>
                    <th>Tanggal</th>
                    <th>Jumlah</th>
                    <th>Keperluan</th>
                    <th>Status</th>
                    <th>Waktu Dibuat</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): $no = 1; while ($row = $result->fetch_assoc()): ?>
                <?php
                    // Logika pewarnaan badge status
                    $status = strtolower($row['status']);
                    $badge_class = 'bg-secondary';
                    if ($status === 'ditolak') {
                        $badge_class = 'bg-danger';
                    } elseif ($status === 'selesai') {
                        $badge_class = 'bg-success';
                    } elseif ($status === 'disetujui') {
                         $badge_class = 'bg-primary';
                    } else { // 'Menunggu Persetujuan'
                        $badge_class = 'bg-warning text-dark';
                    }
                ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nama']); ?></td>
                    <td><?= htmlspecialchars($row['divisi']); ?></td>
                    <td class="text-center"><?= date("d M Y", strtotime($row['tanggal'])); ?></td>
                    <td class="text-end">Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></td>
                    <td><?= htmlspecialchars($row['keperluan']); ?></td>
                    <td class="text-center"><span class="badge <?= $badge_class ?>"><?= strtoupper($row['status']) ?></span></td>
                    <td class="text-center"><?= date("d M Y, H:i", strtotime($row['tanggal_dibuat'])); ?></td>
                    <td class="text-center">
                        <?php if ($row['status'] === 'selesai'): ?>
                            <a href="https://datarealsolution.net/cetak_surat_kasbon.php?id=<?= $row['id'] ?>" class="btn btn-outline-secondary btn-sm"><i data-lucide="file-text"></i> Surat</a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="9" class="text-center text-muted p-4">Belum ada data kasbon.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Menutup koneksi yang dibuka di config.php
$conn_bbm->close();

// Memuat footer FMS
require_once 'templates/footer.php';
?>