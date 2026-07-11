<?php
require __DIR__ . '/config.php';

$status = $_GET['status'] ?? 'pending';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';

$allowed_status = ['pending', 'approved', 'rejected', 'all'];
$allowed_sorts = ['id', 'nama_pemilik', 'nama_brand', 'kapasitas_nilai', 'created_at', 'status'];
$allowed_orders = ['ASC', 'DESC'];

if (!in_array($status, $allowed_status, true)) $status = 'pending';
if (!in_array($sort, $allowed_sorts)) $sort = 'created_at';
if (!in_array($order, $allowed_orders)) $order = 'DESC';

if (!function_exists('e')) {
  function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
  }
}

$query = "SELECT * FROM mitra_resmi WHERE 1=1";
$params = [];

if ($status !== 'all') {
  $query .= " AND status = :status";
  $params[':status'] = $status;
}

if ($search) {
  $query .= " AND (nama_pemilik LIKE :search OR nama_brand LIKE :search)";
  $params[':search'] = '%' . $search . '%';
}

$query .= " ORDER BY $sort $order LIMIT 500";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$stat_stmt = $pdo->query("SELECT status, COUNT(*) as count FROM mitra_resmi GROUP BY status");
$stats = $stat_stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$total = array_sum($stats);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Mitra Resmi</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
      background: #f8f9fa;
      color: #333;
    }

    /* NAVBAR */
    .navbar {
      background: white;
      border-bottom: 2px solid #007bff;
      padding: 1rem 2rem;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      position: sticky;
      top: 0;
      z-index: 100;
    }

    .navbar-brand {
      font-size: 1.5rem;
      font-weight: 700;
      color: #007bff;
    }

    .navbar i {
      margin-right: 0.5rem;
    }

    /* CONTAINER */
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }

    /* HEADER */
    .header {
      margin-bottom: 2rem;
    }

    .header h1 {
      font-size: 2rem;
      color: #000;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .header p {
      color: #666;
      font-size: 0.95rem;
    }

    /* STATS */
    .stats-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-box {
      background: white;
      border: 1px solid #ddd;
      border-left: 4px solid #007bff;
      padding: 1.5rem;
      border-radius: 6px;
    }

    .stat-box.pending {
      border-left-color: #ffc107;
    }

    .stat-box.approved {
      border-left-color: #28a745;
    }

    .stat-box.rejected {
      border-left-color: #dc3545;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: 700;
      color: #000;
    }

    .stat-label {
      color: #666;
      font-size: 0.9rem;
      margin-top: 0.5rem;
    }

    /* FILTER BOX */
    .filter-box {
      background: white;
      border: 1px solid #ddd;
      padding: 1.5rem;
      border-radius: 6px;
      margin-bottom: 2rem;
    }

    .filter-title {
      font-weight: 700;
      color: #000;
      margin-bottom: 1rem;
      font-size: 1rem;
    }

    .filter-title i {
      margin-right: 0.5rem;
      color: #007bff;
    }

    /* BUTTONS STATUS */
    .status-buttons {
      display: flex;
      gap: 0.75rem;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
    }

    .btn-status {
      padding: 0.6rem 1.2rem;
      border: 2px solid #ddd;
      background: white;
      color: #666;
      border-radius: 4px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.9rem;
    }

    .btn-status:hover {
      border-color: #007bff;
      color: #007bff;
    }

    .btn-status.active {
      background: #007bff;
      color: white;
      border-color: #007bff;
    }

    /* FILTER ROW */
    .filter-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1rem;
      margin-bottom: 1rem;
    }

    input, select {
      padding: 0.7rem 0.9rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 0.95rem;
      font-family: inherit;
      width: 100%;
    }

    input:focus, select:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
    }

    input::placeholder {
      color: #999;
    }

    select option {
      background: white;
      color: #333;
    }

    /* BUTTON SEARCH */
    .btn-search {
      padding: 0.7rem 1.5rem;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      font-size: 0.95rem;
    }

    .btn-search:hover {
      background: #0056b3;
    }

    .btn-search i {
      margin-right: 0.5rem;
    }

    /* TABLE */
    .table-wrapper {
      background: white;
      border: 1px solid #ddd;
      border-radius: 6px;
      overflow: hidden;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead {
      background: #f5f5f5;
      border-bottom: 2px solid #007bff;
    }

    thead th {
      padding: 1rem;
      text-align: left;
      font-weight: 700;
      color: #007bff;
      font-size: 0.9rem;
      text-transform: uppercase;
    }

    tbody tr {
      border-bottom: 1px solid #eee;
      transition: background 0.2s;
    }

    tbody tr:hover {
      background: #f9f9f9;
    }

    tbody td {
      padding: 1rem;
      color: #333;
    }

    .col-id {
      font-weight: 700;
      color: #007bff;
      width: 50px;
    }

    .col-name {
      font-weight: 600;
    }

    .col-sub {
      color: #666;
      font-size: 0.85rem;
      margin-top: 0.25rem;
    }

    .badge {
      display: inline-block;
      padding: 0.4rem 0.8rem;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 600;
      text-transform: uppercase;
    }

    .badge-pending {
      background: #fff3cd;
      color: #856404;
    }

    .badge-approved {
      background: #d4edda;
      color: #155724;
    }

    .badge-rejected {
      background: #f8d7da;
      color: #721c24;
    }

    .badge-brand {
      background: #e7f3ff;
      color: #007bff;
    }

    /* EMPTY STATE */
    .empty-state {
      text-align: center;
      padding: 3rem;
      color: #666;
    }

    .empty-state i {
      font-size: 2.5rem;
      color: #ccc;
      margin-bottom: 1rem;
    }

    .empty-state h3 {
      color: #333;
      margin-bottom: 0.5rem;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .container {
        padding: 1rem;
      }

      .header h1 {
        font-size: 1.5rem;
      }

      .stats-row {
        grid-template-columns: 1fr 1fr;
      }

      .status-buttons {
        flex-direction: column;
      }

      .btn-status {
        width: 100%;
      }

      .filter-row {
        grid-template-columns: 1fr;
      }

      table {
        font-size: 0.85rem;
      }

      thead th, tbody td {
        padding: 0.7rem;
      }

      .col-sub {
        display: block;
        margin-top: 0.3rem;
      }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<div class="navbar">
  <div class="navbar-brand">
    <i class="fas fa-building"></i> Admin Mitra Resmi
  </div>
</div>

<!-- MAIN -->
<div class="container">
  <!-- HEADER -->
  <div class="header">
    <h1>Data Pendaftar</h1>
    <p>Dashboard kelola pendaftar mitra resmi</p>
  </div>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat-box pending">
      <div class="stat-number"><?= $stats['pending'] ?? 0 ?></div>
      <div class="stat-label">Menunggu Verifikasi</div>
    </div>
    <div class="stat-box approved">
      <div class="stat-number"><?= $stats['approved'] ?? 0 ?></div>
      <div class="stat-label">Disetujui</div>
    </div>
    <div class="stat-box rejected">
      <div class="stat-number"><?= $stats['rejected'] ?? 0 ?></div>
      <div class="stat-label">Ditolak</div>
    </div>
    <div class="stat-box">
      <div class="stat-number"><?= $total ?></div>
      <div class="stat-label">Total Pendaftar</div>
    </div>
  </div>

  <!-- FILTER -->
  <div class="filter-box">
    <div class="filter-title">
      <i class="fas fa-filter"></i> Filter
    </div>

    <form method="GET">
      <!-- STATUS BUTTONS -->
      <div class="status-buttons">
        <button type="submit" name="status" value="pending" class="btn-status <?= $status === 'pending' ? 'active' : '' ?>">
          Pending
        </button>
        <button type="submit" name="status" value="approved" class="btn-status <?= $status === 'approved' ? 'active' : '' ?>">
          Approved
        </button>
        <button type="submit" name="status" value="rejected" class="btn-status <?= $status === 'rejected' ? 'active' : '' ?>">
          Rejected
        </button>
        <button type="submit" name="status" value="all" class="btn-status <?= $status === 'all' ? 'active' : '' ?>">
          Semua
        </button>
      </div>

      <!-- SEARCH & SORT -->
      <div class="filter-row">
        <input type="text" name="search" placeholder="Cari nama atau brand..." value="<?= e($search) ?>">
        <select name="sort">
          <option value="created_at" <?= $sort === 'created_at' ? 'selected' : '' ?>>Tanggal Terbaru</option>
          <option value="nama_pemilik" <?= $sort === 'nama_pemilik' ? 'selected' : '' ?>>Nama Pemilik</option>
          <option value="nama_brand" <?= $sort === 'nama_brand' ? 'selected' : '' ?>>Nama Brand</option>
          <option value="kapasitas_nilai" <?= $sort === 'kapasitas_nilai' ? 'selected' : '' ?>>Kapasitas</option>
        </select>
        <button type="submit" class="btn-search">
          <i class="fas fa-search"></i> Cari
        </button>
      </div>
    </form>
  </div>

  <!-- TABLE -->
  <div class="table-wrapper">
    <?php if ($rows): ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Nama Pemilik</th>
            <th>Brand</th>
            <th>Lokasi</th>
            <th>Kapasitas</th>
            <th>Status</th>
            <th>Tanggal</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; foreach ($rows as $r): ?>
            <tr>
              <td class="col-id"><?= $no++ ?></td>
              <td>
                <div class="col-name"><?= e($r['nama_pemilik']) ?></div>
                <div class="col-sub"><?= e($r['kelurahan_dusun']) ?>, <?= e($r['kota_kab']) ?></div>
              </td>
              <td>
                <span class="badge badge-brand"><?= e($r['nama_brand']) ?></span>
              </td>
              <td class="col-sub"><?= e($r['provinsi']) ?></td>
              <td class="col-sub"><?= e($r['kapasitas_nilai']) ?> <?= e($r['kapasitas_satuan']) ?></td>
              <td>
                <span class="badge badge-<?= $r['status'] ?>">
                  <?= ucfirst($r['status']) ?>
                </span>
              </td>
              <td class="col-sub"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Tidak ada data</h3>
        <p>Coba ubah filter pencarian Anda</p>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
  // Auto submit sort
  document.querySelector('select[name="sort"]')?.addEventListener('change', function() {
    this.closest('form').submit();
  });

  // Search dengan Enter
  document.querySelector('input[name="search"]')?.addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      this.closest('form').submit();
    }
  });
</script>

</body>
</html>