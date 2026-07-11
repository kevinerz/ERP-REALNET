<?php
// ==========================================
// SESSION & AUTH GUARD — harus di baris paling atas
// ==========================================
session_start();

$dashboard_divisi = ['Admin', 'IT', 'Manager', 'SPV Teknis', 'Finance'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['divisi'], $dashboard_divisi)) {
    header("Location: login.php");
    exit;
}

// ==========================================
// CONFIG & ERROR HANDLING
// ==========================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
require 'cabut/config.php';
require 'cabut/notify.php';

// ====== KONSTANTA ======
$allowedPop = ['Rajeg', 'Mauk', 'Kemeri'];
$statusList = ['belum selesai', 'selesai']; // DB lama cuma punya 2 status

// ====== ACTION: CREATE TICKET (SAFE) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'create') {
    $pop = $_POST['pop'] ?? 'Rajeg';
    if (!in_array($pop, $allowedPop, true)) $pop = 'Rajeg';

    $nama   = trim($_POST['nama'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $wa     = preg_replace('/\s+/', '', $_POST['wa'] ?? '');
    $alasan = trim($_POST['alasan'] ?? '');
    $sn     = trim($_POST['sn_modem'] ?? '');
    
    if (empty($nama) || empty($alamat) || empty($wa) || empty($alasan) || empty($sn)) {
        redirectWithMessage('cabut.php', 'warning', 'Harap lengkapi semua field.');
    }

    try {
        // INSERT HANYA SEKALI - tidak ada auto-trigger
        $stmt = $pdo->prepare("
            INSERT INTO tickets_cabut_modem 
            (pop, nama, alamat, wa, alasan, sn_modem, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 'belum selesai', NOW())
        ");
        $stmt->execute([$pop, $nama, $alamat, $wa, $alasan, $sn]);

        $idBaru = $pdo->lastInsertId();
        $q = $pdo->prepare("SELECT * FROM tickets_cabut_modem WHERE id=?");
        $q->execute([$idBaru]);
        $rowNew = $q->fetch();

        if ($rowNew) {
            $groupId = getGroupIdForPop($rowNew['pop']);
            if ($groupId) {
                $message = "📋 TIKET CABUT MODEM BARU\n\n";
                $message .= "Nama: {$rowNew['nama']}\n";
                $message .= "POP: {$rowNew['pop']}\n";
                $message .= "Alamat: {$rowNew['alamat']}\n";
                $message .= "No. WA: {$rowNew['wa']}\n";
                $message .= "Alasan: {$rowNew['alasan']}\n";
                $message .= "SN: {$rowNew['sn_modem']}\n";
                $message .= "Status: BELUM SELESAI\n";
                $message .= "Waktu: " . date('d-m-Y H:i:s') . "\n";
                sendWaGroupMessage($groupId, $message);
            }
        }

        redirectWithMessage('cabut.php', 'success', 'Tiket berhasil dibuat dengan status BELUM SELESAI.');
    } catch (Exception $e) {
        redirectWithMessage('cabut.php', 'danger', 'Error: ' . $e->getMessage());
    }
}

// ====== ACTION: UPDATE STATUS (HANYA MANUAL DARI BUTTON) ======
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['action'] === 'updateStatus' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $newStatus = $_POST['status'] ?? '';
    
    // Validasi status
    if (!in_array($newStatus, $statusList, true)) {
        redirectWithMessage('cabut.php', 'danger', 'Status tidak valid.');
    }

    try {
        // GET current data
        $row = $pdo->prepare("SELECT * FROM tickets_cabut_modem WHERE id=?");
        $row->execute([$id]);
        $data = $row->fetch();

        if (!$data) {
            redirectWithMessage('cabut.php', 'danger', 'Tiket tidak ditemukan.');
        }

        $oldStatus = $data['status'];

        // Prevent update jika status sama
        if ($oldStatus === $newStatus) {
            redirectWithMessage('cabut.php', 'warning', 'Status sudah sama, tidak ada perubahan.');
        }

        // UPDATE - ONLY ONCE, NO TRIGGER
        $upd = $pdo->prepare("
            UPDATE tickets_cabut_modem 
            SET status = ?
            WHERE id = ?
        ");
        $upd->execute([$newStatus, $id]);

        // Verify update succeed
        $verify = $pdo->prepare("SELECT status FROM tickets_cabut_modem WHERE id=?");
        $verify->execute([$id]);
        $checkStatus = $verify->fetch();

        if ($checkStatus['status'] !== $newStatus) {
            throw new Exception('Update gagal diverifikasi!');
        }

        // Send WA notification
        $groupId = getGroupIdForPop($data['pop']);
        if ($groupId) {
            $msg = "🔄 UPDATE STATUS TIKET\n\n";
            $msg .= "Nama: {$data['nama']}\n";
            $msg .= "SN: {$data['sn_modem']}\n";
            $msg .= "Status Lama: " . strtoupper($oldStatus) . "\n";
            $msg .= "Status Baru: " . strtoupper($newStatus) . "\n";
            $msg .= "Waktu: " . date('d-m-Y H:i:s') . "\n";
            sendWaGroupMessage($groupId, $msg);
        }

        $statusLabel = $newStatus === 'selesai' ? 'SELESAI' : 'BELUM SELESAI';
        redirectWithMessage('cabut.php', 'success', "Status tiket berhasil diubah menjadi: $statusLabel");

    } catch (Exception $e) {
        redirectWithMessage('cabut.php', 'danger', 'Error: ' . $e->getMessage());
    }
}

// ====== READ DATA (SAFE - NO AUTO UPDATE) ======
$keyword   = trim($_GET['q'] ?? '');
$filterPop = $_GET['pop'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$params = [];
$sql = "SELECT * FROM tickets_cabut_modem WHERE 1=1";

if (!empty($keyword)) {
    $sql .= " AND (pop LIKE :kw OR nama LIKE :kw OR alamat LIKE :kw OR wa LIKE :kw OR alasan LIKE :kw OR sn_modem LIKE :kw)";
    $params[':kw'] = "%$keyword%";
}

if (!empty($filterPop) && in_array($filterPop, $allowedPop, true)) {
    $sql .= " AND pop = :pop";
    $params[':pop'] = $filterPop;
}

if (!empty($filterStatus) && in_array($filterStatus, $statusList, true)) {
    $sql .= " AND status = :status";
    $params[':status'] = $filterStatus;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// ====== STATISTIK ======
$totalTiket = count($tickets);
$totalSelesai = 0;
$totalBelum = 0;
$perPop = [];

foreach ($tickets as $t) {
    if ($t['status'] === 'selesai') {
        $totalSelesai++;
    } else {
        $totalBelum++;
    }
    $p = $t['pop'] ?? 'Lainnya';
    $perPop[$p] = ($perPop[$p] ?? 0) + 1;
}

// ====== HELPERS ======
function badgeStatus($s) {
    if ($s === 'selesai') {
        return "<span class='badge bg-success'><i class='bi bi-check-circle-fill me-1'></i>Selesai</span>";
    }
    return "<span class='badge bg-warning'><i class='bi bi-hourglass-split me-1'></i>Belum Selesai</span>";
}

function badgePop($p) {
    $map = ['Rajeg' => 'primary', 'Mauk' => 'info', 'Kemeri' => 'warning'];
    $cls = $map[$p] ?? 'secondary';
    return "<span class='badge bg-$cls'>$p</span>";
}

function waLink($wa) {
    $w = preg_replace('/\D+/', '', $wa);
    if (strpos($w, '0') === 0) {
        $w = '62' . substr($w, 1);
    }
    return "https://wa.me/$w";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tiket Cabut Modem - REALNET ERP</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --rn-primary: #16a085;
            --rn-blue: #3498db;
            --rn-bg: #f4f7fb;
        }

        body {
            background: radial-gradient(circle at top left, #e0f2fe 0, var(--rn-bg) 40%, #ecfeff 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-max { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 88px 12px 32px 12px; 
        }

        .page-header { 
            background: linear-gradient(110deg, var(--rn-blue) 0%, var(--rn-primary) 55%, #1abc9c 100%);
            border-radius: 16px; 
            color: #fff; 
            padding: 2rem; 
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.35); 
            margin-bottom: 2rem; 
        }
        .page-title { 
            font-size: 1.6rem; 
            font-weight: 800; 
            text-transform: uppercase; 
            letter-spacing: 0.5px;
        }
        .page-desc {
            font-size: 0.95rem;
            opacity: 0.95;
            margin-top: 0.5rem;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-box {
            background: #fff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-left: 5px solid;
        }
        .stat-box.blue { border-left-color: var(--rn-blue); }
        .stat-box.green { border-left-color: #27ae60; }
        .stat-box.orange { border-left-color: #f39c12; }
        .stat-num {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0.5rem 0;
        }
        .stat-label {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
        }

        .form-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        .form-card-header {
            background: linear-gradient(90deg, var(--rn-blue), var(--rn-primary));
            color: #fff;
            padding: 1.2rem 1.5rem;
            font-weight: 700;
            border: none;
        }
        .form-card-body { padding: 2rem; }

        .table-card {
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-radius: 12px;
            overflow: hidden;
        }
        .table thead th {
            background: linear-gradient(90deg, var(--rn-blue), var(--rn-primary)) !important;
            color: #fff !important;
            border: none;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            padding: 1rem;
        }
        .table tbody td { padding: 0.9rem 1rem; border-color: #e5e7eb; }
        .table tbody tr:hover { background: #f8fafc; }

        .form-label { font-weight: 600; color: #333; font-size: 0.95rem; margin-bottom: 0.5rem; }
        .form-control, .form-select { border: 1.5px solid #ddd; border-radius: 8px; padding: 0.7rem 0.9rem; }
        .form-control:focus, .form-select:focus { border-color: var(--rn-primary); box-shadow: 0 0 0 0.15rem rgba(22,160,133,0.25); }

        .btn-success { background: linear-gradient(90deg, var(--rn-primary), #27ae60) !important; border: none; font-weight: 600; }
        .btn-success:hover { filter: brightness(1.08); }

        .modal-header { background: linear-gradient(90deg, var(--rn-blue), var(--rn-primary)); color: #fff; border: none; }
        .btn-close-white { filter: brightness(0) invert(1); }

        .action-btn { padding: 0.5rem 1rem; font-size: 0.85rem; border-radius: 6px; font-weight: 600; }

        @media (max-width: 768px) {
            .stats-row { grid-template-columns: 1fr 1fr; }
            .page-title { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container-max">
    <?php
    if (function_exists('display_notification')) {
        display_notification();
    } elseif (function_exists('displayFlashMessage')) {
        displayFlashMessage();
    }
    ?>

    <!-- HEADER -->
    <div class="page-header">
        <div class="d-flex align-items-center gap-3">
            <div style="font-size: 2.5rem;"><i class="bi bi-tools"></i></div>
            <div>
                <div class="page-title">Manajemen Tiket Cabut Modem</div>
                <div class="page-desc"><i class="bi bi-shield-lock-fill me-1"></i>Update status HANYA manual via tombol - tidak ada auto-trigger</div>
            </div>
        </div>
    </div>

    <!-- STATISTIK -->
    <div class="stats-row">
        <div class="stat-box blue">
            <div class="stat-num text-primary"><?= $totalTiket ?></div>
            <div class="stat-label">Total Tiket</div>
        </div>
        <div class="stat-box orange">
            <div class="stat-num" style="color: #f39c12;"><?= $totalBelum ?></div>
            <div class="stat-label">Belum Selesai (Menunggu)</div>
        </div>
        <div class="stat-box green">
            <div class="stat-num text-success"><?= $totalSelesai ?></div>
            <div class="stat-label">Sudah Selesai</div>
        </div>
    </div>

    <!-- PER POP STATS -->
    <?php if (!empty($perPop)): ?>
    <div class="form-card">
        <div class="form-card-header">
            <i class="bi bi-diagram-3 me-2"></i>Distribusi Tiket per POP
        </div>
        <div class="form-card-body">
            <div class="row g-3">
                <?php foreach ($perPop as $popName => $cnt): ?>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <div class="fw-bold"><?= htmlspecialchars($popName) ?></div>
                            <div class="text-primary fw-bold" style="font-size: 1.8rem;"><?= $cnt ?></div>
                            <small class="text-muted">tiket</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- FORM TAMBAH TIKET -->
    <div class="form-card">
        <div class="form-card-header">
            <i class="bi bi-plus-circle me-2"></i>Tambah Tiket Cabut Modem
        </div>
        <div class="form-card-body">
            <form method="post" action="?action=create">
                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label">POP</label>
                        <select name="pop" class="form-select" required>
                            <option value="Rajeg">Rajeg</option>
                            <option value="Mauk">Mauk</option>
                            <option value="Kemeri">Kemeri</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Nama Pelanggan</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Alamat Lengkap</label>
                        <input type="text" name="alamat" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">No. WhatsApp</label>
                        <input type="text" name="wa" class="form-control" placeholder="08xxxxxxxxxx" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Alasan Cabut</label>
                        <input type="text" name="alasan" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">SN Modem</label>
                        <input type="text" name="sn_modem" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success btn-lg w-100">
                            <i class="bi bi-save2 me-2"></i>Simpan Tiket (Auto: Belum Selesai)
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- FILTER & TABEL -->
    <div class="table-card">
        <div class="card-header p-3 border-bottom">
            <form class="d-flex flex-wrap gap-2 align-items-flex-end" method="get">
                <div>
                    <label class="form-label mb-2" style="font-size: 0.85rem;">Cari</label>
                    <input type="text" class="form-control" name="q" placeholder="Nama/WA/SN..." 
                           value="<?= htmlspecialchars($keyword) ?>" style="min-width: 200px;">
                </div>
                <div>
                    <label class="form-label mb-2" style="font-size: 0.85rem;">POP</label>
                    <select name="pop" class="form-select" style="width: auto;">
                        <option value="">Semua POP</option>
                        <?php foreach ($allowedPop as $p): ?>
                            <option value="<?= $p ?>" <?= $filterPop === $p ? 'selected' : '' ?>><?= $p ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label mb-2" style="font-size: 0.85rem;">Status</label>
                    <select name="status" class="form-select" style="width: auto;">
                        <option value="">Semua Status</option>
                        <option value="belum selesai" <?= $filterStatus === 'belum selesai' ? 'selected' : '' ?>>Belum Selesai</option>
                        <option value="selesai" <?= $filterStatus === 'selesai' ? 'selected' : '' ?>>Selesai</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="bi bi-search"></i> Cari
                </button>
                <?php if (!empty($keyword) || !empty($filterPop) || !empty($filterStatus)): ?>
                    <a class="btn btn-outline-danger" href="cabut.php">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                <tr>
                    <th style="width: 5%;">No</th>
                    <th style="width: 8%;">POP</th>
                    <th style="width: 18%;">Nama</th>
                    <th style="width: 18%;">Alamat</th>
                    <th style="width: 14%;">No. WA</th>
                    <th style="width: 12%;">SN Modem</th>
                    <th style="width: 12%;">Status</th>
                    <th style="width: 10%;">Dibuat</th>
                    <th style="width: 13%;">Aksi</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($tickets)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-danger">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i><br>Belum ada tiket
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($tickets as $i => $t): ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $i + 1 ?></td>
                            <td><?= badgePop($t['pop']) ?></td>
                            <td class="fw-600"><?= htmlspecialchars($t['nama']) ?></td>
                            <td><small class="text-muted"><?= htmlspecialchars($t['alamat']) ?></small></td>
                            <td>
                                <a href="<?= waLink($t['wa']) ?>" target="_blank" class="text-decoration-none fw-500">
                                    <i class="bi bi-whatsapp text-success"></i> <?= htmlspecialchars($t['wa']) ?>
                                </a>
                            </td>
                            <td><code><?= htmlspecialchars($t['sn_modem']) ?></code></td>
                            <td><?= badgeStatus($t['status']) ?></td>
                            <td><small class="text-muted"><?= date('d/m/Y', strtotime($t['created_at'])) ?></small></td>
                            <td>
                                <button class="btn btn-sm btn-primary action-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#statusModal" 
                                        onclick="openStatusModal(<?= $t['id'] ?>, '<?= htmlspecialchars($t['nama']) ?>', '<?= $t['status'] ?>')">
                                    <i class="bi bi-pencil-square"></i> Ubah
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-center mt-4 mb-3">
        <small class="text-muted">© <?= date('Y') ?> PT. Real Data Solusindo | Status Update: MANUAL ONLY</small>
    </div>
</div>

<!-- MODAL UBAH STATUS -->
<div class="modal fade" id="statusModal" tabindex="-1" backdrop="static" keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Ubah Status Tiket</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" id="statusForm" onsubmit="return confirmStatusChange();">
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong id="ticketDisplay">-</strong>
                    </div>

                    <label class="form-label fw-600">Ubah Status Menjadi</label>
                    <div class="mb-4">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="status" value="selesai" id="statusSelesai" required>
                            <label class="form-check-label fw-500" for="statusSelesai">
                                <i class="bi bi-check-circle text-success me-1"></i> <strong>Selesai</strong> - Modem sudah dicabut & tercatat
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="status" value="belum selesai" id="statusBelum" required>
                            <label class="form-check-label fw-500" for="statusBelum">
                                <i class="bi bi-hourglass-split text-warning me-1"></i> <strong>Belum Selesai</strong> - Masih menunggu eksekusi
                            </label>
                        </div>
                    </div>

                    <input type="hidden" id="ticketId" name="id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success fw-600">
                        <i class="bi bi-check-circle me-2"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
function openStatusModal(id, name, currentStatus) {
    document.getElementById('ticketId').value = id;
    document.getElementById('ticketDisplay').textContent = name + ' (Status saat ini: ' + currentStatus.toUpperCase() + ')';
    
    // Reset radio
    document.getElementById('statusSelesai').checked = false;
    document.getElementById('statusBelum').checked = false;
    
    document.getElementById('statusForm').action = '?action=updateStatus&id=' + id;
}

function confirmStatusChange() {
    const selected = document.querySelector('input[name="status"]:checked');
    if (!selected) {
        alert('❌ Pilih status tujuan terlebih dahulu!');
        return false;
    }
    return confirm('⚠️ Yakin ingin mengubah status tiket?');
}
</script>

</body>
</html>