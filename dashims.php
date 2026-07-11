<?php
require_once __DIR__ . '/config/database.php';
session_start();

$dashboard_divisi = ['Admin', 'IT', 'Manager', 'SPV Teknis', 'Finance'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['divisi'], $dashboard_divisi)) {
    header("Location: login.php");
    exit;
}
date_default_timezone_set('Asia/Jakarta');

// =========================
// KONEKSI DATABASE
// =========================
$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

$conn = getErpDbConnection();
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// =========================
// SESSION & FLASH MESSAGE
// =========================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['flash'])) {
    $_SESSION['flash'] = [];
}

function flash($type, $msg) {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function show_flash() {
    if (empty($_SESSION['flash'])) return;
    foreach ($_SESSION['flash'] as $f) {
        $icon = $f['type'] === 'success' ? 'check-circle' : 'exclamation-triangle';
        $cls  = $f['type'] === 'success' ? 'alert-success' : 'alert-danger';
        echo "<div class='alert {$cls} alert-dismissible fade show'>
                <i class='bi bi-{$icon}'></i> {$f['msg']}
                <button class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
    $_SESSION['flash'] = [];
}

/**
 * Log aktivitas modem
 */
function log_activity($conn, $action, $detail, $sn = null) {
    $username = 'admin';
    if (!empty($_SESSION['user']['username'])) {
        $username = $_SESSION['user']['username'];
    } elseif (!empty($_SESSION['username'])) {
        $username = $_SESSION['username'];
    }

    $stmt = $conn->prepare("
        INSERT INTO jaringan_modem_logging (username, action, sn_lama, sn_baru, waktu)
        VALUES (?, ?, ?, ?, NOW())
    ");
    if ($stmt) {
        $stmt->bind_param("ssss", $username, $action, $detail, $sn);
        $stmt->execute();
        $stmt->close();
    }
}

// =========================
// AJAX HANDLERS
// =========================
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');

    // Detail modem by ID
    if ($_GET['ajax'] === 'get_modem') {
        $id  = (int)($_GET['id'] ?? 0);
        $res = $conn->query("SELECT * FROM jaringan_modem WHERE id_modem = {$id}");
        $row = $res ? $res->fetch_assoc() : null;
        echo json_encode($row ?: []);
        exit;
    }

    // Daftar modem di teknisi tertentu (berdasarkan lokasi_penyimpanan)
    if ($_GET['ajax'] === 'get_teknisi_modems') {
        $teknisi = $conn->real_escape_string($_GET['teknisi'] ?? '');
        $res     = $conn->query("
            SELECT * 
            FROM jaringan_modem 
            WHERE LOWER(lokasi_penyimpanan) = LOWER('{$teknisi}')
            ORDER BY serial_number
        ");
        $modems = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $modems[] = $r;
            }
        }
        echo json_encode($modems);
        exit;
    }

    // Suggestion search (autocomplete)
    if ($_GET['ajax'] === 'search') {
        $q   = $conn->real_escape_string($_GET['q'] ?? '');
        $res = $conn->query("
            SELECT serial_number, model, merk 
            FROM jaringan_modem 
            WHERE serial_number LIKE '%{$q}%' 
               OR model LIKE '%{$q}%' 
               OR merk LIKE '%{$q}%'
            ORDER BY serial_number
            LIMIT 10
        ");
        $items = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $items[] = $r;
            }
        }
        echo json_encode($items);
        exit;
    }
}

// =========================
// FILTER QUERY (SEARCH, STATUS, TANGGAL)
// =========================
$conditions = [];
$params_url = [];

// 1. Filter teks
$search = $_GET['search'] ?? '';
if ($search !== '') {
    $s = $conn->real_escape_string($search);
    $conditions[] = "(serial_number LIKE '%{$s}%' 
                  OR model LIKE '%{$s}%' 
                  OR merk LIKE '%{$s}%' 
                  OR lokasi_penyimpanan LIKE '%{$s}%')";
    $params_url['search'] = $search;
}

