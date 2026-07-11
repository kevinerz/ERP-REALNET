<?php
require_once "core/auth.php";
cekLogin();
hanyaTeknisi();

require_once "core/db.php";
require_once "core/modem.php";

$id = $_GET['id'];

$lokasi = "Gofur"; // Default, bisa diganti dropdown UI

kembalikanModem($id, $_SESSION['id_karyawan'], $lokasi);

header("Location: index.php");
exit;
