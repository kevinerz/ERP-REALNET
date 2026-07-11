<?php
session_start();

// =============== KONFIG ===============
define('FEE_MARKETING_NOMINAL', 50000);
define('STARSENDER_API_KEY', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124');
define('STARSENDER_URL', 'https://api.starsender.online/api/send');

define('WA_LOG_DIR', __DIR__ . '/logs');
define('WA_LOG_FILE', __DIR__ . '/logs/wa_starsender.log');

function wa_log($msg) {
    if (!is_dir(WA_LOG_DIR)) @mkdir(WA_LOG_DIR, 0755, true);
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents(WA_LOG_FILE, $line, FILE_APPEND);
}

function rupiah($n) {
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
}

function normalize_wa($waRaw) {
    $wa = preg_replace('/[^0-9]/', '', (string)$waRaw);
    if ($wa === '') return '';
    if (strpos($wa, '0') === 0) $wa = '62' . substr($wa, 1);
    return $wa;
}

function starsender_send($to, $message) {
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
    if (function_exists('wa_log')) {
        wa_log("STARSENDER JSON to={$to} HTTP={$code} err={$err} resp=" . substr((string)$resp, 0, 600));
    }
    $json = null;
    if (is_string($resp) && $resp !== '') {
        $tmp = json_decode($resp, true);
        if (json_last_error() === JSON_ERROR_NONE) $json = $tmp;
    }
    return [
        'ok'   => ($err === '' && $code >= 200 && $code < 300),
        'code' => $code,
        'err'  => $err,
        'resp' => $resp,
        'json' => $json,
    ];
}

function buildPaidMessage(string $namaMitra, array $pemasanganRow): string {
    $fee           = rupiah(FEE_MARKETING_NOMINAL);
    $namaPelanggan = trim((string)($pemasanganRow['nama'] ?? '-'));
    $pop           = trim((string)($pemasanganRow['pop'] ?? '-'));
    $alamat        = trim((string)($pemasanganRow['alamat'] ?? '-'));
    $tanggal       = trim((string)($pemasanganRow['tanggal'] ?? ''));
    $msg  = "✅ *FEE MARKETING SUDAH DIBAYAR*\n\n";
    $msg .= "Halo *{$namaMitra}*,\n";
    $msg .= "Fee marketing sebesar *{$fee}* sudah dibayarkan.\n\n";
    $msg .= "Detail pemasangan:\n";
    $msg .= "• Pelanggan: {$namaPelanggan}\n";
    $msg .= "• POP: {$pop}\n";
    $msg .= "• Alamat: {$alamat}\n";
    if ($tanggal !== '') $msg .= "• Tanggal: {$tanggal}\n";
    $msg .= "\nTerima kasih.\nRealnet";
    return $msg;
}

// =============== AJAX HANDLER ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_payment_status') {
    header('Content-Type: application/json');
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Session expired']);
        exit;
    }
    require_once 'config/database.php';
    $pemasangan_id = $_POST['id'] ?? '';
    $new_status    = $_POST['status'] ?? '';
    if (!in_array($new_status, ['paid', 'unpaid'], true) || !is_numeric($pemasangan_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid input.']);
        exit;
    }
    $pid = (int)$pemasangan_id;
    $stmtP = $conn_pasang->prepare("SELECT id, nama, pop, alamat, telp, marketing, tanggal FROM pemasangan WHERE id=? LIMIT 1");
    if (!$stmtP) { echo json_encode(['success' => false, 'message' => 'DB error pemasangan: ' . $conn_pasang->error]); exit; }
    $stmtP->bind_param("i", $pid);
    $stmtP->execute();
    $resP = $stmtP->get_result();
    $rowP = ($resP && $resP->num_rows > 0) ? $resP->fetch_assoc() : null;
    $stmtP->close();
    if (!$rowP) { echo json_encode(['success' => false, 'message' => 'Data pemasangan tidak ditemukan.']); exit; }
    $check_stmt = $conn_pasang->prepare("SELECT id FROM pemasangan_fee_marketing_status WHERE pemasangan_id = ?");
    if (!$check_stmt) { echo json_encode(['success' => false, 'message' => 'DB error: ' . $conn_pasang->error]); exit; }
    $check_stmt->bind_param("i", $pid);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result && $check_result->num_rows > 0) {
        $stmt = $conn_pasang->prepare("UPDATE pemasangan_fee_marketing_status SET status = ? WHERE pemasangan_id = ?");
        if (!$stmt) { $check_stmt->close(); echo json_encode(['success' => false, 'message' => 'DB error update: ' . $conn_pasang->error]); exit; }
        $stmt->bind_param("si", $new_status, $pid);
    } else {
        $stmt = $conn_pasang->prepare("INSERT INTO pemasangan_fee_marketing_status (pemasangan_id, status) VALUES (?, ?)");
        if (!$stmt) { $check_stmt->close(); echo json_encode(['success' => false, 'message' => 'DB error insert: ' . $conn_pasang->error]); exit; }
        $stmt->bind_param("is", $pid, $new_status);
    }
    if (!$stmt->execute()) { $stmt->close(); $check_stmt->close(); echo json_encode(['success' => false, 'message' => $stmt->error]); exit; }
    $stmt->close();
    $check_stmt->close();
    if ($new_status !== 'paid') { echo json_encode(['success' => true, 'wa_sent' => null]); exit; }
    if (!isset($conn_market) || !($conn_market instanceof mysqli)) {
        wa_log("PAID pid={$pid} -> conn_market tidak tersedia.");
        echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi koneksi DB market tidak tersedia.']); exit;
    }
    if (STARSENDER_API_KEY === '' || STARSENDER_API_KEY === 'ISI_API_KEY_ANDA') {
        wa_log("PAID pid={$pid} -> API KEY belum diisi.");
        echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi API KEY StarSender belum diisi.']); exit;
    }
    $marketingName = trim((string)($rowP['marketing'] ?? ''));
    if ($marketingName === '') { wa_log("PAID pid={$pid} -> marketing kosong."); echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi marketing kosong.']); exit; }
    $stmtM = $conn_market->prepare("SELECT nama, wa, payment_type, bank_nama, bank_rekening, e_wallet_nama, e_wallet_nomor FROM mitra WHERE nama=? LIMIT 1");
    if (!$stmtM) { wa_log("PAID pid={$pid} -> prepare mitra error: " . $conn_market->error); echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi query mitra error.']); exit; }
    $stmtM->bind_param("s", $marketingName);
    $stmtM->execute();
    $resM = $stmtM->get_result();
    $mitra = ($resM && $resM->num_rows > 0) ? $resM->fetch_assoc() : null;
    $stmtM->close();
    if (!$mitra) { wa_log("PAID pid={$pid} -> mitra tidak ditemukan. marketing={$marketingName}"); echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi mitra tidak ditemukan di DB market (nama harus sama persis).']); exit; }
    $to = normalize_wa($mitra['wa'] ?? '');
    if ($to === '') { wa_log("PAID pid={$pid} -> WA mitra kosong/invalid. marketing={$marketingName}"); echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, tapi nomor WA mitra kosong/invalid.']); exit; }
    $msg  = buildPaidMessage((string)$mitra['nama'], $rowP);
    $send = starsender_send($to, $msg);
    if ($send['ok']) {
        wa_log("PAID pid={$pid} -> WA TERKIRIM ke {$to} ({$mitra['nama']}) HTTP {$send['code']}");
        echo json_encode(['success' => true, 'wa_sent' => true]); exit;
    }
    wa_log("PAID pid={$pid} -> WA GAGAL ke {$to} ({$mitra['nama']}) HTTP {$send['code']} err={$send['err']} resp=" . substr((string)$send['resp'], 0, 400));
    echo json_encode(['success' => true, 'wa_sent' => false, 'message' => 'Status tersimpan, WA gagal. Cek logs/wa_starsender.log']);
    exit;
}
// =============== END AJAX HANDLER ===============

require_once 'templates/header.php';

// ---- Filter ----
$cari           = isset($_GET['cari'])           ? trim($_GET['cari'])           : '';
$cari_alamat    = isset($_GET['cari_alamat'])    ? trim($_GET['cari_alamat'])    : '';  // BARU
$pop_filter     = isset($_GET['pop_filter'])     ? trim($_GET['pop_filter'])     : '';
$payment_filter = isset($_GET['payment_filter']) ? trim($_GET['payment_filter']) : '';
$tgl_dari       = isset($_GET['tgl_dari'])       ? trim($_GET['tgl_dari'])       : '';  // BARU
$tgl_sampai     = isset($_GET['tgl_sampai'])     ? trim($_GET['tgl_sampai'])     : '';  // BARU

// Validasi format tanggal
$tgl_dari_valid    = ($tgl_dari !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_dari));
$tgl_sampai_valid  = ($tgl_sampai !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tgl_sampai));

// Daftar POP
$daftar_pop = [];
$qrpop = $conn_pasang->query("SELECT DISTINCT pop FROM pemasangan WHERE status IN ('selesai','on') AND marketing IS NOT NULL AND marketing != '' ORDER BY pop ASC");
if ($qrpop) {
    while ($poprow = $qrpop->fetch_assoc()) {
        $daftar_pop[] = $poprow['pop'];
    }
}

// Pagination
$limit = 30;
$page  = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Where clause
$where = "p.status IN ('selesai','on') AND p.marketing IS NOT NULL AND p.marketing != ''";
if ($cari !== '') {
    $cari_safe = $conn_pasang->real_escape_string($cari);
    $where .= " AND (p.nama LIKE '%$cari_safe%' OR p.marketing LIKE '%$cari_safe%')";
}
if ($cari_alamat !== '') {                                                              // BARU
    $alamat_safe = $conn_pasang->real_escape_string($cari_alamat);
    $where .= " AND p.alamat LIKE '%$alamat_safe%'";
}
if ($pop_filter !== '') {
    $pop_safe = $conn_pasang->real_escape_string($pop_filter);
    $where .= " AND p.pop='$pop_safe'";
}
if ($payment_filter === 'paid') {
    $where .= " AND pfs.status = 'paid'";
} elseif ($payment_filter === 'unpaid') {
    $where .= " AND (pfs.status = 'unpaid' OR pfs.status IS NULL)";
}
if ($tgl_dari_valid) {                                                                  // BARU
    $where .= " AND p.tanggal >= '" . $conn_pasang->real_escape_string($tgl_dari) . "'";
}
if ($tgl_sampai_valid) {                                                                // BARU
    $where .= " AND p.tanggal <= '" . $conn_pasang->real_escape_string($tgl_sampai) . "'";
}

// Total
$total_sql    = "SELECT COUNT(p.id) AS total FROM pemasangan p LEFT JOIN pemasangan_fee_marketing_status pfs ON p.id = pfs.pemasangan_id WHERE $where";
$total_result = $conn_pasang->query($total_sql);
$total        = ($total_result) ? (int)($total_result->fetch_assoc()['total'] ?? 0) : 0;
$total_halaman = max(1, (int)ceil($total / $limit));
$page          = min($page, $total_halaman); // clamp
$offset        = ($page - 1) * $limit;

// Query utama
$sql = "SELECT p.*, COALESCE(pfs.status, 'unpaid') AS status_pembayaran
        FROM pemasangan p
        LEFT JOIN pemasangan_fee_marketing_status pfs ON p.id = pfs.pemasangan_id
        WHERE $where
        ORDER BY p.tanggal DESC
        LIMIT $limit OFFSET $offset";
$result = $conn_pasang->query($sql);

$result_rows   = [];
$marketingNames = [];
if ($result && $result->num_rows > 0) {
    while ($r = $result->fetch_assoc()) {
        $result_rows[]   = $r;
        $mk = trim((string)($r['marketing'] ?? ''));
        if ($mk !== '') $marketingNames[$mk] = true;
    }
}

// Bulk ambil mitra
$mitraMap = [];
if (!empty($marketingNames) && isset($conn_market) && ($conn_market instanceof mysqli)) {
    $names        = array_keys($marketingNames);
    $placeholders = implode(',', array_fill(0, count($names), '?'));
    $sqlM  = "SELECT id, nama, wa, payment_type, bank_nama, bank_rekening, e_wallet_nama, e_wallet_nomor, created_at FROM mitra WHERE nama IN ($placeholders)";
    $stmtM = $conn_market->prepare($sqlM);
    if ($stmtM) {
        $types = str_repeat('s', count($names));
        $stmtM->bind_param($types, ...$names);
        $stmtM->execute();
        $resM = $stmtM->get_result();
        while ($m = $resM->fetch_assoc()) {
            $mitraMap[$m['nama']] = $m;
        }
        $stmtM->close();
    }
}

// Helper: build pagination URL tetap bawa semua filter
function pagUrl(int $p): string {
    $params = $_GET;
    $params['page'] = $p;
    return '?' . http_build_query($params);
}

// Cek apakah ada filter aktif
$ada_filter = ($cari !== '' || $cari_alamat !== '' || $pop_filter !== '' || $payment_filter !== '' || $tgl_dari !== '' || $tgl_sampai !== '');
?>

<h1><i class="bi bi-megaphone-fill"></i> Fee Marketing</h1>

<!-- ===== FILTER CARD ===== -->
<div class="form-card mb-4">
    <h2 class="form-title mb-3">Filter & Pencarian</h2>
    <form method="get" id="formFilter">

        <!-- Baris 1: Nama/Marketing & Alamat -->
        <div class="row gx-3 gy-2 mb-2">
            <div class="col-md-6">
                <label class="form-label" for="cariData">
                    <i class="bi bi-search"></i> Cari Nama / Marketing
                </label>
                <input name="cari" id="cariData" class="form-control"
                       placeholder="Nama pelanggan atau marketing..."
                       value="<?= htmlspecialchars($cari) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="cariAlamat">
                    <i class="bi bi-geo-alt"></i> Cari Alamat
                </label>
                <input name="cari_alamat" id="cariAlamat" class="form-control"
                       placeholder="Kata kunci alamat..."
                       value="<?= htmlspecialchars($cari_alamat) ?>">
            </div>
        </div>

        <!-- Baris 2: POP, Status, Tanggal Dari, Tanggal Sampai -->
        <div class="row gx-3 gy-2 mb-3">
            <div class="col-md-3">
                <label class="form-label" for="filterPop">
                    <i class="bi bi-diagram-3"></i> Filter POP
                </label>
                <select name="pop_filter" id="filterPop" class="form-select">
                    <option value="">-- Semua POP --</option>
                    <?php foreach ($daftar_pop as $pop): ?>
                        <option value="<?= htmlspecialchars($pop) ?>"
                            <?= $pop_filter === $pop ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pop) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="filterPaymentStatus">
                    <i class="bi bi-credit-card"></i> Status Pembayaran
                </label>
                <select name="payment_filter" id="filterPaymentStatus" class="form-select">
                    <option value="">-- Semua Status --</option>
                    <option value="unpaid" <?= $payment_filter === 'unpaid' ? 'selected' : '' ?>>Belum Dibayar</option>
                    <option value="paid"   <?= $payment_filter === 'paid'   ? 'selected' : '' ?>>Sudah Dibayar</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="tglDari">
                    <i class="bi bi-calendar-event"></i> Tanggal Pasang Dari
                </label>
                <input type="date" name="tgl_dari" id="tglDari" class="form-control"
                       value="<?= htmlspecialchars($tgl_dari) ?>"
                       max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="tglSampai">
                    <i class="bi bi-calendar-check"></i> Sampai Tanggal
                </label>
                <input type="date" name="tgl_sampai" id="tglSampai" class="form-control"
                       value="<?= htmlspecialchars($tgl_sampai) ?>"
                       max="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <!-- Tombol aksi -->
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-search"></i> Terapkan Filter
            </button>
            <?php if ($ada_filter): ?>
                <a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Reset Filter
                </a>
            <?php endif; ?>
            <span class="ms-auto text-muted small">
                Menampilkan <strong><?= number_format($total) ?></strong> data
                <?php if ($total_halaman > 1): ?>
                    &mdash; Halaman <strong><?= $page ?></strong> / <strong><?= $total_halaman ?></strong>
                <?php endif; ?>
            </span>
        </div>

    </form>
