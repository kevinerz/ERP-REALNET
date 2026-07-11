<?php
require_once "core/auth.php";
cekLogin();
hanyaAdmin();
require_once "core/db.php";

include "partials/header.php";
$data = $conn->query("SELECT l.*, m.serial_number, k.nama FROM modem_log l
LEFT JOIN modem m ON l.id_modem=m.id_modem
LEFT JOIN karyawan k ON l.id_karyawan=k.id
ORDER BY l.id_log DESC");
?>

<div class="container">
<div class="card p-4 shadow-sm">
<h4 class="mb-3">Riwayat Aktivitas Modem</h4>

<table class="table table-striped">
<thead>
<tr>
    <th>Waktu</th>
    <th>Modem</th>
    <th>Teknisi</th>
    <th>Aksi</th>
    <th>Lokasi Tujuan</th>
</tr>
</thead>

<tbody>
<?php while($r = $data->fetch_assoc()): ?>
<tr>
  <td><?= $r['waktu'] ?></td>
  <td><?= $r['serial_number'] ?></td>
  <td><?= $r['nama'] ?></td>
  <td><?= $r['aksi'] ?></td>
  <td><?= $r['lokasi_tujuan'] ?></td>
</tr>
<?php endwhile ?>
</tbody>

</table>
</div>
</div>

<?php include "partials/footer.php"; ?>
