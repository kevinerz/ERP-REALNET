<?php
// ==========================================
// AKTIVASI PELANGGAN - TECH DASHBOARD MODE
// ==========================================
session_start(); 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ------------------------------------------
// KONFIGURASI API & DB
// ------------------------------------------
define('STARSENDER_API_URL',   'https://api.starsender.online/api/send');
define('STARSENDER_API_TOKEN', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124');

function getDbConnection($dbName) {
    if ($dbName === 'pemasangan') {
        $conn = new mysqli("localhost", "u272457353_kevinsamsung9", "Admionkevin99", "u272457353_db_pemasangan");
    } elseif ($dbName === 'umum') {
        $conn = new mysqli("localhost", "u272457353_kevinsamsung99", "Admionkevin99", "u272457353_umumdata");
    }
    
    if (isset($conn) && $conn->connect_error) {
        die("DB Connection Error ($dbName): " . $conn->connect_error);
    }
    return $conn;
}

// ------------------------------------------
// SYSTEM NOTIFICATION
// ------------------------------------------
$globalNotif = null;
function set_notification($message, $type = "info") {
    global $globalNotif;
    $globalNotif = ['message' => $message, 'type' => $type];
}

// ------------------------------------------
// WA SENDER (FORMAT TEKNIS & KEREN)
// ------------------------------------------
function getGroupIdForPop($pop) {
    $groups = [
        "rajeg"    => "6281293958590-1587210420@g.us",
        "kemeri"   => "6287770366015-1628875457@g.us",
        "kelapa"   => "120363423157487069@g.us",
        "panggang" => "120363405472722137@g.us",
        "muncung"  => "120363424548647899@g.us",
        "mauk"     => "120363419348224895@g.us",
    ];
    return $groups[strtolower(trim($pop))] ?? null;
}

function sendWhatsAppNotification($customerData, $paketData) {
    $nama_pop = $customerData['pop'] ?? 'Unknown';
    $group_id = getGroupIdForPop($nama_pop);

    if (!$group_id) return false;

    $id_tik  = $customerData['id'];
    $nama    = $customerData['nama'] ?? '-';
    $alamat  = $customerData['alamat'] ?? '-';
    $userPpp = $customerData['userppp'] ?? '-';
    $passPpp = $customerData['passwordppp'] ?? '-';
    $vlan    = $customerData['vlan'] ?? '-';
    $telp    = $customerData['telp'] ?? '-';
    
    $pkgName  = $paketData['nama_paket'] ?? 'Custom';
    $pkgSpeed = $paketData['kecepatan'] ?? '-';
    $pkgPrice = number_format($paketData['harga'] ?? 0, 0, ',', '.');
    $tglAktif = date('d/m/Y H:i');

    $message = "⚡ *AKTIVASI LAYANAN BARU* ⚡\n" .
               "══════════════════\n" .
               "🆔 *Tiket ID :* #{$id_tik}\n" .
               "🏢 *POP Area :* {$nama_pop}\n" .
               "📅 *Waktu    :* {$tglAktif} WIB\n\n" .

               "👤 *CUSTOMER INFO*\n" .
               "──────────────────\n" .
               "🏷️ *Nama    :* {$nama}\n" .
               "🏠 *Alamat :* {$alamat}\n" .
               "📱 *Kontak :* {$telp}\n\n" .

               "📦 *SERVICE DATA*\n" .
               "──────────────────\n" .
               "🚀 *Paket  :* {$pkgName}\n" .
               "⚡ *Speed  :* {$pkgSpeed}\n" .
               "💰 *Tagihan:* Rp {$pkgPrice}/bln\n\n" .

               "🔐 *NETWORK CONFIG (PPPoE)*\n" .
               "──────────────────\n" .
               "👤 *User :* `{$userPpp}`\n" .
               "🔑 *Pass :* `{$passPpp}`\n" .
               "🔢 *VLAN :* `{$vlan}`\n\n" .
               
               "⚠️ _Mohon teknisi melakukan konfigurasi modem sesuai data di atas._\n" .
               "✅ _Status: ONLINE_";

    $curl = curl_init(STARSENDER_API_URL);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "messageType" => "text",
            "to"          => $group_id,
            "body"        => $message
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: ' . STARSENDER_API_TOKEN
        ],
        CURLOPT_TIMEOUT => 10
    ]);

    $resp     = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return ($httpCode >= 200 && $httpCode < 300);
}

