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

if (!file_exists('db_config.php') || !file_exists('notification_handler.php')) {
    die("ERROR: File konfigurasi atau handler notifikasi hilang.");
}
require_once 'db_config.php';
require_once 'notification_handler.php';

$connPemasangan = getDbConnection('pemasangan');
$connUmum       = getDbConnection('umum');

if (!$connPemasangan || !$connUmum) { exit; }

// ==========================================
// LOGIKA FILTER, SORTING, DAN PAGINASI
// ==========================================
$cari          = isset($_GET['cari'])          ? trim($_GET['cari']) : '';
$pop_filter    = isset($_GET['pop_filter'])    ? trim($_GET['pop_filter']) : '';
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : '';
$sort          = isset($_GET['sort'])          ? $_GET['sort'] : 'tanggal';
$order         = ($sort == 'nama' || $sort == 'pop') ? 'ASC' : 'DESC';

$daftar_status = [
    'on'      => 'Aktif (ON)',
    'selesai' => 'Selesai'
];

// Daftar POP unik
$daftar_pop = [];
$qrpop = $connPemasangan->query("
    SELECT DISTINCT pop 
    FROM pemasangan 
    WHERE status IN ('selesai','on') 
      AND pop IS NOT NULL 
      AND pop != '' 
    ORDER BY pop ASC
");
while ($poprow = $qrpop->fetch_assoc()) {
    $daftar_pop[] = $poprow['pop'];
}

// WHERE dinamis
$where_params = [];
$where_types  = '';
$where_sql    = "status IN ('selesai','on')";

if ($cari !== '') {
    $where_sql      .= " AND nama LIKE ?";
    $where_params[]  = "%$cari%";
    $where_types    .= 's';
}
if ($pop_filter !== '') {
    $where_sql      .= " AND pop = ?";
    $where_params[]  = $pop_filter;
    $where_types    .= 's';
}
if ($status_filter && array_key_exists($status_filter, $daftar_status)) {
    $where_sql      .= " AND status = ?";
    $where_params[]  = $status_filter;
    $where_types    .= 's';
}

// Total Data
$total = 0;
$stmt_total = $connPemasangan->prepare("SELECT COUNT(*) AS total FROM pemasangan WHERE $where_sql");
if ($stmt_total) {
    if ($where_types) {
        $stmt_total->bind_param($where_types, ...$where_params);
    }
    $stmt_total->execute();
    $total = $stmt_total->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_total->close();
}

// Statistik per status (ON / Selesai) untuk header
$status_counts = ['on' => 0, 'selesai' => 0];
$stmt_stat = $connPemasangan->prepare("
    SELECT status, COUNT(*) AS jml
    FROM pemasangan
    WHERE $where_sql
    GROUP BY status
");
if ($stmt_stat) {
    if ($where_types) {
        $stmt_stat->bind_param($where_types, ...$where_params);
    }
    $stmt_stat->execute();
    $res_stat = $stmt_stat->get_result();
    while ($row_stat = $res_stat->fetch_assoc()) {
        $s = $row_stat['status'];
        if (isset($status_counts[$s])) {
            $status_counts[$s] = (int)$row_stat['jml'];
        }
    }
    $stmt_stat->close();
}

// Pagination
$limit = 30;
$page  = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset         = ($page - 1) * $limit;
$total_halaman  = max(1, ceil($total / $limit));

// Fetch data
$order_by  = "ORDER BY (status='on') DESC, $sort $order";
$sql       = "SELECT * FROM pemasangan WHERE $where_sql $order_by LIMIT ? OFFSET ?";
$stmt_data = $connPemasangan->prepare($sql);

$where_types_query = $where_types . 'ii';
$where_params_query = $where_params;
$where_params_query[] = $limit;
$where_params_query[] = $offset;

if ($stmt_data) {
    $stmt_data->bind_param($where_types_query, ...$where_params_query);
    $stmt_data->execute();
    $result = $stmt_data->get_result();
    $stmt_data->close();
} else {
    $result = (object)['num_rows' => 0];
}

// Paket Map
$paket_map = [];
$rp = $connUmum->query("SELECT id_paket, nama_paket, kecepatan FROM paket");
while ($p = $rp->fetch_assoc()) {
    $paket_map[$p['id_paket']] = $p;
}

// Serial Number Map
$serial_map = [];
$rm = $connUmum->query("SELECT id_modem, serial_number FROM modem");
while ($rowm = $rm->fetch_assoc()) {
    $serial_map[$rowm['id_modem']] = $rowm['serial_number'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>REALNET | Data Pemasangan Selesai / ON</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Monitoring pemasangan pelanggan REALNET (status ON & Selesai) dengan filter POP, status, dan paket.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --rn-primary: #2563eb;
            --rn-primary-soft: #e0edff;
            --rn-success: #16a34a;
            --rn-success-soft: #e8f8f0;
            --rn-bg: #f3f6fb;
            --rn-text-dark: #1f2933;
            --rn-border-soft: #e2e8f0;
        }
        body {
            background: var(--rn-bg);
        }
        .container-page {
            padding-top: 30px;
            padding-bottom: 40px;
        }

        /* Header summary */
        .page-header-themed {
            background: linear-gradient(120deg, var(--rn-primary) 0%, #22c55e 100%);
            color: #fff;
            padding: 1.4rem 1.5rem;
            border-radius: 18px;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.18);
        }
        .page-header-title {
            font-size: 1.2rem;
            font-weight: 700;
            letter-spacing: .01em;
        }
        .page-header-sub {
            font-size: .9rem;
            opacity: .8;
        }
        .chip-stat {
            background: rgba(255, 255, 255, 0.12);
            border-radius: 999px;
            padding: .5rem .9rem;
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font-size: .8rem;
            margin-left: .4rem;
        }
        .chip-dot {
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #bbf7d0;
        }
        .chip-dot.on {
            background: #bfdbfe;
        }
        .chip-label {
            opacity: .9;
        }
        .chip-value {
            font-weight: 700;
        }

        /* Filter card */
        .filter-card {
            background: #fff;
            border-radius: 14px;
            padding: 1rem 1.25rem 0.6rem;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.06);
            border: 1px solid var(--rn-border-soft);
            margin-bottom: 1.25rem;
        }
        .filter-card .form-label {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6b7280;
        }

        /* Table card */
        .table-card {
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .responsive-table {
            width: 100%;
        }
        .responsive-table thead th {
            background: #eef4ff;
            color: #4b5563;
            font-weight: 700;
            font-size: .78rem;
            text-transform: uppercase;
            padding: .7rem .9rem;
            border: none;
            white-space: nowrap;
        }
        .responsive-table tbody td {
            vertical-align: middle;
            padding: .7rem .9rem;
            font-size: .85rem;
            border-top: 1px solid #f1f5f9;
        }
        .responsive-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .responsive-table tbody tr:hover {
            background-color: #eef2ff;
        }

        /* Status badges */
        .status-on {
            background-color: #dbeafe !important;
            color: #1d4ed8 !important;
            font-weight: 600;
            border-radius: 999px;
            padding: .2rem .65rem;
            font-size: .75rem;
        }
        .status-selesai {
            background-color: #dcfce7 !important;
            color: #15803d !important;
            font-weight: 600;
            border-radius: 999px;
            padding: .2rem .65rem;
            font-size: .75rem;
        }
        .row-on-soft {
            box-shadow: inset 3px 0 0 #2563eb33;
        }

        .col-name-main {
            font-weight: 600;
            color: var(--rn-text-dark);
        }
        .col-name-sub {
            font-size: .78rem;
        }

        /* Aksi buttons */
        .btn-action {
            font-size: .78rem;
            border-radius: 999px;
            padding: .3rem .65rem;
        }

        /* Tag filter aktif */
        .active-filters {
            font-size: .78rem;
        }
        .badge-filter {
            border-radius: 999px;
            background: #eff6ff;
            color: #1e40af;
            padding: .25rem .6rem;
            margin-right: .25rem;
        }

        /* Pagination */
        .pagination .page-link {
            color: var(--rn-primary);
            border-radius: 999px !important;
            margin: 0 .1rem;
            font-size: .8rem;
        }
        .pagination .page-item.active .page-link {
            background: var(--rn-primary);
            border-color: var(--rn-primary);
            color: #fff;
        }

        /* Mobile card table */
        @media (max-width: 992px) {
            .page-header-themed {
                padding: 1.1rem 1rem;
                border-radius: 16px;
            }
            .filter-card {
                padding: .9rem .9rem .5rem;
            }
            .responsive-table thead {
                display: none;
            }
            .responsive-table tr {
                display: block;
                background: #fff;
                border-radius: 12px;
                margin: 0.75rem 0;
                box-shadow: 0 10px 18px rgba(15, 23, 42, 0.06);
                border: 1px solid #e5e7eb;
            }
            .responsive-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: .6rem .9rem;
                border-top: 1px solid #f1f5f9;
            }
            .responsive-table tbody td[data-label]::before {
                content: attr(data-label);
                font-weight: 600;
                color: #4b5563;
                text-align: left;
                padding-right: .75rem;
                flex-shrink: 0;
                font-size: .75rem;
            }
            .responsive-table tbody td .data-content {
                text-align: right;
                flex-grow: 1;
                font-size: .8rem;
            }
            .responsive-table tbody tr td:first-child {
                border-top: none;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container container-page">
    <!-- HEADER SUMMARY -->
    <div class="page-header-themed mb-3">
        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-2">
            <div>
                <div class="page-header-title mb-1">
                    <i class="bi bi-clipboard2-data me-1"></i>
                    Data Pemasangan Selesai / ON
                </div>
                <div class="page-header-sub">
                    Total <?= number_format($total, 0, ',', '.') ?> pelanggan pada filter saat ini.
                </div>
            </div>
            <div class="d-flex flex-wrap align-items-center">
                <span class="chip-stat">
                    <span class="chip-dot on"></span>
                    <span class="chip-label">Aktif (ON)</span>
                    <span class="chip-value"><?= number_format($status_counts['on'], 0, ',', '.'); ?></span>
                </span>
                <span class="chip-stat">
                    <span class="chip-dot"></span>
                    <span class="chip-label">Selesai</span>
                    <span class="chip-value"><?= number_format($status_counts['selesai'], 0, ',', '.'); ?></span>
                </span>
            </div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-card">
        <form method="get" class="row gx-2 gy-2 align-items-end filter-form">
            <div class="col-md-3 col-sm-6">
                <label class="form-label mb-1">Cari Nama</label>
                <input
                    name="cari"
                    class="form-control form-control-sm"
                    placeholder="Nama pelanggan"
                    value="<?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-3 col-sm-6">
                <label class="form-label mb-1">Filter POP</label>
                <select name="pop_filter" class="form-select form-select-sm">
                    <option value="">Semua POP</option>
                    <?php foreach ($daftar_pop as $pop): ?>
                        <option value="<?= htmlspecialchars($pop, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $pop_filter === $pop ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($pop), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label mb-1">Filter Status</label>
                <select name="status_filter" class="form-select form-select-sm">
                    <option value="">Semua Status</option>
                    <?php foreach ($daftar_status as $val => $label): ?>
                        <option value="<?= htmlspecialchars($val, ENT_QUOTES, 'UTF-8') ?>"
                            <?= $status_filter === $val ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 col-sm-6">
                <label class="form-label mb-1">Urutkan</label>
                <select name="sort" class="form-select form-select-sm">
                    <option value="tanggal" <?= $sort === 'tanggal' ? 'selected' : '' ?>>Tgl. Pemasangan</option>
                    <option value="nama"    <?= $sort === 'nama'    ? 'selected' : '' ?>>Nama</option>
                    <option value="pop"     <?= $sort === 'pop'     ? 'selected' : '' ?>>POP</option>
                    <option value="status"  <?= $sort === 'status'  ? 'selected' : '' ?>>Status</option>
                </select>
            </div>
            <div class="col-md-2 col-sm-12 d-flex gap-1 justify-content-md-end">
                <button class="btn btn-primary btn-sm w-50 w-md-auto mt-3">
                    <i class="bi bi-funnel"></i> Terapkan
                </button>
                <a href="<?= htmlspecialchars(basename($_SERVER['PHP_SELF']), ENT_QUOTES, 'UTF-8'); ?>"
                   class="btn btn-outline-secondary btn-sm w-50 w-md-auto mt-3">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset
                </a>
            </div>
        </form>

        <?php if ($cari || $pop_filter || $status_filter): ?>
            <div class="mt-2 active-filters">
                <span class="text-muted me-1"><i class="bi bi-sliders"></i> Filter aktif:</span>
                <?php if ($cari): ?>
                    <span class="badge-filter">Nama: <?= htmlspecialchars($cari, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if ($pop_filter): ?>
                    <span class="badge-filter">POP: <?= htmlspecialchars(ucfirst($pop_filter), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if ($status_filter && isset($daftar_status[$status_filter])): ?>
                    <span class="badge-filter">Status: <?= htmlspecialchars($daftar_status[$status_filter], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- TABLE -->
    <div class="table-responsive table-card mt-3">
        <table class="table responsive-table align-middle mb-0">
            <thead>
            <tr>
                <th class="text-start">No</th>
                <th class="text-start">Nama Pelanggan</th>
                <th>POP</th>
                <th>Status</th>
                <th>Paket</th>
                <th>PPPoE / VLAN</th>
                <th>Tgl. Selesai</th>
                <th class="text-center">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="bi bi-search"></i> Data tidak ditemukan.
                    </td>
                </tr>
            <?php else:
                $no = $offset + 1;
                while ($row = $result->fetch_assoc()):
                    $paket_data = $paket_map[$row['paket']] ?? ['nama_paket' => 'Tidak diketahui', 'kecepatan' => '-'];
                    $sn_modem   = $serial_map[$row['modem']] ?? 'SN tidak terdaftar';
                    $is_on      = ($row['status'] === 'on');
                    $tgl        = $row['tanggal'] ? date('d/m/Y', strtotime($row['tanggal'])) : '-';
                    ?>
                    <tr class="<?= $is_on ? 'row-on-soft' : '' ?>">
                        <td data-label="No" class="text-start">
                            <span class="data-content"><?= $no++; ?></span>
                        </td>

                        <td data-label="Pelanggan" class="text-start">
                            <div class="data-content text-start">
                                <span class="col-name-main d-block">
                                    <?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="col-name-sub text-muted d-block">
                                    <?= htmlspecialchars($row['alamat'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                        </td>

                        <td data-label="POP" class="text-center">
                            <span class="data-content">
                                <?= htmlspecialchars(ucfirst($row['pop']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>

                        <td data-label="Status" class="text-center">
                            <span class="data-content">
                                <span class="badge <?= $is_on ? 'status-on' : 'status-selesai' ?>">
                                    <i class="bi <?= $is_on ? 'bi-lightning-charge-fill' : 'bi-check-circle-fill' ?>"></i>
                                    <?= htmlspecialchars($daftar_status[$row['status']] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </span>
                        </td>

                        <td data-label="Paket">
                            <div class="data-content text-start text-md-center">
                                <span class="d-block fw-semibold text-success">
                                    <?= htmlspecialchars($paket_data['nama_paket'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <small class="text-muted">
                                    <?= htmlspecialchars($paket_data['kecepatan'], ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </div>
                        </td>

                        <td data-label="PPPoE / VLAN">
                            <div class="data-content text-start text-md-center">
                                <span class="d-block fw-semibold">
                                    <?= htmlspecialchars($row['userppp'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <small class="text-muted">
                                    VLAN: <?= htmlspecialchars($row['vlan'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </div>
                        </td>

                        <td data-label="Tgl. Selesai" class="text-center">
                            <span class="data-content"><?= $tgl; ?></span>
                        </td>

                        <td data-label="Aksi" class="text-center">
                            <div class="data-content d-flex flex-column flex-lg-row justify-content-center gap-1">
                                <button
                                    class="btn btn-info btn-action text-white"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailModal<?= (int)$row['id']; ?>">
                                    <i class="bi bi-eye"></i> Detail
                                </button>
                                <a href="cetak_reimburse_teknisi.php?id=<?= urlencode($row['id']); ?>"
                                   target="_blank"
                                   class="btn btn-success btn-action">
                                    <i class="bi bi-printer"></i> Rembes
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- MODAL DETAIL -->
                    <div class="modal fade" id="detailModal<?= (int)$row['id']; ?>"
                         tabindex="-1"
                         aria-labelledby="detailModalLabel<?= (int)$row['id']; ?>"
                         aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                            <div class="modal-content">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">
                                        <i class="bi bi-person-lines-fill me-1"></i>
                                        Detail Pelanggan: <?= htmlspecialchars($row['nama'], ENT_QUOTES, 'UTF-8') ?>
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <h6 class="mb-2">
                                                <i class="bi bi-tools text-primary me-1"></i>Informasi Teknis
                                            </h6>
                                            <ul class="list-group list-group-flush small">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Username PPPoE</span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($row['userppp'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Password PPPoE</span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($row['passwordppp'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>SN Modem</span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($sn_modem, ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>ODP</span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($row['odp'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Dropcore</span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($row['dropcore'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Teknisi PJ</span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($row['teknisi'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="col-md-6">
                                            <h6 class="mb-2">
                                                <i class="bi bi-person-circle text-success me-1"></i>Kontak & Lokasi
                                            </h6>
                                            <ul class="list-group list-group-flush small">
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>KTP</span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($row['ktp'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                <span>Telepon</span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($row['telp'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Email</span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($row['email'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between">
                                                    <span>Marketing</span>
                                                    <span class="fw-semibold"><?= htmlspecialchars($row['marketing'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                                </li>
                                                <li class="list-group-item">
                                                    <span class="d-block mb-1">Alamat</span>
                                                    <span class="d-block text-wrap">
                                                        <?= htmlspecialchars($row['alamat'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                                    </span>
                                                </li>
                                                <li class="list-group-item">
                                                    <span class="d-block mb-1">URL Maps</span>
                                                    <?php if (!empty($row['url_maps'])): ?>
                                                        <a href="<?= htmlspecialchars($row['url_maps'], ENT_QUOTES, 'UTF-8') ?>"
                                                           target="_blank"
                                                           class="text-decoration-none">
                                                            Lihat Lokasi <i class="bi bi-box-arrow-up-right"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">Belum ada link maps.</span>
                                                    <?php endif; ?>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mt-3">
        <div class="small text-muted mb-2 mb-md-0">
            Halaman <?= $page; ?> dari <?= $total_halaman; ?> ·
            Menampilkan <?= $result->num_rows ?: 0; ?> data dari <?= $total; ?> hasil.
        </div>
        <nav>
            <ul class="pagination mb-0">
                <?php
                function build_url_page($page_target) {
                    $params = $_GET;
                    $params['page'] = $page_target;
                    return '?' . http_build_query($params);
                }

                $start_page = max(1, $page - 2);
                $end_page   = min($total_halaman, $page + 2);

                if ($page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="' . build_url_page($page - 1) . '"><i class="bi bi-chevron-left"></i></a></li>';
                }
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?= ($page === $i) ? 'active' : ''; ?>">
                        <a class="page-link" href="<?= build_url_page($i); ?>"><?= $i; ?></a>
                    </li>
                <?php endfor;
                if ($page < $total_halaman) {
                    echo '<li class="page-item"><a class="page-link" href="' . build_url_page($page + 1) . '"><i class="bi bi-chevron-right"></i></a></li>';
                }
                ?>
            </ul>
        </nav>
    </div>

    <div class="text-center mt-4 mb-1">
        <small class="text-muted">© <?= date('Y'); ?> PT. Real Data Solusindo · REALNET ERP</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$connPemasangan->close();
$connUmum->close();
?>
