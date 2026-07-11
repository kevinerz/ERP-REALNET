<?php
// index_dashboard.php

// Konfigurasi database (samakan dengan yang lain)
$servername   = "localhost";
$username     = "u272457353_kevinsamsung";
$password     = "Admionkevin99";
$database     = "u272457353_tiket_helpdesk";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// 1. Total tiket
$totalQ    = "SELECT COUNT(*) AS total FROM tiket";
$total     = (int)$conn->query($totalQ)->fetch_assoc()['total'];

// 2. Jumlah per status
$statusQ   = "SELECT status, COUNT(*) AS jumlah 
              FROM tiket 
              GROUP BY status";
$statusRes = $conn->query($statusQ);
$statusData = [];
while ($r = $statusRes->fetch_assoc()) {
    $statusData[$r['status']] = (int)$r['jumlah'];
}

// 3. Jumlah per POP
$popQ      = "SELECT pop, COUNT(*) AS jumlah 
              FROM tiket 
              GROUP BY pop 
              ORDER BY pop";
$popRes    = $conn->query($popQ);
$popLabels = [];
$popCounts = [];
while ($r = $popRes->fetch_assoc()) {
    $popLabels[] = $r['pop'];
    $popCounts[] = (int)$r['jumlah'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Dashboard Statistik</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="container my-4">
    <h1 class="mb-4 text-center">Dashboard Statistik Tiket</h1>

    <div class="row g-4">
      <!-- Total Tiket -->
      <div class="col-md-4">
        <div class="card text-center shadow-sm">
          <div class="card-body">
            <h5 class="card-title">Total Tiket</h5>
            <p class="display-4"><?= $total ?></p>
          </div>
        </div>
      </div>

      <!-- Distribusi Status -->
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-header">Distribusi Status</div>
          <div class="card-body">
            <canvas id="statusChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Distribusi per POP -->
      <div class="col-md-4">
        <div class="card shadow-sm">
          <div class="card-header">Tiket per POP</div>
          <div class="card-body">
            <canvas id="popChart"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Data status
    const statusLabels = <?= json_encode(array_keys($statusData)) ?>;
    const statusCounts = <?= json_encode(array_values($statusData)) ?>;

    new Chart(document.getElementById('statusChart'), {
      type: 'pie',
      data: {
        labels: statusLabels,
        datasets: [{
          data: statusCounts,
          // warna default Chart.js
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });

    // Data per POP
    const popLabels = <?= json_encode($popLabels) ?>;
    const popCounts = <?= json_encode($popCounts) ?>;

    new Chart(document.getElementById('popChart'), {
      type: 'bar',
      data: {
        labels: popLabels,
        datasets: [{
          label: 'Jumlah Tiket',
          data: popCounts,
          // warna default Chart.js
        }]
      },
      options: {
        responsive: true,
        scales: {
          x: { title: { display: true, text: 'POP' } },
          y: { title: { display: true, text: 'Jumlah' }, beginAtZero: true }
        }
      }
    });
  </script>
</body>
</html>