</div>

<!-- ===== LABEL FILTER AKTIF ===== -->
<?php if ($ada_filter): ?>
<div class="mb-3 d-flex flex-wrap gap-2 align-items-center">
    <small class="text-muted me-1">Filter aktif:</small>
    <?php if ($cari !== ''): ?>
        <span class="badge bg-secondary"><?= htmlspecialchars($cari) ?> <a href="?<?= http_build_query(array_merge($_GET, ['cari'=>'', 'page'=>1])) ?>" class="text-white ms-1 text-decoration-none">&times;</a></span>
    <?php endif; ?>
    <?php if ($cari_alamat !== ''): ?>
        <span class="badge bg-secondary">Alamat: <?= htmlspecialchars($cari_alamat) ?> <a href="?<?= http_build_query(array_merge($_GET, ['cari_alamat'=>'', 'page'=>1])) ?>" class="text-white ms-1 text-decoration-none">&times;</a></span>
    <?php endif; ?>
    <?php if ($pop_filter !== ''): ?>
        <span class="badge bg-info text-dark">POP: <?= htmlspecialchars($pop_filter) ?> <a href="?<?= http_build_query(array_merge($_GET, ['pop_filter'=>'', 'page'=>1])) ?>" class="text-dark ms-1 text-decoration-none">&times;</a></span>
    <?php endif; ?>
    <?php if ($payment_filter !== ''): ?>
        <span class="badge <?= $payment_filter === 'paid' ? 'bg-success' : 'bg-danger' ?>">
            <?= $payment_filter === 'paid' ? 'Sudah Dibayar' : 'Belum Dibayar' ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['payment_filter'=>'', 'page'=>1])) ?>" class="text-white ms-1 text-decoration-none">&times;</a>
        </span>
    <?php endif; ?>
    <?php if ($tgl_dari !== '' || $tgl_sampai !== ''): ?>
        <span class="badge bg-warning text-dark">
            <i class="bi bi-calendar3"></i>
            <?= $tgl_dari !== '' ? date('d/m/Y', strtotime($tgl_dari)) : '...' ?>
            &mdash;
            <?= $tgl_sampai !== '' ? date('d/m/Y', strtotime($tgl_sampai)) : '...' ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['tgl_dari'=>'', 'tgl_sampai'=>'', 'page'=>1])) ?>" class="text-dark ms-1 text-decoration-none">&times;</a>
        </span>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ===== TABEL DATA ===== -->
