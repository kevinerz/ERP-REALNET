<?php
require_once __DIR__ . '/config/database.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['nama'])) {
    header('Location: login.php');
    exit;
}
$username = $_SESSION['username'];
$nama     = $_SESSION['nama'];

/* ==========================================
   KONEKSI DATABASE
   ========================================== */
// DB pemasangan
$conn_pemasangan = getErpDbConnection();
if ($conn_pemasangan->connect_error) {
    die("Koneksi gagal (db pemasangan): " . $conn_pemasangan->connect_error);
}

// DB gangguan (tiket_helpdesk)
$conn_gangguan = getErpDbConnection();
if ($conn_gangguan->connect_error) {
    die("Koneksi gagal (db gangguan): " . $conn_gangguan->connect_error);
}

// DB modem / BBM / kasbon (umumdata)
$conn_modem = getErpDbConnection();
if ($conn_modem->connect_error) {
    die("Koneksi gagal (db modem): " . $conn_modem->connect_error);
}

/* ==========================================
   FUNGSI BANTU
   ========================================== */

// Hitung total by status (tanpa filter tanggal) – dipakai untuk gangguan, modem, dll
function getCount($conn, $tabel, $status_col, $status_val) {
    $sql  = "SELECT COUNT(*) AS total FROM $tabel WHERE $status_col = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status_val);
    $stmt->execute();
    $res  = $stmt->get_result();
    $row  = $res->fetch_assoc();
    $stmt->close();
    return $row['total'] ?? 0;
}

// Hitung total by status + tanggal – dipakai untuk pemasangan bulanan
function getCountByStatusAndDate($conn, $table, $status_col, $date_col, $status_val, $start_date, $end_date) {
    $sql  = "SELECT COUNT(*) AS total FROM $table WHERE $status_col = ? AND $date_col BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $status_val, $start_date, $end_date);
    $stmt->execute();
    $res  = $stmt->get_result();
    $row  = $res->fetch_assoc();
    $stmt->close();
    return $row['total'] ?? 0;
}

// Hitung total by status + POP + tanggal – dipakai untuk statistik per POP bulanan
function getCountByPOPAndDate($conn, $table, $status_col, $status_val, $pop_val, $date_col, $start_date, $end_date) {
    $sql  = "SELECT COUNT(*) AS total 
             FROM $table 
             WHERE $status_col = ? AND pop = ? AND $date_col BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $status_val, $pop_val, $start_date, $end_date);
    $stmt->execute();
    $res  = $stmt->get_result();
    $row  = $res->fetch_assoc();
    $stmt->close();
    return $row['total'] ?? 0;
}

// Hitung modem per status
function getModemCount($conn_modem, $status) {
    $sql  = "SELECT COUNT(*) AS total FROM jaringan_modem WHERE status = ?";
    $stmt = $conn_modem->prepare($sql);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $res  = $stmt->get_result();
    $row  = $res->fetch_assoc();
    $stmt->close();
    return $row['total'] ?? 0;
}

