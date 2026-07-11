<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['nama'])) {
    header('Location: login.php');
    exit;
}
$username = $_SESSION['username'];
$nama = $_SESSION['nama'];

// Koneksi DB pemasangan
$servername_pemasangan = "localhost";
$username_pemasangan = "u272457353_kevinsamsung9";
$password_pemasangan = "Admionkevin99";
$database_pemasangan = "u272457353_db_pemasangan";
$conn_pemasangan = new mysqli($servername_pemasangan, $username_pemasangan, $password_pemasangan, $database_pemasangan);
if ($conn_pemasangan->connect_error) { die("Koneksi gagal (db pemasangan): " . $conn_pemasangan->connect_error); }

// Koneksi DB gangguan (tiket_helpdesk)
$host_utama = 'localhost';
$username_utama = 'u272457353_kevinsamsung';
$password_utama = 'Admionkevin99';
$database_utama = 'u272457353_tiket_helpdesk';
$conn_gangguan = new mysqli($host_utama, $username_utama, $password_utama, $database_utama);
if ($conn_gangguan->connect_error) { die("Koneksi gagal (db gangguan): " . $conn_gangguan->connect_error); }

// Koneksi DB modem (umumdata)
$host_modem = 'localhost';
$username_modem = 'u272457353_kevinsamsung99';
$password_modem = 'Admionkevin99';
$database_modem = 'u272457353_umumdata';
$conn_modem = new mysqli($host_modem, $username_modem, $password_modem, $database_modem);
if ($conn_modem->connect_error) { die("Koneksi gagal (db modem): " . $conn_modem->connect_error); }

// Fungsi ambil jumlah per status
function getCount($conn, $tabel, $status_col, $status_val) {
    $sql = "SELECT COUNT(*) as total FROM $tabel WHERE $status_col = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status_val);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    return $row['total'] ?? 0;
}

// --- Statistik Pemasangan ---
$pemasangan_selesai = getCount($conn_pemasangan, "pemasangan", "status", "selesai");
$pemasangan_aktivasi = getCount($conn_pemasangan, "pemasangan", "status", "aktivasi");
$pemasangan_belum_proses = getCount($conn_pemasangan, "pemasangan", "status", "belum diproses");
// Note: $pemasangan_selesai is calculated twice, but doesn't affect the final value.

$fee_pasang = 100000 * $pemasangan_selesai;
$fee_marketing = 50000 * $pemasangan_selesai;

// --- Statistik Gangguan ---
$gangguan_selesai = getCount($conn_gangguan, "tiket", "status", "selesai");
$gangguan_belum_kerja = getCount($conn_gangguan, "tiket", "status", "belum dikerjakan");
$gangguan_diproses = getCount($conn_gangguan, "tiket", "status", "di proses");

// --- Statistik Modem per status ---
function getModemCount($conn_modem, $status) {
    $sql = "SELECT COUNT(*) as total FROM modem WHERE status=?";
    $stmt = $conn_modem->prepare($sql);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    return $row['total'] ?? 0;
}
$totalModemTersedia = getModemCount($conn_modem, 'tersedia');
$totalModemDipasang = getModemCount($conn_modem, 'dipasang');
$totalModemCabutan = getModemCount($conn_modem, 'cabutan');
$totalModemRusak = getModemCount($conn_modem, 'rusak');

// Statistik Total Nominal BBM (Ini tetap menampilkan total keseluruhan, termasuk yang selesai)
$total_bbm = 0;
$sql_bbm = "SELECT SUM(total) as total_bbm FROM reimburse_bbm";
$res_bbm = $conn_modem->query($sql_bbm);
if ($res_bbm && $row = $res_bbm->fetch_assoc()) {
    $total_bbm = $row['total_bbm'] ?: 0;
}

// Statistik Total Liter BBM (Ini tetap menampilkan total keseluruhan, termasuk yang selesai)
$total_liter_bbm = 0;
$sql_liter_bbm = "SELECT SUM(liter) as total_liter_bbm FROM reimburse_bbm";
$res_liter_bbm = $conn_modem->query($sql_liter_bbm);
if ($res_liter_bbm && $row = $res_liter_bbm->fetch_assoc()) {
    $total_liter_bbm = $row['total_liter_bbm'] ?: 0;
}

