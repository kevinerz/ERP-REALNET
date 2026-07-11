<?php
session_start();
// Cek apakah user sudah login atau belum
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Memuat header FMS
require_once 'templates/header.php';

// ATURAN WAJIB
$id_paket_valid = [25, 28, 31, 32];
$paket_in_clause = implode(',', $id_paket_valid);

// Ambil daftar SEMUA PIC (marketing) yang ada untuk mengisi dropdown
$daftar_pic = [];
$query_pic = $conn_pasang->query("SELECT DISTINCT marketing FROM pemasangan WHERE marketing IS NOT NULL AND marketing != '' ORDER BY marketing ASC");
while ($pic_row = $query_pic->fetch_assoc()) {
    $daftar_pic[] = $pic_row['marketing'];
}

// Logika untuk filter tabel di bawah (tidak berubah)
$paket_filter = isset($_GET['paket_filter']) ? (int)$_GET['paket_filter'] : 0;
// ... sisa logika filter & paginasi yang sudah ada ...
// (Untuk mempersingkat, saya tidak tampilkan lagi di sini, cukup salin-tempel seluruh file)
?>

<h1><i class="bi bi-person-check-fill"></i> Fee PIC</h1>

<div class="form-card bg-light border-primary">
    <h2 class="form-title mb-3"><i class="bi bi-printer"></i> Buat Laporan Fee Bulanan</h2>
    <form action="cetak_fee_pic.php" method="GET" target="_blank" class="row gx-3 gy-2 align-items-end">
        <div class="col-md-6">
            <label class="form-label" for="pic_laporan"><b>Pilih PIC</b></label>
            <select name="pic_nama" id="pic_laporan" class="form-select" required>
                <option value="">-- Pilih Nama PIC --</option>
                <?php foreach ($daftar_pic as $pic): ?>
                    <option value="<?= htmlspecialchars($pic) ?>"><?= htmlspecialchars($pic) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="periode_laporan"><b>Pilih Periode Laporan</b></label>
            <input type="month" id="periode_laporan" name="periode" class="form-control" value="<?= date('Y-m') ?>" required>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-success w-100"><i class="bi bi-file-earmark-pdf"></i> Buat PDF</button>
        </div>
    </form>
</div>
<h2 class="mt-4">Daftar Pemasangan (Filter per Paket)</h2>
<div class="form-card">
    <form method="get" class="row gx-3 gy-2 align-items-end">
        <div class="col-md-10">
            <label class="form-label" for="paketFilter"><b>Filter Berdasarkan Paket</b></label>
            <select name="paket_filter" id="paketFilter" class="form-select">
                <option value="0">-- Tampilkan Semua Paket Khusus --</option>
                <?php 
                    $daftar_paket_filter = [];
                    $query_paket_filter = $conn_bbm->query("SELECT id_paket, nama_paket FROM paket WHERE id_paket IN ($paket_in_clause) ORDER BY nama_paket ASC");
                    while($paket_row_filter = $query_paket_filter->fetch_assoc()){
                        $daftar_paket_filter[] = $paket_row_filter;
                    }
                ?>
                <?php foreach ($daftar_paket_filter as $paket): ?>
                <option value="<?= $paket['id_paket'] ?>" <?= ($paket_filter == $paket['id_paket']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($paket['nama_paket']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Filter</button>
        </div>
    </form>
</div>

<div class="table-container mt-4">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>POP</th>
                    <th>Paket</th>
                    <th>Alamat</th>
                    <th>Telepon</th>
                    <th>Marketing/PIC</th>
                    </tr>
            </thead>
            <tbody>
            <?php 
                // Logika query untuk tabel ini tidak berubah, kita tampilkan saja di sini agar lengkap
                $where = "status IN ('selesai','on') AND paket IN ($paket_in_clause)";
                if ($paket_filter > 0) { $where .= " AND paket = " . $paket_filter; }
                $sql = "SELECT * FROM pemasangan WHERE $where ORDER BY tanggal DESC";
                $result = $conn_pasang->query($sql);
                $paket_map = [];
                foreach ($daftar_paket_filter as $paket) { $paket_map[$paket['id_paket']] = $paket['nama_paket']; }
                $no = 1;
            ?>
            <?php if (!$result || $result->num_rows === 0): ?>
                <tr><td colspan="7" class="text-center p-4 text-muted">Data tidak ditemukan.</td></tr>
            <?php else: while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nama'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['pop'] ?? '-') ?></td>
                    <td><span class="badge bg-secondary"><?= htmlspecialchars($paket_map[$row['paket']] ?? 'N/A') ?></span></td>
                    <td><?= htmlspecialchars($row['alamat'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['telp'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['marketing'] ?? '-') ?></td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
// Menutup koneksi dan memuat footer
$conn_pasang->close();
$conn_bbm->close();
require_once 'templates/footer.php';
?>