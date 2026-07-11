<?php
require_once __DIR__ . '/config/database.php';
// Konfigurasi database
$servername = "localhost";
$username = "u272457353_kevinsamsung99";
$password = "Admionkevin99";
$database = "u272457353_umumdata";

// Koneksi ke database
$conn = getErpDbConnection();

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$pesan = ""; // Variabel untuk menyimpan pesan status
$id_paket = null;
$nama_paket = "";
$kecepatan = "";
$deskripsi = "";
$harga = "";

// Mendapatkan ID paket dari URL jika ada
if (isset($_GET["edit"])) {
    $id_paket = mysqli_real_escape_string($conn, $_GET["edit"]);

    // Mengambil data paket berdasarkan ID
    $sql_select = "SELECT id_paket, nama_paket, kecepatan, deskripsi, harga FROM jaringan_paket WHERE id_paket = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("i", $id_paket);
    $stmt_select->execute();
    $result_select = $stmt_select->get_result();

    if ($result_select->num_rows == 1) {
        $row = $result_select->fetch_assoc();
        $nama_paket = $row["nama_paket"];
        $kecepatan = $row["kecepatan"];
        $deskripsi = $row["deskripsi"];
        $harga = $row["harga"];
    } else {
        $pesan = "<div class='alert alert-danger'>Data paket tidak ditemukan.</div>";
    }
    $stmt_select->close();
}

// Proses update data jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["id_paket_edit"])) {
    $id_paket_edit = mysqli_real_escape_string($conn, $_POST["id_paket_edit"]);
    $nama_paket_edit = mysqli_real_escape_string($conn, $_POST["nama_paket"]);
    $kecepatan_edit = mysqli_real_escape_string($conn, $_POST["kecepatan"]);
    $deskripsi_edit = mysqli_real_escape_string($conn, $_POST["deskripsi"]);
    $harga_edit = mysqli_real_escape_string($conn, $_POST["harga"]);

    $sql_update = "UPDATE jaringan_paket SET nama_paket=?, kecepatan=?, deskripsi=?, harga=? WHERE id_paket=?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssii", $nama_paket_edit, $kecepatan_edit, $deskripsi_edit, $harga_edit, $id_paket_edit);

    if ($stmt_update->execute()) {
        $pesan = "<div class='alert alert-success'>Data paket berhasil diupdate. <a href='dashpaket.php' class='alert-link'>Kembali ke Dashboard</a></div>";
    } else {
        $pesan = "<div class='alert alert-danger'>Error updating record: " . $stmt_update->error . "</div>";
    }
    $stmt_update->close();
}

include('navbar.php');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Paket Internet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Edit Paket Internet</h2>

    <?php echo $pesan; ?>

    <?php if ($id_paket): ?>
        <div class="card">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0">Form Edit Paket</h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="id_paket_edit" value="<?php echo $id_paket; ?>">
                    <div class="mb-3">
                        <label for="nama_paket" class="form-label">Nama Paket:</label>
                        <input type="text" class="form-control" id="nama_paket" name="nama_paket" value="<?php echo htmlspecialchars($nama_paket); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="kecepatan" class="form-label">Kecepatan:</label>
                        <input type="text" class="form-control" id="kecepatan" name="kecepatan" value="<?php echo htmlspecialchars($kecepatan); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="deskripsi" class="form-label">Deskripsi:</label>
                        <textarea class="form-control" id="deskripsi" name="deskripsi" rows="3"><?php echo htmlspecialchars($deskripsi); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="harga" class="form-label">Harga:</label>
                        <input type="number" class="form-control" id="harga" name="harga" value="<?php echo htmlspecialchars($harga); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-warning">Update Paket</button>
                    <a href="dashpaket.php" class="btn btn-secondary">Batal</a>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>