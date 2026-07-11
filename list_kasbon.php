<?php
session_start();

$dashboard_divisi = ['Admin', 'IT', 'Manager', 'SPV Teknis', 'Finance'];

if (!isset($_SESSION['username']) || !in_array($_SESSION['divisi'], $dashboard_divisi)) {
    header("Location: login.php");
    exit;
}
include 'navbar.php';
include 'kasbon/koneksi.php';

// Ambil data dari session
$username = $_SESSION['username'];
$divisi   = $_SESSION['divisi'];
$nama     = $_SESSION['nama'];

// Ambil SEMUA kasbon untuk SIAPAPUN yang login
$stmt = $conn->prepare("
    SELECT k.*, u.nama, u.divisi 
    FROM kasbon k 
    JOIN karyawan u ON k.id_karyawan = u.id 
    ORDER BY k.tanggal_dibuat DESC
");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Kasbon</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #00d4ff;
            --danger-color: #f5576c;
            --warning-color: #feca57;
            --dark-bg: #1a1a2e;
            --card-bg: #ffffff;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.16);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding-bottom: 3rem;
        }
        
        /* Hero Header */
        .hero-header {
            background: var(--primary-gradient);
            color: white;
            padding: 3rem 1.5rem;
            border-radius: 24px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }

        .hero-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 300px;
            height: 300px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .hero-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -5%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        .hero-header .content {
            position: relative;
            z-index: 1;
        }

        .hero-header h1 {
            font-weight: 800;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .hero-header p {
            font-size: 1.1rem;
            opacity: 0.95;
            font-weight: 400;
        }

        .hero-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: inline-block;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .container-max {
            max-width: 1400px;
            margin: auto;
            padding: 0 1rem;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .btn-modern {
            padding: 0.875rem 2rem;
            border-radius: 16px;
            font-weight: 600;
            border: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            text-transform: none;
            letter-spacing: 0.3px;
        }

        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .btn-modern:hover::before {
            left: 100%;
        }

        .btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-modern:active {
            transform: translateY(0);
        }

        .btn-gradient-primary {
            background: var(--success-gradient);
            color: white;
        }

        .btn-gradient-secondary {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-modern i {
            margin-right: 0.5rem;
        }

        /* Card Styles */
        .data-card {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card-header-custom {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem 2rem;
            font-weight: 700;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Desktop Table */
        .table-modern {
            width: 100%;
            margin: 0;
        }

        .table-modern thead {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }

        .table-modern th {
            padding: 1.25rem 1rem;
            font-weight: 700;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-primary);
            border: none;
            white-space: nowrap;
        }

        .table-modern td {
            padding: 1.25rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .table-modern tbody tr {
            transition: all 0.2s ease;
        }

        .table-modern tbody tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
            transform: scale(1.01);
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5rem 1.25rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.875rem;
            display: inline-block;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
        }

        .badge-pending {
            background: linear-gradient(135deg, #feca57 0%, #ff9ff3 100%);
            color: #fff;
        }

        .badge-selesai {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: #fff;
        }

        .badge-ditolak {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: #fff;
        }

        /* Mobile Card View */
        .kasbon-card-mobile {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .kasbon-card-mobile::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .kasbon-card-mobile:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .card-mobile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .card-mobile-title {
            flex: 1;
        }

        .card-mobile-title h5 {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            font-size: 1.125rem;
        }

        .card-mobile-title .divisi-tag {
            display: inline-block;
            background: linear-gradient(135deg, #e0e7ff 0%, #ddd6fe 100%);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        .card-mobile-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .info-value {
            font-size: 1rem;
            color: var(--text-primary);
            font-weight: 600;
        }

        .info-value.amount {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 700;
        }

        .keperluan-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
        }

        .keperluan-section .label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .keperluan-section .text {
            color: var(--text-primary);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .card-mobile-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .timestamp {
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .btn-print {
            background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            color: white;
            padding: 0.625rem 1.5rem;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            font-size: 0.875rem;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--text-secondary);
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1.125rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero-header h1 {
                font-size: 2rem;
            }

            .hero-header p {
                font-size: 1rem;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-modern {
                width: 100%;
                justify-content: center;
            }

            .desktop-view {
                display: none;
            }

            .mobile-view {
                display: block;
            }
        }

        @media (min-width: 993px) {
            .desktop-view {
                display: block;
            }

            .mobile-view {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .hero-header {
                padding: 2rem 1rem;
            }

            .hero-header h1 {
                font-size: 1.75rem;
            }

            .container-max {
                padding: 0 0.75rem;
            }

            .card-mobile-info {
                grid-template-columns: 1fr;
            }

            .info-value.amount {
                font-size: 1.125rem;
            }
        }

        /* Loading Animation */
        @keyframes shimmer {
            0% { background-position: -1000px 0; }
            100% { background-position: 1000px 0; }
        }

        .loading {
            animation: shimmer 2s infinite;
            background: linear-gradient(to right, #f6f7f8 0%, #edeef1 20%, #f6f7f8 40%, #f6f7f8 100%);
            background-size: 1000px 100%;
        }
    </style>
</head>
<body>

<div class="container container-max mt-4">
    <!-- Hero Header -->
    <div class="hero-header">
        <div class="content">
            <div class="hero-icon">💰</div>
            <h1>
                <i class="bi bi-wallet2"></i>
                Daftar Kasbon
            </h1>
            <p>Kelola dan pantau semua pengajuan kasbon karyawan dengan mudah</p>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="kasbon/form_kasbon.php" class="btn-modern btn-gradient-primary">
            <i class="bi bi-plus-circle-fill"></i> Ajukan Kasbon Baru
        </a>
        <?php 
        $approver_divisi_untuk_tombol = ['Leader Area', 'SPV Teknis', 'Manager', 'Admin', 'Finance'];
        if (in_array($divisi, $approver_divisi_untuk_tombol)): 
        ?>
            <a href="kasbon/approval_kasbon.php" class="btn-modern btn-gradient-secondary">
                <i class="bi bi-patch-check-fill"></i> Approval Kasbon
            </a>
        <?php endif; ?>
    </div>

    <!-- Data Card -->
    <div class="data-card">
        <div class="card-header-custom">
            <i class="bi bi-journal-text"></i>
            <span>Riwayat Pengajuan Kasbon</span>
        </div>
        
        <?php if ($result->num_rows > 0): ?>
            
            <!-- Desktop View -->
            <div class="desktop-view">
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr class="text-center">
                                <th>No</th>
                                <th>Nama</th>
                                <th>Divisi</th>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Keperluan</th>
                                <th>Status</th>
                                <th>Waktu Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $result->data_seek(0); // Reset pointer
                            $no = 1; 
                            while ($row = $result->fetch_assoc()): 
                                $status = strtolower($row['status']);
                                $badge_class = 'badge-pending';
                                if ($status === 'ditolak') {
                                    $badge_class = 'badge-ditolak';
                                } elseif ($status === 'selesai') {
                                    $badge_class = 'badge-selesai';
                                }
                            ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= $no++; ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['nama']); ?></td>
                                    <td><?= htmlspecialchars($row['divisi']); ?></td>
                                    <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal'])); ?></td>
                                    <td class="text-end fw-bold" style="color: var(--primary-color);">
                                        Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?>
                                    </td>
                                    <td style="max-width: 250px;">
                                        <div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                             title="<?= htmlspecialchars($row['keperluan']); ?>">
                                            <?= htmlspecialchars($row['keperluan']); ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="status-badge <?= $badge_class ?>">
                                            <?= strtoupper($row['status']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <small><?= date('d/m/Y H:i', strtotime($row['tanggal_dibuat'])); ?></small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['status'] === 'selesai'): ?>
                                            <a href="cetak_surat_kasbon.php?id=<?= $row['id'] ?>" 
                                               target="_blank" 
                                               class="btn-print">
                                                <i class="bi bi-printer-fill"></i> Cetak
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile View -->
            <div class="mobile-view" style="padding: 1rem;">
                <?php 
                $result->data_seek(0); // Reset pointer
                while ($row = $result->fetch_assoc()): 
                    $status = strtolower($row['status']);
                    $badge_class = 'badge-pending';
                    if ($status === 'ditolak') {
                        $badge_class = 'badge-ditolak';
                    } elseif ($status === 'selesai') {
                        $badge_class = 'badge-selesai';
                    }
                ?>
                    <div class="kasbon-card-mobile">
                        <div class="card-mobile-header">
                            <div class="card-mobile-title">
                                <h5><?= htmlspecialchars($row['nama']); ?></h5>
                                <span class="divisi-tag">
                                    <i class="bi bi-building"></i> <?= htmlspecialchars($row['divisi']); ?>
                                </span>
                            </div>
                            <span class="status-badge <?= $badge_class ?>">
                                <?= strtoupper($row['status']) ?>
                            </span>
                        </div>

                        <div class="card-mobile-info">
                            <div class="info-item">
                                <span class="info-label">
                                    <i class="bi bi-calendar3"></i> Tanggal
                                </span>
                                <span class="info-value"><?= date('d/m/Y', strtotime($row['tanggal'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">
                                    <i class="bi bi-cash-stack"></i> Jumlah
                                </span>
                                <span class="info-value amount">Rp <?= number_format($row['jumlah'], 0, ',', '.'); ?></span>
                            </div>
                        </div>

                        <div class="keperluan-section">
                            <div class="label">
                                <i class="bi bi-chat-left-text"></i> Keperluan
                            </div>
                            <div class="text"><?= htmlspecialchars($row['keperluan']); ?></div>
                        </div>

                        <div class="card-mobile-footer">
                            <div class="timestamp">
                                <i class="bi bi-clock"></i>
                                <?= date('d/m/Y H:i', strtotime($row['tanggal_dibuat'])); ?>
                            </div>
                            <?php if ($row['status'] === 'selesai'): ?>
                                <a href="cetak_surat_kasbon.php?id=<?= $row['id'] ?>" 
                                   target="_blank" 
                                   class="btn-print">
                                    <i class="bi bi-printer-fill"></i> Cetak Surat
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>Belum ada pengajuan kasbon</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // Add loading animation on page load
    window.addEventListener('load', function() {
        document.querySelectorAll('.kasbon-card-mobile, .table-modern tbody tr').forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            setTimeout(() => {
                el.style.transition = 'all 0.5s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 50);
        });
    });
</script>

</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>