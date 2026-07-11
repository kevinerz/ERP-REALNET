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
// Include the navbar
include('navbar.php');
// Fungsi untuk menambah POP baru
if (isset($_POST['add_pop'])) {
    $pop_name = $_POST['pop_name'];
    $query = "INSERT INTO jaringan_pop (name) VALUES ('$pop_name')";
    $conn->query($query);
    header("Location: pop.php");
}

// Fungsi untuk menghapus POP
if (isset($_GET['delete'])) {
    $pop_id = $_GET['delete'];
    $query = "DELETE FROM jaringan_pop WHERE id = $pop_id";
    $conn->query($query);
    header("Location: pop.php");
}

// Ambil semua POP
$query = "SELECT * FROM jaringan_pop ORDER BY name ASC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen POP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>



<div class="container mt-4">
    <h2 class="text-center mb-4">Manajemen POP</h2>

    <!-- Form Tambah POP -->
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label for="pop_name" class="form-label">Nama POP</label>
            <input type="text" class="form-control" id="pop_name" name="pop_name" required>
        </div>
        <button type="submit" name="add_pop" class="btn btn-primary">Tambah POP</button>
    </form>

    <!-- Daftar POP -->
    <h4>Daftar POP</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>No</th>
                <th>Nama POP</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++; ?></td>
                    <td><?= htmlspecialchars($row['name']); ?></td>
                    <td>
                        <a href="edit_pop.php?id=<?= $row['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="pop.php?delete=<?= $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Apakah Anda yakin ingin menghapus POP ini?')">Hapus</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
