<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Inventaris Modem</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { background:#f4f6f9; }
.card-shadow { box-shadow:0 4px 15px rgba(0,0,0,0.08); }
.navbar-brand { font-weight:700; color:#16a085 !important; }
</style>
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php">
      <i class="bi bi-hdd-network"></i> Inventaris Modem
    </a>

    <div>
      <span class="me-3 fw-semibold"><?= $_SESSION['nama'] ?? '' ?></span>
      <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
    </div>
  </div>
</nav>