// Statistik BBM sudah dicairkan (status_keuangan = 'Disetujui' atau 'Selesai' jika itu definisi dicairkan Anda)
// Saat ini menggunakan 'Disetujui', yang konsisten dengan alur finance Anda
$total_bbm_cair = 0;
$sql_bbm_cair = "SELECT SUM(total) as total_bbm_cair FROM reimburse_bbm WHERE status_keuangan='Disetujui'";
$res_bbm_cair = $conn_modem->query($sql_bbm_cair);
if ($res_bbm_cair && $row = $res_bbm_cair->fetch_assoc()) {
    $total_bbm_cair = $row['total_bbm_cair'] ?: 0;
}
$total_liter_bbm_cair = 0;
$sql_liter_bbm_cair = "SELECT SUM(liter) as total_liter_bbm_cair FROM reimburse_bbm WHERE status_keuangan='Disetujui'";
$res_liter_bbm_cair = $conn_modem->query($sql_liter_bbm_cair);
if ($res_liter_bbm_cair && $row = $res_liter_bbm_cair->fetch_assoc()) {
    $total_liter_bbm_cair = $row['total_liter_bbm_cair'] ?: 0;
}

// --- PERUBAHAN UTAMA: Fungsi getBBMTotalByPengaju yang Disesuaikan ---
// Hanya menghitung total BBM untuk entri yang status_keuangan-nya BUKAN 'Selesai'
function getBBMTotalByPengaju($conn, $nama_pengaju) {
    $stmt = $conn->prepare("SELECT SUM(total) as total_bbm, SUM(liter) as total_liter FROM reimburse_bbm WHERE nama_pengaju=? AND status_keuangan != 'Selesai'");
    $stmt->bind_param("s", $nama_pengaju);
    $stmt->execute();
    $stmt->bind_result($total_bbm, $total_liter);
    $stmt->fetch();
    $stmt->close();
    return [
        'total_bbm' => $total_bbm ?? 0,
        'total_liter' => $total_liter ?? 0
    ];
}

$bbm_mauk = getBBMTotalByPengaju($conn_modem, 'ARISTA DWI CANDRA');
$bbm_rajeg = getBBMTotalByPengaju($conn_modem, 'MUHAMAD GOFUR');
$bbm_spv = getBBMTotalByPengaju($conn_modem, 'kevin aby aria sujono');
$bbm_kemeri = getBBMTotalByPengaju($conn_modem, 'Ramdani');
$bbm_admin = getBBMTotalByPengaju($conn_modem, 'SITI ROBIATUL ADAWIYAH');

// Statistik Total Nominal Kasbon Selesai
$total_kasbon_selesai = 0;
$sql = "SELECT SUM(jumlah) as total_kasbon_selesai FROM kasbon WHERE status='selesai'";
$res = $conn_modem->query($sql);
if ($res && $row = $res->fetch_assoc()) {
    $total_kasbon_selesai = $row['total_kasbon_selesai'] ?: 0;
}

