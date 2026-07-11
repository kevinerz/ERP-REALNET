<?php
session_start();
session_unset();  // Hapus semua session variables
session_destroy(); // Hancurkan session
header("Location: login.php"); // Redirect ke halaman login
exit();
?>
