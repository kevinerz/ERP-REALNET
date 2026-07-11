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
require_once 'db_config.php'; 
require_once 'notification_handler.php'; 

$connPemasangan = getDbConnection('pemasangan');
$connUmum       = getDbConnection('umum');

if (!$connPemasangan || !$connUmum) {
    die("Koneksi Database Gagal. Cek konfigurasi db_config.php");
}

$paketArray = [];
$resPaket = $connUmum->query("SELECT * FROM jaringan_paket ORDER BY id_paket ASC");
if ($resPaket) {
    while ($rowPaket = $resPaket->fetch_assoc()) {
        $paketArray[$rowPaket['id_paket']] = $rowPaket;
    }
    $resPaket->free();
}

$popList = [];
$resPop = $connPemasangan->query("SELECT DISTINCT pop FROM pelanggan_instalasi WHERE pop IS NOT NULL AND pop != '' ORDER BY pop ASC");
if ($resPop) {
    while ($r = $resPop->fetch_assoc()) { $popList[] = $r['pop']; }
    $resPop->free();
}

$filterPop = isset($_GET['pop']) ? trim($_GET['pop']) : '';

// Sort parameter (default: tanggal DESC = terbaru di atas)
$allowedSort = ['tanggal', 'nama', 'pop', 'vlan'];
$sortCol     = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort) ? $_GET['sort'] : 'tanggal';
$sortDir     = isset($_GET['dir'])  && $_GET['dir'] === 'asc' ? 'ASC' : 'DESC';
$nextDir     = $sortDir === 'DESC' ? 'asc' : 'desc';

$sql = "SELECT * FROM pelanggan_instalasi WHERE status = 'aktivasi'";
$params = []; $types = '';
if ($filterPop !== '') { $sql .= " AND pop = ?"; $params[] = $filterPop; $types .= 's'; }
$sql .= " ORDER BY {$sortCol} {$sortDir}";

$stmt = $connPemasangan->prepare($sql);
$result = (object)['num_rows' => 0];
if ($stmt) {
    if (count($params) > 0) $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) $result = $stmt->get_result();
    $stmt->close();
}

// Build all rows to array for client-side search
$allRows = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $allRows[] = $row;
    }
}