<div class="table-container">
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-dark">
                <tr>
                    <th class="text-center" style="width:50px">No</th>
                    <th>Nama Pelanggan</th>
                    <th>POP</th>
                    <th>Alamat</th>
                    <th>Telepon</th>
                    <th>Marketing</th>
                    <th class="text-center" style="width:90px">Tgl Pasang</th>
                    <th style="min-width:260px">Status Pembayaran</th>
                    <th class="text-center" style="width:80px">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($result_rows)): ?>
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                        Data tidak ditemukan.
                        <?php if ($ada_filter): ?>
                            <br><a href="<?= strtok($_SERVER['REQUEST_URI'], '?') ?>">Reset filter</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: $no = $offset + 1; foreach ($result_rows as $row): ?>
                <?php
                    $status = $row['status_pembayaran'] ?? 'unpaid';
                    $mkName = trim((string)($row['marketing'] ?? ''));
                    $mitra  = ($mkName !== '' && isset($mitraMap[$mkName])) ? $mitraMap[$mkName] : null;
                    $tglFmt = '';
                    if (!empty($row['tanggal'])) {
                        $ts = strtotime($row['tanggal']);
                        $tglFmt = $ts ? date('d/m/Y', $ts) : htmlspecialchars($row['tanggal']);
                    }
                ?>
                <tr id="row-<?= (int)$row['id'] ?>">
                    <td class="text-center text-muted small"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['nama'] ?? '-') ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['pop'] ?? '-') ?></span></td>
                    <td class="small"><?= htmlspecialchars($row['alamat'] ?? '-') ?></td>
                    <td class="small"><?= htmlspecialchars($row['telp'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($row['marketing'] ?? '-') ?></td>
                    <td class="text-center small"><?= $tglFmt ?: '-' ?></td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button"
                                    class="btn <?= ($status === 'paid') ? 'btn-success' : 'btn-outline-success' ?>"
                                    onclick="updatePaymentStatus(<?= (int)$row['id'] ?>, 'paid')"
                                    title="Tandai sudah dibayar">
                                <i class="bi bi-check-circle<?= ($status === 'paid') ? '-fill' : '' ?>"></i>
                                Sudah
                            </button>
                            <button type="button"
                                    class="btn <?= ($status === 'unpaid') ? 'btn-danger' : 'btn-outline-danger' ?>"
                                    onclick="updatePaymentStatus(<?= (int)$row['id'] ?>, 'unpaid')"
                                    title="Tandai belum dibayar">
                                <i class="bi bi-x-circle<?= ($status === 'unpaid') ? '-fill' : '' ?>"></i>
                                Belum
                            </button>
                        </div>

                        <div class="small text-muted mt-1" id="wa-status-<?= (int)$row['id'] ?>"></div>

                        <?php if ($status === 'unpaid'): ?>
                            <div class="mt-1 small text-muted lh-sm">
                                <span class="fw-semibold">Fee:</span> <?= htmlspecialchars(rupiah(FEE_MARKETING_NOMINAL)) ?> &bull;
                                <?php if (!$mitra): ?>
                                    <span class="text-danger">Mitra tidak ditemukan</span>
                                <?php else: ?>
                                    <?php
                                        $ptype = strtolower(trim((string)$mitra['payment_type']));
                                        if (in_array($ptype, ['bank','transfer','rekening'], true)) {
                                    ?>
                                        TF Bank <?= htmlspecialchars($mitra['bank_nama'] ?: '-') ?>
                                        &mdash; <?= htmlspecialchars($mitra['bank_rekening'] ?: '-') ?>
                                    <?php } elseif (in_array($ptype, ['ewallet','e-wallet','wallet'], true)) { ?>
                                        <?= htmlspecialchars($mitra['e_wallet_nama'] ?: '-') ?>
                                        &mdash; <?= htmlspecialchars($mitra['e_wallet_nomor'] ?: '-') ?>
                                    <?php } else { ?>
                                        Bank: <?= htmlspecialchars(($mitra['bank_nama'] ?: '-') . ' / ' . ($mitra['bank_rekening'] ?: '-')) ?>
                                        | E-Wallet: <?= htmlspecialchars(($mitra['e_wallet_nama'] ?: '-') . ' / ' . ($mitra['e_wallet_nomor'] ?: '-')) ?>
                                    <?php } ?>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <a href="cetak_fee_marketing.php?id=<?= urlencode($row['id']) ?>"
                           target="_blank" class="btn btn-outline-info btn-sm" title="Cetak">
                            <i class="bi bi-printer"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ===== PAGINASI ===== -->
<?php if ($total_halaman > 1): ?>
<?php
    // Rentang halaman yang ditampilkan (maks 7 tombol angka)
    $range   = 3; // halaman kiri/kanan dari current
    $pg_start = max(1, $page - $range);
    $pg_end   = min($total_halaman, $page + $range);
    // selalu tampilkan minimal 7 halaman jika cukup
    if (($pg_end - $pg_start) < ($range * 2)) {
        if ($pg_start === 1) $pg_end   = min($total_halaman, 1 + $range * 2);
        else                 $pg_start = max(1, $total_halaman - $range * 2);
    }
?>
<nav class="mt-4" aria-label="Navigasi halaman">
    <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3">

        <!-- Info -->
        <div class="text-muted small order-2 order-sm-1">
            <?php
                $dari_no  = $offset + 1;
                $sampai_no = min($offset + $limit, $total);
            ?>
            Menampilkan <strong><?= number_format($dari_no) ?></strong> &ndash; <strong><?= number_format($sampai_no) ?></strong>
            dari <strong><?= number_format($total) ?></strong> data
        </div>

        <!-- Tombol halaman -->
        <ul class="pagination pagination-sm mb-0 flex-wrap order-1 order-sm-2" style="gap:3px">

            <!-- Pertama + Prev -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link rounded" href="<?= pagUrl(1) ?>" title="Halaman pertama">
                    <i class="bi bi-chevron-double-left"></i>
                </a>
            </li>
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link rounded" href="<?= pagUrl($page - 1) ?>" title="Sebelumnya">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>

            <!-- Elipsis kiri -->
            <?php if ($pg_start > 1): ?>
                <li class="page-item disabled"><span class="page-link border-0 bg-transparent">…</span></li>
            <?php endif; ?>

            <!-- Nomor halaman -->
            <?php for ($i = $pg_start; $i <= $pg_end; $i++): ?>
                <li class="page-item <?= ($page === $i) ? 'active' : '' ?>">
                    <a class="page-link rounded" href="<?= pagUrl($i) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <!-- Elipsis kanan -->
            <?php if ($pg_end < $total_halaman): ?>
                <li class="page-item disabled"><span class="page-link border-0 bg-transparent">…</span></li>
            <?php endif; ?>

            <!-- Next + Terakhir -->
            <li class="page-item <?= ($page >= $total_halaman) ? 'disabled' : '' ?>">
                <a class="page-link rounded" href="<?= pagUrl($page + 1) ?>" title="Berikutnya">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
            <li class="page-item <?= ($page >= $total_halaman) ? 'disabled' : '' ?>">
                <a class="page-link rounded" href="<?= pagUrl($total_halaman) ?>" title="Halaman terakhir">
                    <i class="bi bi-chevron-double-right"></i>
                </a>
            </li>

        </ul>

        <!-- Loncat ke halaman -->
        <form method="get" class="d-flex align-items-center gap-2 order-3 small" id="formJumpPage">
            <?php foreach ($_GET as $k => $v): if ($k === 'page') continue; ?>
                <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
            <?php endforeach; ?>
            <label for="jumpPage" class="text-muted mb-0 text-nowrap">Ke hal.</label>
            <input type="number" name="page" id="jumpPage" class="form-control form-control-sm"
                   min="1" max="<?= $total_halaman ?>" value="<?= $page ?>"
                   style="width:65px; text-align:center;"
                   onchange="this.form.submit()">
            <span class="text-muted text-nowrap">/ <?= $total_halaman ?></span>
        </form>

    </div>
</nav>
<?php endif; ?>

<style>
/* Paginasi custom */
.pagination .page-link {
    min-width: 34px;
    text-align: center;
    font-size: .8rem;
    line-height: 1.4;
    padding: .3rem .55rem;
    border-radius: 6px !important;
    transition: background .15s;
}
.pagination .page-item.active .page-link {
    font-weight: 600;
    z-index: 1;
}
.pagination .page-item.disabled .page-link {
    opacity: .45;
    cursor: default;
}
/* Agar tabel tidak terlalu padat di kolom kecil */
.table th, .table td { vertical-align: middle; }
</style>

<script>
function updatePaymentStatus(id, status) {
    const label = status === 'paid' ? 'Sudah Dibayar' : 'Belum Dibayar';
    let msg = 'Ubah status menjadi "' + label + '"?';
    if (status === 'paid') msg += '\n\nNotifikasi WA akan dikirim jika data mitra tersedia.';
    if (!confirm(msg)) return;

    const waInfoEl = document.getElementById('wa-status-' + id);
    if (waInfoEl) waInfoEl.textContent = (status === 'paid') ? '⏳ Mengirim WA...' : '';

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=update_payment_status&id=' + encodeURIComponent(id) + '&status=' + encodeURIComponent(status)
    })
    .then(response => response.text().then(text => {
        if (!response.ok) throw new Error('HTTP ' + response.status + ': ' + text.substring(0, 200));
        try { return JSON.parse(text); } catch(e) { throw new Error('Invalid JSON: ' + e.message + '\n' + text.substring(0, 300)); }
    }))
    .then(data => {
        if (!data.success) {
            if (waInfoEl) waInfoEl.textContent = '';
            alert('Gagal: ' + (data.message || 'Unknown error'));
            return;
        }
        window.location.reload();
    })
    .catch(error => {
        if (waInfoEl) waInfoEl.textContent = '';
        alert('Error: ' + error.message);
    });
}

// Validasi range tanggal saat submit form
document.getElementById('formFilter').addEventListener('submit', function(e) {
    const dari   = document.getElementById('tglDari').value;
    const sampai = document.getElementById('tglSampai').value;
    if (dari && sampai && dari > sampai) {
        e.preventDefault();
        alert('Tanggal "Dari" tidak boleh lebih besar dari tanggal "Sampai".');
        document.getElementById('tglDari').focus();
    }
});
</script>

<?php require_once 'templates/footer.php'; ?>