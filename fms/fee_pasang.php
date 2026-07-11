<?php
session_start();

/**
 * =========================================================
 * FEE PEMASANGAN TEKNISI + NOTIF WA (PAID) + DETAIL TF (UNPAID)
 * - PAID  : kirim WA ke teknisi (ambil karyawan.no_telp by username)
 * - UNPAID: TIDAK kirim WA, tampilkan detail TF bank/rekening teknisi di dashboard
 * =========================================================
 */

/* ================== KONFIG STARSENDER ================== */
define('STARSENDER_API_KEY', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124'); // Authorization: YOUR API KEY
define('STARSENDER_URL', 'https://api.starsender.online/api/send');

define('WA_LOG_DIR', __DIR__ . '/logs');
define('WA_LOG_FILE', __DIR__ . '/logs/wa_starsender_teknisi.log');

/* ================== HELPERS ================== */
function wa_log($msg) {
    if (!is_dir(WA_LOG_DIR)) @mkdir(WA_LOG_DIR, 0755, true);
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents(WA_LOG_FILE, $line, FILE_APPEND);
}
function rupiah($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }

/** StarSender contoh Anda pakai to="08xxxx" */
function normalize_wa_08($waRaw) {
    $wa = preg_replace('/[^0-9]/', '', (string)$waRaw);
    if ($wa === '') return '';
    if (strpos($wa, '62') === 0) $wa = '0' . substr($wa, 2);
    return $wa;
}

/** ambil username kandidat dari field p.teknisi (mis: "gofur, luckyman" / "gofur@pop") */
function teknisi_username_from_field($teknisiField) {
    $s = trim((string)$teknisiField);
    if ($s === '') return '';

    $parts = preg_split('/\s*,\s*/', $s);
    $u = trim((string)($parts[0] ?? ''));

    if (strpos($u, '@') !== false) $u = explode('@', $u)[0];

    $u = preg_replace('/[^a-zA-Z0-9._-]/', '', $u);
    return $u;
}

function starsender_send_json($to, $message) {
    $payload = [
        "messageType" => "text",
        "to"          => $to,
        "body"        => $message,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => STARSENDER_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: ' . STARSENDER_API_KEY,
            'Accept: application/json',
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    wa_log("STARSENDER to={$to} HTTP={$code} err={$err} resp=" . substr((string)$resp, 0, 600));

    return [
        'ok'   => ($err === '' && $code >= 200 && $code < 300),
        'code' => $code,
        'err'  => $err,
        'resp' => $resp,
    ];
}

function buildFeeTeknisiMessage(array $p, array $karyawan): string {
    $namaTeknisi   = trim((string)($karyawan['nama'] ?? ($p['teknisi'] ?? '-')));
    $namaPelanggan = trim((string)($p['nama'] ?? '-'));
    $pop           = trim((string)($p['pop'] ?? '-'));
    $alamat        = trim((string)($p['alamat'] ?? '-'));
    $tanggal       = trim((string)($p['tanggal'] ?? ''));
    $ktp           = trim((string)($p['ktp'] ?? ''));
    $telp          = trim((string)($p['telp'] ?? ''));

    $msg  = "✅ *FEE PASANG SUDAH DIBAYAR*\n\n";
    $msg .= "Halo *{$namaTeknisi}*,\n";
    $msg .= "Fee pemasangan untuk pekerjaan berikut sudah dibayarkan.\n\n";
    $msg .= "Detail:\n";
    $msg .= "• Pelanggan: {$namaPelanggan}\n";
    $msg .= "• POP: {$pop}\n";
    $msg .= "• Alamat: {$alamat}\n";
    if ($tanggal !== '') $msg .= "• Tanggal: {$tanggal}\n";
    if ($ktp !== '')     $msg .= "• KTP: {$ktp}\n";
    if ($telp !== '')    $msg .= "• Telp: {$telp}\n";
    $msg .= "\nTerima kasih.\nRealnet";

    return $msg;
}

/* =========================================================
 * AJAX HANDLER PALING ATAS
 * - update_teknisi_payment_status: UPSERT status + jika paid kirim WA ke teknisi
 * - bulk_update: update banyak (tanpa WA)
 * ========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit;
    }

    // support JSON body utk bulk_update
    $ct = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($ct, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $j = json_decode($raw, true);
        if (is_array($j)) $_POST = array_merge($_POST, $j);
    }

    require_once 'config/database.php'; // harus ada: $conn_pasang (db_pemasangan) dan $conn_bbm (umumdata)

    // ========== SINGLE UPDATE ==========
    if ($_POST['action'] === 'update_teknisi_payment_status') {
        $pemasangan_id = $_POST['id'] ?? '';
        $new_status    = $_POST['status'] ?? '';

        if (!in_array($new_status, ['paid', 'unpaid'], true) || !is_numeric($pemasangan_id)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
            exit;
        }
        $pid = (int)$pemasangan_id;

        // Ambil data pemasangan
        $stmtP = $conn_pasang->prepare("SELECT id, nama, pop, alamat, telp, ktp, teknisi, tanggal FROM pemasangan WHERE id=? LIMIT 1");
        if (!$stmtP) {
            echo json_encode(['success' => false, 'message' => 'DB error pemasangan: ' . $conn_pasang->error]);
            exit;
        }
        $stmtP->bind_param("i", $pid);
        $stmtP->execute();
        $resP = $stmtP->get_result();
        $rowP = ($resP && $resP->num_rows > 0) ? $resP->fetch_assoc() : null;
        $stmtP->close();

        if (!$rowP) {
            echo json_encode(['success' => false, 'message' => 'Data pemasangan tidak ditemukan.']);
            exit;
        }

        // UPSERT status teknisi
        $check_stmt = $conn_pasang->prepare("SELECT id FROM pemasangan_fee_teknisi_status WHERE pemasangan_id = ?");
        if (!$check_stmt) {
            echo json_encode(['success' => false, 'message' => 'Database prepare error: ' . $conn_pasang->error]);
            exit;
        }
        $check_stmt->bind_param("i", $pid);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result && $check_result->num_rows > 0) {
            $stmt = $conn_pasang->prepare("UPDATE pemasangan_fee_teknisi_status SET status = ? WHERE pemasangan_id = ?");
            if (!$stmt) {
                $check_stmt->close();
                echo json_encode(['success' => false, 'message' => 'DB error update: ' . $conn_pasang->error]);
                exit;
            }
            $stmt->bind_param("si", $new_status, $pid);
        } else {
            $stmt = $conn_pasang->prepare("INSERT INTO pemasangan_fee_teknisi_status (pemasangan_id, status) VALUES (?, ?)");
            if (!$stmt) {
                $check_stmt->close();
                echo json_encode(['success' => false, 'message' => 'DB error insert: ' . $conn_pasang->error]);
                exit;
            }
            $stmt->bind_param("is", $pid, $new_status);
        }

        if (!$stmt->execute()) {
            $stmt->close();
            $check_stmt->close();
            echo json_encode(['success' => false, 'message' => $stmt->error]);
            exit;
        }
        $stmt->close();
        $check_stmt->close();

        // UNPAID: tidak kirim WA
        if ($new_status !== 'paid') {
            echo json_encode(['success' => true, 'wa_sent' => null]);
            exit;
        }

        // PAID: kirim WA ke teknisi (lookup username)
        if (!isset($conn_bbm) || !($conn_bbm instanceof mysqli)) {
            wa_log("PAID pid={$pid} -> conn_bbm tidak tersedia.");
            echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi koneksi DB umumdata tidak tersedia.']);
            exit;
        }

        if (STARSENDER_API_KEY === '' || STARSENDER_API_KEY === 'ISI_API_KEY_ANDA') {
            wa_log("PAID pid={$pid} -> API KEY belum diisi.");
            echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi API KEY StarSender belum diisi.']);
            exit;
        }

        $teknisiField = $rowP['teknisi'] ?? '';
        $teknisiUser  = teknisi_username_from_field($teknisiField);

        if ($teknisiUser === '') {
            wa_log("PAID pid={$pid} -> teknisi username kosong. field={$teknisiField}");
            echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi teknisi kosong.']);
            exit;
        }

        $stmtK = $conn_bbm->prepare("SELECT id, nama, username, no_telp, bank, rekening FROM karyawan WHERE username = ? LIMIT 1");
        if (!$stmtK) {
            wa_log("PAID pid={$pid} -> prepare karyawan error: " . $conn_bbm->error);
            echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi query karyawan error.']);
            exit;
        }
        $stmtK->bind_param("s", $teknisiUser);
        $stmtK->execute();
        $resK = $stmtK->get_result();
        $karyawan = ($resK && $resK->num_rows > 0) ? $resK->fetch_assoc() : null;
        $stmtK->close();

        if (!$karyawan) {
            wa_log("PAID pid={$pid} -> teknisi tidak ditemukan by username. teknisiUser={$teknisiUser} field={$teknisiField}");
            echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi teknisi tidak ditemukan di DB umumdata (cek username).']);
            exit;
        }

        $to = normalize_wa_08($karyawan['no_telp'] ?? '');
        if ($to === '') {
            wa_log("PAID pid={$pid} -> no_telp invalid. username={$karyawan['username']}");
            echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi nomor teknisi kosong/invalid.']);
            exit;
        }

        $msg  = buildFeeTeknisiMessage($rowP, $karyawan);
        $send = starsender_send_json($to, $msg);

        if ($send['ok']) {
            wa_log("PAID pid={$pid} -> WA TERKIRIM ke {$to} (username={$karyawan['username']}) HTTP {$send['code']}");
            echo json_encode(['success' => true, 'wa_sent' => true]);
            exit;
        }

        wa_log("PAID pid={$pid} -> WA GAGAL ke {$to} (username={$karyawan['username']}) HTTP {$send['code']} err={$send['err']} resp=" . substr((string)$send['resp'], 0, 400));
        echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, WA gagal. Cek logs/wa_starsender_teknisi.log']);
        exit;
    }

    // ========== BULK UPDATE (tanpa WA) ==========
    if ($_POST['action'] === 'bulk_update') {
        $ids = $_POST['ids'] ?? [];
        $status = $_POST['status'] ?? '';

        if (!is_array($ids)) $ids = json_decode((string)$ids, true);

        if (empty($ids) || !in_array($status, ['paid', 'unpaid'], true)) {
            echo json_encode(['success' => false, 'message' => 'Invalid input']);
            exit;
        }

        $success_count = 0;
        foreach ($ids as $id) {
            if (!is_numeric($id)) continue;
            $id = (int)$id;

            $check = $conn_pasang->query("SELECT id FROM pemasangan_fee_teknisi_status WHERE pemasangan_id = $id");
            if ($check && $check->num_rows > 0) {
                $conn_pasang->query("UPDATE pemasangan_fee_teknisi_status SET status = '$status' WHERE pemasangan_id = $id");
            } else {
                $conn_pasang->query("INSERT INTO pemasangan_fee_teknisi_status (pemasangan_id, status) VALUES ($id, '$status')");
            }
            $success_count++;
        }

        echo json_encode(['success' => true, 'count' => $success_count]);
        exit;
    }

    if ($_POST['action'] === 'export_excel') {
        echo json_encode(['success' => true, 'message' => 'Export fitur akan segera tersedia']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

/* =========================================================
 * HTML PAGE
 * - tampilkan detail TF bank+rekening untuk UNPAID
 * ========================================================= */
require_once 'templates/header.php';

// --- Filter & Paginasi
$cari           = isset($_GET['cari'])           ? trim($_GET['cari']) : '';
$pop_filter     = isset($_GET['pop_filter'])     ? trim($_GET['pop_filter']) : '';
$payment_filter = isset($_GET['payment_filter']) ? trim($_GET['payment_filter']) : '';
$sort_by        = isset($_GET['sort_by'])        ? trim($_GET['sort_by']) : 'terbaru';
$limit = isset($_GET['limit']) && in_array($_GET['limit'], [10, 20, 30, 50], true) ? (int)$_GET['limit'] : 30;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- List POP
$daftar_pop = [];
$qrpop = $conn_pasang->query("SELECT DISTINCT pop FROM pemasangan WHERE status IN ('selesai','on') ORDER BY pop");
if ($qrpop) while ($poprow = $qrpop->fetch_assoc()) $daftar_pop[] = $poprow['pop'];

// --- Where Clause
$where = "p.status IN ('selesai','on')";
if ($cari)       $where .= " AND (p.nama LIKE '%" . $conn_pasang->real_escape_string($cari) . "%' OR p.teknisi LIKE '%" . $conn_pasang->real_escape_string($cari) . "%')";
if ($pop_filter) $where .= " AND p.pop='" . $conn_pasang->real_escape_string($pop_filter) . "'";
if ($payment_filter) {
    if ($payment_filter === 'paid') {
        $where .= " AND pfts.status = 'paid'";
    } else {
        $where .= " AND (pfts.status = 'unpaid' OR pfts.status IS NULL)";
    }
}

// --- Order By
$order_by = "p.tanggal DESC";
if ($sort_by === 'nama') $order_by = "p.nama ASC";
elseif ($sort_by === 'pop') $order_by = "p.pop ASC";

// --- Total Data & Stats
$total_sql = "SELECT COUNT(p.id) AS total, 
    SUM(CASE WHEN pfts.status = 'paid' THEN 1 ELSE 0 END) AS paid_count,
    SUM(CASE WHEN pfts.status != 'paid' THEN 1 ELSE 0 END) AS unpaid_count
    FROM pemasangan p
    LEFT JOIN pemasangan_fee_teknisi_status pfts ON p.id = pfts.pemasangan_id
    WHERE $where";
$total_result = $conn_pasang->query($total_sql);
$stats = ($total_result) ? $total_result->fetch_assoc() : ['total' => 0, 'paid_count' => 0, 'unpaid_count' => 0];
$total = (int)($stats['total'] ?? 0);
$total_halaman = ($limit > 0) ? (int)ceil($total / $limit) : 1;

// --- Query Data
$sql = "SELECT p.*, COALESCE(pfts.status, 'unpaid') AS status_pembayaran_teknisi
    FROM pemasangan p
    LEFT JOIN pemasangan_fee_teknisi_status pfts ON p.id = pfts.pemasangan_id
    WHERE $where
    ORDER BY $order_by
    LIMIT $limit OFFSET $offset";
$result = $conn_pasang->query($sql);

/**
 * Ambil data teknisi (bank/rekening/no_telp) untuk baris yang tampil.
 * Key map by username teknisi.
 */
$teknisiUsers = [];
$rows_cache = [];
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $rows_cache[] = $r;
        $u = teknisi_username_from_field($r['teknisi'] ?? '');
        if ($u !== '') $teknisiUsers[$u] = true;
    }
}

