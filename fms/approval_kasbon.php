<?php
// Menggunakan header FMS yang sudah berisi session_start, login check, dan koneksi DB
require_once 'templates/header.php';

// Ambil divisi asli dari user yang login
$divisi_login = $_SESSION['divisi'];

// Peta status untuk approval chain (Logika inti Anda, kita pertahankan)
$status_map = [
    'Leader Area'      => 'menunggu persetujuan',
    'SPV Teknis'       => 'leader_area',
    'Manager'          => 'spv_teknis',
    'Admin'            => 'manager', // Asumsi Admin menyetujui setelah Manager
    'Finance'          => 'admin',   // Asumsi Finance menyetujui setelah Admin
];

// Peta untuk status berikutnya setelah disetujui
$next_status = [
    'menunggu persetujuan' => 'leader_area',
    'leader_area'         => 'spv_teknis',
    'spv_teknis'          => 'manager',
    'manager'             => 'admin',
    'admin'               => 'finance',
    'finance'             => 'selesai',
];

// Cek apakah divisi user yang login ada di dalam peta approval
if (!isset($status_map[$divisi_login])) {
    // Jika tidak punya hak, tampilkan pesan dan keluar
    echo "<h1>Akses Ditolak</h1><p>Anda tidak memiliki hak untuk mengakses halaman approval.</p>";
    require_once 'templates/footer.php';
    exit;
}

// Tentukan status kasbon yang harus ditampilkan untuk divisi ini
$status_filter = $status_map[$divisi_login];

// Proses form approval/penolakan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kasbon_id = $_POST['kasbon_id'];
    $aksi = $_POST['aksi'];
    $catatan_tambahan = $_POST['catatan'] ?? '';
    $nama_approver = $_SESSION['nama']; // Ambil nama approver dari session

    if ($aksi == 'setujui') {
        $new_status = $next_status[$status_filter] ?? 'selesai';
        $catatan_text = "\nDisetujui oleh $nama_approver ($divisi_login): " . $catatan_tambahan;
        $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Kasbon berhasil disetujui.'];
    } else {
        $new_status = 'ditolak';
        $catatan_text = "\nDitolak oleh $nama_approver ($divisi_login): " . $catatan_tambahan;
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Kasbon telah ditolak.'];
    }

    // Update data kasbon di DB umumdata (menggunakan $conn_bbm)
    $stmt = $conn_bbm->prepare("UPDATE keu_kasbon SET status = ?, catatan = CONCAT(IFNULL(catatan,''), ?) WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $catatan_text, $kasbon_id);
    $stmt->execute();

    // Redirect kembali ke halaman ini untuk refresh data
    header('Location: approval_kasbon.php');
    exit;
}

// Ambil data kasbon yang menunggu approval dari divisi ini
$stmt = $conn_bbm->prepare("
    SELECT k.*, u.nama, u.divisi
    FROM keu_kasbon k
    JOIN hr_karyawan u ON k.id_karyawan = u.id
    WHERE k.status = ?
    ORDER BY k.tanggal_dibuat DESC
");
$stmt->bind_param("s", $status_filter);
$stmt->execute();
$result = $stmt->get_result();

// Tampilkan flash message jika ada
if(isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    echo "<div class='alert alert-{$message['type']}'>{$message['text']}</div>";
    unset($_SESSION['flash_message']);
}
?>

<h1><i data-lucide="check-square"></i> Approval Kasbon (Divisi: <?= htmlspecialchars($divisi_login) ?>)</h1>
<p class="text-muted">Halaman ini hanya menampilkan pengajuan kasbon yang menunggu persetujuan dari Anda.</p>

<div class="table-container">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Nama Pengaju</th>
                    <th>Tanggal</th>
                    <th>Jumlah</th>
                    <th>Keperluan & Catatan</th>
                    <th style="width: 25%;">Aksi Anda</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <b><?= htmlspecialchars($row['nama']) ?></b>
                            <br><small><?= htmlspecialchars($row['divisi']) ?></small>
                        </td>
                        <td><?= date("d M Y", strtotime($row['tanggal'])) ?></td>
                        <td><b>Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></b></td>
                        <td>
                            <?= htmlspecialchars($row['keperluan']) ?>
                            <?php if(!empty($row['catatan'])): ?>
                                <hr class="my-1">
                                <small class="text-muted fst-italic">Catatan Histori: <?= nl2br(htmlspecialchars($row['catatan'])) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="post" action="approval_kasbon.php">
                                <input type="hidden" name="kasbon_id" value="<?= $row['id'] ?>">
                                <div class="mb-2">
                                    <textarea name="catatan" class="form-control form-control-sm" placeholder="Tambah catatan..."></textarea>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="submit" name="aksi" value="setujui" class="btn btn-success btn-sm flex-grow-1"><i data-lucide="check"></i> Setujui</button>
                                    <button type="submit" name="aksi" value="tolak" class="btn btn-danger btn-sm flex-grow-1"><i data-lucide="x"></i> Tolak</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted p-5">
                            <i data-lucide="inbox" class="d-block mx-auto mb-2" style="width:48px; height:48px;"></i>
                            Tidak ada pengajuan kasbon yang menunggu persetujuan Anda saat ini.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Menutup koneksi
$conn_bbm->close();
// Memuat footer
require_once 'templates/footer.php';
?>