<?php
// CATATAN: Database `u272457353_cabut` BELUM dikonsolidasi ke erprealnet
// (belum ada skema/dump-nya). File ini sengaja TIDAK diubah.
// config.php

$DB_HOST = 'localhost'; // biasanya 'localhost', kalau pakai hosting cek dokumentasi
$DB_NAME = 'u272457353_cabut';
$DB_USER = 'u272457353_kevinsamsungcb';
$DB_PASS = 'Admionkevin99';

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    die('Koneksi database gagal: ' . $e->getMessage());
}

session_start();

/**
 * Helper flash message
 */
function redirectWithMessage($url, $type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    header("Location: $url");
    exit;
}

function displayFlashMessage() {
    if (!empty($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type']; // success | danger | warning | info
        $msg  = $_SESSION['flash']['msg'];
        unset($_SESSION['flash']);
        echo "<div class='alert alert-$type alert-dismissible fade show' role='alert'>"
           . htmlspecialchars($msg)
           . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
    }
}
