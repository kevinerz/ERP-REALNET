<?php
require_once "core/auth.php";
cekLogin();
hanyaAdmin();
require_once "core/db.php";
include "partials/header.php";

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $serial = $_POST['serial'];
    $model  = $_POST['model'];
    $merk   = $_POST['merk'];
    $lokasi = $_POST['lokasi'];

    $q = $conn->prepare("INSERT INTO modem(serial_number,model,merk,status,tanggal_masuk,lokasi_penyimpanan) VALUES(?,?,?,'Tersedia',NOW(),?)");
    $q->bind_param("ssss", $serial,$model,$merk,$lokasi);
    $q->execute();

    header("Location: index.php");
    exit;
}
?>

<div class="container">
<div class="card p-4 shadow-sm">
<h4>Tambah Modem</h4>

<form method="POST">
<input class="form-control mb-2" name="serial" placeholder="Serial Number" required>
<input class="form-control mb-2" name="model" placeholder="Model" required>
<input class="form-control mb-2" name="merk" placeholder="Merk" required>
<input class="form-control mb-2" name="lokasi" placeholder="Lokasi Penyimpanan" required>

<button class="btn btn-success">Simpan</button>
</form>

</div>
</div>

<?php include "partials/footer.php"; ?>
