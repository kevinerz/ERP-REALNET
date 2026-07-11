<?php
require 'config.php';
require 'notify.php'; // helper StarSender: getGroupIdForPop(), sendWaGroupMessage(), format*Message()

// ====== ACTIONS ======
$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'create') {
  // Validasi input
  $allowedPop = ['Rajeg','Mauk','Kemeri'];
  $pop      = $_POST['pop'] ?? 'Rajeg';
  if (!in_array($pop, $allowedPop, true)) $pop = 'Rajeg';

  $nama     = trim($_POST['nama'] ?? '');
  $alamat   = trim($_POST['alamat'] ?? '');
  $wa       = preg_replace('/\s+/', '', $_POST['wa'] ?? '');
  $alasan   = trim($_POST['alasan'] ?? '');
  $sn       = trim($_POST['sn_modem'] ?? '');
  $status   = ($_POST['status'] ?? 'belum selesai') === 'selesai' ? 'selesai' : 'belum selesai';

  if ($nama === '' || $alamat === '' || $wa === '' || $alasan === '' || $sn === '') {
    redirectWithMessage('index.php', 'warning', 'Harap lengkapi semua field.');
  }

  // INSERT
  $stmt = $pdo->prepare("INSERT INTO tickets_cabut_modem (pop, nama, alamat, wa, alasan, sn_modem, status) VALUES (?,?,?,?,?,?,?)");
  $stmt->execute([$pop, $nama, $alamat, $wa, $alasan, $sn, $status]);

  // Ambil data tiket yang baru dibuat
  $idBaru = $pdo->lastInsertId();
  $q = $pdo->prepare("SELECT * FROM tickets_cabut_modem WHERE id=?");
  $q->execute([$idBaru]);
  $rowNew = $q->fetch() ?: [
    'pop'=>$pop, 'nama'=>$nama, 'alamat'=>$alamat, 'wa'=>$wa,
    'alasan'=>$alasan, 'sn_modem'=>$sn, 'status'=>$status, 'created_at'=>date('Y-m-d H:i:s')
  ];

  // Kirim notifikasi WA ke grup berdasarkan POP
  $groupId = getGroupIdForPop($rowNew['pop']);
  if ($groupId) {
    $message = formatNewTicketMessage($rowNew);
    $sendRes = sendWaGroupMessage($groupId, $message);
    // Jika gagal, cukup lanjut tanpa mengganggu flow
    // if (!$sendRes['success']) { error_log('StarSender gagal: '.json_encode($sendRes)); }
  }

  redirectWithMessage('index.php', 'success', 'Tiket berhasil dibuat.');
}

if ($action === 'toggle' && isset($_GET['id'])) {
  $id = (int) $_GET['id'];
  $row = $pdo->prepare("SELECT * FROM tickets_cabut_modem WHERE id=?");
  $row->execute([$id]);
  $data = $row->fetch();

  if ($data) {
    $oldStatus = $data['status'];
    $newStatus = $oldStatus === 'selesai' ? 'belum selesai' : 'selesai';

    $upd = $pdo->prepare("UPDATE tickets_cabut_modem SET status=? WHERE id=?");
    $upd->execute([$newStatus, $id]);

    // Notifikasi perubahan status
    $groupId = getGroupIdForPop($data['pop']);
    if ($groupId) {
      $msg = formatStatusChangeMessage($data, $oldStatus, $newStatus);
      $sendRes = sendWaGroupMessage($groupId, $msg);
      // if (!$sendRes['success']) { error_log('StarSender gagal: '.json_encode($sendRes)); }
    }

    redirectWithMessage('index.php', 'success', 'Status diperbarui: '.$newStatus);
  } else {
    redirectWithMessage('index.php', 'danger', 'Tiket tidak ditemukan.');
  }
}

/*
 * FUNGSI DELETE DIHILANGKAN DARI SINI
 */

// ====== READ + FILTER ======
$keyword    = trim($_GET['q'] ?? '');
$filterPop = $_GET['pop'] ?? '';
$allowedPop = ['Rajeg','Mauk','Kemeri'];

