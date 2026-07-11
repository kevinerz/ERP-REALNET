<?php
require_once "core/auth.php";
cekLogin();
hanyaAdmin();
require_once "core/db.php";

$id = $_GET['id'];
$data = $conn->query("SELECT * FROM modem WHERE id_modem=$id")->fetch_assoc();

include "partials/header.php";

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $serial = $_POST['serial'];
    $model  = $_POST['model'];
    $merk   = $_POST['merk'];
    $lokasi = $_POST['lokasi'];

    $q = $conn->prepare("UPDATE modem SET serial_number=?, model=?, merk=?, lokasi_penyimpanan=? WHERE id_modem=?");
    $q->bind_param("ssssi", $serial,$model,$merk,$lokasi,$id);
    $q->execute();

    header("Location: index.php");
    exit;
}
?>

<div class="container">
<div class="card p-4 shadow-sm">
<h4>Edit Modem</h4>

<form method="POST">
<input class="form-control mb-2" name="serial" value="<?= $data['serial_number'] ?>" required>
<input class="form-control mb-2" name="model" value="<?= $data['model'] ?>" required>
<input class="form-control mb-2" name="merk" value="<?= $data['merk'] ?>" required>
<input class="form-control mb-2" name="lokasi" value="<?= $data['lokasi_penyimpanan'] ?>" required>

<button class="btn btn-success">Simpan</button>
</form>

</div>
</div>

<?php include "partials/footer.php"; ?>
