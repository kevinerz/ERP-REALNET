<?php
require_once __DIR__ . '/../config/database.php';
session_start();

// Konfigurasi Database
$servername = "localhost";
$username   = "u272457353_kevinsamsung";
$password   = "Admionkevin99";
$dbname     = "u272457353_tiket_helpdesk";

// Buat koneksi
$conn = getErpDbConnection();
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Ambil user login dan set hak akses POP
$user = strtoupper($_SESSION['username'] ?? '');
$akses_pop = [];

switch ($user) {
    case 'ARIES':
    case 'SARANI':
        $akses_pop[] = 'mauk';
        break;
    case 'GOFUR':
    case 'ALFARIZ':
    case 'JIHAN':
        $akses_pop[] = 'rajeg';
        break;
    case 'RAMDANI':
    case 'BASIR':
    case 'FZR41':
    case 'SOPI':
        $akses_pop[] = 'kemeri';
        break;
    default:
        $akses_pop = []; // default: tidak bisa akses apapun
}

// Ambil parameter GET
$keyword        = isset($_GET['cari'])          ? $conn->real_escape_string($_GET['cari'])          : '';
$status_filter  = isset($_GET['status_filter']) ? $conn->real_escape_string($_GET['status_filter']) : '';
$allowedSort    = ['nama_pelanggan', 'status'];
$sort_by        = isset($_GET['sort_by']) && in_array($_GET['sort_by'], $allowedSort)
                  ? $conn->real_escape_string($_GET['sort_by'])
                  : 'nama_pelanggan';

// Bangun WHERE clause
$where = [];

if ($keyword !== '') {
    $where[] = "(nama_pelanggan LIKE '%$keyword%' OR keluhan LIKE '%$keyword%')";
}
if ($status_filter !== '' && in_array(strtolower($status_filter), ['belum dikerjakan','di proses','selesai'])) {
    $where[] = "LOWER(status) = '".strtolower($status_filter)."'";
}
if (!empty($akses_pop)) {
    $escaped_pop = array_map([$conn, 'real_escape_string'], $akses_pop);
    $in_clause = "'" . implode("','", $escaped_pop) . "'";
    $where[] = "pop IN ($in_clause)";
}
$whereClause = count($where) ? 'WHERE '.implode(' AND ', $where) : '';

// Pagination settings
$rows_per_page = 5;
$page_tiket    = isset($_GET['page_tiket']) ? max(1, (int)$_GET['page_tiket']) : 1;

// Hitung total baris & halaman
$sqlCount    = "SELECT COUNT(*) AS total FROM tiket_gangguan $whereClause";
$total_rows  = $conn->query($sqlCount)->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_rows / $rows_per_page));
$page_tiket  = min($page_tiket, $total_pages);
$start_row   = ($page_tiket - 1) * $rows_per_page;

// Ambil data tiket sesuai filter dan urutan dengan prioritas 'Belum Dikerjakan' di atas
$sql = "
  SELECT 
    id, nama_pelanggan, alamat, whatsapp, pop, vlan, sn, keluhan, 
    maps_url, teknisi, action, tanggal_dibuat, tanggal_selesai, status
  FROM tiket_gangguan
  $whereClause
  ORDER BY 
    CASE 
      WHEN LOWER(status) = 'belum dikerjakan' THEN 0
      WHEN LOWER(status) = 'di proses' THEN 1
      WHEN LOWER(status) = 'selesai' THEN 2
      ELSE 3
    END ASC,
    $sort_by ASC
  LIMIT $start_row, $rows_per_page
";
$result = $conn->query($sql);