$params = [];
$sql = "SELECT * FROM tickets_cabut_modem WHERE 1=1";

if ($keyword !== '') {
  $sql .= " AND (
    pop LIKE :kw OR
    nama LIKE :kw OR  
    alamat LIKE :kw OR  
    wa LIKE :kw OR  
    alasan LIKE :kw OR  
    sn_modem LIKE :kw OR
    status LIKE :kw
  )";
  $params[':kw'] = "%$keyword%";
}

if ($filterPop !== '' && in_array($filterPop, $allowedPop, true)) {
  $sql .= " AND pop = :pop";
  $params[':pop'] = $filterPop;
}

$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// ====== HELPERS ======
function badgeStatus($s) {
  if ($s === 'selesai') return "<span class='badge bg-success'>selesai</span>";
  return "<span class='badge bg-secondary'>belum selesai</span>";
}
function badgePop($p) {
  $map = ['Rajeg'=>'primary','Mauk'=>'info','Kemeri'=>'warning'];
  $cls = $map[$p] ?? 'secondary';
  return "<span class='badge bg-$cls'>$p</span>";
}
function waLink($wa) {
  $w = preg_replace('/\D+/', '', $wa);
  if (strpos($w, '0') === 0) $w = '62'.substr($w, 1);
  return "https://wa.me/$w";
}
?>
<?php include('navbar.php'); ?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Tiket Cabut Modem</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body { background: #f4f7fb; }
    .container-max { max-width: 1200px; margin: auto; }
    .table-card { background:#fff; box-shadow:0 2px 14px rgba(0,0,0,0.05); border-radius:16px; overflow:hidden; }
    .table thead th {
      background: linear-gradient(90deg,#3498db 0%,#16a085 100%) !important;
      color:#fff !important; border:none;
    }
    .table-bordered td, .table-bordered th { border:1px solid #e5e7eb; }
    .table tbody tr:hover { background:#f1f5f9; }
    .form-control:focus { border-color:#16a085; box-shadow:0 0 0 .12rem rgba(22,160,133,0.2); }
    .btn-success, .btn-success:active, .btn-success:focus {
      background: linear-gradient(90deg,#16a085,#27ae60) !important; border:none;
    }
    .btn-success:hover { filter:brightness(1.1); }
    .floating-btn {
      position:fixed; bottom:30px; right:30px; z-index:1000;
      background:#16a085; color:#fff; border-radius:50%;
      width:56px; height:56px; box-shadow:0 6px 20px rgba(22,160,133,0.2);
      display:flex; align-items:center; justify-content:center;
      font-size:1.7em; border:none; transition:background .2s;
    }
    .floating-btn:hover { background:#1abc9c; }
    @media (max-width: 600px) {
      .container-max { padding: 0 2px; }
      .table { font-size:12px; min-width:1000px; }
      .btn, .badge { font-size:11px; }
      h2 { font-size:20px; }
      .table-responsive { overflow-x:auto !important; -webkit-overflow-scrolling:touch; }
    }
  </style>
</head>
<body>
<div class="container container-max mt-4">

  <?php
  // Tampilkan notifikasi/flash sesuai helper yang tersedia
  if (function_exists('display_notification')) { display_notification(); }
  else { displayFlashMessage(); }
  ?>

  <h2 class="mb-4 text-center fw-bold text-primary">
    <i class="bi bi-tools"></i> Tiket Cabut Modem
  </h2>

  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card shadow-sm" style="border-radius:16px;">
        <div class="card-header fw-semibold" style="background:linear-gradient(90deg,#3498db,#16a085); color:#fff;">
          Tambah Tiket
        </div>
        <div class="card-body">
          <form method="post" action="?action=create">
            <div class="mb-2">
              <label class="form-label">POP</label>
              <select name="pop" class="form-select" required>
                <option value="Rajeg"  <?= ($filterPop==='Rajeg')?'selected':''; ?>>Rajeg</option>
                <option value="Mauk"   <?= ($filterPop==='Mauk')?'selected':''; ?>>Mauk</option>
                <option value="Kemeri" <?= ($filterPop==='Kemeri')?'selected':''; ?>>Kemeri</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label">Nama</label>
              <input type="text" name="nama" class="form-control" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Alamat</label>
              <textarea name="alamat" class="form-control" rows="2" required></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label">No. WhatsApp</label>
              <input type="text" name="wa" class="form-control" placeholder="08xxxxxxxxxx" required>
            </div>
            <div class="mb-2">
              <label class="form-label">Alasan Cabut</label>
              <textarea name="alasan" class="form-control" rows="2" required></textarea>
            </div>
            <div class="mb-2">
              <label class="form-label">SN Modem</label>
              <input type="text" name="sn_modem" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Status</label>
              <select name="status" class="form-select">
                <option value="belum selesai">belum selesai</option>
                <option value="selesai">selesai</option>
              </select>
            </div>
            <button class="btn btn-success w-100">
              <i class="bi bi-save2"></i> Simpan
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card table-card">
        <div class="card-header d-flex align-items-center justify-content-between">
          <form class="d-flex gap-2" method="get" action="">
            <input type="text" class="form-control" name="q" placeholder="Cari nama/WA/SN/alamat/status..." value="<?=htmlspecialchars($keyword)?>">
            <select name="pop" class="form-select">
              <option value="">Semua POP</option>
              <?php foreach ($allowedPop as $opt): ?>
                <option value="<?=$opt?>" <?= $filterPop===$opt?'selected':''?>><?=$opt?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-secondary"><i class="bi bi-search"></i> Cari</button>
            <?php if ($keyword !== '' || $filterPop !== ''): ?>
              <a class="btn btn-outline-dark" href="index.php"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            <?php endif; ?>
          </form>
          <a class="btn btn-sm btn-outline-primary" href="export_csv.php"><i class="bi bi-filetype-csv"></i> Export CSV</a>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle mb-0">
            <thead>
              <tr class="text-center">
                <th>No</th>
                <th>POP</th>
                <th>Nama & Alamat</th>
                <th>WA</th>
                <th>Alasan</th>
                <th>SN Modem</th>
                <th>Status</th>
                <th>Waktu</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php if (!$tickets): ?>
              <tr><td colspan="9" class="text-center text-danger">Belum ada data</td></tr>
            <?php else: foreach ($tickets as $i => $t): ?>
              <tr>
                <td class="text-center"><?= $i+1 ?></td>
                <td class="text-center"><?= badgePop($t['pop']) ?></td>
                <td>
                  <div class="fw-semibold"><?= htmlspecialchars($t['nama']) ?></div>
                  <div class="small text-muted"><?= nl2br(htmlspecialchars($t['alamat'])) ?></div>
                </td>
                <td>
                  <a class="text-decoration-none" target="_blank" href="<?= waLink($t['wa']) ?>">
                    <i class="bi bi-whatsapp"></i> <?= htmlspecialchars($t['wa']) ?>
                  </a>
                </td>
                <td class="small"><?= nl2br(htmlspecialchars($t['alasan'])) ?></td>
                <td><code><?= htmlspecialchars($t['sn_modem']) ?></code></td>
                <td><?= badgeStatus($t['status']) ?></td>
                <td class="small text-muted"><?= htmlspecialchars($t['created_at']) ?></td>
                <td class="text-nowrap">
                  <div class="btn-group btn-group-sm">
                    <a class="btn btn-outline-success" href="?action=toggle&id=<?=$t['id']?>"
                      onclick="return confirm('Ubah status tiket ini?');">
                      <i class="bi bi-arrow-repeat"></i> Toggle
                    </a>
                    </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <a href="dashboard.php" class="floating-btn" title="Kembali ke Dashboard">
    <i class="bi bi-arrow-left"></i>
  </a>

  <div class="text-center mt-4 mb-2">
    <small class="text-muted">© <?= date('Y') ?> PT. Real Data Solusindo</small>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>