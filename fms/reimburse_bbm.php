<?php
session_start();
// Cek apakah user sudah login atau belum
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Memuat header FMS (termasuk koneksi DB)
require_once 'templates/header.php';

// Filter Periode - DEFAULT BULAN INI
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01');
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');
$periode_cepat = $_GET['periode_cepat'] ?? '';

// Handle periode cepat
if ($periode_cepat) {
    switch ($periode_cepat) {
        case 'periode1_bulanini':
            $tanggal_awal = date('Y-m-01');
            $tanggal_akhir = date('Y-m-15');
            break;
        case 'periode2_bulanini':
            $tanggal_awal = date('Y-m-16');
            $tanggal_akhir = date('Y-m-t');
            break;
        case 'periode1_bulanlalu':
            $last_month = new DateTime('first day of last month');
            $tanggal_awal = $last_month->format('Y-m-01');
            $tanggal_akhir = $last_month->format('Y-m-15');
            break;
        case 'periode2_bulanlalu':
            $last_month = new DateTime('first day of last month');
            $tanggal_awal = $last_month->format('Y-m-16');
            $tanggal_akhir = $last_month->format('Y-m-t');
            break;
        case 'bulan_ini':
            $tanggal_awal = date('Y-m-01');
            $tanggal_akhir = date('Y-m-t');
            break;
        case 'bulan_lalu':
            $last_month = new DateTime('first day of last month');
            $tanggal_awal = $last_month->format('Y-m-01');
            $tanggal_akhir = $last_month->format('Y-m-t');
            break;
        case 'semua':
            $tanggal_awal = '';
            $tanggal_akhir = '';
            break;
    }
}

// Build WHERE clause
$where_conditions = [];
if ($tanggal_awal && $tanggal_akhir) {
    $awal = $conn_bbm->real_escape_string($tanggal_awal);
    $akhir = $conn_bbm->real_escape_string($tanggal_akhir);
    $where_conditions[] = "tanggal BETWEEN '$awal' AND '$akhir'";
}
$where = !empty($where_conditions) ? implode(' AND ', $where_conditions) : '1';

// Query untuk data utama
$query = "SELECT id, nama_pengaju, tanggal, tujuan, liter, catatan, foto_nota
          FROM keu_reimburse_bbm 
          WHERE $where 
          ORDER BY tanggal DESC";
$data = $conn_bbm->query($query);

// Query untuk statistik
$stats_query = "SELECT 
    COUNT(*) as total_pengajuan,
    SUM(liter) as total_liter,
    COUNT(DISTINCT nama_pengaju) as total_pengaju
    FROM keu_reimburse_bbm 
    WHERE $where";
$stats_result = $conn_bbm->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Query untuk top 5 pengaju (berdasarkan jumlah pengajuan)
$top_pengaju_query = "SELECT 
    nama_pengaju,
    COUNT(*) as jumlah_pengajuan,
    SUM(liter) as total_liter
    FROM keu_reimburse_bbm 
    WHERE $where
    GROUP BY nama_pengaju
    ORDER BY jumlah_pengajuan DESC
    LIMIT 5";
$top_pengaju = $conn_bbm->query($top_pengaju_query);
?>

