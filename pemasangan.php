<?php
session_start();

if (
    !isset($_SESSION['username']) ||
    !in_array($_SESSION['divisi'], ['Admin', 'SPV Teknis'])
) {
    $_SESSION['notif'] = 'Akses hanya untuk Admin & SPV Teknis!';
    header("Location: dashboard.php");
    exit;
}

if (isset($_SESSION['notif'])) {
    echo '<div class="alert alert-danger text-center mb-0 rounded-0">'.htmlspecialchars($_SESSION['notif']).'</div>';
    unset($_SESSION['notif']);
}

// DB koneksi
$conn_pemasangan = new mysqli('localhost', 'u272457353_kevinsamsung9', 'Admionkevin99', 'u272457353_db_pemasangan');
if ($conn_pemasangan->connect_error) die("Koneksi gagal: " . $conn_pemasangan->connect_error);

$conn_umum = new mysqli('localhost', 'u272457353_kevinsamsung99', 'Admionkevin99', 'u272457353_umumdata');
if ($conn_umum->connect_error) die("Koneksi gagal: " . $conn_umum->connect_error);

$paket_array = [];
$res_paket = $conn_umum->query("SELECT * FROM paket ORDER BY id_paket ASC");
while ($row_paket = $res_paket->fetch_assoc()) {
    $paket_array[$row_paket['id_paket']] = $row_paket;
}

// Proses aktivasi dari modal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_aktivasi'])) {
    $id      = $_POST['id_aktivasi'];
    $userppp = trim($_POST['userppp']);
    $passwordppp = trim($_POST['passwordppp']);
    $vlan    = trim($_POST['vlan']);
    $paket   = intval($_POST['paket']);
    $status  = "aktivasi";
    $last_updated_by = $_SESSION['username'];

    $stmt = $conn_pemasangan->prepare("UPDATE pemasangan SET userppp=?, passwordppp=?, vlan=?, paket=?, status=?, last_updated_by=? WHERE id=?");
    $stmt->bind_param("ssssssi", $userppp, $passwordppp, $vlan, $paket, $status, $last_updated_by, $id);
    $stmt->execute();
    $msg = $stmt->affected_rows > 0 ? "Pelanggan berhasil diaktivasi!" : "Aktivasi gagal.";
}

$sql = "SELECT * FROM pemasangan WHERE status='belum diproses' ORDER BY tanggal DESC";
$result = $conn_pemasangan->query($sql);

