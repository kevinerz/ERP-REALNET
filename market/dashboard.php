<?php
require_once __DIR__ . '/../config/database.php';
// --- 1. KONEKSI DB MITRA (Lokal) ---
require_once 'koneksi.php';

// --- 2. KONEKSI DB PEMASANGAN (Remote) & AMBIL DATA STATISTIK ---
$db_host_pasang = 'localhost';
$db_user_pasang = 'u272457353_kevinsamsung9';
$db_pass_pasang = 'Admionkevin99'; 
$db_name_pasang = 'u272457353_db_pemasangan';

$conn_pasang = getErpDbConnection();

// Array penampung statistik
$stats_pelanggan = []; // Format: ['Nama Mitra' => Jumlah Pelanggan]
$total_pelanggan_all = 0;
$top_mitra_arr = [];

if (!$conn_pasang->connect_error) {
    // A. Ambil Jumlah Pelanggan per Marketing
    $sql_stats = "SELECT marketing, COUNT(*) as total FROM pelanggan_instalasi GROUP BY marketing ORDER BY total DESC";
    $res_stats = $conn_pasang->query($sql_stats);
    
    if ($res_stats) {
        while($row = $res_stats->fetch_assoc()) {
            // Normalisasi nama (lowercase biar matching gampang)
            $nama_key = strtolower(trim($row['marketing']));
            $stats_pelanggan[$nama_key] = $row['total'];
            $total_pelanggan_all += $row['total'];
            
            // Simpan untuk Top 5 Leaderboard
            if (count($top_mitra_arr) < 5) {
                $top_mitra_arr[] = [
                    'nama' => $row['marketing'],
                    'total' => $row['total']
                ];
            }
        }
    }
}

// --- 3. AMBIL DATA MITRA (Utama) ---
$rows = [];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = "WHERE 1";
if ($search !== '') {
    $search_esc = $conn->real_escape_string($search);
    $where .= " AND nama LIKE '%$search_esc%'";
}

$sql = "SELECT `id`, `nama`, `wa`, `payment_type`, `bank_nama`, `bank_rekening`, `e_wallet_nama`, `e_wallet_nomor`, `created_at`
        FROM `mitra`
        $where
        ORDER BY nama ASC"; // Kita ambil semua dulu, sorting by ranking dilakukan via PHP nanti jika perlu, tapi default nama ASC oke.

$result = $conn->query($sql);
$total_mitra = 0;

if ($result && $result->num_rows > 0) {
    $total_mitra = $result->num_rows;
    while ($row = $result->fetch_assoc()) {
        // Inject data statistik ke dalam row mitra
        $nama_key = strtolower(trim($row['nama']));
        $row['jumlah_pelanggan'] = isset($stats_pelanggan[$nama_key]) ? $stats_pelanggan[$nama_key] : 0;
        $rows[] = $row;
    }
}
$conn->close();