$karyawanMap = []; // username => data
if (!empty($teknisiUsers) && isset($conn_bbm) && ($conn_bbm instanceof mysqli)) {
    $users = array_keys($teknisiUsers);
    $placeholders = implode(',', array_fill(0, count($users), '?'));
    $sqlK = "SELECT id, nama, username, no_telp, bank, rekening
             FROM karyawan
             WHERE username IN ($placeholders)";
    $stmt = $conn_bbm->prepare($sqlK);
    if ($stmt) {
        $types = str_repeat('s', count($users));
        $stmt->bind_param($types, ...$users);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($k = $res->fetch_assoc()) {
            $karyawanMap[$k['username']] = $k;
        }
        $stmt->close();
    }
}
?>

<style>
.stats-card {
    border-radius: 8px;
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.stats-card.paid { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
.stats-card.unpaid { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.stats-card h3 { font-size: 2rem; font-weight: bold; margin: 0; }
.stats-card p { margin: 0.5rem 0 0 0; opacity: 0.95; }
.filter-collapse { display: none; }
.filter-collapse.show { display: block; }

@media (max-width: 768px) { .stats-card { margin-bottom: 1rem; } }

.table-mobile-view { display: none; }
@media (max-width: 1024px) {
    .table-desktop { display: none; }
    .table-mobile-view { display: block; }
}

.card-mobile {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    background: white;
}
.card-mobile-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}
.card-mobile-title { font-weight: bold; font-size: 1rem; }
.card-mobile-status {
    padding: 0.25rem 0.75rem;
    border-radius: 4px;
    font-size: 0.85rem;
    font-weight: 500;
}
.card-mobile-body { font-size: 0.9rem; line-height: 1.6; }
.card-mobile-body div { margin-bottom: 0.5rem; }
.card-mobile-body label {
    font-weight: 600;
    color: #666;
    display: inline-block;
    min-width: 100px;
}
.card-mobile-footer {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}
.card-mobile-footer button { flex: 1; min-width: 120px; }

.toolbar {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    align-items: center;
}
.toolbar-buttons { display: flex; gap: 0.5rem; }
@media (max-width: 576px) {
    .toolbar { flex-direction: column; }
    .toolbar-buttons { width: 100%; }
    .toolbar-buttons button { flex: 1; }
}

.tf-box{
    margin-top:8px;
    padding:10px;
    border:1px dashed #c9c9c9;
    border-radius:8px;
    background:#fffef7;
    font-size:.88rem;
}
.tf-box .lbl{font-weight:700; color:#333;}
.tf-box .val{font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;}
</style>

<div class="page-header mb-4">
    <h1><i class="bi bi-person-fill-gear"></i> Fee Pemasangan Teknisi</h1>
</div>

<!-- Stats Section -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="stats-card">
            <h3><?= (int)($stats['total'] ?? 0) ?></h3>
            <p>Total Data</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stats-card paid">
            <h3><?= (int)($stats['paid_count'] ?? 0) ?></h3>
            <p>Sudah Dibayar</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stats-card unpaid">
            <h3><?= (int)($stats['unpaid_count'] ?? 0) ?></h3>
            <p>Belum Dibayar</p>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="stats-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <h3><?= $total > 0 ? (int)round(((int)($stats['paid_count'] ?? 0)) / $total * 100) : 0 ?>%</h3>
            <p>Tingkat Pembayaran</p>
        </div>
    </div>
</div>

<!-- Filter Section -->
<div class="form-card mb-4">
    <h2 class="form-title mb-3">
        <i class="bi bi-funnel"></i> Filter & Pencarian
        <button type="button" class="btn btn-sm btn-outline-secondary float-end d-md-none" id="toggleFilter">
            <i class="bi bi-chevron-down"></i> Buka
        </button>
    </h2>
    <form method="get" id="filterForm" class="filter-collapse show">
        <div class="row gx-3 gy-2 align-items-end">
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label" for="cariData">Cari Nama / Teknisi</label>
                <input name="cari" id="cariData" class="form-control" placeholder="Nama pelanggan atau teknisi..." value="<?= htmlspecialchars($cari) ?>">
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label" for="filterPop">Filter POP</label>
                <select name="pop_filter" id="filterPop" class="form-select">
                    <option value="">-- Semua POP --</option>
                    <?php foreach ($daftar_pop as $pop): ?>
                    <option value="<?= htmlspecialchars($pop) ?>" <?= $pop_filter == $pop ? 'selected' : '' ?>><?= htmlspecialchars($pop) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label" for="filterPaymentStatus">Status</label>
                <select name="payment_filter" id="filterPaymentStatus" class="form-select">
                    <option value="">Semua Status</option>
                    <option value="unpaid" <?= $payment_filter == 'unpaid' ? 'selected' : '' ?>>Belum Dibayar</option>
                    <option value="paid" <?= $payment_filter == 'paid' ? 'selected' : '' ?>>Sudah Dibayar</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label" for="sortBy">Urutkan</label>
                <select name="sort_by" id="sortBy" class="form-select">
                    <option value="terbaru" <?= $sort_by == 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
                    <option value="nama" <?= $sort_by == 'nama' ? 'selected' : '' ?>>Nama A-Z</option>
                    <option value="pop" <?= $sort_by == 'pop' ? 'selected' : '' ?>>POP</option>
                </select>
            </div>
            <div class="col-12 col-md-1">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> <span class="d-none d-sm-inline">Cari</span></button>
            </div>
        </div>
        <div class="row gx-3 gy-2 mt-2">
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label" for="limitRows">Data per halaman</label>
                <select name="limit" id="limitRows" class="form-select" onchange="this.form.submit()">
                    <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                    <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20</option>
                    <option value="30" <?= $limit == 30 ? 'selected' : '' ?>>30</option>
                    <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                </select>
            </div>
            <div class="col-12 col-sm-6 col-md-3">
                <a href="?" class="btn btn-outline-secondary w-100"><i class="bi bi-arrow-clockwise"></i> Reset Filter</a>
            </div>
        </div>
    </form>
</div>

<!-- Toolbar -->
<div class="toolbar d-none" id="toolbar">
    <span id="selectedCount">0 data terpilih</span>
    <div class="toolbar-buttons ms-auto">
        <button type="button" class="btn btn-success btn-sm" onclick="bulkUpdateStatus('paid')">
            <i class="bi bi-check-circle"></i> Tandai Dibayar
        </button>
        <button type="button" class="btn btn-warning btn-sm" onclick="bulkUpdateStatus('unpaid')">
            <i class="bi bi-x-circle"></i> Tandai Belum Dibayar
        </button>
    </div>
</div>

<!-- Desktop Table View -->
<div class="table-container table-desktop">
    <table class="table table-striped table-hover align-middle mb-0" style="font-size: 0.95rem;">
        <thead class="table-light">
            <tr>
                <th style="width: 35px;"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)"></th>
                <th style="width: 50px;">No</th>
                <th style="width: 180px;">Nama</th>
                <th style="width: 70px;">POP</th>
                <th style="width: 150px;">Alamat</th>
                <th style="width: 130px;">Teknisi</th>
                <th style="width: 130px;">KTP</th>
                <th style="width: 120px;">Telp</th>
                <th style="width: 160px;" class="text-center">Status</th>
                <th style="width: 80px;" class="text-center">Cetak</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows_cache)): ?>
            <tr><td colspan="10" class="text-center p-4 text-muted">Data tidak ditemukan.</td></tr>
        <?php else: $no = $offset + 1; foreach ($rows_cache as $row): ?>
            <?php
                $status = $row['status_pembayaran_teknisi'] ?? 'unpaid';
                $u = teknisi_username_from_field($row['teknisi'] ?? '');
                $k = ($u !== '' && isset($karyawanMap[$u])) ? $karyawanMap[$u] : null;
            ?>
            <tr id="row-<?= htmlspecialchars($row['id']) ?>">
                <td><input type="checkbox" class="row-checkbox" value="<?= htmlspecialchars($row['id']) ?>" onchange="updateCheckboxStatus()"></td>
                <td class="text-center text-muted" style="font-size: 0.9rem;"><?= $no++; ?></td>
                <td><strong><?= htmlspecialchars(substr($row['nama'] ?? '-', 0, 25)) ?></strong></td>
                <td><span class="badge bg-info"><?= htmlspecialchars($row['pop'] ?? '-') ?></span></td>
                <td><small><?= htmlspecialchars(substr($row['alamat'] ?? '-', 0, 20)) ?></small></td>
                <td>
                    <small><?= htmlspecialchars($row['teknisi'] ?? '-') ?></small>
                    <?php if ($u !== ''): ?>
                        <div class="text-muted" style="font-size: 0.8rem;">@<?= htmlspecialchars($u) ?></div>
                    <?php endif; ?>
                </td>
                <td><small style="color: #c41e3a; font-weight: 500;"><?= htmlspecialchars(substr($row['ktp'] ?? '-', 0, 14)) ?></small></td>
                <td><small><?= htmlspecialchars(substr($row['telp'] ?? '-', 0, 15)) ?></small></td>
                <td class="text-center">
                    <div style="display:flex; gap:6px; justify-content:center; flex-wrap:wrap;">
                        <button type="button"
                                class="btn btn-sm <?= ($status === 'paid') ? 'btn-success' : 'btn-outline-success' ?>"
                                onclick="updateTeknisiPaymentStatus(<?= (int)$row['id'] ?>, 'paid')"
                                title="Tandai Sudah Dibayar (kirim WA teknisi)">
                            <i class="bi bi-check-circle"></i> Bayar
                        </button>
                        <button type="button"
                                class="btn btn-sm <?= ($status === 'unpaid') ? 'btn-danger' : 'btn-outline-danger' ?>"
                                onclick="updateTeknisiPaymentStatus(<?= (int)$row['id'] ?>, 'unpaid')"
                                title="Tandai Belum Dibayar (tanpa WA)">
                            <i class="bi bi-x-circle"></i> Belum
                        </button>
                    </div>

                    <?php if ($status === 'unpaid'): ?>
                        <div class="tf-box text-start">
                            <div><span class="lbl">TF ke Teknisi:</span></div>
                            <?php if (!$k): ?>
                                <div class="text-danger">Data teknisi tidak ditemukan (cek username di tabel karyawan).</div>
                            <?php else: ?>
                                <div><span class="lbl">Nama</span>: <?= htmlspecialchars($k['nama'] ?? '-') ?></div>
                                <div><span class="lbl">Bank</span>: <?= htmlspecialchars($k['bank'] ?? '-') ?></div>
                                <div><span class="lbl">Rek</span>: <span class="val"><?= htmlspecialchars($k['rekening'] ?? '-') ?></span></div>
                                <div><span class="lbl">WA</span>: <?= htmlspecialchars($k['no_telp'] ?? '-') ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <a href="https://datarealsolution.net/cetak_reimburse_teknisi.php?id=<?= urlencode($row['id']) ?>" target="_blank" class="btn btn-info btn-sm" title="Cetak Reimburse">
                        <i class="bi bi-printer"></i> Cetak
                    </a>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Mobile Card View -->
