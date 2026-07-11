<?php
// config.php
//
// CATATAN (update): tabel tickets_cabut_modem sudah dipindahkan ke database
// utama (erprealnet/u272457353_erprealnetku) supaya modul CABUT di Next.js
// dan halaman PHP ini sama-sama baca/tulis data yang sama secara live.
// Database lama u272457353_cabut TIDAK dipakai lagi oleh file ini (data
// lamanya sengaja tidak dimigrasikan -- mulai dari kosong di database baru).
//
// Koneksi sekarang lewat helper terpusat getErpDbPdo() (lihat
// config/database.php), bukan kredensial hardcoded ke database terpisah
// seperti sebelumnya.
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getErpDbPdo();
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
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
