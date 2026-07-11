<?php
// 1. Selalu mulai session terlebih dahulu
session_start();

// 2. Hapus semua variabel session yang ada
// Ini akan menghapus data seperti 'loggedin', 'user_id', 'nama', 'username', dan 'divisi' dari memori
session_unset();

// 3. Hancurkan session itu sendiri secara permanen
// Ini akan menghapus file session di server
session_destroy();

// 4. Arahkan pengguna kembali ke halaman login
// Pengguna tidak akan bisa kembali ke halaman sebelumnya (yang terproteksi) dengan tombol "Back" di browser
header('Location: login.php');

// 5. Pastikan tidak ada kode lain yang dieksekusi setelah pengalihan
exit;
?>