<?php
require_once "core/auth.php";
cekLogin();
hanyaAdmin();

require_once "core/db.php";

$id = $_GET['id'];
$conn->query("DELETE FROM jaringan_modem WHERE id_modem=$id");

header("Location: index.php");
exit;
