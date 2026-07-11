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
// koneksi
include('konfig.php');

// jangan include navbar di sini, tapi di dalam <body>

// Ambil daftar POP unik untuk filter awal
$query_pop   = "SELECT DISTINCT pop FROM tiket_gangguan ORDER BY pop ASC";
$result_pop  = $conn->query($query_pop);
$daftar_pop  = [];
while ($row = $result_pop->fetch_assoc()) {
    $daftar_pop[] = $row['pop'];
}

// Ambil statistik
$stats_query = "SELECT 
    COUNT(*) as total_tiket,
    SUM(CASE WHEN status = 'belum dikerjakan' THEN 1 ELSE 0 END) as belum_dikerjakan,
    SUM(CASE WHEN status = 'di proses' THEN 1 ELSE 0 END) as di_proses,
    SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) as selesai
FROM tiket_gangguan";
$stats = $conn->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Data Gangguan Pelanggan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --rn-primary: #16a085;
            --rn-primary-dark: #138a71;
            --rn-accent:  #1abc9c;
            --rn-bg:      #f3f6fb;
            --rn-card:    #ffffff;
            --rn-border:  #e2e8f0;
            --rn-danger:  #e74c3c;
            --rn-warning: #f39c12;
            --rn-success: #27ae60;
        }

        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #ecf0f1 100%);
            min-height: 100vh;
        }

        /* tombol dan warna utama */
        .btn-primary,
        .btn-primary:focus {
            background-color: var(--rn-primary);
            border-color: var(--rn-primary);
        }
        .btn-primary:hover {
            background-color: var(--rn-primary-dark);
            border-color: var(--rn-primary-dark);
        }
        .bg-primary {
            background-color: var(--rn-primary) !important;
        }

        /* wrapper utama */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 88px 12px 32px 12px;
        }

        /* header halaman */
        .page-header-card {
            background: linear-gradient(115deg, var(--rn-primary) 0%, var(--rn-accent) 100%);
            color: #fff;
            border-radius: 16px;
            padding: 1.8rem;
            box-shadow: 0 12px 30px rgba(22, 160, 133, 0.25);
            margin-bottom: 2rem;
        }
        .page-title {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .page-subtitle {
            font-size: .95rem;
            opacity: .9;
            margin-top: 0.5rem;
        }

        /* STAT CARDS */
        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.2rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: var(--rn-card);
            border-radius: 12px;
            padding: 1.5rem;
            border-left: 4px solid var(--rn-primary);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .stat-box:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-box.danger { border-left-color: var(--rn-danger); }
        .stat-box.warning { border-left-color: var(--rn-warning); }
        .stat-box.success { border-left-color: var(--rn-success); }

        .stat-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--rn-primary);
        }

        .stat-box.danger .stat-icon { color: var(--rn-danger); }
        .stat-box.warning .stat-icon { color: var(--rn-warning); }
        .stat-box.success .stat-icon { color: var(--rn-success); }

        .stat-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #94a3b8;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
        }

        /* card umum */
        .table-card,
        .filter-card,
        .summary-card {
            background: var(--rn-card);
            border-radius: 14px;
            border: 1px solid var(--rn-border);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }
        .filter-card .card-body {
            padding: 1.3rem;
        }
        .summary-card .card-body {
            padding: 1rem;
        }

        /* header POP */
        .table-card .card-header {
            background: linear-gradient(90deg, var(--rn-primary) 0%, var(--rn-accent) 100%);
            color: white;
            border-bottom: none;
            padding: 1rem 1.2rem;
            font-weight: 700;
        }
        .table-card .card-header h5 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
        }

        /* tabel */
        .table {
            margin-bottom: 0;
            font-size: .9rem;
        }
        .table thead th {
            background-color: #f8fafc;
            border-bottom: 2px solid var(--rn-border);
            text-transform: uppercase;
            font-size: .75rem;
            letter-spacing: .05em;
            font-weight: 700;
            color: #64748b;
        }

        table tbody tr {
            transition: all 0.2s ease;
        }

        table tbody tr:hover {
            background-color: #f8fafc;
        }

        /* status badge */
        .badge-pending {
            background: #fee2e2;
            color: #991b1b;
            font-weight: 600;
        }
        .badge-process {
            background: #fef08a;
            color: #854d0e;
            font-weight: 600;
        }
        .badge-done {
            background: #dcfce7;
            color: #166534;
            font-weight: 600;
        }

        /* overlay loading */
        #loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(255, 255, 255, 0.9);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1050;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        /* ringkasan */
        .summary-title {
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            letter-spacing: .05em;
        }
        .summary-text {
            font-size: .95rem;
            color: #0f172a;
            font-weight: 600;
        }
        .summary-filter {
            font-size: .8rem;
            border-radius: 999px;
            padding: .4rem .9rem;
            background-color: #ecfeff;
            color: #0e7490;
            border: 1px solid #7dd3fc;
            display: inline-block;
        }

        /* filter form */
        .filter-label {
            font-size: .75rem;
            text-transform: uppercase;
            font-weight: 600;
            color: #64748b;
            letter-spacing: .06em;
            margin-bottom: 0.5rem;
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
        }

        .filter-form input,
        .filter-form select {
            border-radius: 8px;
            border: 1px solid var(--rn-border);
            transition: all 0.3s ease;
        }

        .filter-form input:focus,
        .filter-form select:focus {
            border-color: var(--rn-primary);
            box-shadow: 0 0 0 3px rgba(22, 160, 133, 0.1);
            outline: none;
        }

        .filter-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .filter-actions button {
            flex: 1;
            min-width: 140px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        /* modal close di header hijau */
        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* pagination */
        .pagination-container {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            padding: 1.5rem;
            flex-wrap: wrap;
        }

        .page-btn {
            padding: 0.5rem 0.8rem;
            border-radius: 6px;
            border: 1px solid var(--rn-border);
            background: white;
            color: #0f172a;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s ease;
        }

        .page-btn:hover {
            border-color: var(--rn-primary);
            background: #f8fafc;
        }

        .page-btn.active {
            background: var(--rn-primary);
            color: white;
            border-color: var(--rn-primary);
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .main-container {
                padding: 80px 8px 24px 8px;
            }
            .page-header-card {
                padding: 1.2rem;
            }
            .page-title {
                font-size: 1.3rem;
            }
            .stat-cards {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.8rem;
            }
            .stat-box {
                padding: 1rem;
            }
            .stat-value {
                font-size: 1.5rem;
            }
            .table {
                font-size: 0.8rem;
            }
            .filter-form {
                grid-template-columns: 1fr;
            }
            .filter-actions {
                flex-direction: column;
            }
            .filter-actions button {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .main-container {
                padding: 70px 6px 20px 6px;
            }
            .page-header-card {
                padding: 1rem;
            }
            .stat-cards {
                grid-template-columns: 1fr;
                gap: 0.6rem;
            }
            .stat-box {
                padding: 0.8rem;
            }
            .stat-value {
                font-size: 1.4rem;
            }
        }
    </style>
</head>
<body>

<?php include('navbar.php'); ?>

<div id="loading-overlay">
    <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
</div>

<div class="main-container">

    <!-- HEADER ATAS -->
    <div class="page-header-card">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-start gap-3">
            <div>
                <div class="page-title">
                    <i class="bi bi-ticket-perforated"></i> DATA GANGGUAN PELANGGAN
                </div>
                <div class="page-subtitle">
                    Monitoring tiket gangguan per POP, status, dan teknisi secara real-time.
                </div>
            </div>
            <div class="text-lg-end">
                <a href="tiket/index.php" class="btn btn-light btn-sm">
                    <i class="bi bi-plus-lg"></i> Tambah Tiket
                </a>
            </div>
        </div>
    </div>

    <!-- STAT CARDS -->
    <div class="stat-cards">
        <div class="stat-box">
            <div class="stat-icon"><i class="fas fa-ticket-alt"></i></div>
            <div class="stat-label">Total Tiket</div>
            <div class="stat-value"><?= $stats['total_tiket'] ?></div>
        </div>

        <div class="stat-box danger">
            <div class="stat-icon"><i class="fas fa-exclamation-circle"></i></div>
            <div class="stat-label">Belum Dikerjakan</div>
            <div class="stat-value"><?= $stats['belum_dikerjakan'] ?></div>
        </div>

        <div class="stat-box warning">
            <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="stat-label">Dalam Proses</div>
            <div class="stat-value"><?= $stats['di_proses'] ?></div>
        </div>

        <div class="stat-box success">
            <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
            <div class="stat-label">Selesai</div>
            <div class="stat-value"><?= $stats['selesai'] ?></div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="card filter-card mb-3">
        <div class="card-body">
            <h6 class="mb-3"><i class="bi bi-funnel"></i> Filter & Pencarian</h6>
            <form id="filterForm" class="filter-form">
                <div>
                    <label class="filter-label">Cari Pelanggan</label>
                    <input type="text" name="cari" class="form-control" placeholder="Nama / WhatsApp..." value="">
                </div>

                <div>
                    <label class="filter-label">Status</label>
                    <select name="status_filter" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="belum dikerjakan">Belum Dikerjakan</option>
                        <option value="di proses">Dalam Proses</option>
                        <option value="selesai">Selesai</option>
                    </select>
                </div>

                <div>
                    <label class="filter-label">POP</label>
                    <select name="pop_filter" class="form-select" id="popFilterSelect">
                        <option value="">Semua POP</option>
                        <?php foreach ($daftar_pop as $p): ?>
                            <option value="<?= htmlspecialchars($p); ?>">
                                <?= htmlspecialchars($p); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="filter-label">Urut Berdasar</label>
                    <select name="sort" class="form-select">
                        <option value="tanggal_dibuat">Tgl Dibuat</option>
                        <option value="nama_pelanggan">Nama Pelanggan</option>
                        <option value="status">Status</option>
                    </select>
                </div>

                <div>
                    <label class="filter-label">Urutan</label>
                    <select name="order" class="form-select">
                        <option value="DESC">Terbaru → Lama</option>
                        <option value="ASC">Terlama → Baru</option>
                    </select>
                </div>
            </form>

            <div class="filter-actions">
                <button class="btn btn-primary" type="submit" form="filterForm">
                    <i class="bi bi-filter"></i> Terapkan
                </button>
                <button type="button" class="btn btn-outline-secondary" id="resetFilterBtn">
                    Reset
                </button>
            </div>
        </div>
    </div>

    <!-- KONTAINER DATA -->
    <div id="ticketDataContainer">
        <div class="alert alert-info text-center mb-0" role="alert">
            <h4 class="alert-heading mb-1">Memuat Data Gangguan...</h4>
            <p class="mb-0">Silakan tunggu sebentar, sistem sedang mengambil data tiket dari server.</p>
        </div>
    </div>
</div>

<!-- MODAL DETAIL -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detailModalLabel">Detail Tiket Gangguan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row gy-2">
                    <div class="col-md-6">
                        <p><strong>Nama Pelanggan:</strong> <span id="modalNamaPelanggan"></span></p>
                        <p><strong>Alamat:</strong> <span id="modalAlamat"></span></p>
                        <p><strong>WhatsApp:</strong> <span id="modalWhatsApp"></span></p>
                        <p><strong>VLAN:</strong> <span id="modalVLAN"></span></p>
                        <p><strong>SN:</strong> <span id="modalSN"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Keluhan:</strong> <span id="modalKeluhan"></span></p>
                        <p><strong>Maps URL:</strong> <span id="modalMapsUrl"></span></p>
                        <p><strong>Teknisi:</strong> <span id="modalTeknisi"></span></p>
                        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                        <p><strong>Tanggal Dibuat:</strong> <span id="modalTglDibuat"></span></p>
                        <p><strong>Tanggal Selesai:</strong> <span id="modalTglSelesai"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="editTicketLink" class="btn btn-warning">
                    <i class="bi bi-pencil"></i> Edit Tiket
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ticketDataContainer = document.getElementById('ticketDataContainer');
    const filterForm          = document.getElementById('filterForm');
    const resetFilterBtn      = document.getElementById('resetFilterBtn');
    const loadingOverlay      = document.getElementById('loading-overlay');
    const popFilterSelect     = document.getElementById('popFilterSelect');

    let currentPage   = 1;
    let currentFilters = {};

    function toggleLoading(show) {
        loadingOverlay.style.display = show ? 'flex' : 'none';
    }

    async function fetchAndRenderTickets(page = 1, filters = {}) {
        toggleLoading(true);
        currentPage = page;

        const params = new URLSearchParams({ page: page, ...filters });
        currentFilters = filters;

        const newUrl = `${window.location.pathname}?${params.toString()}`;
        window.history.pushState(filters, '', newUrl);

        try {
            const response = await fetch(`fetch_tickets.php?${params.toString()}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const data = await response.json();

            renderTickets(data.tickets, data.total_pages, data.current_page, data.total_tickets);
            updateFilterInputs(data.filters);
        } catch (error) {
            console.error('Error fetching ticket data:', error);
            ticketDataContainer.innerHTML = `
                <div class="alert alert-danger text-center" role="alert">
                    Terjadi kesalahan saat memuat data. Silakan coba lagi nanti.
                </div>
            `;
        } finally {
            toggleLoading(false);
        }
    }

    function renderTickets(tickets, totalPages, currentPage, totalTickets) {
        let html = '';

        if (totalTickets === 0 && (currentFilters.cari || currentFilters.status_filter || currentFilters.pop_filter)) {
            html += `
                <div class="alert alert-warning text-center" role="alert">
                    Tidak ada data gangguan yang sesuai dengan filter yang diterapkan.
                </div>
            `;
        } else if (totalTickets === 0) {
            html += `
                <div class="alert alert-info text-center" role="alert">
                    <h4 class="alert-heading">Tidak Ada Data Gangguan!</h4>
                    <p>Belum ada tiket gangguan yang tercatat dalam sistem.</p>
                </div>
            `;
        } else {

            // ringkasan global
            const filterActive = Object.keys(currentFilters).some(k => currentFilters[k]);
            html += `
                <div class="card summary-card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="summary-title">Ringkasan Tiket</div>
                                <div class="summary-text">
                                    Total <strong>${totalTickets}</strong> tiket · Halaman <strong>${currentPage}</strong> dari <strong>${totalPages}</strong>
                                </div>
                            </div>
                            <div class="summary-filter">
                                <i class="bi bi-funnel"></i>
                                ${filterActive ? 'Filter aktif' : 'Semua data'}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // kelompok per POP
            const ticketsByPop = tickets.reduce((acc, ticket) => {
                const pop = ticket.pop || 'Tidak Ada POP';
                if (!acc[pop]) acc[pop] = [];
                acc[pop].push(ticket);
                return acc;
            }, {});

            for (const pop in ticketsByPop) {
                const popTickets = ticketsByPop[pop];
                html += `
                    <div class="card table-card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-building-gear"></i> POP: ${escapeHtml(pop)}</h5>
                            <span class="badge bg-light text-dark">
                                ${popTickets.length} tiket
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Pelanggan</th>
                                        <th>Alamat</th>
                                        <th>WhatsApp</th>
                                        <th>Keluhan</th>
                                        <th>Teknisi</th>
                                        <th>Status</th>
                                        <th>Tgl Dibuat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                `;

                popTickets.forEach((row, index) => {
                    const limit_per_halaman = 10;
                    const no = (currentPage - 1) * limit_per_halaman + index + 1;

                    let statusBadgeClass = 'badge-pending';
                    let statusText = 'Belum Dikerjakan';
                    
                    if (row.status === 'di proses') {
                        statusBadgeClass = 'badge-process';
                        statusText = 'Dalam Proses';
                    } else if (row.status === 'selesai') {
                        statusBadgeClass = 'badge-done';
                        statusText = 'Selesai';
                    }

                    html += `
                        <tr>
                            <td><strong>${no}</strong></td>
                            <td>${escapeHtml(row.nama_pelanggan)}</td>
                            <td>${escapeHtml(row.alamat)}</td>
                            <td>${escapeHtml(row.whatsapp)}</td>
                            <td>${escapeHtml(row.keluhan)}</td>
                            <td>${escapeHtml(row.teknisi)}</td>
                            <td>
                                <span class="badge ${statusBadgeClass}">
                                    ${statusText}
                                </span>
                            </td>
                            <td>${formatDateTime(row.tanggal_dibuat)}</td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#detailModal"
                                    data-id="${row.id}"
                                    data-namapelanggan="${escapeHtml(row.nama_pelanggan)}"
                                    data-alamat="${escapeHtml(row.alamat)}"
                                    data-whatsapp="${escapeHtml(row.whatsapp)}"
                                    data-vlan="${escapeHtml(row.vlan)}"
                                    data-sn="${escapeHtml(row.sn)}"
                                    data-keluhan="${escapeHtml(row.keluhan)}"
                                    data-mapsurl="${escapeHtml(row.maps_url)}"
                                    data-teknisi="${escapeHtml(row.teknisi)}"
                                    data-status="${escapeHtml(row.status)}"
                                    data-tanggaldibuat="${formatDateTime(row.tanggal_dibuat)}"
                                    data-tangalselesai="${row.tanggal_selesai ? formatDateTime(row.tanggal_selesai) : '-'}">
                                    <i class="bi bi-eye"></i> Detail
                                </button>
                                <a href="hapus_gangguan.php?id=${row.id}" class="btn btn-danger btn-sm"
                                   onclick="return confirm('Yakin ingin menghapus data ini?')">
                                   <i class="bi bi-trash"></i> Hapus
                                </a>
                            </td>
                        </tr>
                    `;
                });

                html += `
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
            }

            // paginasi
            html += `
                <nav class="mt-3">
                    <ul class="pagination justify-content-center">
            `;
            html += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage - 1}" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
            `;

            const maxPagesToShow = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
            let endPage   = Math.min(totalPages, startPage + maxPagesToShow - 1);
            if (endPage - startPage + 1 < maxPagesToShow) {
                startPage = Math.max(1, endPage - maxPagesToShow + 1);
            }

            for (let i = startPage; i <= endPage; i++) {
                html += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" data-page="${i}">${i}</a>
                    </li>
                `;
            }

            html += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${currentPage + 1}" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                </ul>
                </nav>
            `;
        }

        ticketDataContainer.innerHTML = html;

        document.querySelectorAll('#ticketDataContainer .pagination .page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                if (!e.currentTarget.parentElement.classList.contains('disabled')) {
                    const page = parseInt(e.currentTarget.dataset.page);
                    fetchAndRenderTickets(page, currentFilters);
                }
            });
        });

        attachModalEventListeners();
    }

    function updateFilterInputs(filters) {
        filterForm.querySelector('[name="cari"]').value           = filters.cari || '';
        filterForm.querySelector('[name="status_filter"]').value  = filters.status_filter || '';
        filterForm.querySelector('[name="pop_filter"]').value     = filters.pop_filter || '';
        filterForm.querySelector('[name="sort"]').value           = filters.sort || 'tanggal_dibuat';
        filterForm.querySelector('[name="order"]').value          = filters.order || 'DESC';
    }

    filterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(filterForm);
        const filters  = {};
        for (const [key, value] of formData.entries()) {
            if (value) filters[key] = value;
        }
        fetchAndRenderTickets(1, filters);
    });

    resetFilterBtn.addEventListener('click', function() {
        filterForm.reset();
        fetchAndRenderTickets(1, {});
    });

    function initializePage() {
        const urlParams      = new URLSearchParams(window.location.search);
        const initialFilters = {};
        for (const [key, value] of urlParams.entries()) {
            if (key !== 'page') {
                initialFilters[key] = value;
            }
        }
        const initialPage = parseInt(urlParams.get('page')) || 1;
        fetchAndRenderTickets(initialPage, initialFilters);
    }

    function attachModalEventListeners() {
        var detailModal = document.getElementById('detailModal');
        if (!detailModal) return;

        detailModal.addEventListener('show.bs.modal', function (event) {
            var button        = event.relatedTarget;
            var id            = button.getAttribute('data-id');
            var namaPelanggan = button.getAttribute('data-namapelanggan');
            var alamat        = button.getAttribute('data-alamat');
            var whatsapp      = button.getAttribute('data-whatsapp');
            var vlan          = button.getAttribute('data-vlan');
            var sn            = button.getAttribute('data-sn');
            var keluhan       = button.getAttribute('data-keluhan');
            var mapsUrl       = button.getAttribute('data-mapsurl');
            var teknisi       = button.getAttribute('data-teknisi');
            var status        = button.getAttribute('data-status');
            var tglDibuat     = button.getAttribute('data-tanggaldibuat');
            var tglSelesai    = button.getAttribute('data-tangalselesai');

            var modalTitle        = detailModal.querySelector('.modal-title');
            var modalNamaPelanggan= detailModal.querySelector('#modalNamaPelanggan');
            var modalAlamat       = detailModal.querySelector('#modalAlamat');
            var modalWhatsApp     = detailModal.querySelector('#modalWhatsApp');
            var modalVLAN         = detailModal.querySelector('#modalVLAN');
            var modalSN           = detailModal.querySelector('#modalSN');
            var modalKeluhan      = detailModal.querySelector('#modalKeluhan');
            var modalMapsUrl      = detailModal.querySelector('#modalMapsUrl');
            var modalTeknisi      = detailModal.querySelector('#modalTeknisi');
            var modalStatus       = detailModal.querySelector('#modalStatus');
            var modalTglDibuat    = detailModal.querySelector('#modalTglDibuat');
            var modalTglSelesai   = detailModal.querySelector('#modalTglSelesai');
            var editTicketLink    = detailModal.querySelector('#editTicketLink');

            modalTitle.textContent          = 'Detail Tiket Gangguan: ' + namaPelanggan;
            modalNamaPelanggan.textContent  = namaPelanggan;
            modalAlamat.textContent         = alamat;
            modalWhatsApp.textContent       = whatsapp;
            modalVLAN.textContent           = vlan;
            modalSN.textContent             = sn;
            modalKeluhan.textContent        = keluhan;
            modalMapsUrl.innerHTML          = mapsUrl && mapsUrl !== '-' ? `<a href="${mapsUrl}" target="_blank">Lihat di Peta</a>` : '-';
            modalTeknisi.textContent        = teknisi;

            var statusBadgeClass = 'badge-pending';
            var statusLabel = 'Belum Dikerjakan';
            
            if (status === 'di proses') {
                statusBadgeClass = 'badge-process';
                statusLabel = 'Dalam Proses';
            } else if (status === 'selesai') {
                statusBadgeClass = 'badge-done';
                statusLabel = 'Selesai';
            }
            
            modalStatus.innerHTML = `<span class="badge ${statusBadgeClass}">${statusLabel}</span>`;

            modalTglDibuat.textContent  = tglDibuat;
            modalTglSelesai.textContent = tglSelesai;
            editTicketLink.href         = `edit_gangguan.php?id=${id}`;
        });
    }

    function escapeHtml(str) {
        if (typeof str !== 'string') return str;
        const div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function formatDateTime(datetimeStr) {
        if (!datetimeStr || datetimeStr === '0000-00-00 00:00:00') return '-';
        try {
            const date = new Date(datetimeStr);
            if (isNaN(date.getTime())) return '-';
            const day = String(date.getDate()).padStart(2, '0');
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = date.getFullYear();
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${day}/${month}/${year} ${hours}:${minutes}`;
        } catch (e) {
            return '-';
        }
    }

    document.addEventListener('DOMContentLoaded', initializePage);
</script>
</body>
</html>
<?php $conn->close(); ?>