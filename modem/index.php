<?php
require_once "core/auth.php";
cekLogin();
require_once "core/db.php";
include "partials/header.php";

// Statistik
$stat = [];
$q = $conn->query("SELECT lokasi_penyimpanan, COUNT(*) AS total FROM modem GROUP BY lokasi_penyimpanan");
while($r = $q->fetch_assoc()) $stat[] = $r;

// Data modem
$data = $conn->query("SELECT * FROM modem ORDER BY id_modem DESC");
?>

<div class="container">
<h3 class="fw-bold mb-4"><i class="bi bi-router"></i> Inventaris Modem</h3>

<div class="row mb-4">
<?php foreach($stat as $s): ?>
<div class="col-md-4">
    <div class="p-3 bg-white border-start border-4 border-success rounded card-shadow">
        <div class="text-secondary fw-semibold"><?= $s['lokasi_penyimpanan'] ?></div>
        <div class="fs-3 fw-bold"><?= $s['total'] ?></div>
    </div>
</div>
<?php endforeach ?>
</div>

<div class="card card-shadow">
<div class="card-header bg-success text-white">
    <div class="d-flex justify-content-between">
        <span class="fw-semibold"><i class="bi bi-list-ul"></i> Daftar Modem</span>
        <a href="tambah.php" class="btn btn-light btn-sm"><i class="bi bi-plus"></i> Tambah</a>
    </div>
</div>

<div class="card-body table-responsive">
<table class="table table-hover">
<thead>
<tr class="text-center">
    <th>Serial</th>
    <th>Model</th>
    <th>Merk</th>
    <th>Status</th>
    <th>Lokasi</th>
    <th>Teknisi</th>
    <th>Aksi</th>
</tr>
</thead>

<tbody>
<?php while($m = $data->fetch_assoc()): ?>
<tr>
  <td><?= $m['serial_number'] ?></td>
  <td><?= $m['model'] ?></td>
  <td><?= $m['merk'] ?></td>
  <td>
    <?php if($m['status']=='Diambil'): ?>
      <span class="badge bg-danger">Diambil</span>
    <?php else: ?>
      <span class="badge bg-success">Tersedia</span>
    <?php endif; ?>
  </td>
  <td><?= $m['lokasi_penyimpanan'] ?></td>
  <td>
    <?php
    if($m['id_karyawan_keluar']){
        $id = $m['id_karyawan_keluar'];
        $u = $conn->query("SELECT nama FROM karyawan WHERE id=$id")->fetch_assoc();
        echo "<b>".$u['nama']."</b>";
    } else echo "-";
    ?>
  </td>
  <td class="text-center">
    <a href="edit.php?id=<?= $m['id_modem'] ?>" class="btn btn-warning btn-sm">Edit</a>
    <a href="hapus.php?id=<?= $m['id_modem'] ?>" onclick="return confirm('Hapus?')" class="btn btn-danger btn-sm">Hapus</a>

    <?php if($_SESSION['divisi']=="Teknisi" && $m['status']=="Tersedia"): ?>
      <a href="ambil.php?id=<?= $m['id_modem'] ?>" class="btn btn-primary btn-sm">Ambil</a>

    <?php elseif($_SESSION['divisi']=="Teknisi" && $m['status']=="Diambil" && $m['id_karyawan_keluar']==$_SESSION['id_karyawan']): ?>
      <a href="kembalikan.php?id=<?= $m['id_modem'] ?>" class="btn btn-success btn-sm">Kembalikan</a>
    <?php endif; ?>
  </td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

</div>

<?php include "partials/footer.php"; ?>
