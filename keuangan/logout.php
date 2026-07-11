<?php
session_start();
session_destroy(); // Hapus semua session
header("Location: login.php"); // Arahkan ke halaman login
exit;