// BBM per periode
function getBBMByPeriod($conn, $start_date, $end_date) {
    $stmt = $conn->prepare("SELECT SUM(total) AS total_bbm, SUM(liter) AS total_liter, COUNT(*) AS jumlah_pengajuan
                            FROM keu_reimburse_bbm 
                            WHERE tanggal BETWEEN ? AND ?");
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $stmt->bind_result($total_bbm, $total_liter, $jumlah_pengajuan);
    $stmt->fetch();
    $stmt->close();
    return [
        'total_bbm'        => $total_bbm        ?? 0,
        'total_liter'      => $total_liter      ?? 0,
        'jumlah_pengajuan' => $jumlah_pengajuan ?? 0
    ];
}

/* ==========================================
   FILTER PERIODE (LAPORAN BULAN KE BULAN)
   ========================================== */

$currentYear  = (int)date('Y');
$currentMonth = (int)date('m');

$year  = isset($_GET['year'])  ? (int)$_GET['year']  : $currentYear;
$month = isset($_GET['month']) ? (int)$_GET['month'] : $currentMonth;

// Sanitasi sederhana untuk year/month
if ($month < 1 || $month > 12) { $month = $currentMonth; }
if ($year < $currentYear - 3 || $year > $currentYear + 1) { $year = $currentYear; }

$start_of_month = sprintf('%04d-%02d-01', $year, $month);
$end_of_month   = date('Y-m-t', strtotime($start_of_month)); // tanggal terakhir bulan itu

// Periode pencairan BBM
$periode1_start = sprintf('%04d-%02d-01', $year, $month);
$periode1_end   = sprintf('%04d-%02d-15', $year, $month);
$periode2_start = sprintf('%04d-%02d-16', $year, $month);
$periode2_end   = $end_of_month;

$label_periode  = date('F Y', strtotime($start_of_month));   // contoh: November 2025

// Untuk tombol prev / next bulan
$prevTime = strtotime('-1 month', strtotime($start_of_month));
$nextTime = strtotime('+1 month', strtotime($start_of_month));
$prevYear = (int)date('Y', $prevTime);
$prevMonth= (int)date('m', $prevTime);
$nextYear = (int)date('Y', $nextTime);
$nextMonth= (int)date('m', $nextTime);

/* ==========================================
   STATISTIK PEMASANGAN BULANAN
   ========================================== */
$tablePemasangan = 'pelanggan_instalasi';
$statusCol       = 'status';
$dateCol         = 'tanggal';

// overall bulan ini (semua POP)
$pemasangan_selesai_bln       = getCountByStatusAndDate($conn_pemasangan, $tablePemasangan, $statusCol, $dateCol, 'selesai',        $start_of_month, $end_of_month);
$pemasangan_aktivasi_bln      = getCountByStatusAndDate($conn_pemasangan, $tablePemasangan, $statusCol, $dateCol, 'aktivasi',       $start_of_month, $end_of_month);
$pemasangan_belum_proses_bln  = getCountByStatusAndDate($conn_pemasangan, $tablePemasangan, $statusCol, $dateCol, 'belum diproses', $start_of_month, $end_of_month);
$total_pemasangan_bln         = $pemasangan_selesai_bln + $pemasangan_aktivasi_bln + $pemasangan_belum_proses_bln;

// Fee berbasis pemasangan selesai per bulan
$fee_pasang    = 100000 * $pemasangan_selesai_bln;
$fee_marketing =  50000 * $pemasangan_selesai_bln;

// Statistik pemasangan per POP (bulan ini)
$popList = [
    'mauk'     => 'POP MAUK',
    'rajeg'    => 'POP RAJEG',
    'kemeri'   => 'POP KEMERI',
    'panggang' => 'POP PANGGANG',
];

$popStats = [];
foreach ($popList as $popKey => $popLabel) {
    $selesai      = getCountByPOPAndDate($conn_pemasangan, $tablePemasangan, $statusCol, 'selesai',        $popKey, $dateCol, $start_of_month, $end_of_month);
    $aktivasi     = getCountByPOPAndDate($conn_pemasangan, $tablePemasangan, $statusCol, 'aktivasi',       $popKey, $dateCol, $start_of_month, $end_of_month);
    $belumProses  = getCountByPOPAndDate($conn_pemasangan, $tablePemasangan, $statusCol, 'belum diproses', $popKey, $dateCol, $start_of_month, $end_of_month);
    $total        = $selesai + $aktivasi + $belumProses;

    $popStats[$popKey] = [
        'label'        => $popLabel,
        'selesai'      => $selesai,
        'aktivasi'     => $aktivasi,
        'belum'        => $belumProses,
        'total'        => $total
    ];
}

/* ==========================================
   STATISTIK GANGGUAN (GLOBAL)
   ========================================== */
$gangguan_selesai       = getCount($conn_gangguan, "tiket_gangguan", "status", "selesai");
$gangguan_belum_kerja   = getCount($conn_gangguan, "tiket_gangguan", "status", "belum dikerjakan");
$gangguan_diproses      = getCount($conn_gangguan, "tiket_gangguan", "status", "di proses");

/* ==========================================
   STATISTIK MODEM (GLOBAL)
   ========================================== */
$totalModemTersedia = getModemCount($conn_modem, 'ready');
$totalModemDipasang = getModemCount($conn_modem, 'dipasang');
$totalModemCabutan  = getModemCount($conn_modem, 'cabutan');
$totalModemRusak    = getModemCount($conn_modem, 'rusak');

/* ==========================================
   STATISTIK BBM BERDASARKAN PERIODE
   ========================================== */
// Periode 1 (1-15)
$bbm_periode1 = getBBMByPeriod($conn_modem, $periode1_start, $periode1_end);

// Periode 2 (16-akhir)
$bbm_periode2 = getBBMByPeriod($conn_modem, $periode2_start, $periode2_end);

// Total bulan ini
$bbm_bulan_ini = getBBMByPeriod($conn_modem, $start_of_month, $end_of_month);

// BBM per pengaju untuk bulan ini - DINAMIS dari database
$bbm_per_pengaju_query = "SELECT 
    nama_pengaju,
    COUNT(*) as jumlah_pengajuan,
    SUM(total) as total_biaya,
    SUM(liter) as total_liter
    FROM keu_reimburse_bbm 
    WHERE tanggal BETWEEN ? AND ?
    GROUP BY nama_pengaju
    ORDER BY total_biaya DESC
    LIMIT 10";
$stmt_pengaju = $conn_modem->prepare($bbm_per_pengaju_query);
$stmt_pengaju->bind_param("ss", $start_of_month, $end_of_month);
$stmt_pengaju->execute();
$result_pengaju = $stmt_pengaju->get_result();

$bbm_per_pengaju = [];
$color_palette = ['blue', 'green', 'cyan', 'yellow', 'purple', 'red', 'orange', 'teal', 'pink', 'indigo'];
$color_index = 0;

while ($row_pengaju = $result_pengaju->fetch_assoc()) {
    $bbm_per_pengaju[] = [
        'nama'             => $row_pengaju['nama_pengaju'],
        'total_biaya'      => $row_pengaju['total_biaya'],
        'total_liter'      => $row_pengaju['total_liter'],
        'jumlah_pengajuan' => $row_pengaju['jumlah_pengajuan'],
        'color'            => $color_palette[$color_index % count($color_palette)]
    ];
    $color_index++;
}
$stmt_pengaju->close();

/* ==========================================
   STATISTIK BBM GLOBAL (ALL TIME)
   ========================================== */
// Total nominal & liter
$total_bbm = 0;
$res_bbm   = $conn_modem->query("SELECT SUM(total) AS total_bbm FROM keu_reimburse_bbm");
if ($res_bbm && $row = $res_bbm->fetch_assoc()) {
    $total_bbm = $row['total_bbm'] ?: 0;
}
$total_liter_bbm = 0;
$res_liter_bbm   = $conn_modem->query("SELECT SUM(liter) AS total_liter_bbm FROM keu_reimburse_bbm");
if ($res_liter_bbm && $row = $res_liter_bbm->fetch_assoc()) {
    $total_liter_bbm = $row['total_liter_bbm'] ?: 0;
}

/* ==========================================
   STATISTIK KASBON (GLOBAL)
   ========================================== */
$total_kasbon_selesai = 0;
$resKasbon = $conn_modem->query("SELECT SUM(jumlah) AS total_kasbon_selesai FROM keu_kasbon WHERE status='selesai'");
if ($resKasbon && $row = $resKasbon->fetch_assoc()) {
    $total_kasbon_selesai = $row['total_kasbon_selesai'] ?: 0;
}

/* ==========================================
   QUOTES MOTIVASI
   ========================================== */
$tanggal = date('l, d F Y');
$quotes = [
    "Sukses adalah hasil dari kerja keras, ketekunan, dan belajar dari kegagalan.",
    "Tidak ada keberhasilan tanpa kerja keras dan pengorbanan.",
    "Mulailah hari ini dengan semangat, agar besok penuh prestasi.",
    "Kesempatan tidak datang dua kali, manfaatkan hari ini sebaik-baiknya.",
    "Kerja keras mengalahkan bakat ketika bakat tidak bekerja keras.",
    "Jangan pernah menyerah, keajaiban butuh proses.",
    "Percaya diri adalah langkah awal menuju keberhasilan.",
    "Setiap hari adalah kesempatan baru untuk menjadi lebih baik."
];
$quote = $quotes[array_rand($quotes)];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ERP PT. REAL DATA SOLUSINDO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --rn-primary: #2563eb;
            --rn-accent:  #22c55e;
            --rn-bg:      #eaf3fa;
            --rn-card-bg: #ffffff;
            --rn-border:  #e2e8f0;
            --rn-text:    #0f172a;
        }
        body {
            background: radial-gradient(circle at top left, #eff6ff 0, var(--rn-bg) 40%, #e2f3ff 100%);
        }
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 12px 40px 12px;
        }
        .page-header-card {
            background: linear-gradient(120deg, var(--rn-primary) 0%, var(--rn-accent) 100%);
            color: #fff;
            border-radius: 24px;
            padding: 1.5rem 1.6rem;
            box-shadow: 0 18px 40px rgba(15, 23, 42, .35);
            margin-bottom: 1.5rem;
        }
        .page-title {
            font-size: 1.4rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }
        .page-subtitle {
            font-size: .9rem;
            opacity: .9;
        }
        .greeting-name {
            font-weight: 700;
        }
        .quote-text {
            font-style: italic;
            font-size: .9rem;
            opacity: .9;
        }
        .period-chip {
            background: rgba(255,255,255,.16);
            border-radius: 999px;
            padding: .4rem .9rem;
            display: inline-flex;
            gap: .4rem;
            align-items: center;
            font-size: .82rem;
            margin-top: .35rem;
        }
        .period-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
            background: #bfdbfe;
        }
        .filter-pill {
            background: #f9fafb;
            border-radius: 999px;
            padding: .4rem .75rem;
            border: 1px solid rgba(148,163,184,.5);
            font-size: .8rem;
        }
        .filter-label {
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
        }
        .card-block {
            background: var(--rn-card-bg);
            border-radius: 22px;
            box-shadow: 0 12px 35px rgba(15, 23, 42, .08);
            padding: 1.2rem 1.3rem;
            margin-bottom: 1.2rem;
            border: 1px solid var(--rn-border);
        }
        .card-block h5 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: .7rem;
            color: var(--rn-text);
        }
        .stat-badge {
            font-size: .75rem;
            font-weight: 600;
            border-radius: 999px;
            padding: .25rem .8rem;
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            margin-bottom: .3rem;
        }
        .stat-number {
            font-size: 1.9rem;
            font-weight: 800;
            letter-spacing: .02em;
        }
        .stat-caption {
            font-size: .8rem;
            color: #6b7280;
        }
        .pop-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0,1fr));
            gap: .9rem;
        }
        @media (max-width: 1200px) {
            .pop-grid {
                grid-template-columns: repeat(2, minmax(0,1fr));
            }
        }
        @media (max-width: 768px) {
            .main-container {
                padding-top: 16px;
            }
            .page-header-card {
                padding: 1.2rem 1.1rem;
                border-radius: 20px;
            }
            .pop-grid {
                grid-template-columns: minmax(0,1fr);
            }
        }
        .pop-card {
            background: #f9fafb;
            border-radius: 18px;
            padding: .9rem .9rem .8rem .9rem;
            border: 1px solid #e5e7eb;
        }
        .pop-title {
            font-size: .92rem;
            font-weight: 700;
            margin-bottom: .3rem;
        }
        .pop-total {
            font-size: 1.3rem;
            font-weight: 800;
        }
        .pop-status-row {
            font-size: .78rem;
            display: flex;
            justify-content: space-between;
            margin-top: .15rem;
        }
        .bbm-pengaju-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: .9rem;
        }
        .bbm-card {
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 26px rgba(15,23,42,.06);
            padding: .9rem .9rem .8rem .9rem;
        }
        .bbm-header {
            border-radius: 12px;
            padding: .55rem .7rem;
            color: #fff;
            margin-bottom: .4rem;
            font-size: .78rem;
        }
        .bbm-nama   { font-weight: 700; letter-spacing: .03em; }
        .bbm-amount { font-size: 1.1rem; font-weight: 700; margin-top: .3rem; }
        .bbm-liter  { font-size: .85rem; color: #6b7280; margin-top: .2rem; }
        .bbm-count  { font-size: .72rem; color: #9ca3af; margin-top: .2rem; }
        
        /* Color variants */
        .bbm-blue   { background: #2563eb; }
        .bbm-green  { background: #16a34a; }
        .bbm-cyan   { background: #0ea5e9; }
        .bbm-yellow { background: #facc15; color:#3b2600; }
        .bbm-purple { background: #9333ea; }
        .bbm-red    { background: #dc2626; }
        .bbm-orange { background: #ea580c; }
        .bbm-teal   { background: #14b8a6; }
        .bbm-pink   { background: #ec4899; }
        .bbm-indigo { background: #6366f1; }

        /* Periode Card untuk BBM */
        .periode-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 18px;
            padding: 1.2rem;
            color: white;
            box-shadow: 0 10px 26px rgba(15,23,42,.15);
        }
        .periode-card h6 {
            font-size: .85rem;
            font-weight: 600;
            opacity: .9;
            margin-bottom: .6rem;
        }
        .periode-card .stat-number {
            font-size: 1.6rem;
            color: white;
        }
        .periode-card .stat-caption {
            color: rgba(255,255,255,.85);
            font-size: .75rem;
        }

        .section-divider {
            margin: 1.2rem 0;
            border-top: 1px dashed #cbd5f5;
        }
        .badge-soft {
            background: #eff6ff;
            color: #1d4ed8;
            border-radius: 999px;
            padding: .2rem .65rem;
            font-size: .7rem;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="main-container">

    <!-- HEADER / FILTER PERIODE -->
    <div class="page-header-card">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <div class="page-title mb-1">
                    <i class="bi bi-speedometer2 me-1"></i> DASHBOARD REALNET
                </div>
                <div class="page-subtitle mb-2">
                    Halo, <span class="greeting-name"><?= htmlspecialchars($nama) ?></span>
                    <span class="ms-1 text-sm">(<?= htmlspecialchars($username) ?>)</span><br>
                    <span><?= htmlspecialchars($tanggal) ?></span>
                </div>
                <div class="quote-text">
                    "<?= htmlspecialchars($quote) ?>"
                </div>
            </div>
            <div class="text-lg-end">
                <div class="period-chip mb-2">
                    <span class="period-dot"></span>
                    <span>Periode Laporan</span>
                    <strong><?= htmlspecialchars($label_periode) ?></strong>
                </div>

                <form method="get" class="d-flex flex-wrap align-items-center justify-content-lg-end gap-2">
                    <div class="filter-pill d-flex align-items-center gap-2">
                        <span class="filter-label">Bulan</span>
                        <select name="month" class="form-select form-select-sm border-0 bg-transparent">
                            <?php for ($m=1; $m<=12; $m++): ?>
                                <option value="<?= $m ?>" <?= ($m === $month ? 'selected' : '') ?>>
                                    <?= date('F', mktime(0,0,0,$m,1,$year)) ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="filter-pill d-flex align-items-center gap-2">
                        <span class="filter-label">Tahun</span>
                        <select name="year" class="form-select form-select-sm border-0 bg-transparent">
                            <?php for ($y = $currentYear - 3; $y <= $currentYear + 1; $y++): ?>
                                <option value="<?= $y ?>" <?= ($y === $year ? 'selected' : '') ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button class="btn btn-light btn-sm rounded-pill px-3">
                        <i class="bi bi-arrow-repeat me-1"></i> Terapkan
                    </button>
                    <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-outline-light btn-sm rounded-circle">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-outline-light btn-sm rounded-circle">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </form>
            </div>
        </div>
    </div>

    <!-- ROW: Pemasangan Bulanan Overall -->
    <div class="card-block">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5><i class="bi bi-wifi me-1"></i> Statistik Pemasangan Bulanan (Semua POP)</h5>
            <span class="badge-soft">
                Total bulan ini: <strong><?= number_format($total_pemasangan_bln,0,',','.') ?></strong> pelanggan
            </span>
        </div>
        <div class="row g-3">
            <div class="col-md-4 col-12">
                <div class="text-center p-3 rounded-4 bg-success-subtle">
                    <div class="stat-badge bg-success text-white">
                        <i class="bi bi-check2-circle"></i> Selesai
                    </div>
                    <div class="stat-number text-success"><?= $pemasangan_selesai_bln ?></div>
                    <div class="stat-caption">Pemasangan selesai dalam periode ini.</div>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="text-center p-3 rounded-4 bg-primary-subtle">
                    <div class="stat-badge bg-primary text-white">
                        <i class="bi bi-lightning-charge"></i> Aktivasi
                    </div>
                    <div class="stat-number text-primary"><?= $pemasangan_aktivasi_bln ?></div>
                    <div class="stat-caption">Sedang proses aktivasi / menunggu on.</div>
                </div>
            </div>
            <div class="col-md-4 col-12">
                <div class="text-center p-3 rounded-4 bg-secondary-subtle">
                    <div class="stat-badge bg-secondary text-white">
                        <i class="bi bi-clock"></i> Belum Diproses
                    </div>
                    <div class="stat-number text-secondary"><?= $pemasangan_belum_proses_bln ?></div>
                    <div class="stat-caption">Antrean pemasangan yang belum dikerjakan.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW: Pemasangan per POP -->
    <div class="card-block">
        <h5 class="mb-2">
            <i class="bi bi-geo-alt-fill me-1"></i>
            Statistik Pemasangan per POP (<?= htmlspecialchars($label_periode) ?>)
        </h5>
        <div class="pop-grid mt-2">
            <?php foreach ($popStats as $key => $ps): ?>
                <div class="pop-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="pop-title"><?= htmlspecialchars($ps['label']) ?></div>
                        <span class="badge-soft">
                            Total: <strong><?= $ps['total'] ?></strong>
                        </span>
                    </div>
                    <div class="pop-total text-primary mt-1 mb-1">
                        <?= $ps['selesai'] ?> <small class="text-muted">selesai</small>
                    </div>
                    <div class="pop-status-row">
                        <span>Aktivasi</span><span class="fw-semibold text-primary"><?= $ps['aktivasi'] ?></span>
                    </div>
                    <div class="pop-status-row">
                        <span>Belum Diproses</span><span class="fw-semibold text-secondary"><?= $ps['belum'] ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- ROW: Gangguan + Modem -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card-block h-100">
                <h5><i class="bi bi-exclamation-triangle me-1"></i> Statistik Gangguan (Global)</h5>
                <div class="row g-3 mt-1">
                    <div class="col-4">
                        <div class="text-center p-3 rounded-4 bg-success-subtle">
                            <div class="stat-badge bg-success text-white">
                                <i class="bi bi-check-circle"></i> Selesai
                            </div>
                            <div class="stat-number text-success"><?= $gangguan_selesai ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-3 rounded-4 bg-warning-subtle">
                            <div class="stat-badge bg-warning text-dark">
                                <i class="bi bi-tools"></i> Diproses
                            </div>
                            <div class="stat-number text-warning"><?= $gangguan_diproses ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="text-center p-3 rounded-4 bg-secondary-subtle">
                            <div class="stat-badge bg-secondary text-white">
                                <i class="bi bi-hourglass-split"></i> Belum Kerja
                            </div>
                            <div class="stat-number text-secondary"><?= $gangguan_belum_kerja ?></div>
                        </div>
                    </div>
                </div>
                <p class="mt-2 mb-0 stat-caption">
                    Data gangguan ini menampilkan total tiket pada sistem helpdesk REALNET.
                </p>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card-block h-100">
                <h5><i class="bi bi-hdd-network me-1"></i> Statistik Modem (Global)</h5>
                <div class="row g-3 mt-1">
                    <div class="col-6">
                        <div class="text-center p-3 rounded-4 bg-info-subtle">
                            <div class="stat-badge bg-info text-white">
                                <i class="bi bi-hdd-stack"></i> Tersedia
                            </div>
                            <div class="stat-number text-info"><?= $totalModemTersedia ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 rounded-4 bg-primary-subtle">
                            <div class="stat-badge bg-primary text-white">
                                <i class="bi bi-plug"></i> Dipasang
                            </div>
                            <div class="stat-number text-primary"><?= $totalModemDipasang ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 rounded-4 bg-warning-subtle mt-3">
                            <div class="stat-badge bg-warning text-dark">
                                <i class="bi bi-arrow-counterclockwise"></i> Cabutan
                            </div>
                            <div class="stat-number text-warning"><?= $totalModemCabutan ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 rounded-4 bg-danger-subtle mt-3">
                            <div class="stat-badge bg-danger text-white">
                                <i class="bi bi-x-octagon"></i> Rusak
                            </div>
                            <div class="stat-number text-danger"><?= $totalModemRusak ?></div>
                        </div>
                    </div>
                </div>
                <p class="mt-2 mb-0 stat-caption">
                    Monitoring stok dan kondisi modem di gudang dan lapangan.
                </p>
            </div>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- ROW: BBM Periode Pencairan -->
    <div class="card-block">
        <h5 class="mb-3">
            <i class="bi bi-fuel-pump-fill me-1"></i> 
            Statistik BBM Periode Pencairan (<?= htmlspecialchars($label_periode) ?>)
        </h5>
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="periode-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <h6><i class="bi bi-calendar-week me-1"></i> Periode 1 (Tgl 1-15)</h6>
                    <div class="stat-number">
                        Rp <?= number_format($bbm_periode1['total_bbm'], 0, ',', '.') ?>
                    </div>
                    <div class="stat-caption">
                        <?= number_format($bbm_periode1['total_liter'], 1) ?> L · <?= $bbm_periode1['jumlah_pengajuan'] ?> pengajuan
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="periode-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h6><i class="bi bi-calendar-week me-1"></i> Periode 2 (Tgl 16-Akhir)</h6>
                    <div class="stat-number">
                        Rp <?= number_format($bbm_periode2['total_bbm'], 0, ',', '.') ?>
                    </div>
                    <div class="stat-caption">
                        <?= number_format($bbm_periode2['total_liter'], 1) ?> L · <?= $bbm_periode2['jumlah_pengajuan'] ?> pengajuan
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="periode-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h6><i class="bi bi-calendar-month me-1"></i> Total Bulan Ini</h6>
                    <div class="stat-number">
                        Rp <?= number_format($bbm_bulan_ini['total_bbm'], 0, ',', '.') ?>
                    </div>
                    <div class="stat-caption">
                        <?= number_format($bbm_bulan_ini['total_liter'], 1) ?> L · <?= $bbm_bulan_ini['jumlah_pengajuan'] ?> pengajuan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW: BBM per Pengaju Bulan Ini - DINAMIS -->
    <?php if (!empty($bbm_per_pengaju)): ?>
    <div class="card-block">
        <h5><i class="bi bi-people-fill me-1"></i> BBM per Pengaju (<?= htmlspecialchars($label_periode) ?>)</h5>
        <div class="bbm-pengaju-row mt-2">
            <?php foreach ($bbm_per_pengaju as $pengaju): ?>
            <div class="bbm-card">
                <div class="bbm-header bbm-<?= $pengaju['color'] ?>">
                    <div class="bbm-nama"><?= htmlspecialchars($pengaju['nama']) ?></div>
                </div>
                <div class="bbm-amount text-<?= $pengaju['color'] == 'yellow' ? 'warning' : 'primary' ?>">
                    Rp <?= number_format($pengaju['total_biaya'], 0, ',', '.') ?>
                </div>
                <div class="bbm-liter">
                    <i class="bi bi-droplet-half me-1"></i><?= number_format($pengaju['total_liter'], 1) ?> L
                </div>
                <div class="bbm-count">
                    <i class="bi bi-file-text me-1"></i><?= $pengaju['jumlah_pengajuan'] ?> pengajuan
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ROW: BBM Global (All Time) -->
    <div class="card-block">
        <h5><i class="bi bi-fuel-pump me-1"></i> Total BBM Global (All Time)</h5>
        <div class="text-center mt-1">
            <div class="stat-badge bg-success text-white mb-2">
                <i class="bi bi-infinity"></i> Semua Reimburse BBM
            </div>
            <div class="stat-number text-success" style="font-size:1.8rem;">
                Rp <?= number_format($total_bbm, 0, ',', '.') ?>
            </div>
            <div class="stat-caption mt-1">
                Total liter: <strong><?= number_format($total_liter_bbm, 1) ?> L</strong>
            </div>
        </div>
    </div>

    <div class="section-divider"></div>

    <!-- ROW: Kasbon + Fee -->
    <div class="row g-3 mb-2">
        <div class="col-lg-4">
            <div class="card-block h-100 text-center">
                <h5><i class="bi bi-wallet2 me-1"></i> Total Kasbon Selesai</h5>
                <div class="stat-badge bg-success text-white mb-2">
                    <i class="bi bi-check-circle"></i> Kasbon
                </div>
                <div class="stat-number text-success" style="font-size:1.7rem;">
                    Rp <?= number_format($total_kasbon_selesai, 0, ',', '.') ?>
                </div>
                <div class="stat-caption">
                    Total nilai kasbon yang sudah dinyatakan selesai.
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-block h-100 text-center">
                <h5><i class="bi bi-person-gear me-1"></i> Fee Pasang (Teknisi)</h5>
                <div class="stat-badge bg-warning text-dark mb-2">
                    Rp 100.000 × pemasangan selesai bulan ini
                </div>
                <div class="stat-number text-warning" style="font-size:1.7rem;">
                    Rp <?= number_format($fee_pasang, 0, ',', '.') ?>
                </div>
                <div class="stat-caption">
                    Berbasis <?= $pemasangan_selesai_bln ?> pemasangan selesai pada periode laporan.
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card-block h-100 text-center">
                <h5><i class="bi bi-person-badge me-1"></i> Fee Marketing</h5>
                <div class="stat-badge bg-info text-white mb-2">
                    Rp 50.000 × pemasangan selesai bulan ini
                </div>
                <div class="stat-number text-info" style="font-size:1.7rem;">
                    Rp <?= number_format($fee_marketing, 0, ',', '.') ?>
                </div>
                <div class="stat-caption">
                    Estimasi fee tim marketing untuk <?= $pemasangan_selesai_bln ?> pemasangan bulan ini.
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-3">
        <small class="text-muted">
            © <?= date('Y') ?> PT. Real Data Solusindo · REALNET ERP · Laporan bulanan dinamis
        </small>
    </div>
</div>

<?php
$conn_pemasangan->close();
$conn_gangguan->close();
$conn_modem->close();
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>