<style>
.stat-card {
    border: none;
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgb(0 0 0 / 0.08);
    transition: all 0.3s ease;
    background: white;
    padding: 1.25rem;
    margin-bottom: 1rem;
}
.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgb(0 0 0 / 0.12);
}
.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}
.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0.5rem 0 0.25rem 0;
}
.stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}
.top-pengaju-item {
    padding: 0.75rem;
    border-bottom: 1px solid #e5e7eb;
    transition: background-color 0.2s ease;
}
.top-pengaju-item:last-child {
    border-bottom: none;
}
.top-pengaju-item:hover {
    background-color: #f9fafb;
}
.pengaju-rank {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #16a085;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}
.btn-periode {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    border-radius: 0.5rem;
    border: 2px solid #e5e7eb;
    background: white;
    color: #374151;
    font-weight: 500;
    transition: all 0.2s ease;
    margin: 0.25rem;
}
.btn-periode:hover {
    border-color: #16a085;
    background: #f0fdfa;
    color: #16a085;
}
.btn-periode.active {
    border-color: #16a085;
    background: #16a085;
    color: white;
}
</style>

<h1><i data-lucide="fuel"></i> Daftar Reimburse BBM</h1>

<!-- Statistik Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon" style="background-color: #dbeafe; color: #1e40af;">
                    <i data-lucide="file-text"></i>
                </div>
                <div class="ms-3 flex-grow-1">
                    <div class="stat-value"><?= number_format($stats['total_pengajuan'] ?? 0) ?></div>
                    <div class="stat-label">Total Pengajuan</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon" style="background-color: #fef3c7; color: #92400e;">
                    <i data-lucide="droplet"></i>
                </div>
                <div class="ms-3 flex-grow-1">
                    <div class="stat-value"><?= number_format($stats['total_liter'] ?? 0, 1) ?></div>
                    <div class="stat-label">Total Liter</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="d-flex align-items-center">
                <div class="stat-icon" style="background-color: #e9d5ff; color: #6b21a8;">
                    <i data-lucide="users"></i>
                </div>
                <div class="ms-3 flex-grow-1">
                    <div class="stat-value"><?= number_format($stats['total_pengaju'] ?? 0) ?></div>
                    <div class="stat-label">Jumlah Pengaju</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top 5 Pengaju -->
<?php if ($top_pengaju->num_rows > 0): ?>
<div class="form-card mb-4">
    <h3><i data-lucide="trophy"></i> Top 5 Pengaju Terbanyak</h3>
    <?php 
    $rank = 1;
    while ($pengaju = $top_pengaju->fetch_assoc()): 
    ?>
    <div class="top-pengaju-item d-flex align-items-center">
        <div class="pengaju-rank me-3"><?= $rank++ ?></div>
        <div class="flex-grow-1">
            <div class="fw-bold" style="color: #1f2937;"><?= htmlspecialchars($pengaju['nama_pengaju']) ?></div>
            <small class="text-muted"><?= $pengaju['jumlah_pengajuan'] ?> pengajuan</small>
        </div>
        <div class="text-end">
            <div class="fw-bold" style="color: #16a085;"><?= number_format($pengaju['total_liter'], 1) ?> L</div>
        </div>
    </div>
    <?php endwhile; ?>
</div>
<?php endif; ?>

<div class="form-card">
    <!-- Periode Cepat -->
    <div class="mb-3">
        <label class="form-label fw-bold">Periode Cepat:</label>
        <div class="d-flex flex-wrap">
            <button type="button" class="btn-periode <?= $periode_cepat == 'periode1_bulanini' ? 'active' : '' ?>" 
                    onclick="location.href='?periode_cepat=periode1_bulanini'">
                <i data-lucide="calendar"></i> Periode 1 Bulan Ini (1-15)
            </button>
            <button type="button" class="btn-periode <?= $periode_cepat == 'periode2_bulanini' ? 'active' : '' ?>" 
                    onclick="location.href='?periode_cepat=periode2_bulanini'">
                <i data-lucide="calendar"></i> Periode 2 Bulan Ini (16-Akhir)
            </button>
            <button type="button" class="btn-periode <?= ($periode_cepat == 'bulan_ini' || (!$periode_cepat && $tanggal_awal == date('Y-m-01') && $tanggal_akhir == date('Y-m-t'))) ? 'active' : '' ?>" 
                    onclick="location.href='?periode_cepat=bulan_ini'">
                <i data-lucide="calendar"></i> Bulan Ini (Full)
            </button>
            <button type="button" class="btn-periode <?= $periode_cepat == 'periode1_bulanlalu' ? 'active' : '' ?>" 
                    onclick="location.href='?periode_cepat=periode1_bulanlalu'">
                <i data-lucide="calendar"></i> Periode 1 Bulan Lalu (1-15)
            </button>
            <button type="button" class="btn-periode <?= $periode_cepat == 'periode2_bulanlalu' ? 'active' : '' ?>" 
                    onclick="location.href='?periode_cepat=periode2_bulanlalu'">
                <i data-lucide="calendar"></i> Periode 2 Bulan Lalu (16-Akhir)
            </button>
            <button type="button" class="btn-periode <?= $periode_cepat == 'bulan_lalu' ? 'active' : '' ?>" 
                    onclick="location.href='?periode_cepat=bulan_lalu'">
                <i data-lucide="calendar"></i> Bulan Lalu (Full)
            </button>
            <button type="button" class="btn-periode <?= $periode_cepat == 'semua' ? 'active' : '' ?>" 
                    onclick="location.href='?periode_cepat=semua'">
                <i data-lucide="calendar"></i> Semua Data
            </button>
        </div>
    </div>

    <!-- Form Custom Periode -->
    <form class="row g-3 align-items-end" method="get" action="">
        <div class="col-md-auto"><label for="tanggal_awal" class="fw-bold">Custom Periode:</label></div>
        <div class="col-md-3"><input type="date" class="form-control" name="tanggal_awal" id="tanggal_awal" value="<?= htmlspecialchars($tanggal_awal) ?>"></div>
        <div class="col-md-auto">s/d</div>
        <div class="col-md-3"><input type="date" class="form-control" name="tanggal_akhir" id="tanggal_akhir" value="<?= htmlspecialchars($tanggal_akhir) ?>"></div>
        <div class="col-md-auto"><button type="submit" class="btn btn-primary"><i data-lucide="search"></i> Tampilkan</button></div>
    </form>

    <?php if ($tanggal_awal && $tanggal_akhir): ?>
    <div class="alert alert-info mt-3 mb-0">
        <i data-lucide="info"></i> Menampilkan data periode: <strong><?= date('d M Y', strtotime($tanggal_awal)) ?></strong> s/d <strong><?= date('d M Y', strtotime($tanggal_akhir)) ?></strong>
    </div>
    <?php endif; ?>

    <hr>
    <div class="d-flex justify-content-end gap-2">
        <a href="arsip_reimburse.php" class="btn btn-secondary"><i data-lucide="archive"></i> Arsip Reimburse</a>
        <a href="form_reimburse.php" class="btn btn-success"><i data-lucide="plus-circle"></i> Tambah Reimburse</a>
    </div>
</div>

<div class="table-container">
    <h2>Data Pengajuan Reimburse</h2>
    <div class="table-responsive">
        <table class="table table-hover table-striped align-middle">
            <thead>
                <tr>
                    <th>No</th> 
                    <th>Nama</th> 
                    <th>Tanggal</th> 
                    <th>Tujuan</th> 
                    <th>Liter</th>
                    <th>Catatan</th>
                    <th class="text-center">Nota</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($data && $data->num_rows > 0): 
                    $no = 1; 
                    while ($row = $data->fetch_assoc()): 
                ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['nama_pengaju']) ?></td>
                    <td><?= date("d/m/Y", strtotime($row['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($row['tujuan']) ?></td>
                    <td><?= htmlspecialchars($row['liter']) ?> L</td>
                    <td style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($row['catatan'])) ?></td>
                    <td class="text-center">
                        <?php
                        $nama_file_nota = basename($row['foto_nota']);
                        $path_untuk_cek_file = '../keuangan/uploads/nota/' . $nama_file_nota;
                        $path_untuk_url_gambar = 'https://datarealsolution.net/keuangan/uploads/nota/' . $nama_file_nota;
                        if (!empty($row['foto_nota']) && file_exists($path_untuk_cek_file)): ?>
                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#notaModal" onclick="setNotaImage('<?= $path_untuk_url_gambar ?>')">
                                <i data-lucide="image"></i> Lihat
                            </button>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="7" class="text-center p-4 text-muted">
                        <i data-lucide="inbox"></i><br>
                        Tidak ada data reimburse untuk periode ini.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="notaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i data-lucide="receipt"></i> Foto Nota BBM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="notaImage" src="" alt="Nota BBM" style="max-width: 100%; border-radius: 0.5rem;">
            </div>
        </div>
    </div>
</div>

<script>
function setNotaImage(src) { 
    document.getElementById('notaImage').src = src; 
}
</script>

<?php
$conn_bbm->close();
require_once 'templates/footer.php';
?>