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

// Ambil ID aset dari parameter URL
$id = $_GET['edit'];

// Ambil data aset dari database
$sql = "SELECT * FROM aset_master WHERE ID_Aset = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

// Proses update aset jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama_aset = $_POST['nama_aset'];
    $jenis_aset = $_POST['jenis_aset'];
    $merk = $_POST['merk'];
    $model = $_POST['model'];
    $serial_number = $_POST['serial_number'];
    $tanggal_pembelian = $_POST['tanggal_pembelian'];
    $harga_pembelian = $_POST['harga_pembelian'];
    $kondisi = $_POST['kondisi'];
    $lokasi = $_POST['lokasi'];

    $sql = "UPDATE aset_master SET Nama_Aset = ?, Jenis_Aset = ?, Merk = ?, Model = ?, Serial_Number = ?, Tanggal_Pembelian = ?, Harga_Pembelian = ?, Kondisi = ?, Lokasi = ? WHERE ID_Aset = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssdssi", $nama_aset, $jenis_aset, $merk, $model, $serial_number, $tanggal_pembelian, $harga_pembelian, $kondisi, $lokasi, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Data aset berhasil diupdate!'); window.location.href='dashaset.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    // Direktori untuk menyimpan foto
$target_dir = "uploads/";

// ... koneksi database dan ambil data aset ...

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ... ambil data lainnya ...

    // Upload foto (jika ada file baru)
    if (!empty($_FILES["foto"]["name"])) {
        $target_file = $target_dir . basename($_FILES["foto"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // ... (validasi upload, sama seperti di tambah_aset.php) ...

        if ($uploadOk == 1) {
            if (move_uploaded_file($_FILES["foto"]["tmp_name"], $target_file)) {
                $foto = htmlspecialchars(basename($_FILES["foto"]["name"]));
            } else {
                $foto = $row['Foto']; // Gunakan foto lama jika upload gagal
            }
        } else {
            $foto = $row['Foto']; // Gunakan foto lama jika upload gagal karena validasi
        }
    } else {
        $foto = $row['Foto']; // Gunakan foto lama jika tidak ada file baru
    }

    // ... kueri UPDATE (termasuk kolom Foto) ...
    $sql = "UPDATE aset_master SET Nama_Aset = ?, Jenis_Aset = ?, Merk = ?, Model = ?, Serial_Number = ?, Tanggal_Pembelian = ?, Harga_Pembelian = ?, Kondisi = ?, Lokasi = ?, Foto = ? WHERE ID_Aset = ?";

    // ... prepared statement ...
    $stmt->bind_param("ssssssdssi", $nama_aset, $jenis_aset, $merk, $model, $serial_number, $tanggal_pembelian, $harga_pembelian, $kondisi, $lokasi, $foto, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Data aset berhasil diupdate!'); window.location.href='dashaset.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
    $stmt->close();
}

// ... form HTML (tambahkan input file dan tampilkan foto lama) ...
    $stmt->close();
}

// Include the navbar
include('navbar.php');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Aset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <h2 class="text-center mb-4">Edit Aset</h2>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . "?edit=" . $id; ?>" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="nama_aset" class="form-label">Nama Aset</label>
            <input type="text" class="form-control" id="nama_aset" name="nama_aset" value="<?= htmlspecialchars($row['Nama_Aset']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="jenis_aset" class="form-label">Jenis Aset</label>
            <input type="text" class="form-control" id="jenis_aset" name="jenis_aset" value="<?= htmlspecialchars($row['Jenis_Aset']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="merk" class="form-label">Merk</label>
            <input type="text" class="form-control" id="merk" name="merk" value="<?= htmlspecialchars($row['Merk']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="model" class="form-label">Model</label>
            <input type="text" class="form-control" id="model" name="model" value="<?= htmlspecialchars($row['Model']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="serial_number" class="form-label">Serial Number</label>
            <input type="text" class="form-control" id="serial_number" name="serial_number" value="<?= htmlspecialchars($row['Serial_Number']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="tanggal_pembelian" class="form-label">Tanggal Pembelian</label>
            <input type="date" class="form-control" id="tanggal_pembelian" name="tanggal_pembelian" value="<?= htmlspecialchars($row['Tanggal_Pembelian']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="harga_pembelian" class="form-label">Harga Pembelian</label>
            <input type="number" class="form-control" id="harga_pembelian" name="harga_pembelian" step="0.01" value="<?= htmlspecialchars($row['Harga_Pembelian']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="kondisi" class="form-label">Kondisi</label>
            <input type="text" class="form-control" id="kondisi" name="kondisi" value="<?= htmlspecialchars($row['Kondisi']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="lokasi" class="form-label">Lokasi</label>
            <input type="text" class="form-control" id="lokasi" name="lokasi" value="<?= htmlspecialchars($row['Lokasi']); ?>" required>
        </div>
        <div class="mb-3">
            <label for="foto" class="form-label">Foto Aset</label>
            <input type="file" class="form-control" id="foto" name="foto">
            <?php if (!empty($row['Foto'])): ?>
                <img src="uploads/<?= htmlspecialchars($row['Foto']); ?>" alt="Foto Aset" style="max-width: 200px; margin-top: 10px;">
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="dashaset.php" class="btn btn-secondary">Batal</a>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>