// 2. Filter status
$filter_status = $_GET['filter_status'] ?? '';
if ($filter_status !== '') {
    $fs = $conn->real_escape_string($filter_status);
    $conditions[] = "status = '{$fs}'";
    $params_url['filter_status'] = $filter_status;
}

// 3. Filter tanggal
$date_start = $_GET['date_start'] ?? '';
$date_end   = $_GET['date_end'] ?? '';
if ($date_start !== '' && $date_end !== '') {
    $ds = $conn->real_escape_string($date_start);
    $de = $conn->real_escape_string($date_end);
    $conditions[] = "DATE(tanggal_masuk) BETWEEN '{$ds}' AND '{$de}'";
    $params_url['date_start'] = $date_start;
    $params_url['date_end']   = $date_end;
}

$where_sql = '';
if (!empty($conditions)) {
    $where_sql = 'WHERE ' . implode(' AND ', $conditions);
}

// =========================
// EXPORT EXCEL HANDLER
// =========================
if (isset($_GET['action']) && $_GET['action'] === 'export_excel') {
    $filename = "data_modem_" . date('Ymd_His') . ".xls";
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"{$filename}\"");

    echo "NO\tSERIAL NUMBER\tMODEL\tMERK\tSTATUS\tTANGGAL MASUK\tLOKASI\n";

    $export_query = $conn->query("SELECT * FROM jaringan_modem {$where_sql} ORDER BY tanggal_masuk DESC");
    $no = 1;
    if ($export_query) {
        while ($row = $export_query->fetch_assoc()) {
            echo $no++ . "\t" .
                 $row['serial_number'] . "\t" .
                 $row['model'] . "\t" .
                 $row['merk'] . "\t" .
                 $row['status'] . "\t" .
                 $row['tanggal_masuk'] . "\t" .
                 $row['lokasi_penyimpanan'] . "\n";
        }
    }
    exit;
}

// =========================
// POST HANDLERS (HANYA CRUD)
// =========================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Tambah / Edit modem
    if ($action === 'save_modem') {
        $id     = (int)($_POST['id'] ?? 0);
        $sn     = strtoupper(trim($_POST['sn'] ?? ''));
        $model  = trim($_POST['model'] ?? '');
        $merk   = trim($_POST['merk'] ?? '');
        $status = $_POST['status'] ?? 'ready';
        $lokasi = trim($_POST['lokasi'] ?? '');

        if ($sn === '') {
            flash('error', 'Serial Number wajib diisi');
        } else {
            // Cek SN duplikat
            $check = $conn->prepare("SELECT id_modem FROM jaringan_modem WHERE serial_number = ? AND id_modem != ?");
            $check->bind_param("si", $sn, $id);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                flash('error', "SN {$sn} sudah terdaftar");
            } else {
                if ($id > 0) {
                    // Update
                    $stmt = $conn->prepare("
                        UPDATE jaringan_modem 
                        SET serial_number = ?, 
                            model          = ?, 
                            merk           = ?, 
                            status         = ?, 
                            lokasi_penyimpanan = ?
                        WHERE id_modem = ?
                    ");
                    $stmt->bind_param("sssssi", $sn, $model, $merk, $status, $lokasi, $id);
                    $stmt->execute();
                    $stmt->close();

                    log_activity($conn, 'UPDATE', "Updated modem: {$sn}", $sn);
                    flash('success', "Modem {$sn} berhasil diperbarui");
                } else {
                    // Insert
                    $stmt = $conn->prepare("
                        INSERT INTO jaringan_modem (serial_number, model, merk, status, tanggal_masuk, lokasi_penyimpanan)
                        VALUES (?, ?, ?, ?, CURDATE(), ?)
                    ");
                    $stmt->bind_param("sssss", $sn, $model, $merk, $status, $lokasi);
                    $stmt->execute();
                    $stmt->close();

                    log_activity($conn, 'ADD', "Added new modem: {$sn}", $sn);
                    flash('success', "Modem {$sn} berhasil ditambahkan");
                }
            }
            $check->close();
        }

        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }

    // Hapus modem
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $sn = '';

        $q = $conn->query("SELECT serial_number FROM jaringan_modem WHERE id_modem = {$id}");
        if ($q && $r = $q->fetch_assoc()) {
            $sn = $r['serial_number'];
        }

        $conn->query("DELETE FROM jaringan_modem WHERE id_modem = {$id}");
        log_activity($conn, 'DELETE', "Deleted modem: {$sn}", $sn);
        flash('success', "Modem {$sn} berhasil dihapus");

        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
}