<div class="table-mobile-view">
    <?php if (empty($rows_cache)): ?>
        <div class="alert alert-info text-center">Data tidak ditemukan.</div>
    <?php else: foreach ($rows_cache as $row): ?>
        <?php
            $status = $row['status_pembayaran_teknisi'] ?? 'unpaid';
            $u = teknisi_username_from_field($row['teknisi'] ?? '');
            $k = ($u !== '' && isset($karyawanMap[$u])) ? $karyawanMap[$u] : null;
        ?>
        <div class="card-mobile" id="row-<?= htmlspecialchars($row['id']) ?>">
            <div class="card-mobile-header">
                <div>
                    <div class="card-mobile-title"><?= htmlspecialchars($row['nama'] ?? '-') ?></div>
                    <small class="text-muted"><?= htmlspecialchars($row['teknisi'] ?? '-') ?><?= $u ? " (@".htmlspecialchars($u).")" : "" ?></small>
                </div>
                <span class="card-mobile-status <?= ($status === 'paid') ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                    <?= ($status === 'paid') ? 'Dibayar' : 'Belum' ?>
                </span>
            </div>
            <div class="card-mobile-body">
                <div><label>POP:</label> <span class="badge bg-info"><?= htmlspecialchars($row['pop'] ?? '-') ?></span></div>
                <div><label>Alamat:</label> <?= htmlspecialchars($row['alamat'] ?? '-') ?></div>
                <div><label>KTP:</label> <code><?= htmlspecialchars($row['ktp'] ?? '-') ?></code></div>
                <div><label>Telp:</label> <a href="tel:<?= htmlspecialchars($row['telp'] ?? '') ?>"><?= htmlspecialchars($row['telp'] ?? '-') ?></a></div>

                <?php if ($status === 'unpaid'): ?>
                    <div class="tf-box">
                        <div><span class="lbl">TF ke Teknisi:</span></div>
                        <?php if (!$k): ?>
                            <div class="text-danger">Data teknisi tidak ditemukan (cek username di tabel karyawan).</div>
                        <?php else: ?>
                            <div><span class="lbl">Nama</span>: <?= htmlspecialchars($k['nama'] ?? '-') ?></div>
                            <div><span class="lbl">Bank</span>: <?= htmlspecialchars($k['bank'] ?? '-') ?></div>
                            <div><span class="lbl">Rek</span>: <span class="val"><?= htmlspecialchars($k['rekening'] ?? '-') ?></span></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-mobile-footer">
                <button type="button" class="btn btn-sm <?= ($status === 'paid') ? 'btn-success' : 'btn-outline-success' ?>" onclick="updateTeknisiPaymentStatus(<?= (int)$row['id'] ?>, 'paid')">
                    <i class="bi bi-check-circle"></i> Sudah Bayar
                </button>
                <button type="button" class="btn btn-sm <?= ($status === 'unpaid') ? 'btn-danger' : 'btn-outline-danger' ?>" onclick="updateTeknisiPaymentStatus(<?= (int)$row['id'] ?>, 'unpaid')">
                    <i class="bi bi-x-circle"></i> Belum Bayar
                </button>
                <a href="https://datarealsolution.net/cetak_reimburse_teknisi.php?id=<?= urlencode($row['id']) ?>" target="_blank" class="btn btn-info btn-sm">
                    <i class="bi bi-printer"></i> Cetak
                </a>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<!-- Pagination -->
