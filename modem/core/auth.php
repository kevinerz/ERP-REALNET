<?php
session_start();

function cekLogin() {
    if (!isset($_SESSION['id_karyawan'])) {
        header("Location: ../login.php");
        exit;
    }
}

function hanyaTeknisi() {
    if ($_SESSION['divisi'] !== 'Teknisi') {
        die("Akses ditolak. Hanya teknisi.");
    }
}
?>
