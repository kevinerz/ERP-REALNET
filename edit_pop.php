<?php
require_once __DIR__ . '/config/database.php';
// Konfigurasi database
$servername = "localhost";
$username = "u272457353_kevinsamsung9";
$password = "Admionkevin99";
$database = "u272457353_db_pemasangan";

// Koneksi ke database
$conn = getErpDbConnection();

// Periksa koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Ambil data POP berdasarkan ID
if (isset($_GET['id'])) {
    $pop_id = $_GET['id'];
    $query = "SELECT * FROM jaringan_pop WHERE id = $pop_id";
    $result = $conn->query($query);
    $pop = $result->fetch_assoc();
}

// Proses perubahan nama POP
if (isset($_POST['edit_pop'])) {
    $pop_name = $_POST['pop_name'];
    $query = "UPDATE jaringan_pop SET name = '$pop_name' WHERE id = $pop_id";
    $conn->query($query);
    header("Location: pop.php");
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit POP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Pemasangan</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="pop.php">Manajemen POP</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Laporan</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="#">Kontak</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <h2 class="text-center mb-4">Edit POP</h2>

    <!-- Form Edit POP -->
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label for="pop_name" class="form-label">Nama POP</label>
            <input type="text" class="form-control" id="pop_name" name="pop_name" value="<?= htmlspecialchars($pop['name']); ?>" required>
        </div>
        <button type="submit" name="edit_pop" class="btn btn-success">Simpan Perubahan</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
