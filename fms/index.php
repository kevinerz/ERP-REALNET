<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'templates/header.php';
date_default_timezone_set('Asia/Jakarta');

// --- LOGIKA DATA ---

// 1. Ambil Ringkasan Total (Pemasukan, Pengeluaran, Saldo)
$sql_summary = "
    SELECT 
        (SELECT SUM(jumlah) FROM keu_pemasukan) as total_pemasukan,
        (SELECT SUM(jumlah) FROM keu_pengeluaran) as total_pengeluaran
";
$data = $conn->query($sql_summary)->fetch_assoc();

$total_pemasukan = $data['total_pemasukan'] ?? 0;
$total_pengeluaran = $data['total_pengeluaran'] ?? 0;
$saldo = $total_pemasukan - $total_pengeluaran;

// 2. Hitung Persentase Pengeluaran (Health Check)
$rasio_pengeluaran = ($total_pemasukan > 0) ? ($total_pengeluaran / $total_pemasukan) * 100 : 0;

// 3. Ambil Data Chart (Pemasukan per Bulan dalam Tahun Ini)
$chart_pemasukan = [];
$chart_pengeluaran = [];
$labels = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];

for ($i = 1; $i <= 12; $i++) {
    $m = str_pad($i, 2, "0", STR_PAD_LEFT);
    $y = date('Y');
    
    $res_in = $conn->query("SELECT SUM(jumlah) as total FROM keu_pemasukan WHERE tanggal LIKE '$y-$m-%'")->fetch_assoc();
    $res_out = $conn->query("SELECT SUM(jumlah) as total FROM keu_pengeluaran WHERE tanggal LIKE '$y-$m-%'")->fetch_assoc();
    
    $chart_pemasukan[] = $res_in['total'] ?? 0;
    $chart_pengeluaran[] = $res_out['total'] ?? 0;
}
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        --danger-gradient: linear-gradient(135deg, #ff416c 0%, #ff4b2b 100%);
    }
    .card-stat {
        border: none;
        border-radius: 15px;
        transition: transform 0.3s;
        color: white;
    }
    .card-stat:hover { transform: translateY(-5px); }
    .bg-pemasukan { background: var(--success-gradient); }
    .bg-pengeluaran { background: var(--danger-gradient); }
    .bg-saldo { background: var(--primary-gradient); }
    .chart-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
</style>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="fw-bold text-dark">Ringkasan Keuangan</h1>
            <p class="text-muted">Pantau kesehatan finansial Anda secara real-time.</p>
        </div>
        <div class="col-md-4 text-md-end">
            <div class="badge bg-light text-dark p-2 border">Tahun Anggaran: <?= date('Y') ?></div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card card-stat bg-pemasukan shadow p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="opacity-75 d-block mb-1">Total Pemasukan</small>
                        <h2 class="fw-bold mb-0">Rp <?= number_format($total_pemasukan, 0, ',', '.'); ?></h2>
                    </div>
                    <i class="bi bi-arrow-up-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-pengeluaran shadow p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="opacity-75 d-block mb-1">Total Pengeluaran</small>
                        <h2 class="fw-bold mb-0">Rp <?= number_format($total_pengeluaran, 0, ',', '.'); ?></h2>
                    </div>
                    <i class="bi bi-arrow-down-circle fs-1 opacity-50"></i>
                </div>
                <div class="mt-3">
                    <small class="d-block">Rasio terhadap pemasukan: <?= number_format($rasio_pengeluaran, 1) ?>%</small>
                    <div class="progress mt-1" style="height: 5px; background: rgba(255,255,255,0.2);">
                        <div class="progress-bar bg-white" style="width: <?= min($rasio_pengeluaran, 100) ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-stat bg-saldo shadow p-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <small class="opacity-75 d-block mb-1">Saldo Bersih</small>
                        <h2 class="fw-bold mb-0">Rp <?= number_format($saldo, 0, ',', '.'); ?></h2>
                    </div>
                    <i class="bi bi-wallet2 fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="chart-container shadow-sm border">
                <h5 class="fw-bold mb-4">Tren Keuangan Bulanan (Tahun <?= date('Y') ?>)</h5>
                <canvas id="financeChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('financeChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Pemasukan',
            data: <?= json_encode($chart_pemasukan) ?>,
            borderColor: '#11998e',
            backgroundColor: 'rgba(17, 153, 142, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }, {
            label: 'Pengeluaran',
            data: <?= json_encode($chart_pengeluaran) ?>,
            borderColor: '#ff416c',
            backgroundColor: 'rgba(255, 65, 108, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + value.toLocaleString('id-ID');
                    }
                }
            }
        }
    }
});
</script>

<?php require_once 'templates/footer.php'; ?>