<?php
require_once __DIR__ . '/config/database.php';
session_start();

$dashboard_divisi = ['Admin', 'IT', 'Manager', 'SPV Teknis', 'Finance'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['divisi'], $dashboard_divisi)) {
    header("Location: login.php");
    exit;
}

$conn = getErpDbConnection();
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Filter Periode - DEFAULT BULAN INI
$tanggal_awal = $_GET['tanggal_awal'] ?? date('Y-m-01'); // Default: awal bulan ini
$tanggal_akhir = $_GET['tanggal_akhir'] ?? date('Y-m-t'); // Default: akhir bulan ini
$periode_cepat = $_GET['periode_cepat'] ?? '';

// Handle periode cepat
if ($periode_cepat) {
    $today = new DateTime();
    
    switch ($periode_cepat) {
        case 'periode1_bulanini':
            $tanggal_awal = date('Y-m-01');
            $tanggal_akhir = date('Y-m-15');
            break;
        case 'periode2_bulanini':
            $tanggal_awal = date('Y-m-16');
            $tanggal_akhir = date('Y-m-t'); // last day of month
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
    $awal = $conn->real_escape_string($tanggal_awal);
    $akhir = $conn->real_escape_string($tanggal_akhir);
    $where_conditions[] = "tanggal BETWEEN '$awal' AND '$akhir'";
}

$where = !empty($where_conditions) ? implode(' AND ', $where_conditions) : '1';

// Query untuk data utama
$query = "SELECT id, nama_pengaju, tanggal, tujuan, liter, total, catatan, foto_nota 
          FROM keu_reimburse_bbm 
          WHERE $where 
          ORDER BY tanggal DESC";
$data = $conn->query($query);

// Query untuk statistik
$stats_query = "SELECT 
    COUNT(*) as total_pengajuan,
    SUM(total) as total_biaya,
    SUM(liter) as total_liter,
    COUNT(DISTINCT nama_pengaju) as total_pengaju
    FROM keu_reimburse_bbm 
    WHERE $where";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Query untuk top 5 pengaju
$top_pengaju_query = "SELECT 
    nama_pengaju,
    COUNT(*) as jumlah_pengajuan,
    SUM(total) as total_biaya
    FROM keu_reimburse_bbm 
    WHERE $where
    GROUP BY nama_pengaju
    ORDER BY total_biaya DESC
    LIMIT 5";
$top_pengaju = $conn->query($top_pengaju_query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>List Reimburse BBM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f9fafb;
            color: #333;
            min-height: 100vh;
        }
        h2 {
            font-weight: 600;
            letter-spacing: 0.03em;
            color: #1f2937;
        }
        .table-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 8px 24px rgb(0 0 0 / 0.05);
            transition: box-shadow 0.3s ease;
        }
        .table-card:hover {
            box-shadow: 0 12px 40px rgb(0 0 0 / 0.1);
        }
        .table-card .card-header {
            font-weight: 600;
            font-size: 1.125rem;
            background: #16a085;
            color: #fff;
            border-radius: 0.75rem 0.75rem 0 0;
            border-bottom: none;
        }

        .btn-primary {
             background-color: #16a085;
             border-color: #16a085;
        }
        .btn-primary:hover {
             background-color: #138a71;
             border-color: #138a71;
        }
        .btn-success {
             background-color: #16a085;
             border-color: #16a085;
        }
        .btn-success:hover {
             background-color: #138a71;
             border-color: #138a71;
        }

        /* Statistik Card */
        .stat-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgb(0 0 0 / 0.08);
            transition: all 0.3s ease;
            background: white;
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

        /* Top Pengaju List */
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

        /* Periode Button Group */
        .periode-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
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

        table {
            border-collapse: separate;
            border-spacing: 0 8px;
            width: 100%;
        }
        thead th {
            border-bottom: none;
            background: transparent !important;
            color: #475569;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 0.78rem;
            padding: 0.3rem 0.5rem;
            white-space: nowrap;
        }
        tbody tr {
            background: white;
            box-shadow: 0 1px 4px rgb(0 0 0 / 0.08);
            border-radius: 0.4rem;
            transition: transform 0.15s ease;
        }
        tbody tr:hover {
            transform: translateY(-1.5px);
            box-shadow: 0 3px 10px rgb(0 0 0 / 0.12);
        }
        tbody td {
            vertical-align: middle;
            padding: 0.6rem 0.8rem;
            font-size: 0.85rem;
            color: #334155;
            white-space: normal;
        }
        tbody td .btn-sm {
            padding: 0.25rem 0.6rem;
            font-weight: 600;
            font-size: 0.78rem;
            border-radius: 0.35rem;
            transition: background-color 0.25s ease;
        }
        tbody td .btn-sm:hover {
            filter: brightness(90%);
        }

        @media (max-width: 1200px) {
            thead th, tbody td { white-space: normal; }
        }
        @media (max-width: 992px) {
            form .btn { min-width: auto; padding: 0.375rem 0.75rem; font-size: 0.9rem; }
            tbody td { padding: 0.5rem 0.5rem; font-size: 0.85rem; }
        }
        @media (max-width: 768px) {
            h2 { font-size: 1.5rem; }
            .table-card { box-shadow: none; border-radius: 0.5rem; }
            thead th { font-size: 0.75rem; }
            tbody td { font-size: 0.75rem; padding: 0.4rem 0.4rem; }
            form .btn { width: 100%; margin-top: 0.5rem; }
            form .col-auto.d-flex { flex-direction: column; gap: 0.5rem; }
            .stat-value { font-size: 1.5rem; }
            .btn-periode { font-size: 0.75rem; padding: 0.4rem 0.8rem; }
        }
        .modal-body img {
            max-width: 100%;
            height: auto;
            max-height: 70vh;
            border-radius: 0.5rem;
            box-shadow: 0 4px 14px rgb(0 0 0 / 0.2);
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container my-5">
    <h2 class="text-center mb-4 fw-bold" style="color: #16a085;">
        <i class="bi bi-fuel-pump"></i> Daftar Reimburse BBM
    </h2>

    <!-- Statistik Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon" style="background-color: #dbeafe; color: #1e40af;">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div class="ms-3 flex-grow-1">
                        <div class="stat-value"><?= number_format($stats['total_pengajuan'] ?? 0) ?></div>
                        <div class="stat-label">Total Pengajuan</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon" style="background-color: #d1fae5; color: #065f46;">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div class="ms-3 flex-grow-1">
                        <div class="stat-value" style="font-size: 1.3rem;">Rp <?= number_format($stats['total_biaya'] ?? 0, 0, ',', '.') ?></div>
                        <div class="stat-label">Total Biaya</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon" style="background-color: #fef3c7; color: #92400e;">
                        <i class="bi bi-droplet-fill"></i>
                    </div>
                    <div class="ms-3 flex-grow-1">
                        <div class="stat-value"><?= number_format($stats['total_liter'] ?? 0, 1) ?></div>
                        <div class="stat-label">Total Liter</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6">
            <div class="card stat-card p-3">
                <div class="d-flex align-items-center">
                    <div class="stat-icon" style="background-color: #e9d5ff; color: #6b21a8;">
                        <i class="bi bi-people"></i>
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
    <div class="card table-card mb-4">
        <div class="card-header">
            <i class="bi bi-trophy"></i> Top 5 Pengaju Terbanyak
        </div>
        <div class="card-body p-3">
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
                    <div class="fw-bold" style="color: #16a085;">Rp <?= number_format($pengaju['total_biaya'], 0, ',', '.') ?></div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card table-card">
        <div class="card-header">
            Data Pengajuan Reimburse
        </div>
        <div class="card-body p-3 p-md-4">
            <!-- Periode Cepat -->
            <div class="periode-btn-group">
                <button type="button" class="btn-periode <?= $periode_cepat == 'periode1_bulanini' ? 'active' : '' ?>" 
                        onclick="location.href='?periode_cepat=periode1_bulanini'">
                    <i class="bi bi-calendar-week"></i> Periode 1 Bulan Ini (1-15)
                </button>
                <button type="button" class="btn-periode <?= $periode_cepat == 'periode2_bulanini' ? 'active' : '' ?>" 
                        onclick="location.href='?periode_cepat=periode2_bulanini'">
                    <i class="bi bi-calendar-week"></i> Periode 2 Bulan Ini (16-Akhir)
                </button>
                <button type="button" class="btn-periode <?= ($periode_cepat == 'bulan_ini' || (!$periode_cepat && $tanggal_awal == date('Y-m-01') && $tanggal_akhir == date('Y-m-t'))) ? 'active' : '' ?>" 
                        onclick="location.href='?periode_cepat=bulan_ini'">
                    <i class="bi bi-calendar-month"></i> Bulan Ini (Full)
                </button>
                <button type="button" class="btn-periode <?= $periode_cepat == 'periode1_bulanlalu' ? 'active' : '' ?>" 
                        onclick="location.href='?periode_cepat=periode1_bulanlalu'">
                    <i class="bi bi-calendar-minus"></i> Periode 1 Bulan Lalu (1-15)
                </button>
                <button type="button" class="btn-periode <?= $periode_cepat == 'periode2_bulanlalu' ? 'active' : '' ?>" 
                        onclick="location.href='?periode_cepat=periode2_bulanlalu'">
                    <i class="bi bi-calendar-minus"></i> Periode 2 Bulan Lalu (16-Akhir)
                </button>
                <button type="button" class="btn-periode <?= $periode_cepat == 'bulan_lalu' ? 'active' : '' ?>" 
                        onclick="location.href='?periode_cepat=bulan_lalu'">
                    <i class="bi bi-calendar-month"></i> Bulan Lalu (Full)
                </button>
                <button type="button" class="btn-periode <?= $periode_cepat == 'semua' ? 'active' : '' ?>" 
                        onclick="location.href='?periode_cepat=semua'">
                    <i class="bi bi-calendar-range"></i> Semua Data
                </button>
            </div>

            <!-- Form Custom Periode -->
            <form class="row g-2 align-items-center mb-4" method="get" action="">
                <div class="col-auto">
                    <label for="tanggal_awal" class="col-form-label fw-semibold">Custom Periode:</label>
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
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-search"></i> Tampilkan</button>
                    <?php
                    $url_cetak = "cetak_reimburse.php";
                    if ($tanggal_awal && $tanggal_akhir) {
                        $url_cetak .= "?tanggal_awal=" . urlencode($tanggal_awal) . "&tanggal_akhir=" . urlencode($tanggal_akhir);
                    }
                    ?>
                    <a href="<?= $url_cetak ?>" target="_blank" class="btn btn-success"><i class="bi bi-printer"></i> Cetak PDF</a>
                </div>
            </form>

            <?php if ($tanggal_awal && $tanggal_akhir): ?>
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle"></i> Menampilkan data periode: <strong><?= date('d M Y', strtotime($tanggal_awal)) ?></strong> s/d <strong><?= date('d M Y', strtotime($tanggal_akhir)) ?></strong>
            </div>
            <?php endif; ?>

            <div class="mb-3 text-end">
                <a href="keuangan/arsip_reimburse.php" class="btn btn-outline-secondary me-2"><i class="bi bi-archive"></i> Arsip Selesai</a>
                <a href="keuangan/form_reimburse.php" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Tambah Reimburse</a>
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
                            <th class="text-center">Nota</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1; 
                        while ($row = $data->fetch_assoc()): 
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><?= htmlspecialchars($row['nama_pengaju']) ?></td>
                            <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($row['tujuan']) ?></td>
                            <td><?= htmlspecialchars($row['liter']) ?> L</td>
                            <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                            <td style="white-space: pre-wrap;"><?= nl2br(htmlspecialchars($row['catatan'])) ?></td>
                            <td class="text-center">
                                <?php
                                $notaPath = 'keuangan/uploads/nota/' . basename($row['foto_nota']);
                                if (!empty($row['foto_nota']) && file_exists($notaPath)): ?>
                                    <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#notaModal" onclick="setNotaImage('<?= htmlspecialchars($notaPath) ?>')">
                                        <i class="bi bi-image"></i> Lihat
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="alert alert-info text-center mb-0">
                    <i class="bi bi-info-circle"></i> Belum ada data reimburse untuk periode ini.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nota -->
<div class="modal fade" id="notaModal" tabindex="-1" aria-labelledby="notaModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-4 shadow-lg">
      <div class="modal-header border-bottom-0">
        <h5 class="modal-title" id="notaModalLabel"><i class="bi bi-receipt"></i> Foto Nota BBM</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body text-center">
        <img id="notaImage" src="" alt="Nota BBM" />
      </div>
    </div>
  </div>
</div>

<script>
function setNotaImage(src) {
    document.getElementById('notaImage').src = src;
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>