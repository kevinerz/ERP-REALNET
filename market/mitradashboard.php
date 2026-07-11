<?php
session_start();

if (!isset($_SESSION['login_mitra'])) {
    header("Location: login.php");
    exit();
}

require_once 'koneksi.php';

$mitra_wa = $_SESSION['mitra_wa'];
$stmt = $conn->prepare("SELECT * FROM mitra WHERE wa = ?");
$stmt->bind_param("s", $mitra_wa);
$stmt->execute();
$profil = $stmt->get_result()->fetch_assoc();
$nama_mitra = $profil['nama'];

$db_host_pasang = 'localhost';
$db_user_pasang = 'u272457353_kevinsamsung9';
$db_pass_pasang = 'Admionkevin99';
$db_name_pasang = 'u272457353_db_pemasangan';

$conn_pasang = new mysqli($db_host_pasang, $db_user_pasang, $db_pass_pasang, $db_name_pasang);
if ($conn_pasang->connect_error) die("Koneksi Gagal");

$sql_pelanggan = "
    SELECT p.id, p.tanggal, p.nama, p.paket, p.alamat, p.status AS status_pasang, f.status AS status_fee
    FROM pemasangan p
    LEFT JOIN pemasangan_fee_marketing_status f ON p.id = f.pemasangan_id
    WHERE p.marketing = ?
    ORDER BY p.tanggal DESC
";

$stmt_pasang = $conn_pasang->prepare($sql_pelanggan);
$stmt_pasang->bind_param("s", $nama_mitra);
$stmt_pasang->execute();
$result = $stmt_pasang->get_result();

$rate_marketing = 50000;
$rate_pic = 5000;
$cair_marketing = $pending_marketing = $cair_pic = $pending_pic = 0;
$pelanggan_aktif = $pelanggan_pending = 0;
$data_pelanggan = [];

while ($row = $result->fetch_assoc()) {
    $data_pelanggan[] = $row;
    
    $is_paid = in_array(strtolower($row['status_fee'] ?? ''), ['paid', 'sudah dibayar', 'lunas']);
    $is_active = in_array(strtolower($row['status_pasang']), ['selesai', 'aktif', 'online']);

    if ($is_paid) {
        $cair_marketing += $rate_marketing;
        $cair_pic += $rate_pic;
    } else {
        $pending_marketing += $rate_marketing;
        $pending_pic += $rate_pic;
    }

    if ($is_active) $pelanggan_aktif++; else $pelanggan_pending++;
}