// Quotes motivasi
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
<?php include 'navbar.php'; ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ERP PT. REAL DATA SOLUSINDO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #eaf3fa; }
        .dashboard-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 6px 30px 0 rgba(20,60,120,0.10);
            padding: 48px 40px 36px 40px;
            margin-top: 60px;
            margin-bottom: 30px;
            max-width: 1000px;
        }
        .motivasi {
            font-style: italic;
            color: #0080ff;
            margin-bottom: 18px;
        }
        .tanggal { color: #495057; font-size: 1.07em; }
        .section-title {
            font-size: 1.23em;
            font-weight: 700;
            margin-bottom: 12px;
            letter-spacing: .02em;
            text-align: left;
        }
        .stat-label {
            font-size: 1.03em;
            font-weight: bold;
            margin-bottom: 4px;
        }
        .stat-number {
            font-size: 2.2em;
            font-weight: 800;
            letter-spacing: .01em;
            margin-bottom: 2px;
        }
        .stat-badge {
            font-size: 1.1em;
            font-weight: 600;
            letter-spacing: .03em;
            border-radius: 1.2em;
            padding: 0.32em 1.1em 0.32em 1.1em;
            margin-bottom: 8px;
            margin-top: 6px;
            display: inline-block;
        }
        @media (max-width: 768px) {
            .dashboard-card { padding: 16px 8px; }
            .section-title { text-align: center;}
        }
        @media (max-width: 576px) {
            .stat-number { font-size: 1.5em; }
            .dashboard-card { margin-top: 20px; }
        }
        .bbm-pengaju-row {
    display: flex;
    gap: 18px;
    flex-wrap: wrap;
    justify-content: center;
}
.bbm-pengaju-card {
    flex: 1 1 170px;
    max-width: 220px;
    min-width: 160px;
    background: #fff;
    border-radius: 1.5em;
    box-shadow: 0 4px 20px 0 rgba(40,60,90,0.07);
    padding: 20px 12px 18px 12px;
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 180px;
    transition: transform 0.15s;
    height: 100%;
}
.bbm-pengaju-card:hover {
    transform: translateY(-5px) scale(1.03);
}

.bbm-blue    { background: #2563eb;}
.bbm-green   { background: #179646;}
.bbm-cyan    { background: #00bcd4;}
.bbm-yellow  { background: #ffc107; color: #3c2c00;}
.bbm-grey    { background: #616161;}
.bbm-amount {
    font-size: 1.5em;
    font-weight: 800;
    margin-bottom: 2px;
}
.bbm-liter {
    font-size: 1.1em;
    color: #666;
    margin-bottom: 3px;
}
@media (max-width: 900px) {
    .bbm-pengaju-row { gap: 10px; }
    .bbm-pengaju-card { max-width: 49%; min-width:140px;}
}
@media (max-width: 600px) {
    .bbm-pengaju-row { gap: 8px;}
    .bbm-pengaju-card { max-width: 95vw; min-width:120px;}
    .bbm-header { font-size: 0.99em;}
}
.bbm-pengaju-row {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 18px;
    margin-bottom: 18px;
}
@media (max-width: 1100px) {
    .bbm-pengaju-row { grid-template-columns: repeat(3, 1fr);}
}
@media (max-width: 800px) {
    .bbm-pengaju-row { grid-template-columns: repeat(2, 1fr);}
}
@media (max-width: 550px) {
    .bbm-pengaju-row { grid-template-columns: 1fr;}
}
.bbm-header {
    border-radius: 1em 1em 1em 1em;
    font-weight: 600;
    color: #fff;
    width: 100%;
    margin-bottom: 12px;
    font-size: 1.04em;
    line-height: 1.10em;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    padding: 9px 14px 6px 14px;
    min-height: 55px;
}

.bbm-lokasi {
    font-size: 1.06em;
    font-weight: 700;
    letter-spacing: .02em;
    text-transform: uppercase;
    margin-bottom: 1px;
    line-height: 1.05em;
}
.bbm-nama {
    font-size: .97em;
    font-weight: 400;
    letter-spacing: .01em;
    text-transform: capitalize;
    opacity: .94;
    line-height: 1.05em;
}

    </style>
</head>
<body>
<div class="container d-flex justify-content-center">
    <div class="dashboard-card w-100">
        <h2 class="text-center mb-1" style="font-weight:800; letter-spacing:.04em;">
            Selamat Datang di <span style="color:#0766b7;">PT. REAL DATA SOLUSINDO</span>
        </h2>
        <h4 class="text-center mb-4" style="font-weight:700;">
            Halo, <span class="text-primary"><?= htmlspecialchars($nama) ?></span>
            <small>(<?= htmlspecialchars($username) ?>)</small>
        </h4>

        <div class="section-title"><i class="bi bi-wifi"></i> Statistik <span style="color:#0d6efd;">Pemasangan</span></div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="bg-success-subtle border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="stat-badge bg-success text-white mb-2"><i class="bi bi-check2-circle"></i> Selesai</div>
                    <div class="stat-number text-success"><?= $pemasangan_selesai ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-primary-subtle border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="stat-badge bg-primary text-white mb-2"><i class="bi bi-lightning-charge"></i> Aktivasi</div>
                    <div class="stat-number text-primary"><?= $pemasangan_aktivasi ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-secondary-subtle border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="stat-badge bg-secondary text-white mb-2"><i class="bi bi-clock"></i> Belum Diproses</div>
                    <div class="stat-number text-secondary"><?= $pemasangan_belum_proses ?></div>
                </div>
            </div>
        </div>

        <div class="section-title"><i class="bi bi-exclamation-triangle"></i> Statistik <span style="color:#ff9600;">Gangguan</span></div>
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="bg-success-subtle border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="stat-badge bg-success text-white mb-2"><i class="bi bi-check-circle"></i> Selesai</div>
                    <div class="stat-number text-success"><?= $gangguan_selesai ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-secondary-subtle border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="stat-badge bg-secondary text-white mb-2"><i class="bi bi-hourglass-split"></i> Belum Dikerjakan</div>
                    <div class="stat-number text-secondary"><?= $gangguan_belum_kerja ?></div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-warning-subtle border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="stat-badge bg-warning text-dark mb-2"><i class="bi bi-tools"></i> Diproses</div>
                    <div class="stat-number text-warning"><?= $gangguan_diproses ?></div>
                </div>
            </div>
        </div>

        <div class="section-title"><i class="bi bi-hdd-network"></i> Statistik <span style="color:#198754;">Modem</span> per Status</div>
        <div class="row g-3 mb-2">
            <div class="col-md-3 col-6">
                <div class="bg-info-subtle border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="stat-badge bg-info text-white mb-2"><i class="bi bi-hdd-stack"></i> Tersedia</div>
                    <div class="stat-number text-info"><?= $totalModemTersedia ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="bg-primary-subtle border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="stat-badge bg-primary text-white mb-2"><i class="bi bi-plug"></i> Dipasang</div>
                    <div class="stat-number text-primary"><?= $totalModemDipasang ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="bg-warning-subtle border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="stat-badge bg-warning text-dark mb-2"><i class="bi bi-arrow-counterclockwise"></i> Cabutan</div>
                    <div class="stat-number text-warning"><?= $totalModemCabutan ?></div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="bg-danger-subtle border-0 shadow-sm rounded-4 p-3 text-center">
                    <div class="stat-badge bg-danger text-white mb-2"><i class="bi bi-x-octagon"></i> Rusak</div>
                    <div class="stat-number text-danger"><?= $totalModemRusak ?></div>
                </div>
            </div>
        </div>

<div class="section-title"><i class="bi bi-fuel-pump-fill"></i> Statistik <span style="color:#28a745;">Total BBM</span> & <span style="color:#0d6efd;">BBM Sudah Dicairkan</span></div>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="bg-light border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div class="stat-badge bg-success text-white mb-2">
                <i class="bi bi-fuel-pump"></i> Total BBM
            </div>
            <div class="stat-number text-success" style="font-size:2em;">
                Rp <?= number_format($total_bbm, 0, ',', '.') ?>
            </div>
            <div class="stat-label text-secondary" style="margin-top: 6px;">
                <i class="bi bi-droplet-half"></i> Total Liter: <b><?= number_format($total_liter_bbm, 2, ',', '.') ?> L</b>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="bg-light border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div class="stat-badge bg-info text-white mb-2">
                <i class="bi bi-cash-stack"></i> Sudah Dicairkan
            </div>
            <div class="stat-number text-info" style="font-size:2em;">
                Rp <?= number_format($total_bbm_cair, 0, ',', '.') ?>
            </div>
            <div class="stat-label text-secondary" style="margin-top: 6px;">
                <i class="bi bi-droplet-half"></i> Total Liter Dicairkan: <b><?= number_format($total_liter_bbm_cair, 2, ',', '.') ?> L</b>
            </div>
        </div>
    </div>
</div>

<div class="section-title mt-3">
    <i class="bi bi-fuel-pump"></i> Total BBM per Pengaju (Belum Selesai)
</div>
<div class="bbm-pengaju-row">
    <div class="bbm-pengaju-card">
        <div class="bbm-header bbm-blue">
            <div class="bbm-lokasi">SP-MAUK</div>
            <div class="bbm-nama">Arista Dwi Candra</div>
        </div>
        <div class="bbm-amount text-primary">
            Rp <?= number_format($bbm_mauk['total_bbm'], 0, ',', '.') ?>
        </div>
        <div class="bbm-liter">
            <i class="bi bi-droplet-half"></i> <?= number_format($bbm_mauk['total_liter'], 2, ',', '.') ?> L
        </div>
    </div>
    <div class="bbm-pengaju-card">
        <div class="bbm-header bbm-green">
            <div class="bbm-lokasi">SP-RAJEG</div>
            <div class="bbm-nama">Muhamad Gofur</div>
        </div>
        <div class="bbm-amount text-success">
            Rp <?= number_format($bbm_rajeg['total_bbm'], 0, ',', '.') ?>
        </div>
        <div class="bbm-liter">
            <i class="bi bi-droplet-half"></i> <?= number_format($bbm_rajeg['total_liter'], 2, ',', '.') ?> L
        </div>
    </div>
    <div class="bbm-pengaju-card">
        <div class="bbm-header bbm-cyan">
            <div class="bbm-lokasi">SPV-TEKNIS</div>
            <div class="bbm-nama">Kevin Aby Aria Sujono</div>
        </div>
        <div class="bbm-amount" style="color:#00bcd4;">
            Rp <?= number_format($bbm_spv['total_bbm'], 0, ',', '.') ?>
        </div>
        <div class="bbm-liter">
            <i class="bi bi-droplet-half"></i> <?= number_format($bbm_spv['total_liter'], 2, ',', '.') ?> L
        </div>
    </div>
    <div class="bbm-pengaju-card">
        <div class="bbm-header bbm-yellow">
            <div class="bbm-lokasi">SP-KEMERI</div>
            <div class="bbm-nama">Ramdani</div>
        </div>
        <div class="bbm-amount text-warning">
            Rp <?= number_format($bbm_kemeri['total_bbm'], 0, ',', '.') ?>
        </div>
        <div class="bbm-liter">
            <i class="bi bi-droplet-half"></i> <?= number_format($bbm_kemeri['total_liter'], 2, ',', '.') ?> L
        </div>
    </div>
    <div class="bbm-pengaju-card">
        <div class="bbm-header bbm-grey">
            <div class="bbm-lokasi">SPV-ADMIN</div>
            <div class="bbm-nama">Siti Robiatul Adawiyah</div>
        </div>
        <div class="bbm-amount text-secondary">
            Rp <?= number_format($bbm_admin['total_bbm'], 0, ',', '.') ?>
        </div>
        <div class="bbm-liter">
            <i class="bi bi-droplet-half"></i> <?= number_format($bbm_admin['total_liter'], 2, ',', '.') ?> L
        </div>
    </div>
</div>


<div class="section-title"><i class="bi bi-wallet2"></i> Statistik <span style="color:#28a745;">Total Kasbon</span></div>
<div class="bg-light border-0 shadow-sm rounded-4 p-3 text-center mb-4">
    <div class="stat-badge bg-success text-white mb-2">
        <i class="bi bi-check-circle"></i> Total Kasbon Selesai
    </div>
    <div class="stat-number text-success" style="font-size:2em;">
        Rp <?= number_format($total_kasbon_selesai, 0, ',', '.') ?>
    </div>
    <div class="stat-label text-secondary" style="margin-top: 6px;">
        Total nilai kasbon status selesai
    </div>
</div>

<div class="section-title">
    <i class="bi bi-cash-coin"></i> Statistik <span style="color:#e40;">Fee Pasang & Marketing</span>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="bg-warning-subtle border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div class="stat-badge bg-warning text-dark mb-2">
                <i class="bi bi-person-gear"></i> Fee Pasang (Teknisi)
            </div>
            <div class="stat-number text-warning" style="font-size:2em;">
                Rp <?= number_format($fee_pasang, 0, ',', '.') ?>
            </div>
            <div class="stat-label text-secondary">
                Jumlah pemasangan selesai × Rp 100.000
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="bg-info-subtle border-0 shadow-sm rounded-4 p-3 text-center h-100">
            <div class="stat-badge bg-info text-white mb-2">
                <i class="bi bi-person-badge"></i> Fee Marketing
            </div>
            <div class="stat-number text-info" style="font-size:2em;">
                Rp <?= number_format($fee_marketing, 0, ',', '.') ?>
            </div>
            <div class="stat-label text-secondary">
                Jumlah pemasangan selesai × Rp 50.000
            </div>
        </div>
    </div>
</div>


<?php
$conn_pemasangan->close();
$conn_gangguan->close();
$conn_modem->close();
?>
</body>
</html>