<?php if($total_halaman > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center flex-wrap">
        <?php
        $start = max(1, $page - 2);
        $end = min($total_halaman, $page + 2);

        if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">First</a></li>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">Prev</a></li>
        <?php endif;

        for ($i = $start; $i <= $end; $i++): ?>
            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            </li>
        <?php endfor;

        if ($page < $total_halaman): ?>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next</a></li>
            <li class="page-item"><a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $total_halaman])) ?>">Last</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<script>
// Toggle Filter pada Mobile
document.getElementById('toggleFilter').addEventListener('click', function() {
    const form = document.getElementById('filterForm');
    form.classList.toggle('show');
    this.innerHTML = form.classList.contains('show')
        ? '<i class="bi bi-chevron-up"></i> Tutup'
        : '<i class="bi bi-chevron-down"></i> Buka';
});

// Select All Checkboxes
function toggleSelectAll(checkbox) {
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = checkbox.checked);
    updateCheckboxStatus();
}
function updateCheckboxStatus() {
    const checked = document.querySelectorAll('.row-checkbox:checked').length;
    const toolbar = document.getElementById('toolbar');
    const count = document.getElementById('selectedCount');
    count.textContent = checked + ' data terpilih';
    if (checked > 0) toolbar.classList.remove('d-none'); else toolbar.classList.add('d-none');
}