// Build sort URL helper
function sortUrl($col, $currentSort, $currentDir, $pop) {
    $dir = ($currentSort === $col && $currentDir === 'DESC') ? 'asc' : 'desc';
    $q = http_build_query(['sort' => $col, 'dir' => $dir, 'pop' => $pop]);
    return "?" . $q;
}
function sortIcon($col, $currentSort, $currentDir) {
    if ($currentSort !== $col) return '<i class="bi bi-arrow-down-up sort-icon-neutral"></i>';
    return $currentDir === 'DESC'
        ? '<i class="bi bi-sort-down sort-icon-active"></i>'
        : '<i class="bi bi-sort-up sort-icon-active"></i>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Proses Aktivasi</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* ==============================
           DESIGN SYSTEM — TOKENS
        ============================== */
        :root {
            --c-bg:        #0a0f1e;
            --c-surface:   #111827;
            --c-surface2:  #1a2235;
            --c-border:    #1e2d45;
            --c-border2:   #243351;
            --c-accent:    #3b82f6;
            --c-accent2:   #60a5fa;
            --c-accent-glow: rgba(59,130,246,.25);
            --c-success:   #10b981;
            --c-success-bg: rgba(16,185,129,.12);
            --c-warning:   #f59e0b;
            --c-warning-bg: rgba(245,158,11,.12);
            --c-danger:    #ef4444;
            --c-cyan:      #06b6d4;
            --c-cyan-bg:   rgba(6,182,212,.1);
            --c-text:      #e2e8f0;
            --c-text2:     #94a3b8;
            --c-text3:     #475569;
            --radius-sm:   8px;
            --radius-md:   12px;
            --radius-lg:   18px;
            --shadow-card: 0 4px 24px rgba(0,0,0,.45);
            --shadow-glow: 0 0 32px rgba(59,130,246,.12);
            --font-main:   'Plus Jakarta Sans', sans-serif;
            --font-mono:   'JetBrains Mono', monospace;
        }

        /* ==============================
           BASE
        ============================== */
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            background-color: var(--c-bg);
            background-image:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(59,130,246,.08), transparent),
                url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%231e2d45' fill-opacity='0.3'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            font-family: var(--font-main);
            color: var(--c-text);
            min-height: 100vh;
        }

        .wrap { max-width: 1500px; margin: 0 auto; padding: 24px 20px 56px; }

        /* ==============================
           HERO HEADER
        ============================== */
        .hero {
            position: relative;
            background: linear-gradient(135deg, #0d1b3e 0%, #0a1628 60%, #091020 100%);
            border: 1px solid var(--c-border2);
            border-radius: var(--radius-lg);
            padding: 2.25rem 2rem;
            margin-bottom: 1.5rem;
            overflow: hidden;
            box-shadow: var(--shadow-card), var(--shadow-glow);
        }
        .hero::before {
            content: '';
            position: absolute; inset: 0;
            background: linear-gradient(135deg, var(--c-accent-glow) 0%, transparent 60%);
            pointer-events: none;
        }
        .hero::after {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 280px; height: 280px;
            background: radial-gradient(circle, rgba(59,130,246,.12), transparent 70%);
            pointer-events: none;
        }
        .hero-label {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(59,130,246,.15); border: 1px solid rgba(59,130,246,.3);
            color: var(--c-accent2); font-size: .72rem; font-weight: 700;
            letter-spacing: 1.5px; text-transform: uppercase;
            padding: 4px 12px; border-radius: 50px; margin-bottom: .85rem;
        }
        .hero h1 {
            font-size: clamp(1.4rem, 3vw, 2rem);
            font-weight: 800; color: #fff;
            margin: 0 0 .35rem;
            letter-spacing: -.5px;
            line-height: 1.2;
        }
        .hero p { color: var(--c-text2); margin: 0; font-size: .88rem; }
        .hero-stats {
            display: flex; gap: .75rem; flex-wrap: wrap; margin-top: 1.5rem;
        }
        .stat-pill {
            display: flex; align-items: center; gap: 8px;
            background: rgba(255,255,255,.04); border: 1px solid var(--c-border2);
            border-radius: 50px; padding: 6px 16px;
            font-size: .82rem; color: var(--c-text2);
        }
        .stat-pill .num {
            font-size: 1.1rem; font-weight: 800;
            color: var(--c-accent2); font-family: var(--font-mono);
        }
        .hero-badge-total {
            display: inline-flex; align-items: center; gap: 8px;
            background: linear-gradient(135deg, var(--c-accent), #1d4ed8);
            color: #fff; font-size: 1rem; font-weight: 700;
            padding: 10px 22px; border-radius: 50px;
            box-shadow: 0 4px 16px rgba(59,130,246,.4);
        }

        /* ==============================
           TOOLBAR (Filter + Search)
        ============================== */
        .toolbar {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            display: flex; gap: .75rem; flex-wrap: wrap; align-items: center;
        }
        .toolbar-group { display: flex; align-items: center; gap: .5rem; flex: 1; min-width: 200px; }
        .toolbar-label {
            font-size: .72rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .8px; color: var(--c-text3); white-space: nowrap;
        }

        /* Custom form controls */
        .ctrl {
            background: var(--c-surface2);
            border: 1px solid var(--c-border2);
            border-radius: var(--radius-sm);
            color: var(--c-text);
            font-family: var(--font-main);
            font-size: .84rem;
            padding: .5rem .875rem;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .ctrl:focus { border-color: var(--c-accent); box-shadow: 0 0 0 3px var(--c-accent-glow); }
        .ctrl option { background: #1a2235; }
        select.ctrl { cursor: pointer; }

        /* Search box */
        .search-wrap { position: relative; flex: 1; min-width: 220px; max-width: 400px; }
        .search-wrap .bi { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--c-text3); font-size: .9rem; pointer-events: none; }
        .search-wrap input {
            width: 100%; padding-left: 36px;
            background: var(--c-surface2);
            border: 1px solid var(--c-border2);
            border-radius: var(--radius-sm);
            color: var(--c-text); font-family: var(--font-main); font-size: .84rem;
            padding-top: .5rem; padding-bottom: .5rem;
            outline: none; transition: border-color .2s, box-shadow .2s;
        }
        .search-wrap input:focus { border-color: var(--c-accent); box-shadow: 0 0 0 3px var(--c-accent-glow); }
        .search-wrap input::placeholder { color: var(--c-text3); }
        #clearSearch {
            position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--c-text3); cursor: pointer;
            font-size: .9rem; padding: 2px 4px; display: none;
        }
        #clearSearch:hover { color: var(--c-danger); }

        .btn-reset {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(239,68,68,.1); border: 1px solid rgba(239,68,68,.25);
            color: #fca5a5; font-size: .78rem; font-weight: 600; padding: 6px 14px;
            border-radius: var(--radius-sm); cursor: pointer; text-decoration: none;
            transition: all .15s; white-space: nowrap;
        }
        .btn-reset:hover { background: rgba(239,68,68,.2); border-color: rgba(239,68,68,.5); color: #fca5a5; }

        /* Search counter badge */
        #searchResultBadge {
            display: none; font-size: .75rem; color: var(--c-accent2);
            white-space: nowrap; font-weight: 600;
        }

        /* ==============================
           TABLE CARD
        ============================== */
        .table-card {
            background: var(--c-surface);
            border: 1px solid var(--c-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-card);
        }

        .data-table {
            width: 100%; border-collapse: separate; border-spacing: 0;
            font-size: .845rem; table-layout: fixed;
        }
        .data-table col.c-no     { width: 46px; }
        .data-table col.c-pel    { width: 22%; }
        .data-table col.c-paket  { width: 17%; }
        .data-table col.c-pppoe  { width: 23%; }
        .data-table col.c-vlan   { width: 80px; }
        .data-table col.c-tgl    { width: 13%; }
        .data-table col.c-pet    { width: 11%; }
        .data-table col.c-aksi   { width: 88px; }
        .data-table td, .data-table th { overflow: hidden; text-overflow: ellipsis; }

        /* HEAD */
        .data-table thead tr {
            background: var(--c-surface2);
        }
        .data-table thead th {
            padding: .9rem .875rem;
            color: var(--c-text3); font-size: .7rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .8px;
            border-bottom: 1px solid var(--c-border2);
            white-space: nowrap; user-select: none;
        }
        .data-table thead th.sortable {
            cursor: pointer; transition: color .15s;
        }
        .data-table thead th.sortable:hover { color: var(--c-accent2); }
        .data-table thead th.sortable a {
            color: inherit; text-decoration: none;
            display: flex; align-items: center; gap: 5px;
        }
        .data-table thead th.sort-active a { color: var(--c-accent2); }
        .sort-icon-neutral { opacity: .35; font-size: .75rem; }
        .sort-icon-active   { color: var(--c-accent2); font-size: .75rem; }

        /* BODY ROWS */
        .data-table tbody tr {
            border-bottom: 1px solid var(--c-border);
            transition: background .15s;
            animation: rowIn .3s ease both;
        }
        @keyframes rowIn {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .data-table tbody tr:last-child { border-bottom: none; }
        .data-table tbody tr:hover { background: rgba(59,130,246,.05); }
        .data-table tbody tr.row-newest {
            background: linear-gradient(90deg, rgba(16,185,129,.06) 0%, transparent 80%);
            border-left: 3px solid var(--c-success);
        }
        .data-table tbody tr.row-newest:hover { background: rgba(16,185,129,.09); }
        .data-table tbody td {
            padding: .875rem .875rem;
            vertical-align: middle; color: var(--c-text);
        }

        /* NO ROW */
        .no-data-row td {
            padding: 4rem 1rem; text-align: center;
        }
        .no-data-row .no-data-icon { font-size: 2.5rem; color: var(--c-text3); margin-bottom: .75rem; }
        .no-data-row p { color: var(--c-text3); margin: 0; font-size: .9rem; }

        /* ==============================
           CELLS — BADGES & ELEMENTS
        ============================== */
        .no-badge {
            display: inline-flex; align-items: center; justify-content: center;
            width: 32px; height: 32px;
            background: var(--c-surface2); border: 1px solid var(--c-border2);
            border-radius: 50%; color: var(--c-text3);
            font-size: .78rem; font-weight: 700;
        }
        /* New badge for latest row */
        .badge-new {
            display: inline-flex; align-items: center; gap: 3px;
            background: var(--c-success-bg); border: 1px solid rgba(16,185,129,.3);
            color: var(--c-success); font-size: .62rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .8px;
            padding: 2px 7px; border-radius: 50px; vertical-align: middle; margin-left: 4px;
        }
        /* Pelanggan cell */
        .cell-pelanggan .name {
            font-size: .9rem; font-weight: 700; color: var(--c-text);
            display: flex; align-items: center; flex-wrap: wrap; gap: 4px;
        }
        .pop-badge {
            display: inline-flex; align-items: center; gap: 4px;
            background: var(--c-warning-bg); border: 1px solid rgba(245,158,11,.2);
            color: var(--c-warning); font-size: .68rem; font-weight: 700;
            padding: 2px 8px; border-radius: 50px; margin-top: 4px;
        }
        /* Paket cell */
        .cell-paket .paket-name { font-weight: 700; color: var(--c-text); }
        .speed-badge {
            display: inline-flex; align-items: center;
            background: var(--c-success-bg); border: 1px solid rgba(16,185,129,.2);
            color: var(--c-success); font-size: .68rem; font-weight: 700;
            padding: 2px 8px; border-radius: 50px; margin-top: 3px;
        }
        .price { color: var(--c-success); font-size: .82rem; font-weight: 700; font-family: var(--font-mono); margin-top: 3px; }
        /* PPPoE cell */
        .cell-pppoe { font-family: var(--font-mono); font-size: .8rem; }
        .pppoe-user { color: var(--c-accent2); font-weight: 600; }
        .pppoe-pass { color: var(--c-text2); margin-top: 4px; }
        /* VLAN */
        .vlan-badge {
            display: inline-flex; align-items: center;
            background: var(--c-cyan-bg); border: 1px solid rgba(6,182,212,.2);
            color: var(--c-cyan); font-family: var(--font-mono); font-size: .8rem; font-weight: 700;
            padding: 4px 10px; border-radius: var(--radius-sm);
        }
        /* Date */
        .date-main { font-weight: 700; font-size: .85rem; color: var(--c-text); }
        .date-time { font-size: .75rem; color: var(--c-text3); font-family: var(--font-mono); margin-top: 2px; }
        /* Petugas */
        .petugas-badge {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(255,255,255,.05); border: 1px solid var(--c-border2);
            color: var(--c-text2); font-size: .75rem; font-weight: 600;
            padding: 4px 10px; border-radius: 50px;
        }
        /* Contact cell */
        .contact-text { font-size: .82rem; color: var(--c-text2); }
        .no-val { color: var(--c-text3); font-style: italic; font-size: .78rem; }

        /* ==============================
           COPY BUTTON
        ============================== */
        .btn-copy {
            display: inline-flex; align-items: center; justify-content: center;
            width: 22px; height: 22px;
            background: rgba(255,255,255,.05); border: 1px solid var(--c-border2);
            border-radius: 5px; color: var(--c-text3); cursor: pointer;
            font-size: 10px; flex-shrink: 0; padding: 0;
            transition: all .15s; vertical-align: middle; margin-left: 3px;
        }
        .btn-copy:hover  { background: rgba(59,130,246,.2); border-color: var(--c-accent); color: var(--c-accent2); }
        .btn-copy.copied { background: rgba(16,185,129,.2); border-color: var(--c-success); color: var(--c-success); }
        .copy-row { display: inline-flex; align-items: center; gap: 0; }
        .copy-row > span { max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

        /* ==============================
           ACTION BUTTON
        ============================== */
        .btn-detail {
            display: inline-flex; align-items: center; gap: 5px;
            background: rgba(59,130,246,.12); border: 1px solid rgba(59,130,246,.25);
            color: var(--c-accent2); font-size: .78rem; font-weight: 600;
            padding: 6px 14px; border-radius: var(--radius-sm); cursor: pointer;
            text-decoration: none; transition: all .2s; white-space: nowrap;
        }
        .btn-detail:hover {
            background: rgba(59,130,246,.25); border-color: rgba(59,130,246,.5);
            color: #fff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(59,130,246,.25);
        }

        /* ==============================
           MODAL
        ============================== */
        .modal-content {
            background: var(--c-surface);
            border: 1px solid var(--c-border2);
            border-radius: var(--radius-lg);
            color: var(--c-text);
        }
        .modal-header {
            background: linear-gradient(135deg, #0d1b3e, #0a1628);
            border-bottom: 1px solid var(--c-border2);
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
            padding: 1.25rem 1.5rem;
        }
        .modal-header .modal-title { font-weight: 800; font-size: 1rem; color: #fff; }
        .modal-body { padding: 1.75rem; }
        .modal-footer { background: var(--c-surface2); border-top: 1px solid var(--c-border); padding: 1rem 1.5rem; border-radius: 0 0 var(--radius-lg) var(--radius-lg); }

        .modal-section-title {
            font-size: .72rem; font-weight: 800; text-transform: uppercase;
            letter-spacing: 1px; padding-bottom: .75rem; margin-bottom: .5rem;
            border-bottom: 1px solid var(--c-border);
        }
        .modal-section-title.tech   { color: var(--c-accent2); }
        .modal-section-title.contact { color: var(--c-success); }

        .detail-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: .55rem 0; border-bottom: 1px solid rgba(255,255,255,.04);
        }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: var(--c-text3); font-size: .78rem; flex-shrink: 0; margin-right: 12px; }
        .detail-val { font-weight: 600; font-size: .85rem; text-align: right; word-break: break-all; }
        .detail-val.mono { font-family: var(--font-mono); }
        .detail-val.accent { color: var(--c-accent2); }
        .detail-val.danger { color: #fca5a5; }
        .detail-val-wrap { display: flex; align-items: center; justify-content: flex-end; gap: 5px; flex-wrap: wrap; }

        /* Alamat block */
        .addr-block {
            background: var(--c-surface2); border: 1px solid var(--c-border);
            border-radius: var(--radius-sm); padding: .6rem .875rem;
            font-size: .8rem; color: var(--c-text2); line-height: 1.6;
        }
        /* Maps btn */
        .btn-maps {
            display: flex; align-items: center; justify-content: center; gap: 7px;
            background: rgba(59,130,246,.1); border: 1px solid rgba(59,130,246,.25);
            color: var(--c-accent2); font-size: .82rem; font-weight: 600;
            padding: 8px 16px; border-radius: var(--radius-sm);
            text-decoration: none; transition: all .2s; width: 100%; margin-top: .75rem;
        }
        .btn-maps:hover { background: rgba(59,130,246,.25); color: #fff; }
        .btn-maps-dis {
            display: flex; align-items: center; justify-content: center; gap: 7px;
            background: var(--c-surface2); border: 1px solid var(--c-border);
            color: var(--c-text3); font-size: .82rem;
            padding: 8px 16px; border-radius: var(--radius-sm); width: 100%;
            margin-top: .75rem; cursor: not-allowed;
        }
        .btn-close-modal {
            background: rgba(255,255,255,.08); border: 1px solid var(--c-border2);
            color: var(--c-text2); padding: 8px 20px; border-radius: var(--radius-sm);
            cursor: pointer; font-size: .84rem; font-weight: 600; transition: all .15s;
        }
        .btn-close-modal:hover { background: rgba(255,255,255,.15); color: #fff; }

        /* ==============================
           SEARCH HIGHLIGHT
        ============================== */
        mark.hl {
            background: rgba(245,158,11,.3); color: var(--c-warning);
            border-radius: 2px; padding: 0 2px;
        }

        /* ==============================
           TOAST
        ============================== */
        .copy-toast {
            position: fixed; bottom: 28px; left: 50%;
            transform: translateX(-50%) translateY(12px);
            background: #0d1b3e; border: 1px solid var(--c-border2);
            color: var(--c-text); padding: 10px 20px;
            border-radius: var(--radius-sm); font-size: .82rem;
            opacity: 0; pointer-events: none;
            transition: opacity .2s, transform .2s;
            z-index: 9999; white-space: nowrap;
            box-shadow: 0 8px 32px rgba(0,0,0,.6);
        }
        .copy-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }

        /* ==============================
           FOOTER
        ============================== */
        .page-footer { text-align: center; color: var(--c-text3); font-size: .78rem; margin-top: 2.5rem; }

        

        /* ==============================
           RESPONSIVE / MOBILE
        ============================== */
        @media (max-width: 768px) {
            .hero { padding: 1.5rem 1.25rem; }
            .hero h1 { font-size: 1.4rem; }
            .hero-stats { display: none; }
            .toolbar { gap: .5rem; }
            .search-wrap { max-width: 100%; }

            .data-table thead { display: none; }
            .data-table, .data-table colgroup,
            .data-table tbody,
            .data-table tr, .data-table td { display: block; width: 100% !important; }
            .data-table tr {
                background: var(--c-surface);
                border: 1px solid var(--c-border2);
                border-radius: var(--radius-md);
                margin-bottom: .875rem;
                overflow: hidden;
            }
            .data-table tr.row-newest { border-left: 3px solid var(--c-success); }
            .data-table tbody tr:hover { background: var(--c-surface); }
            .data-table td {
                display: flex; justify-content: space-between;
                align-items: flex-start; padding: .65rem 1rem;
                border-bottom: 1px solid var(--c-border); text-align: right;
                overflow: visible;
            }
            .data-table td:last-child { border-bottom: none; }
            .data-table td[data-label]::before {
                content: attr(data-label); flex-shrink: 0;
                font-size: .68rem; font-weight: 800; letter-spacing: .6px;
                text-transform: uppercase; color: var(--c-text3);
                margin-right: 1rem; padding-top: 3px;
            }
            .data-content { text-align: right; flex: 1; word-break: break-word; }
            .copy-row > span { max-width: 150px; }
            .table-card { border-radius: 0; border-left: none; border-right: none; }
        }

        /* Scroll bar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--c-bg); }
        ::-webkit-scrollbar-thumb { background: var(--c-border2); border-radius: 99px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--c-accent); }

        /* Bootstrap override for dark modal backdrop */
        .modal-backdrop { background-color: #000; }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>
    <?php if(function_exists('display_notification')) display_notification(); ?>

    <!-- COPY TOAST -->
    <div class="copy-toast" id="copyToast"></div>

    <div class="wrap">

        <!-- ===== HERO HEADER ===== -->
        <div class="hero">
            <div class="row align-items-center gy-3">
                <div class="col-lg-8">
                    <div class="hero-label">
                        <i class="bi bi-activity"></i> Live Monitoring
                    </div>
                    <h1><i class="bi bi-ui-checks-grid me-2"></i>Data Proses Aktivasi</h1>
                    <p>Monitoring pelanggan dengan status <strong style="color:var(--c-accent2)">Aktivasi</strong> yang menunggu jadwal instalasi teknisi. Data terbaru ditampilkan paling atas.</p>
                    <div class="hero-stats">
                        <div class="stat-pill">
                            <span class="num"><?= count($allRows) ?></span>
                            <span>Total Antrean</span>
                        </div>
                        <div class="stat-pill">
                            <i class="bi bi-router-fill" style="color:var(--c-warning)"></i>
                            <span><?= count($popList) ?> POP Aktif</span>
                        </div>
                        <div class="stat-pill">
                            <i class="bi bi-calendar-check" style="color:var(--c-success)"></i>
                            <span>Update: <?= date('d M Y, H:i') ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <div class="hero-badge-total">
                        <i class="bi bi-people-fill"></i>
                        <span><?= count($allRows) ?> Pelanggan</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== TOOLBAR ===== -->
        <div class="toolbar">
            <!-- Filter POP -->
            <form method="get" class="toolbar-group" id="filterForm">
                <span class="toolbar-label"><i class="bi bi-funnel me-1"></i>POP</span>
                <!-- Carry sort params -->
                <input type="hidden" name="sort" value="<?= htmlspecialchars($sortCol) ?>">
                <input type="hidden" name="dir"  value="<?= $sortDir === 'DESC' ? 'desc' : 'asc' ?>">
                <select name="pop" class="ctrl" id="filterPop" onchange="this.form.submit()">
                    <option value="">Semua POP</option>
                    <?php foreach($popList as $pop): ?>
                        <option value="<?= htmlspecialchars($pop) ?>" <?= $pop === $filterPop ? 'selected' : '' ?>>
                            POP <?= htmlspecialchars(strtoupper($pop)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>

            <!-- Search -->
            <div class="toolbar-group">
                <span class="toolbar-label"><i class="bi bi-search me-1"></i>Cari</span>
                <div class="search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" id="globalSearch" placeholder="Nama, PPPoE, VLAN, email, telepon…" autocomplete="off">
                    <button id="clearSearch" title="Hapus pencarian"><i class="bi bi-x-lg"></i></button>
                </div>
                <span id="searchResultBadge"></span>
            </div>

            <?php if($filterPop !== ''): ?>
                <a href="?sort=<?= htmlspecialchars($sortCol) ?>&dir=<?= $sortDir === 'DESC' ? 'desc' : 'asc' ?>" class="btn-reset">
                    <i class="bi bi-x-circle"></i> Reset Filter
                </a>
            <?php endif; ?>
        </div>

        <!-- ===== TABLE ===== -->
        <div class="table-card">
            <table class="data-table" id="mainTable">
                <colgroup>
                    <col class="c-no">
                    <col class="c-pel">
                    <col class="c-paket">
                    <col class="c-pppoe">
                    <col class="c-vlan">
                    <col class="c-tgl">
                    <col class="c-pet">
                    <col class="c-aksi">
                </colgroup>
                    <thead>
                        <tr>
                            <th class="text-center">#</th>

                            <th class="sortable <?= $sortCol === 'nama' ? 'sort-active' : '' ?>">
                                <a href="<?= sortUrl('nama', $sortCol, $sortDir, $filterPop) ?>">
                                    Pelanggan <?= sortIcon('nama', $sortCol, $sortDir) ?>
                                </a>
                            </th>

                            <th>Paket</th>

                            <th>Data PPPoE</th>

                            <th class="sortable text-center <?= $sortCol === 'vlan' ? 'sort-active' : '' ?>">
                                <a href="<?= sortUrl('vlan', $sortCol, $sortDir, $filterPop) ?>" style="justify-content:center">
                                    VLAN <?= sortIcon('vlan', $sortCol, $sortDir) ?>
                                </a>
                            </th>

                            <th class="sortable text-center <?= $sortCol === 'tanggal' ? 'sort-active' : '' ?>">
                                <a href="<?= sortUrl('tanggal', $sortCol, $sortDir, $filterPop) ?>" style="justify-content:center">
                                    Tgl. Aktivasi <?= sortIcon('tanggal', $sortCol, $sortDir) ?>
                                </a>
                            </th>

                            <th class="text-center">Petugas</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                        <?php if (empty($allRows)): ?>
                            <tr class="no-data-row">
                                <td colspan="8">
                                    <div class="no-data-icon"><i class="bi bi-inbox"></i></div>
                                    <p>Tidak ada data aktivasi<?= $filterPop ? " untuk POP <strong>".htmlspecialchars(strtoupper($filterPop))."</strong>" : '' ?>.</p>
                                </td>
                            </tr>
                        <?php else:
                            $no = 1;
                            $isFirst = true;
                            foreach ($allRows as $row):
                                $idPaket = intval($row['paket']);
                                $paket   = $paketArray[$idPaket] ?? ['nama_paket' => 'Unknown', 'kecepatan' => '-', 'harga' => 0];

                                $rawMaps = trim($row['gmaps'] ?? $row['url_maps'] ?? $row['maps'] ?? '');
                                $mapsUrl = '#'; $hasMap = false;
                                if (!empty($rawMaps)) {
                                    $hasMap  = true;
                                    $mapsUrl = preg_match('/^http/i', $rawMaps)
                                        ? $rawMaps
                                        : "https://www.google.com/maps/search/?api=1&query=" . urlencode($rawMaps);
                                }
                                $email = trim($row['email'] ?? '');
                                $telp  = trim($row['telp']  ?? '');

                                // Mark as newest (first row = most recent when sorted by tanggal DESC)
                                $isNewest = ($isFirst && $sortCol === 'tanggal' && $sortDir === 'DESC' && $filterPop === '');
                                $isFirst  = false;

                                // Search data attributes (lowercase for JS search)
                                $searchData = strtolower(implode(' ', [
                                    $row['nama'], $row['userppp'], $row['passwordppp'],
                                    $row['vlan'], $row['pop'], $email, $telp,
                                    $paket['nama_paket'], $row['teknisi'] ?? '',
                                    $row['last_updated_by'] ?? '', $row['odp'] ?? '',
                                    $row['sn'] ?? ''
                                ]));
                        ?>
                            <tr class="<?= $isNewest ? 'row-newest' : '' ?>" data-search="<?= htmlspecialchars($searchData) ?>">

                                <!-- No -->
                                <td data-label="#" class="text-center">
                                    <div class="data-content">
                                        <span class="no-badge"><?= $no++ ?></span>
                                        <?php if($isNewest): ?>
                                            <br><span class="badge-new" style="margin-top:4px;display:inline-flex"><i class="bi bi-lightning-fill"></i>Baru</span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Pelanggan -->
                                <td data-label="Pelanggan">
                                    <div class="data-content cell-pelanggan">
                                        <div class="name">
                                            <span><?= htmlspecialchars($row['nama']) ?></span>
                                            <button class="btn-copy" onclick="copyText(this,'<?= htmlspecialchars($row['nama'], ENT_QUOTES) ?>')" title="Salin nama"><i class="bi bi-clipboard"></i></button>
                                        </div>
                                        <div class="pop-badge">
                                            <i class="bi bi-router-fill"></i><?= htmlspecialchars(strtoupper($row['pop'])) ?>
                                        </div>
                                    </div>
                                </td>

                                <!-- Paket -->
                                <td data-label="Paket">
                                    <div class="data-content cell-paket">
                                        <div class="paket-name"><?= htmlspecialchars($paket['nama_paket']) ?></div>
                                        <div class="speed-badge"><i class="bi bi-speedometer2 me-1"></i><?= htmlspecialchars($paket['kecepatan']) ?></div>
                                        <div class="price">Rp <?= number_format($paket['harga'], 0, ',', '.') ?></div>
                                    </div>
                                </td>

                                <!-- PPPoE -->
                                <td data-label="PPPoE">
                                    <div class="data-content cell-pppoe">
                                        <div class="copy-row pppoe-user">
                                            <i class="bi bi-person-fill me-1" style="font-size:.75rem"></i>
                                            <span title="<?= htmlspecialchars($row['userppp']) ?>"><?= htmlspecialchars($row['userppp']) ?></span>
                                            <button class="btn-copy" onclick="copyText(this,'<?= htmlspecialchars($row['userppp'], ENT_QUOTES) ?>')" title="Salin username"><i class="bi bi-clipboard"></i></button>
                                        </div>
                                        <div class="copy-row pppoe-pass" style="margin-top:4px">
                                            <i class="bi bi-key-fill me-1" style="font-size:.75rem"></i>
                                            <span><?= htmlspecialchars($row['passwordppp']) ?></span>
                                            <button class="btn-copy" onclick="copyText(this,'<?= htmlspecialchars($row['passwordppp'], ENT_QUOTES) ?>')" title="Salin password"><i class="bi bi-clipboard"></i></button>
                                        </div>
                                    </div>
                                </td>


                                <!-- VLAN -->
                                <td data-label="VLAN" class="text-md-center">
                                    <div class="data-content">
                                        <span class="copy-row" style="justify-content:flex-end">
                                            <span class="vlan-badge"><?= htmlspecialchars($row['vlan']) ?></span>
                                            <button class="btn-copy" onclick="copyText(this,'<?= htmlspecialchars($row['vlan'], ENT_QUOTES) ?>')" title="Salin VLAN"><i class="bi bi-clipboard"></i></button>
                                        </span>
                                    </div>
                                </td>

                                <!-- Tanggal -->
                                <td data-label="Tgl. Aktivasi" class="text-md-center">
                                    <div class="data-content">
                                        <div class="date-main"><?= date('d M Y', strtotime($row['tanggal'])) ?></div>
                                        <div class="date-time"><i class="bi bi-clock me-1"></i><?= date('H:i', strtotime($row['tanggal'])) ?></div>
                                    </div>
                                </td>

                                <!-- Petugas -->
                                <td data-label="Petugas" class="text-md-center">
                                    <div class="data-content">
                                        <span class="petugas-badge">
                                            <i class="bi bi-person-check"></i>
                                            <?= htmlspecialchars($row['last_updated_by'] ?? '—') ?>
                                        </span>
                                    </div>
                                </td>

                                <!-- Aksi -->
                                <td data-label="Aksi" class="text-md-center">
                                    <div class="data-content">
                                        <button class="btn-detail"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalDetail<?= $row['id'] ?>">
                                            <i class="bi bi-eye-fill"></i> Detail
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- ===== MODAL DETAIL ===== -->
                            <div class="modal fade" id="modalDetail<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="bi bi-person-badge-fill me-2" style="color:var(--c-accent2)"></i>
                                                <?= htmlspecialchars($row['nama']) ?>
                                                <span class="pop-badge ms-2">POP <?= htmlspecialchars(strtoupper($row['pop'])) ?></span>
                                            </h5>
                                            <button type="button" class="btn-copy" style="width:28px;height:28px" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-4">
                                                <!-- Kolom Kiri: Data Teknis -->
                                                <div class="col-lg-6">
                                                    <div class="modal-section-title tech">
                                                        <i class="bi bi-hdd-rack me-2"></i>Data Teknis
                                                    </div>
                                                    <?php
                                                    $techFields = [
                                                        ['label' => 'User PPPoE',    'val' => $row['userppp'],     'cls' => 'mono accent'],
                                                        ['label' => 'Password',       'val' => $row['passwordppp'], 'cls' => 'mono danger'],
                                                        ['label' => 'VLAN',           'val' => $row['vlan'],        'cls' => 'mono'],
                                                        ['label' => 'POP',            'val' => strtoupper($row['pop'] ?? ''), 'cls' => ''],
                                                        ['label' => 'ODP',            'val' => $row['odp'] ?? '',   'cls' => ''],
                                                        ['label' => 'SN Modem',       'val' => $row['sn'] ?? '',    'cls' => 'mono'],
                                                        ['label' => 'Kabel Dropcore', 'val' => $row['dropcore'] ?? '', 'cls' => ''],
                                                        ['label' => 'Teknisi',        'val' => $row['teknisi'] ?? '', 'cls' => ''],
                                                    ];
                                                    foreach ($techFields as $f):
                                                        $v   = trim($f['val']);
                                                        $esc = htmlspecialchars($v, ENT_QUOTES);
                                                    ?>
                                                    <div class="detail-row">
                                                        <span class="detail-label"><?= $f['label'] ?></span>
                                                        <div class="detail-val-wrap">
                                                            <?php if (!empty($v)): ?>
                                                                <span class="detail-val <?= $f['cls'] ?>"><?= htmlspecialchars($v) ?></span>
                                                                <button class="btn-copy" onclick="copyText(this,'<?= $esc ?>')" title="Salin"><i class="bi bi-clipboard"></i></button>
                                                            <?php else: ?>
                                                                <span class="no-val">—</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>

                                                <!-- Kolom Kanan: Kontak & Lokasi -->
                                                <div class="col-lg-6">
                                                    <div class="modal-section-title contact">
                                                        <i class="bi bi-geo-alt-fill me-2"></i>Kontak & Lokasi
                                                    </div>
                                                    <?php
                                                    $contactFields = [
                                                        ['label' => 'Nama',      'val' => $row['nama']      ?? ''],
                                                        ['label' => 'No. KTP',   'val' => $row['ktp']       ?? ''],
                                                        ['label' => 'Telepon/WA','val' => $row['telp']      ?? ''],
                                                        ['label' => 'Email',     'val' => $email],
                                                        ['label' => 'Sales',     'val' => $row['marketing'] ?? ''],
                                                    ];
                                                    foreach ($contactFields as $f):
                                                        $v   = trim($f['val']);
                                                        $esc = htmlspecialchars($v, ENT_QUOTES);
                                                    ?>
                                                    <div class="detail-row">
                                                        <span class="detail-label"><?= $f['label'] ?></span>
                                                        <div class="detail-val-wrap">
                                                            <?php if (!empty($v)): ?>
                                                                <span class="detail-val"><?= htmlspecialchars($v) ?></span>
                                                                <button class="btn-copy" onclick="copyText(this,'<?= $esc ?>')" title="Salin"><i class="bi bi-clipboard"></i></button>
                                                            <?php else: ?>
                                                                <span class="no-val">—</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>

                                                    <!-- Alamat -->
                                                    <div class="detail-row" style="flex-direction:column;align-items:flex-start;">
                                                        <div class="d-flex justify-content-between w-100 align-items-center mb-2">
                                                            <span class="detail-label mb-0">Alamat</span>
                                                            <?php if (!empty($row['alamat'])): ?>
                                                            <button class="btn-copy" onclick="copyText(this,'<?= htmlspecialchars($row['alamat'], ENT_QUOTES) ?>')"><i class="bi bi-clipboard"></i></button>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="addr-block w-100"><?= nl2br(htmlspecialchars($row['alamat'] ?? '—')) ?></div>
                                                    </div>

                                                    <!-- Maps -->
                                                    <?php if($hasMap): ?>
                                                        <a href="<?= htmlspecialchars($mapsUrl) ?>" target="_blank" class="btn-maps">
                                                            <i class="bi bi-map-fill"></i> Buka Google Maps
                                                        </a>
                                                    <?php else: ?>
                                                        <div class="btn-maps-dis"><i class="bi bi-slash-circle me-1"></i> Tidak ada data peta</div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <small style="color:var(--c-text3)">Diperbarui oleh: <strong style="color:var(--c-text2)"><?= htmlspecialchars($row['last_updated_by'] ?? '—') ?></strong></small>
                                            <button type="button" class="btn-close-modal" data-bs-dismiss="modal">Tutup</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; endif; ?>
                    </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="page-footer">
            <p class="mb-0">&copy; <?= date('Y') ?> PT. Real Data Solusindo &mdash; Sistem Manajemen Pemasangan</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // ==============================
    // COPY
    // ==============================
    function copyText(btn, text) {
        const doToast = () => {
            const toast = document.getElementById('copyToast');
            const display = text.length > 40 ? text.substring(0, 40) + '…' : text;
            toast.innerHTML = '<i class="bi bi-check2 me-1" style="color:#10b981"></i>Disalin: <strong>' + display + '</strong>';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2000);
        };
        const doIcon = () => {
            const icon = btn.querySelector('i');
            icon.className = 'bi bi-check2';
            btn.classList.add('copied');
            setTimeout(() => { icon.className = 'bi bi-clipboard'; btn.classList.remove('copied'); }, 2000);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => { doIcon(); doToast(); }).catch(() => fallback());
        } else { fallback(); }
        function fallback() {
            const ta = document.createElement('textarea');
            ta.value = text; ta.style.cssText = 'position:fixed;opacity:0;pointer-events:none;';
            document.body.appendChild(ta); ta.select();
            try { document.execCommand('copy'); doIcon(); doToast(); } catch(e) {}
            document.body.removeChild(ta);
        }
    }

    // ==============================
    // CLIENT-SIDE SEARCH
    // ==============================
    (function() {
        const input   = document.getElementById('globalSearch');
        const clearBtn = document.getElementById('clearSearch');
        const badge   = document.getElementById('searchResultBadge');
        const rows    = document.querySelectorAll('#tableBody tr[data-search]');
        const total   = rows.length;

        function doSearch() {
            const q = input.value.trim().toLowerCase();
            clearBtn.style.display = q ? 'block' : 'none';

            if (!q) {
                rows.forEach(r => { r.style.display = ''; });
                badge.style.display = 'none';
                return;
            }

            const terms = q.split(/\s+/).filter(Boolean);
            let vis = 0;
            rows.forEach(r => {
                const hay = r.getAttribute('data-search') || '';
                const match = terms.every(t => hay.includes(t));
                r.style.display = match ? '' : 'none';
                if (match) vis++;
            });

            badge.style.display = 'inline';
            badge.textContent = vis + ' / ' + total + ' ditemukan';
        }

        input.addEventListener('input', doSearch);
        clearBtn.addEventListener('click', () => { input.value = ''; doSearch(); input.focus(); });
    })();
    </script>
</body>
</html>

<?php
if(isset($connPemasangan)) $connPemasangan->close();
if(isset($connUmum)) $connUmum->close();
?>