// Fungsi badge status
function getStatusBadge($status) {
    switch (strtolower($status)) {
        case 'belum dikerjakan':
            return '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Belum Dikerjakan</span>';
        case 'di proses':
            return '<span class="badge bg-warning text-dark"><i class="fas fa-circle-notch fa-spin me-1"></i>Sedang Diproses</span>';
        case 'selesai':
            return '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Selesai</span>';
        default:
            return '<span class="badge bg-secondary"><i class="fas fa-question-circle me-1"></i>Tidak Diketahui</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Data Gangguan (Tiket)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet" />
  <style>
    .accordion-button:not(.collapsed) { background-color: #007bff; color: #fff; }
    .modal-dialog { max-width: 800px; }
  </style>
</head>
<body>
<div class="container py-4">
  <h2 class="text-center mb-4">Data Gangguan (Tiket)</h2>

  <!-- Form Cari, Filter Status & Sort -->
  <form method="GET" class="mb-4">
    <div class="row g-2">
      <div class="col-md-4">
        <input
          type="text"
          name="cari"
          class="form-control"
          placeholder="Cari tiket..."
          value="<?= htmlspecialchars($keyword) ?>"
        />
      </div>
      <div class="col-md-3">
        <select name="status_filter" class="form-select">
          <option value="" <?= $status_filter==''?'selected':'' ?>>Semua Status</option>
          <option value="Belum Dikerjakan" <?= $status_filter=='Belum Dikerjakan'?'selected':'' ?>>Belum Dikerjakan</option>
          <option value="Di Proses"       <?= $status_filter=='Di Proses'?'selected':'' ?>>Sedang Diproses</option>
          <option value="Selesai"         <?= $status_filter=='Selesai'?'selected':'' ?>>Selesai</option>
        </select>
      </div>
      <div class="col-md-3">
        <select name="sort_by" class="form-select">
          <option value="nama_pelanggan" <?= $sort_by=='nama_pelanggan'?'selected':'' ?>>Urutkan: Nama Pelanggan</option>
          <option value="status"         <?= $sort_by=='status'?'selected':'' ?>>Urutkan: Status</option>
        </select>
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100">Terapkan</button>
      </div>
    </div>
  </form>

  <!-- Accordion Tiket -->
  <div class="accordion" id="accordionTiket">
    <?php if ($result->num_rows): ?>
      <?php $i = 0; while($row = $result->fetch_assoc()): $i++; ?>
        <div class="accordion-item">
          <h2 class="accordion-header" id="heading<?= $i ?>">
            <button
              class="accordion-button collapsed"
              type="button"
              data-bs-toggle="collapse"
              data-bs-target="#collapse<?= $i ?>"
              aria-expanded="false"
              aria-controls="collapse<?= $i ?>"
            >
              <?= getStatusBadge($row['status']) ?> &nbsp;
              ID: <?= $row['id'] ?> – <?= htmlspecialchars($row['nama_pelanggan']) ?>
            </button>
          </h2>
          <div
            id="collapse<?= $i ?>"
            class="accordion-collapse collapse"
            aria-labelledby="heading<?= $i ?>"
            data-bs-parent="#accordionTiket"
          >
            <div class="accordion-body">
              <?php
                $orig = $row['whatsapp'];
                $disp = htmlspecialchars($orig);
                $link = (substr($orig,0,1)=='0') ? '62'.substr($orig,1) : $orig;
              ?>
              <p><strong>Alamat:</strong> <?= htmlspecialchars($row['alamat']) ?></p>
              <p><strong>WhatsApp:</strong>
                <a href="https://wa.me/<?= htmlspecialchars($link) ?>" target="_blank"><?= $disp ?></a>
              </p>
              <p><strong>Keluhan:</strong> <?= htmlspecialchars($row['keluhan']) ?></p>
              <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#detailModal<?= $i ?>">Detail</button>
              <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal<?= $i ?>">Update</button>
            </div>
          </div>
        </div>

        <!-- Modal Detail -->
        <div class="modal fade" id="detailModal<?= $i ?>" tabindex="-1">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">Detail Gangguan ID <?= $row['id'] ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <?php foreach (['id','nama_pelanggan','alamat','pop','vlan','sn','keluhan','maps_url','teknisi','action','tanggal_dibuat','tanggal_selesai'] as $col): ?>
                  <p>
                    <strong><?= ucwords(str_replace('_',' ',$col)) ?>:</strong>
                    <?= $col==='maps_url'
                        ? '<a href="'.htmlspecialchars($row[$col]).'" target="_blank">'.htmlspecialchars($row[$col]).'</a>'
                        : htmlspecialchars($row[$col])
                    ?>
                  </p>
                <?php endforeach; ?>
                <p><strong>Status:</strong> <?= getStatusBadge($row['status']) ?></p>
              </div>
              <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Update -->
        <div class="modal fade" id="updateModal<?= $i ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form action="update_ticket.php" method="POST">
                <div class="modal-header">
                  <h5 class="modal-title">Update Gangguan ID <?= $row['id'] ?></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="id" value="<?= $row['id'] ?>">
                  <div class="mb-3">
                    <label class="form-label">VLAN</label>
                    <input type="text" name="vlan" class="form-control" value="<?= htmlspecialchars($row['vlan']) ?>">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">SN</label>
                    <input type="text" name="sn" class="form-control" value="<?= htmlspecialchars($row['sn']) ?>">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Teknisi</label>
                    <input type="text" name="teknisi" class="form-control" value="<?= htmlspecialchars($row['teknisi']) ?>">
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Action</label>
                    <textarea name="action" class="form-control"><?= htmlspecialchars($row['action']) ?></textarea>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Maps URL</label>
                    <input type="text" name="maps_url" class="form-control" value="<?= htmlspecialchars($row['maps_url']) ?>">
                  </div>
                </div>
                <div class="modal-footer">
          <?php if (strtolower($row['status']) === 'belum dikerjakan'): ?>
            <button type="submit" name="status" value="di proses" class="btn btn-warning">
              <i class="fas fa-circle-notch fa-spin me-1"></i> Di Proses
            </button>
          <?php elseif (strtolower($row['status']) === 'di proses'): ?>
            <button type="submit" name="status" value="selesai" class="btn btn-success">
              <i class="fas fa-check-circle me-1"></i> Selesai
            </button>
          <?php else: ?>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
          <?php endif; ?>
        </div>
              </form>
            </div>
          </div>
        </div>

      <?php endwhile; ?>
    <?php else: ?>
      <p class="text-center">Tidak ada data gangguan ditemukan.</p>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <li class="page-item <?= $page_tiket<=1?'disabled':'' ?>">
        <a class="page-link" href="?page_tiket=<?= $page_tiket-1 ?>&cari=<?= urlencode($keyword) ?>&status_filter=<?= urlencode($status_filter) ?>&sort_by=<?= $sort_by ?>">&laquo;</a>
      </li>
      <?php
        $win   = 2;
        $start = max(1, $page_tiket - $win);
        $end   = min($total_pages, $page_tiket + $win);
        if ($start>1) {
          echo '<li class="page-item"><a class="page-link" href="?page_tiket=1&cari='.urlencode($keyword).'&status_filter='.urlencode($status_filter).'&sort_by='.$sort_by.'">1</a></li>';
          if ($start>2) echo '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
        for ($p=$start; $p<=$end; $p++){
          echo '<li class="page-item '.($p==$page_tiket?'active':'').'">'
             .'<a class="page-link" href="?page_tiket='.$p.'&cari='.urlencode($keyword).'&status_filter='.urlencode($status_filter).'&sort_by='.$sort_by.'">'.$p.'</a>'
             .'</li>';
        }
        if ($end<$total_pages) {
          if ($end<$total_pages-1) echo '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
          echo '<li class="page-item"><a class="page-link" href="?page_tiket='.$total_pages.'&cari='.urlencode($keyword).'&status_filter='.urlencode($status_filter).'&sort_by='.$sort_by.'">'.$total_pages.'</a></li>';
        }
      ?>
      <li class="page-item <?= $page_tiket>=$total_pages?'disabled':'' ?>">
        <a class="page-link" href="?page_tiket=<?= $page_tiket+1 ?>&cari=<?= urlencode($keyword) ?>&status_filter=<?= urlencode($status_filter) ?>&sort_by=<?= $sort_by ?>">&raquo;</a>
      </li>
    </ul>
  </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