// Single update (paid -> kirim WA)
function updateTeknisiPaymentStatus(id, status) {
    const statusText = status === 'paid' ? 'Sudah Dibayar' : 'Belum Dibayar';
    const note = status === 'paid'
        ? '\n\nCatatan: Saat "paid", sistem akan kirim notifikasi WA ke teknisi (berdasar username teknisi).'
        : '\n\nCatatan: Saat "unpaid", tidak ada notifikasi WA.';

    if (!confirm(`Ubah status menjadi "${statusText}"?${note}`)) return;

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update_teknisi_payment_status&id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(status)
    })
    .then(r => r.text().then(t => {
        try { return JSON.parse(t); } catch(e) { throw new Error('Invalid JSON: ' + t.substring(0,200)); }
    }))
    .then(data => {
        if (data.success) {
            showAlert('Status berhasil diperbarui!' + (data.message ? ' ' + data.message : ''), 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            showAlert('Gagal: ' + (data.message || 'Error'), 'danger');
        }
    })
    .catch(e => showAlert('Error: ' + e.message, 'danger'));
}

// Bulk update (tanpa WA)
function bulkUpdateStatus(status) {
    const ids = Array.from(document.querySelectorAll('.row-checkbox:checked')).map(cb => cb.value);
    if (ids.length === 0) return;

    const statusText = status === 'paid' ? 'Sudah Dibayar' : 'Belum Dibayar';
    if (!confirm(`Ubah ${ids.length} data menjadi "${statusText}"?\n\nCatatan: Bulk tidak mengirim WA.`)) return;

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'bulk_update', ids: ids, status: status })
    })
    .then(r => r.text().then(t => {
        try { return JSON.parse(t); } catch(e) { throw new Error('Invalid JSON: ' + t.substring(0,200)); }
    }))
    .then(data => {
        if (data.success) {
            showAlert(`${data.count} data berhasil diperbarui!`, 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            showAlert('Error: ' + (data.message || 'Gagal'), 'danger');
        }
    })
    .catch(e => showAlert('Error: ' + e.message, 'danger'));
}

// Alert
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 5000);
}
</script>

<?php require_once 'templates/footer.php'; ?>