// ------------------------------------------
// MAIN LOGIC
// ------------------------------------------
$connPemasangan = getDbConnection('pemasangan');
$connUmum       = getDbConnection('umum');

// Load Data Paket
$paketArray = [];
$resPaket   = $connUmum->query("SELECT * FROM paket ORDER BY harga ASC");
while ($row = $resPaket->fetch_assoc()) {
    $paketArray[$row['id_paket']] = $row;
}

// Handler: HAPUS DATA (BARU)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_data'])) {
    $id = filter_var($_POST['id_hapus'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $connPemasangan->prepare("DELETE FROM pemasangan WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        set_notification("🗑️ Data #$id berhasil dihapus.", "danger");
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handler: Batal / Pending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_aktivasi'])) {
    $id = filter_var($_POST['id_aktivasi'], FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $connPemasangan->prepare("UPDATE pemasangan SET status='disimpan', last_updated_by='Admin' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        set_notification("Data pelanggan disimpan (Pending).", "warning");
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handler: Simpan & Aktivasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_activate'])) {
    $id   = filter_var($_POST['id_aktivasi'], FILTER_VALIDATE_INT);
    $user = trim($_POST['userppp']);
    $pass = trim($_POST['passwordppp']);
    $vlan = trim($_POST['vlan']);
    $pkt  = filter_var($_POST['paket'], FILTER_VALIDATE_INT);

    if ($id && $user && $pass && $vlan && $pkt) {
        $stmt = $connPemasangan->prepare("UPDATE pemasangan SET userppp=?, passwordppp=?, vlan=?, paket=?, status='aktivasi', last_updated_by='Admin' WHERE id=?");
        $stmt->bind_param("ssssi", $user, $pass, $vlan, $pkt, $id);
        
        if ($stmt->execute()) {
            set_notification("✅ Aktivasi Sukses! Notifikasi dikirim ke Group.", "success");
            $resCust  = $connPemasangan->query("SELECT * FROM pemasangan WHERE id=$id");
            $custData = $resCust->fetch_assoc();
            $pktData  = $paketArray[$pkt] ?? [];
            sendWhatsAppNotification($custData, $pktData);
        } else {
            set_notification("Gagal update database.", "danger");
        }
        $stmt->close();
    } else {
        set_notification("Data tidak lengkap!", "danger");
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ------------------------------------------
// FILTER & SEARCH (BARU)
// ------------------------------------------
$filterPop   = trim($_GET['pop'] ?? '');
$searchQuery = trim($_GET['q'] ?? '');

// Build query dinamis
$where  = "WHERE status='belum diproses'";
$params = [];
$types  = '';

if ($filterPop !== '') {
    $where   .= " AND pop=?";
    $params[] = $filterPop;
    $types   .= 's';
}
if ($searchQuery !== '') {
    $like     = "%$searchQuery%";
    $where   .= " AND (nama LIKE ? OR telp LIKE ? OR alamat LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}

if (count($params) > 0) {
    $stmtList = $connPemasangan->prepare("SELECT * FROM pemasangan $where ORDER BY id DESC");
    $stmtList->bind_param($types, ...$params);
    $stmtList->execute();
    $resultList = $stmtList->get_result();
} else {
    $resultList = $connPemasangan->query("SELECT * FROM pemasangan $where ORDER BY id DESC");
}
$totalQueue = $resultList->num_rows;

// Ambil list POP untuk dropdown filter
$popList    = [];
$resPopList = $connPemasangan->query("SELECT DISTINCT pop FROM pemasangan WHERE status='belum diproses' AND pop IS NOT NULL AND pop != '' ORDER BY pop ASC");
while ($rp = $resPopList->fetch_assoc()) $popList[] = $rp['pop'];

// Helper highlight search — didefinisikan sekali di sini, bukan di dalam loop
function hl($text, $q) {
    if (!$q) return htmlspecialchars($text);
    return preg_replace('/(' . preg_quote(htmlspecialchars($q), '/') . ')/i', '<mark>$1</mark>', htmlspecialchars($text));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NOC Dashboard — Aktivasi Pelanggan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: #f8fafc;
            font-family: 'Inter', sans-serif;
            color: #1e293b;
            font-size: 14px;
        }

        /* HEADER */
        .page-header {
            background: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 0;
            margin-bottom: 24px;
        }
        .page-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .page-sub { font-size: 12px; color: #94a3b8; margin-top: 2px; }

        .badge-queue {
            background: #fee2e2;
            color: #dc2626;
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-time {
            background: #f1f5f9;
            color: #64748b;
            border-radius: 6px;
            padding: 4px 12px;
            font-size: 12px;
            font-weight: 500;
        }

        /* FILTER */
        .filter-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 16px;
        }
        .filter-box .form-control,
        .filter-box .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            font-size: 13px;
            height: 38px;
            color: #1e293b;
            background: #fff;
        }
        .filter-box .form-control:focus,
        .filter-box .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,.1);
        }
        .filter-box .input-group-text {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-right: none;
            color: #94a3b8;
            border-radius: 7px 0 0 7px;
        }
        .filter-box .form-control.no-left {
            border-left: none;
            border-radius: 0 7px 7px 0;
        }
        .btn-go {
            height: 38px;
            background: #2563eb;
            border: none;
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            border-radius: 7px;
            padding: 0 16px;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-go:hover { background: #1d4ed8; color: #fff; }
        .btn-rst {
            height: 38px; width: 38px;
            background: #fff;
            border: 1px solid #e2e8f0;
            color: #94a3b8;
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            text-decoration: none;
            flex-shrink: 0;
        }
        .btn-rst:hover { border-color: #ef4444; color: #ef4444; }

        /* TABLE */
        .table-box {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .table-noc { width: 100%; border-collapse: collapse; margin: 0; }
        .table-noc thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: 11px 16px;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .table-noc thead th:first-child { padding-left: 20px; }
        .table-noc thead th:last-child  { padding-right: 20px; text-align: right; }
        .table-noc tbody tr { border-bottom: 1px solid #f1f5f9; }
        .table-noc tbody tr:last-child { border-bottom: none; }
        .table-noc tbody tr:hover { background: #f8fafc; }
        .table-noc td { padding: 13px 16px; vertical-align: middle; }
        .table-noc td:first-child { padding-left: 20px; }
        .table-noc td:last-child  { padding-right: 20px; }

        .tid { font-size: 12px; font-weight: 600; color: #2563eb; }
        .cust-name  { font-weight: 600; color: #0f172a; }
        .cust-phone { font-size: 12px; color: #64748b; margin-top: 2px; }
        .cust-phone i { color: #22c55e; }
        .pop-badge {
            display: inline-block;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            color: #1d4ed8;
            font-size: 11px; font-weight: 600;
            text-transform: uppercase;
            padding: 2px 8px; border-radius: 5px;
        }
        .addr-txt { font-size: 12px; color: #94a3b8; margin-top: 3px; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pkg-name { font-weight: 600; color: #0f172a; }
        .pkg-speed { font-size: 12px; color: #16a34a; margin-top: 2px; }
        .pkg-unset { color: #dc2626; font-size: 12px; font-weight: 600; }
        .date-txt  { font-size: 12px; color: #94a3b8; }

        .btn-proses {
            display: inline-flex; align-items: center; gap: 5px;
            background: #2563eb; color: #fff;
            border: none; border-radius: 7px;
            font-size: 12px; font-weight: 600;
            padding: 6px 14px; cursor: pointer;
            white-space: nowrap;
        }
        .btn-proses:hover { background: #1d4ed8; color: #fff; }
        .btn-hapus {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px;
            background: #fff;
            border: 1px solid #fecaca;
            color: #ef4444;
            border-radius: 7px; cursor: pointer; font-size: 13px;
        }
        .btn-hapus:hover { background: #fef2f2; border-color: #ef4444; }

        /* MOBILE CARDS */
        .card-grid { display: none; }

        @media (max-width: 767px) {
            .table-box  { display: none; }
            .card-grid  { display: flex; flex-direction: column; gap: 10px; }

            .m-card {
                background: #fff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 14px;
            }
            .m-card-top {
                display: flex; justify-content: space-between;
                align-items: center; margin-bottom: 8px;
            }
            .m-tid { font-size: 11px; font-weight: 600; color: #2563eb; background: #eff6ff; border: 1px solid #bfdbfe; padding: 2px 8px; border-radius: 5px; }
            .m-date { font-size: 11px; color: #94a3b8; }
            .m-name { font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 2px; }
            .m-phone { font-size: 12px; color: #64748b; margin-bottom: 8px; }
            .m-addr {
                font-size: 12px; color: #64748b;
                background: #f8fafc; border-radius: 6px;
                padding: 7px 10px; margin-bottom: 10px;
            }
            .m-meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px; }
            .m-meta-item { background: #f8fafc; border-radius: 7px; padding: 8px 10px; }
            .m-meta-lbl { font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; font-weight: 600; margin-bottom: 2px; }
            .m-meta-val { font-size: 13px; font-weight: 600; color: #0f172a; }
            .m-meta-val.blue  { color: #2563eb; }
            .m-meta-val.green { color: #16a34a; }
            .m-meta-val.red   { color: #dc2626; }
            .m-actions { display: flex; gap: 8px; }
            .m-actions .btn-proses { flex: 1; justify-content: center; padding: 10px; font-size: 13px; border-radius: 8px; }
            .m-actions .btn-hapus  { width: 42px; height: 42px; border-radius: 8px; font-size: 15px; flex-shrink: 0; }
        }

        /* EMPTY */
        .empty-box { padding: 56px 24px; text-align: center; }
        .empty-box i { font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 10px; }
        .empty-box p { color: #94a3b8; margin: 0; font-size: 13px; }

        /* NOTIF */
        .notif-bar {
            position: fixed; top: 72px; right: 16px; z-index: 9999;
            min-width: 280px; max-width: calc(100vw - 32px);
            border-radius: 8px;
            box-shadow: 0 4px 16px rgba(0,0,0,.1);
            font-size: 13px;
        }

        /* MODALS */
        .modal-content { border-radius: 12px; border: 1px solid #e2e8f0; }
        .modal-header  { background: #f8fafc; border-bottom: 1px solid #e2e8f0; border-radius: 12px 12px 0 0; padding: 14px 18px; }
        .modal-title   { font-size: 14px; font-weight: 700; color: #0f172a; }
        .modal-body    { padding: 18px; }
        .modal-footer  { background: #f8fafc; border-top: 1px solid #e2e8f0; border-radius: 0 0 12px 12px; padding: 12px 18px; }

        .f-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; color: #64748b; display: block; margin-bottom: 5px; }
        .f-ctrl {
            width: 100%; border: 1px solid #e2e8f0; border-radius: 7px;
            padding: 8px 11px; font-size: 13px; color: #0f172a;
            background: #fff; font-family: 'Inter', sans-serif;
            -webkit-appearance: none;
        }
        .f-ctrl:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); outline: none; }
        .f-hint { font-size: 11px; color: #94a3b8; margin-top: 4px; }

        .sec-label {
            font-size: 11px; font-weight: 600; text-transform: uppercase;
            letter-spacing: .05em; color: #64748b;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 8px; margin-bottom: 14px;
        }
        .cust-info-box {
            background: #f8fafc; border: 1px solid #e2e8f0;
            border-radius: 8px; padding: 10px 12px; margin-bottom: 16px;
        }
        .cust-info-name { font-weight: 700; color: #0f172a; font-size: 13px; }
        .cust-info-sub  { font-size: 11px; color: #64748b; margin-top: 2px; }

        .btn-modal-primary {
            background: #2563eb; border: none; color: #fff;
            border-radius: 7px; padding: 8px 20px;
            font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .btn-modal-primary:hover { background: #1d4ed8; color: #fff; }
        .btn-modal-secondary {
            background: #fff; border: 1px solid #e2e8f0; color: #64748b;
            border-radius: 7px; padding: 8px 16px;
            font-size: 13px; font-weight: 500; cursor: pointer;
        }
        .btn-modal-secondary:hover { border-color: #94a3b8; color: #374151; }
        .btn-modal-danger {
            background: #ef4444; border: none; color: #fff;
            border-radius: 7px; padding: 8px 20px;
            font-size: 13px; font-weight: 600; cursor: pointer;
        }
        .btn-modal-danger:hover { background: #dc2626; color: #fff; }

        .modal-del .modal-header { background: #fff5f5; border-bottom-color: #fecaca; }

        mark { background: #fef9c3; color: #854d0e; padding: 0 2px; border-radius: 2px; }

        .page-footer { text-align: center; padding: 24px 0 12px; font-size: 11px; color: #cbd5e1; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <?php if ($globalNotif): ?>
    <div class="alert alert-<?= $globalNotif['type'] ?> alert-dismissible fade show notif-bar" role="alert">
        <?= $globalNotif['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- HEADER -->
    <div class="page-header">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <div class="page-title"><i class="bi bi-hdd-rack me-2 text-primary"></i>NOC Activation</div>
                <div class="page-sub">Realnet — Manage Pending Installation Requests</div>
            </div>
            <div class="d-flex gap-2">
                <span class="badge-time"><i class="bi bi-clock me-1"></i><?= date('H:i') ?> WIB</span>
                <span class="badge-queue"><i class="bi bi-hourglass-split me-1"></i>Queue: <?= $totalQueue ?></span>
            </div>
        </div>
    </div>

    <div class="container pb-5">

        <!-- FILTER -->
        <div class="filter-box">
            <form method="GET">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-sm-5">
                        <div class="input-group" style="height:38px;">
                            <span class="input-group-text"><i class="bi bi-search" style="font-size:12px;"></i></span>
                            <input type="text" name="q" class="form-control no-left"
                                   placeholder="Cari nama, no. HP, atau alamat..."
                                   value="<?= htmlspecialchars($searchQuery) ?>">
                        </div>
                    </div>
                    <div class="col-7 col-sm-4">
                        <select name="pop" class="form-select">
                            <option value="">Semua POP Area</option>
                            <?php foreach ($popList as $p): ?>
                                <option value="<?= htmlspecialchars($p) ?>" <?= $filterPop === $p ? 'selected' : '' ?>>
                                    <?= htmlspecialchars(strtoupper($p)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5 col-sm-3 d-flex gap-2">
                        <button type="submit" class="btn-go w-100">
                            <i class="bi bi-funnel-fill me-1"></i>Filter
                        </button>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn-rst"><i class="bi bi-x-lg" style="font-size:12px;"></i></a>
                    </div>
                </div>
                <?php if ($searchQuery || $filterPop): ?>
                <div class="mt-2" style="font-size:12px; color:#94a3b8;">
                    <?= $totalQueue ?> hasil
                    <?php if ($searchQuery): ?> &mdash; "<strong style="color:#475569;"><?= htmlspecialchars($searchQuery) ?></strong>"<?php endif; ?>
                    <?php if ($filterPop): ?> &mdash; POP: <strong style="color:#475569;"><?= htmlspecialchars(strtoupper($filterPop)) ?></strong><?php endif; ?>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <?php
        $allRows = [];
        while ($row = $resultList->fetch_assoc()) $allRows[] = $row;
        ?>

        <?php if (count($allRows) === 0): ?>
        <div class="table-box">
            <div class="empty-box">
                <i class="bi bi-<?= ($searchQuery || $filterPop) ? 'search' : 'check2-all' ?>"></i>
                <p><?= ($searchQuery || $filterPop) ? 'Tidak ada data yang cocok.' : 'Tidak ada antrean aktivasi.' ?></p>
                <?php if ($searchQuery || $filterPop): ?>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" style="font-size:12px;color:#2563eb;">Reset Filter</a>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>

        <!-- DESKTOP TABLE -->
        <div class="table-box">
            <table class="table-noc">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>POP / Alamat</th>
                        <th>Paket</th>
                        <th>Tgl Daftar</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($allRows as $row): ?>
                    <tr>
                        <td><span class="tid">#<?= $row['id'] ?></span></td>
                        <td>
                            <div class="cust-name"><?= hl($row['nama'], $searchQuery) ?></div>
                            <div class="cust-phone"><i class="bi bi-whatsapp"></i> <?= hl($row['telp'], $searchQuery) ?></div>
                        </td>
                        <td>
                            <span class="pop-badge"><?= htmlspecialchars($row['pop']) ?></span>
                            <div class="addr-txt"><?= hl($row['alamat'], $searchQuery) ?></div>
                        </td>
                        <td>
                            <?php if (isset($paketArray[$row['paket']])): $p = $paketArray[$row['paket']]; ?>
                                <div class="pkg-name"><?= htmlspecialchars($p['nama_paket']) ?></div>
                                <div class="pkg-speed"><?= htmlspecialchars($p['kecepatan']) ?></div>
                            <?php else: ?>
                                <span class="pkg-unset">Unset</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="date-txt"><?= date('d M Y', strtotime($row['tanggal'])) ?></span></td>
                        <td>
                            <div class="d-flex gap-2 justify-content-end">
                                <button class="btn-proses" data-bs-toggle="modal" data-bs-target="#actModal<?= $row['id'] ?>">
                                    <i class="bi bi-lightning-charge-fill"></i> PROSES
                                </button>
                                <button class="btn-hapus" data-bs-toggle="modal" data-bs-target="#delModal<?= $row['id'] ?>">
                                    <i class="bi bi-trash3"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- MOBILE CARDS -->
        <div class="card-grid">
            <?php foreach ($allRows as $row):
                $p = $paketArray[$row['paket']] ?? null;
            ?>
            <div class="m-card">
                <div class="m-card-top">
                    <span class="m-tid">#<?= $row['id'] ?></span>
                    <span class="m-date"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                </div>
                <div class="m-name"><?= hl($row['nama'], $searchQuery) ?></div>
                <div class="m-phone"><i class="bi bi-whatsapp text-success me-1"></i><?= hl($row['telp'], $searchQuery) ?></div>
                <div class="m-addr"><i class="bi bi-geo-alt me-1 text-muted"></i><?= hl($row['alamat'], $searchQuery) ?></div>
                <div class="m-meta">
                    <div class="m-meta-item">
                        <div class="m-meta-lbl">POP</div>
                        <div class="m-meta-val blue"><?= htmlspecialchars(strtoupper($row['pop'])) ?></div>
                    </div>
                    <div class="m-meta-item">
                        <div class="m-meta-lbl">Paket</div>
                        <div class="m-meta-val <?= $p ? '' : 'red' ?>"><?= $p ? htmlspecialchars($p['nama_paket']) : 'Unset' ?></div>
                    </div>
                    <?php if ($p): ?>
                    <div class="m-meta-item" style="grid-column:span 2;">
                        <div class="m-meta-lbl">Kecepatan</div>
                        <div class="m-meta-val green"><?= htmlspecialchars($p['kecepatan']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="m-actions">
                    <button class="btn-proses" data-bs-toggle="modal" data-bs-target="#actModal<?= $row['id'] ?>">
                        <i class="bi bi-lightning-charge-fill"></i> PROSES AKTIVASI
                    </button>
                    <button class="btn-hapus" data-bs-toggle="modal" data-bs-target="#delModal<?= $row['id'] ?>">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

        <!-- MODALS -->
        <?php foreach ($allRows as $row):
            $cleanTelp  = preg_replace('/[^0-9]/', '', $row['telp']);
            $suffix     = '@' . $cleanTelp;
            $maxNameLen = max(3, 30 - strlen($suffix));
            $cleanName  = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $row['nama']));
            $sugUser    = substr($cleanName, 0, $maxNameLen) . $suffix;
            $sugPass    = "12345";
        ?>

        <!-- Activation Modal -->
        <div class="modal fade" id="actModal<?= $row['id'] ?>" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <form method="POST">
                        <input type="hidden" name="id_aktivasi" value="<?= $row['id'] ?>">
                        <input type="hidden" name="do_activate" value="1">
                        <div class="modal-header">
                            <div class="modal-title">
                                <i class="bi bi-terminal-fill text-primary me-2"></i>Activation Console #<?= $row['id'] ?>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="cust-info-box">
                                <div class="cust-info-name"><?= htmlspecialchars($row['nama']) ?></div>
                                <div class="cust-info-sub">
                                    <i class="bi bi-whatsapp text-success me-1"></i><?= htmlspecialchars($row['telp']) ?>
                                    <span class="mx-2 text-muted">|</span>
                                    <i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($row['pop']) ?>
                                </div>
                            </div>

                            <div class="sec-label"><i class="bi bi-box-seam me-1"></i>Service Configuration</div>
                            <div class="mb-4">
                                <label class="f-label">Paket Internet</label>
                                <select name="paket" class="f-ctrl" required>
                                    <option value="" disabled>-- Pilih Paket --</option>
                                    <?php foreach ($paketArray as $pk): ?>
                                        <option value="<?= $pk['id_paket'] ?>" <?= ($pk['id_paket'] == $row['paket']) ? 'selected' : '' ?>>
                                            <?= $pk['nama_paket'] ?> [<?= $pk['kecepatan'] ?>] — Rp <?= number_format($pk['harga']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="sec-label"><i class="bi bi-router me-1"></i>Network & Auth (PPPoE)</div>
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <label class="f-label">Username</label>
                                    <input type="text" name="userppp" class="f-ctrl"
                                           value="<?= htmlspecialchars($row['userppp'] ?: $sugUser) ?>" required maxlength="30">
                                    <div class="f-hint">Max 30 karakter</div>
                                </div>
                                <div class="col-6">
                                    <label class="f-label">Password</label>
                                    <input type="text" name="passwordppp" class="f-ctrl"
                                           value="<?= htmlspecialchars($row['passwordppp'] ?: $sugPass) ?>" required>
                                </div>
                            </div>
                            <div>
                                <label class="f-label">VLAN ID</label>
                                <input type="number" name="vlan" class="f-ctrl"
                                       placeholder="Contoh: 100, 200" value="<?= htmlspecialchars($row['vlan']) ?>" required>
                                <div class="f-hint">Pastikan sesuai konfigurasi OLT / Mikrotik.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="cancel_aktivasi" value="1" class="btn-modal-secondary">
                                <i class="bi bi-archive me-1"></i>Simpan (Pending)
                            </button>
                            <button type="submit" class="btn-modal-primary">
                                <i class="bi bi-check-lg me-1"></i>AKTIVASI & KIRIM WA
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Modal -->
        <div class="modal fade modal-del" id="delModal<?= $row['id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-title text-danger">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>Hapus Data
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <div style="width:52px;height:52px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-size:20px;color:#ef4444;margin-bottom:12px;">
                            <i class="bi bi-trash3-fill"></i>
                        </div>
                        <div style="font-weight:700;color:#0f172a;margin-bottom:4px;">Hapus data #<?= $row['id'] ?>?</div>
                        <div style="font-size:12px;color:#64748b;margin-bottom:8px;"><?= htmlspecialchars($row['nama']) ?></div>
                        <div style="font-size:12px;color:#ef4444;">Tindakan ini tidak dapat dibatalkan.</div>
                    </div>
                    <div class="modal-footer justify-content-center gap-2">
                        <button type="button" class="btn-modal-secondary" data-bs-dismiss="modal">Batal</button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="id_hapus" value="<?= $row['id'] ?>">
                            <button type="submit" name="delete_data" value="1" class="btn-modal-danger">
                                <i class="bi bi-trash3 me-1"></i>Hapus
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php endforeach; ?>

        <div class="page-footer">REALNET NETWORK OPERATION CENTER</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const notif = document.querySelector('.notif-bar');
        if (notif) setTimeout(() => bootstrap.Alert.getOrCreateInstance(notif)?.close(), 4000);
    </script>
</body>
</html>

<?php
if (isset($connPemasangan)) $connPemasangan->close();
if (isset($connUmum)) $connUmum->close();
?>