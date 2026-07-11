<?php
require_once __DIR__ . '/../config/database.php';
$conn = getErpDbConnection();
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Ambil data dari form
$nama   = $_POST['nama_pengaju'];
$tgl    = $_POST['tanggal'];
$tujuan = $_POST['tujuan'];
$liter  = $_POST['liter'];
$total  = $_POST['total'];
$catatan= $_POST['catatan'];

// Validasi dan upload file nota
if (isset($_FILES['nota']) && $_FILES['nota']['error'] === 0) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $fileType = mime_content_type($_FILES['nota']['tmp_name']);

    if (!in_array($fileType, $allowedTypes)) {
        die("Format file tidak didukung. Hanya JPG atau PNG.");
    }

    $uploadDir = 'uploads/nota/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = uniqid('nota_') . '_' . basename($_FILES['nota']['name']);
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['nota']['tmp_name'], $filePath)) {
        die("Gagal upload file nota.");
    }
} else {
    die("File nota wajib diunggah.");
}

// Simpan ke database
$stmt = $conn->prepare("INSERT INTO keu_reimburse_bbm (nama_pengaju, tanggal, tujuan, liter, total, catatan, foto_nota) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssddss", $nama, $tgl, $tujuan, $liter, $total, $catatan, $filePath);

if ($stmt->execute()) {
    echo "✅ Berhasil dikirim. <a href='form_reimburse.php'>Ajukan lagi</a>";
} else {
    echo "❌ Gagal menyimpan: " . $conn->error;
}
?>