// =========================
// DATA UTAMA UNTUK TAMPILAN
// =========================
$stats = [
    'total'    => $conn->query("SELECT COUNT(*) j FROM jaringan_modem")->fetch_assoc()['j'] ?? 0,
    'ready'    => $conn->query("SELECT COUNT(*) j FROM jaringan_modem WHERE status = 'ready'")->fetch_assoc()['j'] ?? 0,
    'dipasang' => $conn->query("SELECT COUNT(*) j FROM jaringan_modem WHERE status = 'dipasang'")->fetch_assoc()['j'] ?? 0,
    'rusak'    => $conn->query("SELECT COUNT(*) j FROM jaringan_modem WHERE status = 'rusak'")->fetch_assoc()['j'] ?? 0,
    'gudang'   => $conn->query("SELECT COUNT(*) j FROM jaringan_modem WHERE LOWER(lokasi_penyimpanan) LIKE '%gudang%'")->fetch_assoc()['j'] ?? 0
];

// Modem per teknisi (lokasi = nama/username)
$teknisi_stats = $conn->query("
    SELECT k.nama, k.username, COUNT(m.id_modem) total
    FROM hr_karyawan k
    LEFT JOIN jaringan_modem m 
        ON LOWER(m.lokasi_penyimpanan) IN (LOWER(k.nama), LOWER(k.username))
    GROUP BY k.id
    HAVING total > 0
    ORDER BY total DESC
");

// Pagination
$limit  = 15;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$total_rows  = $conn->query("SELECT COUNT(*) j FROM jaringan_modem {$where_sql}")->fetch_assoc()['j'] ?? 0;
$total_pages = max(1, (int)ceil($total_rows / $limit));

$modems = $conn->query("
    SELECT * 
    FROM jaringan_modem 
    {$where_sql}
    ORDER BY tanggal_masuk DESC 
    LIMIT {$offset}, {$limit}
");

$recent_logs = $conn->query("
    SELECT * 
    FROM jaringan_modem_logging 
    ORDER BY waktu DESC 
    LIMIT 30
");

// Helper URL pagination
function get_pagination_url($page, $params) {
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// Helper URL export
function get_export_url($params) {
    $params['action'] = 'export_excel';
    return '?' . http_build_query($params);
}

// Helper escape
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Include navbar jika ada
if (file_exists("navbar.php")) {
    include "navbar.php";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sistem Inventaris Modem Pro</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary: #2563eb;
    --success: #10b981;
    --warning: #f59e0b;
    --danger:  #ef4444;
}
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.main-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    margin: 20px auto;
    max-width: 1400px;
}
.stat-card {
    background: linear-gradient(135deg, var(--color1), var(--color2));
    border-radius: 15px;
    padding: 25px;
    color: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    transition: transform 0.3s;
}
.stat-card:hover {
    transform: translateY(-5px);
}
.stat-card h2 {
    font-size: 2.5rem;
    font-weight: bold;
    margin: 10px 0;
}
.table-hover tbody tr:hover {
    background-color: #f0f9ff;
    cursor: pointer;
}
.modal-content {
    border-radius: 15px;
    border: none;
}
.filter-bar {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 15px;
}
.search-dropdown-menu {
    position: absolute;
    z-index: 1000;
    width: 100%;
    max-height: 200px;
    overflow-y: auto;
    display: none;
}
.teknisi-item .card {
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
}
</style>
</head>
<body>

<div class="main-container p-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-0"><i class="bi bi-router text-primary"></i> Inventaris Modem Pro</h2>
            <p class="text-muted mb-0">Sistem Manajemen Modem Terintegrasi</p>
        </div>
        <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-circle"></i> Tambah Modem
        </button>
    </div>

    <?php show_flash(); ?>

    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="stat-card" style="--color1:#3b82f6; --color2:#2563eb;">
                <i class="bi bi-hdd-network fs-3"></i>
                <h2><?=$stats['total']?></h2>
                <div>Total Modem</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card" style="--color1:#10b981; --color2:#059669;">
                <i class="bi bi-check-circle fs-3"></i>
                <h2><?=$stats['ready']?></h2>
                <div>Ready</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card" style="--color1:#f59e0b; --color2:#d97706;">
                <i class="bi bi-gear fs-3"></i>
                <h2><?=$stats['dipasang']?></h2>
                <div>Dipasang</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card" style="--color1:#ef4444; --color2:#dc2626;">
                <i class="bi bi-exclamation-triangle fs-3"></i>
                <h2><?=$stats['rusak']?></h2>
                <div>Rusak</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card" style="--color1:#8b5cf6; --color2:#7c3aed;">
                <i class="bi bi-building fs-3"></i>
                <h2><?=$stats['gudang']?></h2>
                <div>Gudang</div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="stat-card" style="--color1:#ec4899; --color2:#db2777;">
                <i class="bi bi-people fs-3"></i>
                <h2><?=$teknisi_stats->num_rows?></h2>
                <div>Teknisi</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-people-fill"></i> Modem di Teknisi</h5>
            <input type="text" id="filterTeknisi" class="form-control form-control-sm w-25 bg-white text-dark" placeholder="🔍 Filter teknisi...">
        </div>
        <div class="card-body" style="max-height: 300px; overflow-y: auto;">
            <div class="row" id="teknisiContainer">
                <?php while($t = $teknisi_stats->fetch_assoc()): ?>
                <div class="col-md-4 col-lg-3 teknisi-item">
                    <div class="card mb-3 border-primary">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold text-truncate"><?=h($t['nama'])?></h6>
                                <span class="badge bg-primary rounded-pill"><?=$t['total']?></span>
                            </div>
                            <small class="text-muted d-block mb-2">@<?=h($t['username'])?></small>
                            <button class="btn btn-sm btn-outline-primary w-100" onclick="showTeknisiModems('<?=htmlspecialchars($t['nama'], ENT_QUOTES)?>')">
                                Lihat Detail
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-table"></i> Data Modem</h5>
                <a href="<?=get_export_url($params_url)?>" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel"></i> Export Excel
                </a>
            </div>
        </div>

        <div class="filter-bar">
            <form method="get" class="row g-2 align-items-end">
                <div class="col-md-3 position-relative">
                    <label class="form-label small text-muted">Cari</label>
                    <input type="text" name="search" id="searchInput" class="form-control" placeholder="SN, Model, Lokasi..." value="<?=h($search)?>" autocomplete="off">
                    <ul class="list-group search-dropdown-menu shadow" id="suggestions"></ul>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted">Status</label>
                    <select name="filter_status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="ready"   <?=$filter_status==='ready'?'selected':''?>>Ready</option>
                        <option value="dipasang"<?=$filter_status==='dipasang'?'selected':''?>>Dipasang</option>
                        <option value="rusak"   <?=$filter_status==='rusak'?'selected':''?>>Rusak</option>
                        <option value="hilang"  <?=$filter_status==='hilang'?'selected':''?>>Hilang</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small text-muted">Dari Tanggal</label>
                    <input type="date" name="date_start" class="form-control" value="<?=h($date_start)?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted">Sampai Tanggal</label>
                    <input type="date" name="date_end" class="form-control" value="<?=h($date_end)?>">
                </div>

                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter"></i> Filter</button>
                        <a href="?" class="btn btn-outline-secondary" title="Reset"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th width="50">No</th>
                            <th>Serial Number</th>
                            <th>Model / Merk</th>
                            <th>Status</th>
                            <th>Tgl Masuk</th>
                            <th>Lokasi</th>
                            <th width="120">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(!$modems || $modems->num_rows == 0): ?>
                        <tr>
                            <td colspan="7" class="text-center p-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                Tidak ada data yang sesuai filter
                            </td>
                        </tr>
                    <?php else: $no = $offset + 1; ?>
                        <?php while($m = $modems->fetch_assoc()): ?>
                        <tr>
                            <td><?=$no++?></td>
                            <td><span class="fw-bold text-primary"><?=h($m['serial_number'])?></span></td>
                            <td>
                                <div class="small fw-bold"><?=h($m['model'])?></div>
                                <div class="small text-muted"><?=h($m['merk'])?></div>
                            </td>
                            <td>
                                <?php
                                $badges = [
                                    'ready'    => 'success',
                                    'dipasang' => 'warning',
                                    'rusak'    => 'danger',
                                    'hilang'   => 'secondary'
                                ];
                                $badge = $badges[$m['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?=$badge?>"><?=ucfirst($m['status'])?></span>
                            </td>
                            <td><?=date('d/m/y', strtotime($m['tanggal_masuk']))?></td>
                            <td><i class="bi bi-geo-alt text-danger"></i> <?=h($m['lokasi_penyimpanan'])?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-info" onclick="editModem(<?=$m['id_modem']?>)" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteModem(<?=$m['id_modem']?>, '<?=htmlspecialchars($m['serial_number'], ENT_QUOTES)?>')" title="Hapus">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if($total_pages > 1): ?>
        <div class="card-footer bg-white py-3">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?=$page <= 1 ? 'disabled' : ''?>">
                        <a class="page-link" href="<?=get_pagination_url($page-1, $params_url)?>">‹ Prev</a>
                    </li>
                    <?php
                    $start = max(1, $page - 2);
                    $end   = min($total_pages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                    <li class="page-item <?=$i === $page ? 'active' : ''?>">
                        <a class="page-link" href="<?=get_pagination_url($i, $params_url)?>"><?=$i?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?=$page >= $total_pages ? 'disabled' : ''?>">
                        <a class="page-link" href="<?=get_pagination_url($page+1, $params_url)?>">Next ›</a>
                    </li>
                </ul>
            </nav>
            <div class="text-center text-muted small mt-2">
                Hal <?=$page?> dari <?=$total_pages?> (Total <?=$total_rows?> Data)
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mt-4">
        <div class="card-header bg-secondary text-white">
            <i class="bi bi-clock-history"></i> Log Aktivitas Terbaru
        </div>
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                <table class="table table-sm table-striped mb-0 small">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>Waktu</th>
                            <th>User</th>
                            <th>Aksi</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($recent_logs): while($log = $recent_logs->fetch_assoc()): ?>
                        <tr>
                            <td><?=date('d/m H:i', strtotime($log['waktu']))?></td>
                            <td><strong><?=h($log['username'])?></strong></td>
                            <td><span class="badge bg-secondary"><?=h($log['action'])?></span></td>
                            <td><?=h($log['sn_lama'])?> (<?=h($log['sn_baru'])?>)</td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- Modal Tambah/Edit Modem -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-hdd-network"></i> <span id="modalTitle">Tambah Modem Baru</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="modemForm">
                <input type="hidden" name="action" value="save_modem">
                <input type="hidden" name="id" id="modemId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Serial Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-barcode"></i></span>
                            <input name="sn" id="modemSN" class="form-control" placeholder="Scan atau Ketik SN" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Model</label>
                            <input name="model" id="modemModel" class="form-control" list="modelList">
                            <datalist id="modelList">
                                <option value="Huawei HG8245H5">
                                <option value="ZTE F609">
                                <option value="Nokia G-240W-F">
                            </datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Merk</label>
                            <input name="merk" id="modemMerk" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="modemStatus" class="form-select" required>
                                <option value="ready">Ready</option>
                                <option value="dipasang">Dipasang</option>
                                <option value="rusak">Rusak</option>
                                <option value="hilang">Hilang</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lokasi</label>
                            <input name="lokasi" id="modemLokasi" class="form-control" value="GUDANG" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Modem per Teknisi -->
<div class="modal fade" id="teknisiModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Modem di <span id="teknisiName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="teknisiModemsList"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Edit Modem
function editModem(id) {
    fetch(`?ajax=get_modem&id=${id}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('modalTitle').textContent = 'Edit Modem';
            document.getElementById('modemId').value     = data.id_modem;
            document.getElementById('modemSN').value     = data.serial_number;
            document.getElementById('modemModel').value  = data.model;
            document.getElementById('modemMerk').value   = data.merk;
            document.getElementById('modemStatus').value = data.status;
            document.getElementById('modemLokasi').value = data.lokasi_penyimpanan;
            new bootstrap.Modal(document.getElementById('addModal')).show();
        });
}

// Delete Modem
function deleteModem(id, sn) {
    if (confirm(`Yakin hapus modem ${sn}?`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Show Modem di Teknisi
function showTeknisiModems(teknisi) {
    document.getElementById('teknisiName').textContent = teknisi;
    document.getElementById('teknisiModemsList').innerHTML =
        '<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>';

    fetch(`?ajax=get_teknisi_modems&teknisi=${encodeURIComponent(teknisi)}`)
        .then(r => r.json())
        .then(modems => {
            let html = '<div class="table-responsive"><table class="table table-striped mb-0">';
            html += '<thead class="table-light"><tr><th>SN</th><th>Model</th><th>Status</th><th>Aksi</th></tr></thead><tbody>';

            if (modems.length === 0) {
                html += '<tr><td colspan="4" class="text-center">Tidak ada modem.</td></tr>';
            } else {
                modems.forEach(m => {
                    html += `
                        <tr>
                            <td><strong>${m.serial_number}</strong></td>
                            <td>${m.model || '-'}</td>
                            <td><span class="badge bg-warning">${m.status}</span></td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="editModem(${m.id_modem})" data-bs-dismiss="modal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            }
            html += '</tbody></table></div>';
            document.getElementById('teknisiModemsList').innerHTML = html;
            new bootstrap.Modal(document.getElementById('teknisiModal')).show();
        });
}

// Filter Teknisi Card
document.getElementById('filterTeknisi').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('.teknisi-item').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
});

// Live Search + Suggestions
let searchTimeout;
const searchInput     = document.getElementById('searchInput');
const suggestionsBox  = document.getElementById('suggestions');

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const query = this.value;

    if (query.length >= 2) {
        searchTimeout = setTimeout(() => {
            fetch(`?ajax=search&q=${encodeURIComponent(query)}`)
                .then(r => r.json())
                .then(items => {
                    suggestionsBox.innerHTML = '';
                    if (items.length > 0) {
                        items.forEach(item => {
                            const li = document.createElement('li');
                            li.className = 'list-group-item list-group-item-action cursor-pointer';
                            li.innerHTML = `<strong>${item.serial_number}</strong> <span class="text-muted small">${item.model}</span>`;
                            li.onclick = () => {
                                searchInput.value = item.serial_number;
                                suggestionsBox.style.display = 'none';
                                searchInput.form.submit();
                            };
                            suggestionsBox.appendChild(li);
                        });
                        suggestionsBox.style.display = 'block';
                    } else {
                        suggestionsBox.style.display = 'none';
                    }
                });
        }, 300);
    } else {
        suggestionsBox.style.display = 'none';
    }
});

document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
        suggestionsBox.style.display = 'none';
    }
});

// Reset modal Tambah/Edit saat ditutup
document.getElementById('addModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('modalTitle').textContent = 'Tambah Modem Baru';
    document.getElementById('modemForm').reset();
    document.getElementById('modemId').value = '';
});
</script>

</body>
</html>
<?php
$conn->close();
?>