$total_pelanggan = count($data_pelanggan);
$total_cair = $cair_marketing + $cair_pic;
$total_pending = $pending_marketing + $pending_pic;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Mitra - RealNet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #A00000;
            --dark: #1a1a1a;
            --light: #f5f7fa;
            --green: #10b981;
            --yellow: #f59e0b;
            --red: #ef4444;
            --blue: #3b82f6;
            --gray: #6b7280;
            --white: #fff;
            --shadow: 0 10px 30px rgba(0,0,0,0.1);
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.06);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { font-family: 'Poppins', sans-serif; background: var(--light); color: var(--dark); line-height: 1.6; }

        .navbar {
            background: var(--white);
            padding: 12px 20px;
            box-shadow: var(--shadow-sm);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; color: var(--primary); font-size: 1.1rem; }
        .btn-logout { background: var(--primary); color: var(--white); padding: 8px 16px; text-decoration: none; border-radius: 8px; font-size: 0.85rem; font-weight: 600; cursor: pointer; border: none; transition: all 0.3s; }
        .btn-logout:hover { background: #800000; transform: translateY(-2px); box-shadow: var(--shadow-sm); }

        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }

        .profile-card {
            background: linear-gradient(135deg, var(--primary) 0%, #800000 100%);
            color: var(--white);
            padding: 25px;
            border-radius: 16px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .profile-left h2 { font-size: 1.8rem; margin-bottom: 15px; font-weight: 800; }
        .profile-info { display: flex; flex-direction: column; gap: 8px; }
        .profile-info p { font-size: 0.9rem; opacity: 0.95; display: flex; align-items: center; gap: 8px; }

        .profile-right { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .profile-stat { background: rgba(255,255,255,0.2); padding: 15px; border-radius: 12px; backdrop-filter: blur(10px); }
        .profile-stat-label { font-size: 0.75rem; text-transform: uppercase; opacity: 0.8; font-weight: 600; margin-bottom: 5px; }
        .profile-stat-value { font-size: 1.3rem; font-weight: 800; }

        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .kpi-card { background: var(--white); padding: 20px; border-radius: 14px; box-shadow: var(--shadow-sm); border-left: 5px solid var(--primary); transition: all 0.3s; }
        .kpi-card:hover { transform: translateY(-4px); box-shadow: var(--shadow); }
        .kpi-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 12px; }
        .kpi-icon.green { background: #dcfce7; color: var(--green); }
        .kpi-icon.yellow { background: #fef3c7; color: var(--yellow); }
        .kpi-icon.blue { background: #dbeafe; color: var(--blue); }
        .kpi-label { font-size: 0.85rem; color: var(--gray); font-weight: 600; margin-bottom: 5px; text-transform: uppercase; }
        .kpi-value { font-size: 1.8rem; font-weight: 800; color: var(--dark); }

        .balance-section { background: var(--white); padding: 20px; border-radius: 14px; margin-bottom: 30px; box-shadow: var(--shadow-sm); }
        .section-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 15px; border-left: 4px solid var(--primary); padding-left: 12px; }
        
        .balance-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .balance-item { display: flex; justify-content: space-between; margin-bottom: 16px; padding-bottom: 16px; border-bottom: 1px dashed #e5e7eb; }
        .balance-item:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .balance-label { font-size: 0.9rem; color: var(--gray); font-weight: 600; }
        .balance-amount { font-size: 1.4rem; font-weight: 800; }
        .balance-amount.success { color: var(--green); }
        .balance-amount.danger { color: var(--red); }

        .controls { background: var(--white); padding: 18px; border-radius: 14px; margin-bottom: 25px; display: flex; flex-wrap: wrap; gap: 12px; align-items: center; box-shadow: var(--shadow-sm); }
        .search-box { flex: 1; min-width: 200px; position: relative; }
        .search-box input { width: 100%; padding: 10px 15px 10px 35px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem; }
        .search-box input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(160,0,0,0.1); }
        .search-box i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--gray); }

        .filter-group { display: flex; gap: 10px; flex-wrap: wrap; }
        .filter-btn { padding: 8px 16px; background: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.3s; }
        .filter-btn:hover, .filter-btn.active { background: var(--primary); color: var(--white); border-color: var(--primary); }

        .sort-select { padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 0.9rem; cursor: pointer; }
        .sort-select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(160,0,0,0.1); }

        .table-wrapper { background: var(--white); border-radius: 14px; box-shadow: var(--shadow-sm); overflow: hidden; }
        .table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 14px 16px; text-align: left; font-size: 0.9rem; }
        th { background: #f9fafb; color: var(--dark); font-weight: 700; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #e5e7eb; }
        tbody tr { border-bottom: 1px solid #e5e7eb; transition: all 0.2s; }
        tbody tr:hover { background: #f9fafb; }

        .customer-name { font-weight: 700; color: var(--dark); }
        .customer-address { font-size: 0.8rem; color: var(--gray); margin-top: 3px; }

        .badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-align: center; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }

        .empty-state { text-align: center; padding: 60px 20px; color: var(--gray); }
        .empty-state i { font-size: 4rem; color: #e5e7eb; margin-bottom: 15px; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .kpi-card, .balance-section, .profile-card { animation: fadeIn 0.5s ease-out; }

        @media (max-width: 768px) {
            .container { padding: 15px; }
            .profile-card { grid-template-columns: 1fr; padding: 20px; }
            .kpi-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
            .balance-grid { grid-template-columns: 1fr; }
            .controls { flex-direction: column; }
            .search-box { min-width: 100%; }
            th, td { padding: 10px 12px; font-size: 0.85rem; }
        }

        @media (max-width: 480px) {
            .container { padding: 12px; }
            .profile-card { padding: 15px; gap: 15px; }
            .kpi-grid { grid-template-columns: 1fr; }
            .profile-stat-value { font-size: 1.1rem; }
            .kpi-value { font-size: 1.3rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">
            <i class="fas fa-layer-group"></i> Dashboard Mitra
        </div>
        <div style="display: flex; gap: 15px; align-items: center;">
            <span id="currentTime" style="font-size: 0.9rem; color: var(--gray);"></span>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Keluar</a>
        </div>
    </nav>

    <div class="container">

        <!-- PROFILE CARD -->
        <div class="profile-card">
            <div class="profile-left">
                <h2><?= htmlspecialchars($nama_mitra) ?></h2>
                <div class="profile-info">
                    <p><i class="fab fa-whatsapp"></i> <?= htmlspecialchars($profil['wa']) ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> Status: <strong style="color:#90EE90;">Aktif</strong></p>
                    <?php if($profil['payment_type'] == 'bank'): ?>
                        <p><i class="fas fa-university"></i> <?= htmlspecialchars($profil['bank_nama']) ?></p>
                    <?php else: ?>
                        <p><i class="fas fa-mobile-alt"></i> <?= htmlspecialchars($profil['e_wallet_nama']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="profile-right">
                <div class="profile-stat">
                    <div class="profile-stat-label">Total Pelanggan</div>
                    <div class="profile-stat-value"><?= $total_pelanggan ?></div>
                </div>
                <div class="profile-stat">
                    <div class="profile-stat-label">Saldo Cair</div>
                    <div class="profile-stat-value" style="color: #90EE90;">Rp <?= number_format($total_cair, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <!-- KPI CARDS -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-icon green"><i class="fas fa-check-circle"></i></div>
                <div class="kpi-label">Pelanggan Aktif</div>
                <div class="kpi-value"><?= $pelanggan_aktif ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon yellow"><i class="fas fa-hourglass-half"></i></div>
                <div class="kpi-label">Menunggu Proses</div>
                <div class="kpi-value"><?= $pelanggan_pending ?></div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon blue"><i class="fas fa-wallet"></i></div>
                <div class="kpi-label">Total Potensi</div>
                <div class="kpi-value">Rp <?= number_format($total_cair + $total_pending, 0, ',', '.') ?></div>
            </div>
        </div>

        <!-- BALANCE SECTION -->
        <div class="balance-section">
            <h3 class="section-title"><i class="fas fa-money-bill-wave"></i> Ringkasan Komisi & Saldo</h3>
            <div class="balance-grid">
                <div>
                    <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--dark);">Marketing (Rp 50.000/Pasang)</h4>
                    <div class="balance-item">
                        <div class="balance-label">✓ Sudah Cair</div>
                        <div class="balance-amount success">Rp <?= number_format($cair_marketing, 0, ',', '.') ?></div>
                    </div>
                    <div class="balance-item">
                        <div class="balance-label">⏳ Pending</div>
                        <div class="balance-amount danger">Rp <?= number_format($pending_marketing, 0, ',', '.') ?></div>
                    </div>
                </div>
                <div>
                    <h4 style="font-weight: 700; margin-bottom: 12px; color: var(--dark);">PIC/Koordinator (Rp 5.000/Aktif)</h4>
                    <div class="balance-item">
                        <div class="balance-label">✓ Sudah Cair</div>
                        <div class="balance-amount success">Rp <?= number_format($cair_pic, 0, ',', '.') ?></div>
                    </div>
                    <div class="balance-item">
                        <div class="balance-label">⏳ Pending</div>
                        <div class="balance-amount danger">Rp <?= number_format($pending_pic, 0, ',', '.') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTROLS & FILTERS -->
        <div class="controls">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Cari nama pelanggan atau alamat...">
            </div>
            <div class="filter-group">
                <button class="filter-btn active" data-filter="all">Semua</button>
                <button class="filter-btn" data-filter="aktif">Aktif</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="paid">Dibayar</button>
            </div>
            <select class="sort-select" id="sortSelect">
                <option value="terbaru">Terbaru</option>
                <option value="terlama">Terlama</option>
                <option value="nama-a-z">Nama A-Z</option>
                <option value="nama-z-a">Nama Z-A</option>
            </select>
        </div>

        <!-- TABLE -->
        <h3 class="section-title"><i class="fas fa-table"></i> Data Riwayat Pelanggan</h3>
        <div class="table-wrapper">
            <?php if (empty($data_pelanggan)): ?>
                <div class="empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>Belum ada data pelanggan.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table id="dataTable">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Nama Pelanggan</th>
                                <th>Paket</th>
                                <th>Status Pemasangan</th>
                                <th>Status Komisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data_pelanggan as $plg): 
                                $st_pasang = strtolower($plg['status_pasang']);
                                $is_active = in_array($st_pasang, ['selesai', 'aktif', 'online']);
                                $is_paid = in_array(strtolower($plg['status_fee'] ?? ''), ['paid', 'sudah dibayar', 'lunas']);
                            ?>
                            <tr class="data-row" data-status="<?= $is_active ? 'aktif' : 'pending' ?>" data-paid="<?= $is_paid ? 'paid' : 'unpaid' ?>">
                                <td><?= date('d/m/Y', strtotime($plg['tanggal'])) ?></td>
                                <td>
                                    <div class="customer-name"><?= htmlspecialchars($plg['nama']) ?></div>
                                    <div class="customer-address"><?= htmlspecialchars($plg['alamat']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($plg['paket']) ?></td>
                                <td><?= $is_active ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Pending</span>' ?></td>
                                <td><?= $is_paid ? '<span class="badge badge-success">PAID</span>' : '<span class="badge badge-danger">Belum Bayar</span>' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <script>
        // Update waktu
        function updateTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('id-ID');
        }
        updateTime();
        setInterval(updateTime, 1000);

        // Filter & Search
        const filterBtns = document.querySelectorAll('.filter-btn');
        const searchInput = document.getElementById('searchInput');
        const sortSelect = document.getElementById('sortSelect');
        const rows = document.querySelectorAll('.data-row');

        function filterTable() {
            const activeFilter = document.querySelector('.filter-btn.active').dataset.filter;
            const searchTerm = searchInput.value.toLowerCase();

            rows.forEach(row => {
                let show = true;

                if (activeFilter !== 'all') {
                    if (activeFilter === 'paid' && row.dataset.paid !== 'paid') show = false;
                    else if (activeFilter === 'aktif' && row.dataset.status !== 'aktif') show = false;
                    else if (activeFilter === 'pending' && row.dataset.status !== 'pending') show = false;
                }

                if (searchTerm && !row.textContent.toLowerCase().includes(searchTerm)) show = false;

                row.style.display = show ? '' : 'none';
            });
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                filterBtns.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterTable();
            });
        });

        searchInput.addEventListener('input', filterTable);

        sortSelect.addEventListener('change', () => {
            const table = document.getElementById('dataTable');
            const tbody = table.querySelector('tbody');
            const rowsArray = Array.from(tbody.querySelectorAll('tr'));

            rowsArray.sort((a, b) => {
                const val = sortSelect.value;
                if (val === 'terbaru') return new Date(b.cells[0].textContent) - new Date(a.cells[0].textContent);
                if (val === 'terlama') return new Date(a.cells[0].textContent) - new Date(b.cells[0].textContent);
                if (val === 'nama-a-z') return a.cells[1].textContent.localeCompare(b.cells[1].textContent);
                if (val === 'nama-z-a') return b.cells[1].textContent.localeCompare(a.cells[1].textContent);
            });

            rowsArray.forEach(row => tbody.appendChild(row));
        });
    </script>

</body>
</html>