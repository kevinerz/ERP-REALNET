<?php
require_once __DIR__ . '/config/database.php';
// =========================================
//  JADWAL LIBUR KARYAWAN - PREMIUM VERSION
// =========================================
date_default_timezone_set('Asia/Jakarta');

define('DB_HOST_UMUMDATA', 'localhost');
define('DB_USER_UMUMDATA', 'u272457353_kevinsamsung99');
define('DB_PASS_UMUMDATA', 'Admionkevin99');
define('DB_NAME_UMUMDATA', 'u272457353_umumdata');

$conn = getErpDbConnection();
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);
$conn->set_charset('utf8mb4');

// BUAT TABEL
$sqlCreate = "CREATE TABLE IF NOT EXISTS jadwal_libur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_karyawan INT NOT NULL,
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_hari_karyawan (hari, id_karyawan),
    FOREIGN KEY (id_karyawan) REFERENCES karyawan(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$conn->query($sqlCreate);

$hariList = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'];
$mapHariEnglish = ['Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'];

$hariToday = $mapHariEnglish[date('l')] ?? 'Senin';
$tanggalToday = date('d-m-Y');

// =====================
// PROSES AJAX
// =====================
if (isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if ($_POST['ajax'] === 'save_single') {
        $id_karyawan = (int)$_POST['id_karyawan'];
        $hari = $_POST['hari'];
        $checked = isset($_POST['checked']) ? (int)$_POST['checked'] : 0;
        
        if (!in_array($hari, $hariList)) die(json_encode(['ok'=>0]));
        
        $sqlCheck = "SELECT id FROM hr_jadwal_libur WHERE id_karyawan = ? AND hari = ?";
        $stmtCheck = $conn->prepare($sqlCheck);
        $stmtCheck->bind_param('is', $id_karyawan, $hari);
        $stmtCheck->execute();
        $exist = $stmtCheck->get_result()->num_rows > 0;
        $stmtCheck->close();
        
        if ($checked && !$exist) {
            $sqlIns = "INSERT INTO hr_jadwal_libur (id_karyawan, hari) VALUES (?, ?)";
            $stmtIns = $conn->prepare($sqlIns);
            $stmtIns->bind_param('is', $id_karyawan, $hari);
            $stmtIns->execute();
            $stmtIns->close();
        } elseif (!$checked && $exist) {
            $sqlDel = "DELETE FROM hr_jadwal_libur WHERE id_karyawan = ? AND hari = ?";
            $stmtDel = $conn->prepare($sqlDel);
            $stmtDel->bind_param('is', $id_karyawan, $hari);
            $stmtDel->execute();
            $stmtDel->close();
        }
        die(json_encode(['ok'=>1]));
    }
}

// AMBIL DATA KARYAWAN
$karyawan = [];
$sqlKaryawan = "SELECT id, nama, divisi, jabatan FROM hr_karyawan WHERE status_aktif = 1 ORDER BY nama ASC";
if ($res = $conn->query($sqlKaryawan)) {
    while ($row = $res->fetch_assoc()) {
        $karyawan[] = $row;
    }
    $res->free();
}

// AMBIL JADWAL
$jadwal = [];
$sqlJadwal = "SELECT hari, id_karyawan FROM hr_jadwal_libur";
if ($res = $conn->query($sqlJadwal)) {
    while ($row = $res->fetch_assoc()) {
        $h = $row['hari'];
        $idk = (int)$row['id_karyawan'];
        if (!isset($jadwal[$h])) $jadwal[$h] = [];
        $jadwal[$h][] = $idk;
    }
    $res->free();
}

// AMBIL DATA LIBUR HARI INI
$liburToday = [];
$sqlToday = "SELECT k.nama, k.divisi, k.jabatan FROM hr_jadwal_libur jl 
    JOIN hr_karyawan k ON k.id = jl.id_karyawan WHERE jl.hari = ? ORDER BY k.nama ASC";
$stmtToday = $conn->prepare($sqlToday);
if ($stmtToday) {
    $stmtToday->bind_param('s', $hariToday);
    $stmtToday->execute();
    $resToday = $stmtToday->get_result();
    if ($resToday) {
        while ($row = $resToday->fetch_assoc()) {
            $liburToday[] = $row;
        }
    }
    $stmtToday->close();
}

// TEXT WA HARI INI
$textWA = "📋 *Jadwal Libur {$hariToday}, {$tanggalToday}*\n\n";
if (count($liburToday) === 0) {
    $textWA .= "✅ Tidak ada jadwal libur terdaftar.";
} else {
    foreach ($liburToday as $k) {
        $nama = $k['nama'];
        $inf = trim($k['divisi'].' - '.$k['jabatan']);
        $textWA .= "• {$nama}";
        if ($inf !== '-' && $inf !== '') $textWA .= " ({$inf})";
        $textWA .= "\n";
    }
}

// TEXT WA SEMINGGU - PERBAIKAN TANGGAL AKURAT
$today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$startOfWeek = clone $today;
// Set ke Senin minggu ini (format ISO 8601: 1=Monday, 7=Sunday)
while ($startOfWeek->format('N') != 1) {
    $startOfWeek->modify('-1 day');
}

$textWASeminggu = "📅 *JADWAL LIBUR MINGGUAN*\n";
$startDate = $startOfWeek->format('d-m-Y');
$endDate = (clone $startOfWeek)->modify('+6 days')->format('d-m-Y');
$textWASeminggu .= "{$startDate} - {$endDate}\n\n";

$adaLibur = false;
foreach ($hariList as $idx => $hari) {
    $selectedIds = $jadwal[$hari] ?? [];
    $dateObj = clone $startOfWeek;
    $dateObj->modify("+{$idx} days");
    $tanggal = $dateObj->format('d-m-Y');
    
    if (count($selectedIds) > 0) {
        $adaLibur = true;
        $textWASeminggu .= "*{$hari}, {$tanggal}:*\n";
        foreach ($selectedIds as $idk) {
            $sqlNama = "SELECT nama, divisi, jabatan FROM hr_karyawan WHERE id = ?";
            $stmtNama = $conn->prepare($sqlNama);
            if ($stmtNama) {
                $stmtNama->bind_param('i', $idk);
                $stmtNama->execute();
                $resNama = $stmtNama->get_result();
                if ($row = $resNama->fetch_assoc()) {
                    $nama = $row['nama'];
                    $inf = trim($row['divisi'].' - '.$row['jabatan']);
                    $textWASeminggu .= "  • {$nama}";
                    if ($inf !== '-' && $inf !== '') $textWASeminggu .= " ({$inf})";
                    $textWASeminggu .= "\n";
                }
                $stmtNama->close();
            }
        }
        $textWASeminggu .= "\n";
    }
}
if (!$adaLibur) {
    $textWASeminggu .= "✅ Tidak ada jadwal libur minggu ini.";
}

// Hitung statistik
$totalKaryawan = count($karyawan);
$totalLiburHariIni = count($liburToday);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Jadwal Libur Karyawan</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --light-bg: #f8fafc;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            color: #1e293b;
            padding-top: 100px;
            padding-bottom: 50px;
            min-height: 100vh;
        }

        .container { max-width: 1400px; }

        /* NAVBAR */
        .navbar-premium {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            padding: 15px 0;
        }

        .navbar-premium h6 {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary);
            margin: 0;
        }

        /* HEADER */
        .header-premium {
            margin-bottom: 40px;
            animation: slideDown 0.6s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .header-premium h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .header-premium p {
            color: #64748b;
            font-size: 1rem;
            margin: 0;
        }

        /* STATS CARDS */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), #0ea5e9);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-md);
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary);
        }

        /* CARD UTAMA */
        .card-premium {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            margin-bottom: 25px;
        }

        .card-premium:hover {
            box-shadow: var(--shadow-md);
        }

        .card-header-premium {
            background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
            color: white;
            padding: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.2rem;
            font-weight: 700;
            border: none;
        }

        .card-header-premium i {
            font-size: 1.5rem;
        }

        .card-body-premium {
            padding: 30px;
        }

        /* TABS */
        .tabs-container {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            overflow-x: auto;
            padding-bottom: 12px;
            flex-wrap: wrap;
        }

        .tab-btn-premium {
            padding: 12px 20px;
            background: var(--light-bg);
            border: 2px solid transparent;
            border-radius: 10px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
            white-space: nowrap;
            position: relative;
        }

        .tab-btn-premium:hover {
            background: #e2e8f0;
            color: var(--primary);
        }

        .tab-btn-premium.active {
            background: linear-gradient(135deg, var(--primary) 0%, #1e40af 100%);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .tab-btn-premium.today {
            background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
            color: white;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        /* CONTENT */
        .hari-content-premium {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .hari-content-premium.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .karyawan-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            max-height: 500px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .karyawan-list::-webkit-scrollbar {
            width: 8px;
        }

        .karyawan-list::-webkit-scrollbar-track {
            background: var(--light-bg);
            border-radius: 4px;
        }

        .karyawan-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }

        .karyawan-card {
            display: flex;
            align-items: center;
            padding: 16px;
            background: var(--light-bg);
            border: 2px solid var(--border);
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .karyawan-card:hover {
            background: white;
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.1);
        }

        .karyawan-card input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            margin-right: 15px;
            flex-shrink: 0;
            accent-color: var(--primary);
        }

        .karyawan-card input[type="checkbox"]:checked + .karyawan-info {
            opacity: 0.7;
        }

        .karyawan-info {
            flex: 1;
            min-width: 0;
        }

        .karyawan-nama {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 4px;
            font-size: 0.95rem;
        }

        .karyawan-detail {
            font-size: 0.85rem;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* EMPTY STATE */
        .empty-state-premium {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state-premium i {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        /* SIDEBAR */
        .sidebar-section {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        /* LIBUR BOX */
        .libur-box {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
        }

        .libur-box h5 {
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 15px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .libur-list {
            max-height: 300px;
            overflow-y: auto;
            padding-right: 8px;
        }

        .libur-list::-webkit-scrollbar {
            width: 6px;
        }

        .libur-list::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 3px;
        }

        .libur-item {
            padding: 12px;
            background: var(--light-bg);
            border-radius: 8px;
            margin-bottom: 8px;
            border-left: 4px solid var(--primary);
        }

        .libur-nama {
            font-weight: 700;
            color: #1e293b;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .libur-detail {
            font-size: 0.8rem;
            color: #64748b;
        }

        /* WHATSAPP */
        .wa-box {
            background: linear-gradient(135deg, #e7f5ea 0%, #dcfce7 100%);
            border: 2px solid var(--success);
            border-radius: 16px;
            padding: 25px;
        }

        .wa-box h5 {
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .wa-box i {
            color: var(--success);
            font-size: 1.3rem;
        }

        .wa-text {
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.6;
            white-space: pre-wrap;
            word-break: break-word;
            color: #1e293b;
            max-height: 200px;
            overflow-y: auto;
            padding: 15px;
            background: white;
            border-radius: 10px;
            margin-bottom: 12px;
            border: 1px solid var(--border);
        }

        .wa-text::-webkit-scrollbar {
            width: 6px;
        }

        .wa-text::-webkit-scrollbar-thumb {
            background: var(--success);
            border-radius: 3px;
        }

        .btn-wa {
            width: 100%;
            padding: 12px 16px;
            background: var(--success);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }

        .btn-wa:hover {
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.3);
        }

        .btn-wa-secondary {
            background: var(--primary);
        }

        .btn-wa-secondary:hover {
            background: #1e40af;
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.3);
        }

        /* INFO BOX */
        .info-premium {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            color: #0c4a6e;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-premium i {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        /* RESPONSIVE */
        @media (max-width: 768px) {
            .header-premium h1 {
                font-size: 1.8rem;
            }

            .karyawan-list {
                grid-template-columns: 1fr;
            }

            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            body {
                padding-top: 80px;
            }

            .card-body-premium {
                padding: 20px;
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <div class="navbar-premium">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h6><i class="bi bi-calendar2-week me-2"></i>Jadwal Libur Karyawan</h6>
                <a href="dashkaryawan.php" style="text-decoration: none; color: var(--primary); font-weight: 600; display: flex; align-items: center; gap: 5px;">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- HEADER -->
        <div class="header-premium">
            <h1><i class="bi bi-calendar3"></i> Jadwal Libur</h1>
            <p>Kelola jadwal libur mingguan karyawan dengan mudah dan cepat</p>
        </div>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-people-fill" style="margin-right: 5px;"></i>Total Karyawan</div>
                <div class="stat-value"><?php echo $totalKaryawan; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-calendar-check" style="margin-right: 5px;"></i>Libur Hari Ini</div>
                <div class="stat-value"><?php echo $totalLiburHariIni; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label"><i class="bi bi-clock" style="margin-right: 5px;"></i>Hari Ini</div>
                <div class="stat-value" style="font-size: 1.5rem; color: #64748b;"><?php echo $hariToday; ?></div>
                <div style="font-size: 0.8rem; color: #94a3b8; margin-top: 5px;"><?php echo $tanggalToday; ?></div>
            </div>
        </div>

        <div class="row g-4">
            <!-- MAIN CONTENT -->
            <div class="col-lg-8">
                <div class="card-premium">
                    <div class="card-header-premium">
                        <i class="bi bi-list-check"></i> Pilih Hari & Karyawan
                    </div>
                    <div class="card-body-premium">
                        <div class="info-premium">
                            <i class="bi bi-info-circle"></i>
                            <span>Centang nama karyawan yang libur. Perubahan otomatis tersimpan.</span>
                        </div>

                        <!-- TABS -->
                        <div class="tabs-container" id="hariTabs">
                            <?php foreach ($hariList as $hari): ?>
                                <button class="tab-btn-premium <?php echo $hari === $hariToday ? 'active today' : ''; ?>" 
                                    onclick="switchHari(this, '<?php echo $hari; ?>')">
                                    <i class="bi bi-calendar-day"></i> <?php echo substr($hari, 0, 3); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- CONTENT -->
                        <?php foreach ($hariList as $hari): ?>
                            <div class="hari-content-premium <?php echo $hari === $hariToday ? 'active' : ''; ?>" id="hari-<?php echo $hari; ?>">
                                <?php
                                $selectedIds = $jadwal[$hari] ?? [];
                                if (count($karyawan) === 0) {
                                    echo '<div class="empty-state-premium"><i class="bi bi-inbox"></i><p>Tidak ada karyawan</p></div>';
                                } else {
                                    echo '<div class="karyawan-list">';
                                    foreach ($karyawan as $k) {
                                        $idk = (int)$k['id'];
                                        $isSel = in_array($idk, $selectedIds, true);
                                        $inf = trim($k['divisi'].' - '.$k['jabatan']);
                                        ?>
                                        <label class="karyawan-card">
                                            <input type="checkbox" 
                                                data-id="<?php echo $idk; ?>" 
                                                data-hari="<?php echo htmlspecialchars($hari); ?>"
                                                class="chk-libur"
                                                <?php echo $isSel ? 'checked' : ''; ?>>
                                            <div class="karyawan-info">
                                                <div class="karyawan-nama"><?php echo htmlspecialchars($k['nama']); ?></div>
                                                <?php if ($inf !== '-' && $inf !== ''): ?>
                                                    <div class="karyawan-detail"><?php echo htmlspecialchars($inf); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                        <?php
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- SIDEBAR -->
            <div class="col-lg-4">
                <div class="sidebar-section">
                    <!-- LIBUR HARI INI -->
                    <div class="libur-box">
                        <h5>
                            <i class="bi bi-check-circle" style="color: var(--success);"></i>
                            Libur Hari Ini
                        </h5>
                        <p style="color: #64748b; font-size: 0.9rem; margin-bottom: 15px;">
                            <i class="bi bi-calendar-event me-1"></i><?php echo $hariToday; ?>, <?php echo $tanggalToday; ?>
                        </p>
                        <div class="libur-list">
                            <?php if (count($liburToday) === 0): ?>
                                <div class="empty-state-premium" style="padding: 30px 15px;">
                                    <i class="bi bi-smile" style="color: var(--success);"></i>
                                    <p style="color: #10b981; font-weight: 600;">Semua Hadir!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($liburToday as $k): ?>
                                    <div class="libur-item">
                                        <div class="libur-nama"><?php echo htmlspecialchars($k['nama']); ?></div>
                                        <div class="libur-detail">
                                            <?php 
                                            $inf = trim($k['divisi'].' - '.$k['jabatan']);
                                            if ($inf !== '-' && $inf !== '') echo htmlspecialchars($inf);
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- WHATSAPP BOX -->
                    <div class="wa-box">
                        <h5>
                            <i class="bi bi-whatsapp"></i>
                            Bagikan WhatsApp
                        </h5>
                        
                        <button class="btn-wa" onclick="copyAndNotify('hari')">
                            <i class="bi bi-calendar-day"></i> Salin - Hari Ini
                        </button>
                        <button class="btn-wa btn-wa-secondary" onclick="copyAndNotify('minggu')">
                            <i class="bi bi-calendar-week"></i> Salin - Seminggu
                        </button>

                        <div class="wa-text" id="waText" style="display: block;"><?php echo htmlspecialchars($textWA); ?></div>
                        <div class="wa-text" id="waTextSeminggu" style="display: none;"><?php echo htmlspecialchars($textWASeminggu); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function switchHari(btn, hari) {
            // Hide all content
            document.querySelectorAll('.hari-content-premium').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected content
            document.getElementById('hari-' + hari).classList.add('active');
            
            // Update button states
            document.querySelectorAll('.tab-btn-premium').forEach(b => {
                b.classList.remove('active');
            });
            btn.classList.add('active');
        }

        // Checkbox change handler
        document.querySelectorAll('.chk-libur').forEach(chk => {
            chk.addEventListener('change', function() {
                const id_karyawan = this.dataset.id;
                const hari = this.dataset.hari;
                const checked = this.checked ? 1 : 0;
                
                fetch(window.location.href, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `ajax=save_single&id_karyawan=${id_karyawan}&hari=${encodeURIComponent(hari)}&checked=${checked}`
                })
                .then(r => r.json())
                .then(data => {
                    if (data.ok) {
                        setTimeout(() => location.reload(), 300);
                    }
                });
            });
        });

        function copyAndNotify(type) {
            let text;
            
            if (type === 'hari') {
                text = document.getElementById('waText').innerText;
                document.getElementById('waText').style.display = 'block';
                document.getElementById('waTextSeminggu').style.display = 'none';
            } else {
                text = document.getElementById('waTextSeminggu').innerText;
                document.getElementById('waText').style.display = 'none';
                document.getElementById('waTextSeminggu').style.display = 'block';
            }
            
            navigator.clipboard.writeText(text).then(() => {
                const btn = event.target.closest('.btn-wa');
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-circle"></i> Berhasil Disalin!';
                btn.style.background = 'var(--success)';
                
                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.style.background = '';
                }, 2000);
            }).catch(() => {
                alert('Gagal copy. Silakan copy manual.');
            });
        }
    </script>
</body>
</html>