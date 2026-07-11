<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}
require_once 'config/database.php';
$nama_user = $_SESSION['nama'] ?? 'User';
$inisial = strtoupper(substr($nama_user, 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FMS - Financial Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Lucide Icons (untuk sidebar yang sudah ada) -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="page-wrapper">
    <nav class="sidebar">
        <div class="sidebar-header">
            <h2>FMS</h2>
        </div>
        <div class="sidebar-nav">
            <a href="index.php" class="nav-link"><i data-lucide="layout-dashboard" class="icon"></i>Dashboard</a>
            <a href="pemasukan.php" class="nav-link"><i data-lucide="arrow-down-circle" class="icon"></i>Pemasukan</a>
            
            <a href="#pengeluaranSubmenu" data-bs-toggle="collapse" class="nav-link collapsed">
                <i data-lucide="arrow-up-circle" class="icon"></i>Pengeluaran <i data-lucide="chevron-down" class="arrow"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="pengeluaranSubmenu">
                <li><a href="pengeluaran.php" class="nav-link">Ringkasan</a></li>
                <li><a href="reimburse_bbm.php" class="nav-link">Reimburse BBM</a></li>
                <li><a href="fee_pasang.php" class="nav-link">Fee Pasang</a></li>
                <li><a href="fee_marketing.php" class="nav-link">Fee Marketing</a></li>
                <li><a href="fee_pic.php" class="nav-link">Fee PIC</a></li>
                <li><a href="biaya_listrik.php" class="nav-link">Biaya Listrik</a></li>
                <li><a href="gaji_karyawan.php" class="nav-link">Gaji Karyawan</a></li>
                <li><a href="sewa_kontrakan.php" class="nav-link">Sewa Kontrakan</a></li>
                <li><a href="bayar_upstream.php" class="nav-link">Bayar Upstream</a></li>
                <li><a href="kasbon.php" class="nav-link">Kasbon Karyawan</a></li>
                <li><a href="kontribusi.php" class="nav-link">Kontribusi</a></li>
            </ul>
            <a href="#asetSubmenu" data-bs-toggle="collapse" class="nav-link collapsed">
                <i data-lucide="archive" class="icon"></i>Manajemen Aset <i data-lucide="chevron-down" class="arrow"></i>
            </a>
            <ul class="collapse sidebar-submenu" id="asetSubmenu">
                <li><a href="pembayaran_aset.php" class="nav-link">Pembayaran Aset</a></li>
            </ul>
            <a href="logout.php" class="nav-link"><i data-lucide="log-out" class="icon"></i>Logout</a>
        </div>
    </nav>
    <div class="page-overlay"></div>
    <div class="main-wrapper">
        <nav class="navbar-top">
            <a href="#" class="sidebar-toggler"><i data-lucide="menu"></i></a>
            
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="profile-pic me-2"><?= $inisial ?></div>
                    <strong><?= htmlspecialchars($nama_user) ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-end text-small shadow">
                    <li><a class="dropdown-item" href="#"><i data-lucide="user-circle" class="me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i data-lucide="log-out" class="me-2"></i>Sign out</a></li>
                </ul>
            </div>
        </nav>
        <main class="main-content">