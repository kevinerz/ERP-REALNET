<?php
session_start();
include 'koneksi.php';
if (!isset($_SESSION['username']) || !isset($_SESSION['divisi'])) {
    echo "Silakan login.";
    exit;
}
$divisi = $_SESSION['divisi'];
// Normalisasi nama divisi untuk keperluan approval
if ($divisi === 'Admin') {
    $divisi = 'SPV Administrasi';
}
$status_map = [
    'Leader Area'       => 'leader_area',
    'SPV Teknis'        => 'spv_teknis',
    'Manager'           => 'manager',
    'SPV Administrasi'  => 'spv_administrasi',
    'Finance'           => 'finance',
];
$next_status = [
    'leader_area'       => 'spv_teknis',
    'spv_teknis'        => 'manager',
    'manager'           => 'finance',
    'spv_administrasi'  => 'manager',
    'finance'           => 'selesai',
];
if (!isset($status_map[$divisi])) {
    echo "Akses ditolak.";
    exit;
}
$status_filter = $status_map[$divisi];
// Proses approval
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kasbon_id = $_POST['kasbon_id'];
    $aksi = $_POST['aksi'];
    $catatan = $_POST['catatan'] ?? '';
    if ($aksi == 'setujui') {
        $new_status = $next_status[$status_filter] ?? 'selesai';
        $catatan_text = "\nDisetujui oleh $divisi: $catatan";
    } else {
        $new_status = 'ditolak';
        $catatan_text = "\nDitolak oleh $divisi: $catatan";
    }
    $stmt = $conn->prepare("UPDATE kasbon SET status = ?, catatan = CONCAT(IFNULL(catatan,''), ?) WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $catatan_text, $kasbon_id);
    $stmt->execute();
    echo "<script>alert('Kasbon diperbarui.'); window.location='approval_kasbon.php';</script>";
    exit;
}
// Ambil data kasbon
$stmt = $conn->prepare("
    SELECT k.*, u.nama, u.divisi
    FROM kasbon k
    JOIN karyawan u ON k.id_karyawan = u.id
    WHERE k.status = ?
    ORDER BY k.tanggal_dibuat DESC
");
$stmt->bind_param("s", $status_filter);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Approval Kasbon - <?= htmlspecialchars($divisi) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            --danger-gradient: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --primary-color: #667eea;
            --success-color: #56ab2f;
            --danger-color: #eb3349;
            --text-primary: #2d3748;
            --text-secondary: #718096;
            --border-color: #e2e8f0;
            --card-bg: #ffffff;
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

        .role-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .container-max {
            max-width: 1400px;
            margin: auto;
            padding: 0 1rem;
        }

        /* Back Button */
        .btn-back {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 1000;
            background: white;
            color: var(--primary-color);
            padding: 0.875rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            border: 2px solid var(--primary-color);
        }

        .btn-back:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Card Styles */
        .approval-card {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .approval-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .card-header-custom {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem 2rem;
            font-weight: 700;
            font-size: 1.25rem;
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
        }

        .table-modern td {
            padding: 1.5rem 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }

        .table-modern tbody tr {
            transition: all 0.2s ease;
        }

        .table-modern tbody tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        /* Form Actions */
        .action-form {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 0.5rem 0;
        }

        .textarea-modern {
            width: 100%;
            min-height: 80px;
            padding: 0.875rem;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            resize: vertical;
            transition: all 0.3s ease;
        }

        .textarea-modern:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-action {
            flex: 1;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-approve {
            background: var(--success-gradient);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-reject {
            background: var(--danger-gradient);
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Mobile Card View */
        .approval-card-mobile {
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

        .approval-card-mobile::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-gradient);
        }

        .approval-card-mobile:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }

        .card-mobile-header {
            margin-bottom: 1.25rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
        }

        .card-mobile-header h5 {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 1.25rem;
        }

        .divisi-tag {
            display: inline-block;
            background: linear-gradient(135deg, #e0e7ff 0%, #ddd6fe 100%);
            color: var(--primary-color);
            padding: 0.375rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.25rem;
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
            font-size: 1.5rem;
            font-weight: 700;
        }

        .keperluan-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.25rem;
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
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 24px;
            box-shadow: var(--shadow-sm);
        }

        .empty-state i {
            font-size: 5rem;
            color: var(--text-secondary);
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-secondary);
            font-size: 1.125rem;
        }

        /* Stats Card */
        .stats-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            border: 1px solid var(--border-color);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
        }

        .stat-item .icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .stat-item .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.25rem;
        }

        .stat-item .label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .btn-back {
                top: 1rem;
                right: 1rem;
                padding: 0.625rem 1.25rem;
                font-size: 0.875rem;
            }

            .hero-header h1 {
                font-size: 2rem;
            }

            .hero-header p {
                font-size: 1rem;
            }

            .desktop-view {
                display: none;
            }

            .mobile-view {
                display: block;
            }

            .info-grid {
                grid-template-columns: 1fr;
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

            .action-buttons {
                flex-direction: column;
            }

            .stats-grid {
                grid-template-columns: 1fr;
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

        /* Confirmation Modal Styling */
        .modal-content {
            border-radius: 24px;
            border: none;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-radius: 24px 24px 0 0;
            border: none;
        }

        .modal-footer {
            border: none;
            padding: 1.5rem;
        }
    </style>
</head>
<body>

<a href="../list_kasbon.php" class="btn-back">
    <i class="bi bi-arrow-left"></i> Kembali
</a>

<div class="container container-max mt-4">
    <!-- Hero Header -->
    <div class="hero-header">
        <div class="content">
            <div class="hero-icon">✅</div>
            <h1>
                <i class="bi bi-patch-check-fill"></i>
                Approval Kasbon
            </h1>
            <p>Review dan setujui pengajuan kasbon yang masuk</p>
            <span class="role-badge">
                <i class="bi bi-shield-check"></i> <?= htmlspecialchars($divisi) ?>
            </span>
        </div>
    </div>

    <!-- Stats Card -->
    <?php
    $total_pending = $result->num_rows;
    $result->data_seek(0);
    $total_amount = 0;
    while ($row = $result->fetch_assoc()) {
        $total_amount += $row['jumlah'];
    }
    $result->data_seek(0);
    ?>
    
    <div class="stats-card">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="icon">📋</div>
                <div class="value"><?= $total_pending ?></div>
                <div class="label">Menunggu Approval</div>
            </div>
            <div class="stat-item">
                <div class="icon">💰</div>
                <div class="value">Rp <?= number_format($total_amount, 0, ',', '.') ?></div>
                <div class="label">Total Nilai Kasbon</div>
            </div>
            <div class="stat-item">
                <div class="icon">👤</div>
                <div class="value"><?= htmlspecialchars($divisi) ?></div>
                <div class="label">Level Approval</div>
            </div>
        </div>
    </div>

    <?php if ($result->num_rows > 0): ?>
        
        <!-- Desktop View -->
        <div class="desktop-view">
            <div class="approval-card">
                <div class="card-header-custom">
                    <i class="bi bi-list-check"></i>
                    Daftar Pengajuan Kasbon
                </div>
                <div class="table-responsive">
                    <table class="table table-modern mb-0">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 15%;">Nama</th>
                                <th style="width: 12%;">Divisi</th>
                                <th style="width: 10%;">Tanggal</th>
                                <th style="width: 12%;">Jumlah</th>
                                <th style="width: 20%;">Keperluan</th>
                                <th style="width: 26%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $result->data_seek(0);
                            $no = 1;
                            while ($row = $result->fetch_assoc()): 
                            ?>
                                <tr>
                                    <td class="text-center fw-bold"><?= $no++; ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td><?= htmlspecialchars($row['divisi']) ?></td>
                                    <td class="text-center"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="text-end fw-bold" style="color: var(--primary-color);">
                                        Rp <?= number_format($row['jumlah'], 0, ',', '.') ?>
                                    </td>
                                    <td>
                                        <div style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                             title="<?= htmlspecialchars($row['keperluan']) ?>">
                                            <?= htmlspecialchars($row['keperluan']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <form method="post" class="action-form" onsubmit="return confirmAction(event)">
                                            <input type="hidden" name="kasbon_id" value="<?= $row['id'] ?>">
                                            <textarea name="catatan" class="textarea-modern" placeholder="Masukkan catatan (opsional)..."></textarea>
                                            <div class="action-buttons">
                                                <button type="submit" name="aksi" value="setujui" class="btn-action btn-approve">
                                                    <i class="bi bi-check-circle-fill"></i> Setujui
                                                </button>
                                                <button type="submit" name="aksi" value="tolak" class="btn-action btn-reject">
                                                    <i class="bi bi-x-circle-fill"></i> Tolak
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Mobile View -->
        <div class="mobile-view">
            <?php 
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()): 
            ?>
                <div class="approval-card-mobile">
                    <div class="card-mobile-header">
                        <h5><?= htmlspecialchars($row['nama']) ?></h5>
                        <span class="divisi-tag">
                            <i class="bi bi-building"></i> <?= htmlspecialchars($row['divisi']) ?>
                        </span>
                    </div>

                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">
                                <i class="bi bi-calendar3"></i> Tanggal
                            </span>
                            <span class="info-value"><?= date('d/m/Y', strtotime($row['tanggal'])) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">
                                <i class="bi bi-cash-stack"></i> Jumlah
                            </span>
                            <span class="info-value amount">Rp <?= number_format($row['jumlah'], 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <div class="keperluan-section">
                        <div class="label">
                            <i class="bi bi-chat-left-text"></i> Keperluan
                        </div>
                        <div class="text"><?= htmlspecialchars($row['keperluan']) ?></div>
                    </div>

                    <form method="post" class="action-form" onsubmit="return confirmAction(event)">
                        <input type="hidden" name="kasbon_id" value="<?= $row['id'] ?>">
                        <textarea name="catatan" class="textarea-modern" placeholder="Masukkan catatan (opsional)..."></textarea>
                        <div class="action-buttons">
                            <button type="submit" name="aksi" value="setujui" class="btn-action btn-approve">
                                <i class="bi bi-check-circle-fill"></i> Setujui
                            </button>
                            <button type="submit" name="aksi" value="tolak" class="btn-action btn-reject">
                                <i class="bi bi-x-circle-fill"></i> Tolak
                            </button>
                        </div>
                    </form>
                </div>
            <?php endwhile; ?>
        </div>

    <?php else: ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h3>Tidak Ada Pengajuan</h3>
            <p>Saat ini tidak ada kasbon yang menunggu approval dari Anda</p>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function confirmAction(event) {
        const form = event.target;
        const aksi = event.submitter.value;
        const aksiText = aksi === 'setujui' ? 'menyetujui' : 'menolak';
        
        if (!confirm(`Apakah Anda yakin ingin ${aksiText} kasbon ini?`)) {
            event.preventDefault();
            return false;
        }
        return true;
    }

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
        document.querySelectorAll('.approval-card-mobile, .table-modern tbody tr').forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            setTimeout(() => {
                el.style.transition = 'all 0.5s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 50);
        });
    });

    // Auto-resize textarea
    document.querySelectorAll('.textarea-modern').forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });
</script>

</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>