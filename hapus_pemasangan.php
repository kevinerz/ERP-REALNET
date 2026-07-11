<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    
    if (!empty($id)) {
        $sql = "DELETE FROM pelanggan_instalasi WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Data pemasangan berhasil dihapus.</div>";
        } else {
            echo "<div class='alert alert-danger'>Gagal menghapus data.</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>ID tidak valid.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hapus Pemasangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">
    <div class="card p-4 shadow-sm">
        <h2 class="mb-4">Hapus Data Pemasangan</h2>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">ID Pemasangan</label>
                <input type="number" name="id" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-danger">Hapus</button>
        </form>
    </div>
</body>
</html>