include('navbar.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Aktivasi Pelanggan (Admin)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap 5.3 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7fb; }
        .container-max { max-width: 1200px; margin: auto; }
        .table-card { background: #fff; box-shadow: 0 2px 14px #0001; border-radius: 16px; overflow: hidden; }
        .table thead th { background: linear-gradient(90deg,#3498db 0%,#16a085 100%) !important; color: #fff !important; border: none; }
        .table-bordered td, .table-bordered th { border: 1px solid #e5e7eb; }
        .table tbody tr:hover { background: #f1f5f9; }
        .form-control:focus { border-color: #16a085; box-shadow: 0 0 0 .12rem #16a08533; }
        .btn-success, .btn-success:active, .btn-success:focus { background: linear-gradient(90deg,#16a085,#27ae60) !important; border: none; }
        .btn-success:hover { filter: brightness(1.1); }
        .alert-info { background: #eafaf1; color: #27ae60; border: 1px solid #d1fae5; }
        .alert-danger { background: #ffeaea; color: #e74c3c; border: 1px solid #f9cacb; }
        .floating-btn { position: fixed; bottom: 30px; right: 30px; z-index: 1000; background: #16a085; color: #fff; border-radius: 50%; width: 56px; height: 56px; box-shadow: 0 6px 20px #16a08533; display: flex; align-items: center; justify-content: center; font-size: 1.7em; border: none; transition: background .2s;}
        .floating-btn:hover { background: #1abc9c; }
        @media (max-width: 600px) {
            .container-max { padding: 0 2px;}
            .table { font-size: 12px; min-width: 900px; }
            .btn, .badge { font-size: 11px;}
            h2 { font-size: 20px;}
            .table-responsive { overflow-x: auto !important; -webkit-overflow-scrolling: touch;}
        }
    </style>
</head>
<body>
<div class="container container-max mt-4">
    <h2 class="mb-4 text-center fw-bold text-primary"><i class="bi bi-lightning-charge-fill"></i> Aktivasi Pelanggan</h2>
    <?php if (isset($msg)): ?>
        <div class="alert alert-info text-center mb-4 shadow-sm"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <div class="table-responsive table-card">
        <table class="table table-bordered align-middle mb-0">
            <thead>
                <tr class="text-center">
                    <th>No</th>
                    <th>POP</th>
                    <th>Nama</th>
                    <th>Paket</th>
                    <th>KTP</th>
                    <th>Alamat</th>
                    <th>No Telp</th>
                    <th>Email</th>
                    <th>Tanggal</th>
                    <th>Aktivasi</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows == 0): ?>
                <tr><td colspan="10" class="text-center text-danger">Belum ada data untuk diaktivasi</td></tr>
            <?php else: $no = 1; while($row = $result->fetch_assoc()): ?>
                <tr>
                    <td class="text-center"><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['pop']) ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td>
                        <?php
                        $idpaket = intval($row['paket']);
                        if(isset($paket_array[$idpaket])){
                            $p = $paket_array[$idpaket];
                            echo "<span class='fw-bold text-success'>".htmlspecialchars($p['nama_paket'])."</span> <small>(".htmlspecialchars($p['kecepatan']).")</small><br><span class='badge bg-info text-dark'>Rp ".number_format($p['harga'],0,',','.')."</span>";
                        } else {
                            echo "<span class='text-danger'>-</span>";
                        }
                        ?>
                    </td>
                    <td><?= htmlspecialchars($row['ktp']) ?></td>
                    <td><?= htmlspecialchars($row['alamat']) ?></td>
                    <td><?= htmlspecialchars($row['telp']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                    <td class="text-center">
                        <button class="btn btn-success btn-sm" 
                                data-bs-toggle="modal" 
                                data-bs-target="#modalAktivasi<?= $row['id'] ?>">
                            <i class="bi bi-person-check"></i> Aktivasi
                        </button>
                        <!-- Modal -->
                        <div class="modal fade" id="modalAktivasi<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $row['id'] ?>" aria-hidden="true">
                          <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                              <form method="post">
                                <input type="hidden" name="id_aktivasi" value="<?= $row['id'] ?>">
                                <div class="modal-header">
                                  <h5 class="modal-title" id="modalLabel<?= $row['id'] ?>"><i class="bi bi-person-check"></i> Aktivasi Pelanggan</h5>
                                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                  <div class="mb-2">
                                    <label class="form-label">Paket</label>
                                    <select name="paket" class="form-control" required>
                                        <option value="">Pilih Paket</option>
                                        <?php foreach($paket_array as $paket): ?>
                                            <option value="<?= $paket['id_paket'] ?>" <?= ($paket['id_paket']==$row['paket']?'selected':'') ?>>
                                                <?= htmlspecialchars($paket['nama_paket']) ?> (<?= htmlspecialchars($paket['kecepatan']) ?>) - Rp <?= number_format($paket['harga'],0,',','.') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                  </div>
                                  <div class="mb-2">
                                    <label class="form-label">Username PPPoE</label>
                                    <input type="text" name="userppp" class="form-control" placeholder="Username PPPoE" required>
                                  </div>
                                  <div class="mb-2">
                                    <label class="form-label">Password PPPoE</label>
                                    <input type="text" name="passwordppp" class="form-control" placeholder="Password PPPoE" required>
                                  </div>
                                  <div class="mb-2">
                                    <label class="form-label">VLAN</label>
                                    <input type="text" name="vlan" class="form-control" placeholder="VLAN" required>
                                  </div>
                                </div>
                                <div class="modal-footer">
                                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                  <button type="submit" class="btn btn-success"><i class="bi bi-check2-circle"></i> Simpan & Aktivasi</button>
                                </div>
                              </form>
                            </div>
                          </div>
                        </div>
                    </td>
                </tr>
            <?php endwhile; endif; ?>
            </tbody>
        </table>
    </div>
    <a href="dashboard.php" class="floating-btn" title="Kembali ke Dashboard"><i class="bi bi-arrow-left"></i></a>
    <div class="text-center mt-4 mb-2">
        <small class="text-muted">© <?= date('Y') ?> PT. Real Data Solusindo</small>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn_pemasangan->close();
$conn_umum->close();
?>