// Fungsi Helper
function tglIndo($tgl) {
    if (!$tgl) return '-';
    $bulan = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agt', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
    $tgl_str = substr($tgl, 0, 10);
    $exp = explode('-', $tgl_str);
    return (count($exp) == 3) ? $exp[2] . ' ' . $bulan[(int)$exp[1]] . ' ' . $exp[0] : $tgl;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Statistik & Data Mitra</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root { --primary: #A00000; --bg: #f3f4f6; --card: #fff; --text: #1f2937; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; color: var(--text); padding-bottom: 50px; }
        .main-container { max-width: 1200px; margin: 30px auto; padding: 0 15px; }
        
        /* Stats Cards */
        .stat-card { background: var(--card); border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); height: 100%; display: flex; align-items: center; border-left: 4px solid var(--primary); }
        .stat-icon { width: 50px; height: 50px; background: #ffeaea; color: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 15px; }
        
        /* Leaderboard */
        .leaderboard-card { background: linear-gradient(135deg, #A00000 0%, #800000 100%); color: white; border-radius: 16px; padding: 25px; box-shadow: 0 10px 20px rgba(160,0,0,0.2); height: 100%; }
        .lb-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .lb-item:last-child { border-bottom: none; }
        .lb-rank { background: rgba(255,255,255,0.2); width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: bold; margin-right: 10px; }
        .lb-name { font-weight: 500; flex-grow: 1; }
        .lb-val { font-weight: 700; font-size: 1.1rem; }

        /* Main Table Card */
        .content-card { background: var(--card); border-radius: 16px; box-shadow: 0 4px 6px rgba(0,0,0,0.03); padding: 25px; margin-top: 30px; }
        
        .table thead th { background: #f9fafb; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; padding: 15px; }
        .table td { padding: 15px; vertical-align: middle; font-size: 0.95rem; }
        
        /* Badges */
        .badge-count { background: #e0f2fe; color: #0284c7; padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; }
        .badge-zero { background: #f3f4f6; color: #9ca3af; }
        
        .wa-btn { background: #dcfce7; color: #16a34a; padding: 4px 10px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.8rem; transition:0.2s; }
        .wa-btn:hover { background: #16a34a; color: white; }

        /* Responsive */
        @media (max-width: 768px) {
            .table thead { display: none; }
            .table tr { display: block; border: 1px solid #e5e7eb; margin-bottom: 15px; border-radius: 8px; padding: 10px; }
            .table td { display: flex; justify-content: space-between; border: none; padding: 8px 5px; border-bottom: 1px dashed #f3f4f6; }
            .table td::before { content: attr(data-label); font-weight: 600; color: #6b7280; font-size: 0.85rem; }
        }
    </style>
</head>
<body>

<div class="main-container">
    
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Mitra</h6>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($total_mitra); ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="stat-card" style="border-left-color: #2563eb;">
                        <div class="stat-icon" style="background: #eff6ff; color: #2563eb;"><i class="fas fa-wifi"></i></div>
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">Total Pelanggan</h6>
                            <h2 class="mb-0 fw-bold"><?php echo number_format($total_pelanggan_all); ?></h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                        <h5 class="fw-bold mb-3 text-secondary"><i class="fas fa-chart-bar me-2"></i>Grafik Top 5 Mitra</h5>
                        <?php if(!empty($top_mitra_arr)): ?>
                            <?php foreach($top_mitra_arr as $tm): 
                                // Hitung persentase bar (max 100% berdasarkan nilai tertinggi)
                                $max_val = $top_mitra_arr[0]['total'];
                                $percent = ($tm['total'] / $max_val) * 100;
                            ?>
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1 small fw-bold">
                                        <span><?php echo htmlspecialchars($tm['nama']); ?></span>
                                        <span><?php echo $tm['total']; ?> Plg</span>
                                    </div>
                                    <div class="progress" style="height: 10px; border-radius: 10px; background: #f0f0f0;">
                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $percent; ?>%; background: var(--primary);" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">Belum ada data pelanggan.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="leaderboard-card">
                <div class="d-flex align-items-center mb-4">
                    <i class="fas fa-trophy fa-2x me-3 text-warning"></i>
                    <h4 class="mb-0 fw-bold">Top Leaderboard</h4>
                </div>
                
                <?php if(!empty($top_mitra_arr)): ?>
                    <?php $rank = 1; foreach($top_mitra_arr as $top): ?>
                        <div class="lb-item">
                            <div class="d-flex align-items-center">
                                <div class="lb-rank"><?php echo $rank++; ?></div>
                                <div class="lb-name"><?php echo htmlspecialchars($top['nama']); ?></div>
                            </div>
                            <div class="lb-val"><?php echo $top['total']; ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-white-50 text-center">Data belum tersedia.</p>
                <?php endif; ?>
                
                <div class="mt-4 pt-3 border-top border-white-50 text-center">
                    <small class="text-white-50">Ranking berdasarkan jumlah pelanggan aktif</small>
                </div>
            </div>
        </div>
    </div>

    <div class="content-card">
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <h4 class="fw-bold m-0"><i class="fas fa-list me-2 text-danger"></i>Data Seluruh Mitra</h4>
            
            <form class="d-flex mt-3 mt-md-0" method="get">
                <input type="text" name="search" class="form-control me-2" placeholder="Cari nama mitra..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-danger"><i class="fas fa-search"></i></button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nama Mitra</th>
                        <th>Total Pelanggan</th>
                        <th>Kontak WA</th>
                        <th>Pembayaran</th>
                        <th class="text-end">Bergabung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td data-label="Nama Mitra">
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['nama']); ?></div>
                                    <small class="text-muted">ID: #<?php echo $row['id']; ?></small>
                                </td>
                                <td data-label="Total Pelanggan">
                                    <?php 
                                        $jml = $row['jumlah_pelanggan'];
                                        $cls = ($jml > 0) ? 'badge-count' : 'badge-count badge-zero';
                                    ?>
                                    <span class="<?php echo $cls; ?>">
                                        <i class="fas fa-user-friends me-1"></i> <?php echo $jml; ?>
                                    </span>
                                </td>
                                <td data-label="Kontak">
                                    <?php if($row['wa']): 
                                        $wa = preg_replace('/[^0-9]/', '', $row['wa']);
                                        if(substr($wa, 0, 1) === '0') $wa = '62'.substr($wa, 1);
                                    ?>
                                        <a href="https://wa.me/<?php echo $wa; ?>" target="_blank" class="wa-btn">
                                            <i class="fab fa-whatsapp"></i> Chat
                                        </a>
                                    <?php else: ?> - <?php endif; ?>
                                </td>
                                <td data-label="Pembayaran">
                                    <?php if($row['bank_nama']): ?>
                                        <small class="d-block text-primary fw-bold"><i class="fas fa-university"></i> <?php echo $row['bank_nama']; ?></small>
                                        <small class="text-muted"><?php echo $row['bank_rekening']; ?></small>
                                    <?php elseif($row['e_wallet_nama']): ?>
                                        <small class="d-block text-success fw-bold"><i class="fas fa-wallet"></i> <?php echo $row['e_wallet_nama']; ?></small>
                                        <small class="text-muted"><?php echo $row['e_wallet_nomor']; ?></small>
                                    <?php else: ?>
                                        <small class="text-muted">-</small>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Bergabung" class="text-md-end">
                                    <small class="text-muted"><?php echo tglIndo($row['created_at']); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">Data tidak ditemukan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>