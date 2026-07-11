<?php
require_once __DIR__ . '/config/database.php';
session_start();

$dashboard_divisi = ['Admin', 'IT', 'Manager', 'SPV Teknis', 'Finance'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['divisi'], $dashboard_divisi)) {
    header("Location: login.php");
    exit;
}
// File: dashboard_paket.php
// Konfigurasi database
$servername = "localhost";
$username = "u272457353_kevinsamsung99";
$password = "Admionkevin99"; // GANTI PASSWORD INI!
$database = "u272457353_umumdata";

// Koneksi dengan error handling
try {
    $conn = getErpDbConnection();
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Maaf, terjadi kesalahan sistem. Silakan coba lagi nanti.");
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    die("Maaf, terjadi kesalahan sistem.");
}

// Query data paket dengan sorting
$sql = "SELECT * FROM jaringan_paket ORDER BY harga ASC";
$result = $conn->query($sql);

include('navbar.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Paket Internet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --primary-dark: #3730a3;
            --secondary-color: #7209b7;
            --success-color: #06d6a0;
            --danger-color: #ef476f;
            --warning-color: #ffd166;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            padding-bottom: 3rem;
        }
        
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .page-title {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 2rem;
            margin: 0;
        }
        
        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .paket-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .paket-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }
        
        .paket-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15);
            border-color: var(--primary-color);
        }
        
        .paket-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .paket-speed {
            display: inline-flex;
            align-items: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .paket-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success-color);
            margin: 1rem 0;
        }
        
        .paket-description {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 1rem;
        }
        
        .btn-custom-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 16px rgba(67, 97, 238, 0.3);
        }
        
        .btn-custom-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(67, 97, 238, 0.4);
            color: white;
        }
        
        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: none;
        }
        
        .btn-edit {
            background-color: var(--warning-color);
            color: #000;
        }
        
        .btn-edit:hover {
            background-color: #ffb800;
            transform: scale(1.05);
        }
        
        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }
        
        .btn-delete:hover {
            background-color: #d62951;
            transform: scale(1.05);
        }
        
        .btn-detail {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-detail:hover {
            background-color: var(--primary-dark);
            transform: scale(1.05);
        }
        
        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 4rem 2rem;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
        }
        
        .empty-state i {
            font-size: 5rem;
            color: var(--primary-color);
            opacity: 0.3;
            margin-bottom: 1rem;
        }
        
        .search-box {
            background: white;
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .search-box:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(67, 97, 238, 0.25);
            outline: none;
        }
        
        .modal-content {
            border-radius: 16px;
            border: none;
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
        }
        
        .modal-detail-item {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-detail-item:last-child {
            border-bottom: none;
        }
        
        .modal-detail-label {
            font-weight: 600;
            color: #6c757d;
        }
        
        .modal-detail-value {
            font-weight: 600;
            color: #212529;
        }
        
        @media (max-width: 768px) {
            .page-title {
                font-size: 1.5rem;
            }
            
            .paket-card {
                padding: 1rem;
            }
            
            .paket-name {
                font-size: 1.25rem;
            }
            
            .paket-price {
                font-size: 1.5rem;
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

<div class="main-container">
    <!-- Header -->
    <div class="page-header fade-in">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="page-title">
                    <i class="bi bi-wifi"></i> Dashboard Paket Internet
                </h1>
                <p class="text-muted mb-0">Kelola semua paket internet Anda</p>
            </div>
            <div class="d-flex gap-2">
                <a href="tambah_paket.php" class="btn btn-custom-primary">
                    <i class="bi bi-plus-circle"></i> Tambah Paket
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <?php
    $total_paket = $result->num_rows;
    $result->data_seek(0); // Reset pointer
    
    $total_harga = 0;
    $paket_termurah = PHP_INT_MAX;
    $paket_termahal = 0;
    
    while ($row = $result->fetch_assoc()) {
        $harga = $row['harga'];
        $total_harga += $harga;
        if ($harga < $paket_termurah) $paket_termurah = $harga;
        if ($harga > $paket_termahal) $paket_termahal = $harga;
    }
    
    $rata_rata = $total_paket > 0 ? $total_harga / $total_paket : 0;
    $result->data_seek(0); // Reset lagi
    ?>
    
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stats-card fade-in" style="animation-delay: 0.1s;">
                <div class="d-flex align-items-center">
                    <div class="stats-icon text-primary me-3">
                        <i class="bi bi-box-seam"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Total Paket</h6>
                        <h3 class="mb-0 fw-bold"><?= $total_paket; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card fade-in" style="animation-delay: 0.2s;">
                <div class="d-flex align-items-center">
                    <div class="stats-icon text-success me-3">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Rata-rata Harga</h6>
                        <h3 class="mb-0 fw-bold">Rp <?= number_format($rata_rata, 0, ',', '.'); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card fade-in" style="animation-delay: 0.3s;">
                <div class="d-flex align-items-center">
                    <div class="stats-icon text-warning me-3">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Termurah</h6>
                        <h3 class="mb-0 fw-bold">Rp <?= $total_paket > 0 ? number_format($paket_termurah, 0, ',', '.') : '0'; ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stats-card fade-in" style="animation-delay: 0.4s;">
                <div class="d-flex align-items-center">
                    <div class="stats-icon text-danger me-3">
                        <i class="bi bi-arrow-up-circle"></i>
                    </div>
                    <div>
                        <h6 class="text-muted mb-1">Termahal</h6>
                        <h3 class="mb-0 fw-bold">Rp <?= $total_paket > 0 ? number_format($paket_termahal, 0, ',', '.') : '0'; ?></h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Box -->
    <div class="mb-4">
        <input type="text" id="searchInput" class="form-control search-box" placeholder="🔍 Cari paket berdasarkan nama, kecepatan, atau harga...">
    </div>

    <!-- Paket Cards -->
    <div class="row" id="paketContainer">
        <?php if ($result->num_rows > 0): ?>
            <?php 
            $delay = 0.5;
            while ($row = $result->fetch_assoc()): 
                $id = (int)$row['id_paket'];
                $nama = htmlspecialchars($row['nama_paket'], ENT_QUOTES, 'UTF-8');
                $kecepatan = htmlspecialchars($row['kecepatan'], ENT_QUOTES, 'UTF-8');
                $deskripsi = htmlspecialchars($row['deskripsi'], ENT_QUOTES, 'UTF-8');
                $harga = $row['harga'];
                $delay += 0.1;
            ?>
                <div class="col-md-6 col-lg-4 paket-item" 
                     data-name="<?= strtolower($nama); ?>" 
                     data-speed="<?= strtolower($kecepatan); ?>" 
                     data-price="<?= $harga; ?>">
                    <div class="paket-card fade-in" style="animation-delay: <?= $delay; ?>s;">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h3 class="paket-name"><?= $nama; ?></h3>
                                <span class="paket-speed">
                                    <i class="bi bi-speedometer2 me-2"></i>
                                    <?= $kecepatan; ?>
                                </span>
                            </div>
                            <i class="bi bi-wifi text-primary" style="font-size: 2rem; opacity: 0.3;"></i>
                        </div>
                        
                        <p class="paket-description"><?= $deskripsi; ?></p>
                        
                        <div class="paket-price">
                            Rp <?= number_format($harga, 0, ',', '.'); ?>
                            <small class="text-muted" style="font-size: 0.6em;">/bulan</small>
                        </div>
                        
                        <div class="d-flex gap-2 flex-wrap">
                            <button class="btn btn-detail btn-action flex-fill" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#detailModal<?= $id; ?>">
                                <i class="bi bi-eye"></i> Detail
                            </button>
                            <a href="edit_paket.php?edit=<?= $id; ?>" class="btn btn-edit btn-action">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="hapus_paket.php?id=<?= $id; ?>" 
                               class="btn btn-delete btn-action" 
                               onclick="return confirm('Apakah Anda yakin ingin menghapus paket <?= $nama; ?>?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Modal Detail -->
                <div class="modal fade" id="detailModal<?= $id; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="bi bi-info-circle"></i> Detail Paket
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-0">
                                <div class="modal-detail-item">
                                    <span class="modal-detail-label">
                                        <i class="bi bi-box text-primary"></i> Nama Paket
                                    </span>
                                    <span class="modal-detail-value"><?= $nama; ?></span>
                                </div>
                                <div class="modal-detail-item">
                                    <span class="modal-detail-label">
                                        <i class="bi bi-speedometer2 text-success"></i> Kecepatan
                                    </span>
                                    <span class="modal-detail-value"><?= $kecepatan; ?></span>
                                </div>
                                <div class="modal-detail-item">
                                    <span class="modal-detail-label">
                                        <i class="bi bi-file-text text-info"></i> Deskripsi
                                    </span>
                                    <span class="modal-detail-value text-end" style="max-width: 60%;"><?= $deskripsi; ?></span>
                                </div>
                                <div class="modal-detail-item">
                                    <span class="modal-detail-label">
                                        <i class="bi bi-currency-dollar text-warning"></i> Harga
                                    </span>
                                    <span class="modal-detail-value text-success">
                                        Rp <?= number_format($harga, 0, ',', '.'); ?>/bulan
                                    </span>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                <a href="edit_paket.php?edit=<?= $id; ?>" class="btn btn-warning">
                                    <i class="bi bi-pencil"></i> Edit Paket
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-state fade-in">
                    <i class="bi bi-inbox"></i>
                    <h4 class="mt-3">Belum Ada Paket Internet</h4>
                    <p class="text-muted mb-4">Mulai tambahkan paket internet pertama Anda</p>
                    <a href="tambah_paket.php" class="btn btn-custom-primary">
                        <i class="bi bi-plus-circle"></i> Tambah Paket Sekarang
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const paketItems = document.querySelectorAll('.paket-item');
    
    paketItems.forEach(item => {
        const name = item.dataset.name;
        const speed = item.dataset.speed;
        const price = item.dataset.price;
        
        const matches = name.includes(searchTerm) || 
                       speed.includes(searchTerm) || 
                       price.includes(searchTerm);
        
        if (matches) {
            item.style.display = '';
            item.classList.add('fade-in');
        } else {
            item.style.display = 'none';
        }
    });
    
    // Show empty message if no results
    const visibleItems = Array.from(paketItems).filter(item => item.style.display !== 'none');
    const container = document.getElementById('paketContainer');
    
    let emptyMessage = document.getElementById('emptySearchMessage');
    if (visibleItems.length === 0 && searchTerm !== '') {
        if (!emptyMessage) {
            emptyMessage = document.createElement('div');
            emptyMessage.id = 'emptySearchMessage';
            emptyMessage.className = 'col-12';
            emptyMessage.innerHTML = `
                <div class="empty-state fade-in">
                    <i class="bi bi-search"></i>
                    <h4 class="mt-3">Tidak Ada Hasil</h4>
                    <p class="text-muted">Tidak ditemukan paket yang cocok dengan pencarian "${searchTerm}"</p>
                </div>
            `;
            container.appendChild(emptyMessage);
        }
    } else if (emptyMessage) {
        emptyMessage.remove();
    }
});
</script>

</body>
</html>

<?php
$conn->close();
?>