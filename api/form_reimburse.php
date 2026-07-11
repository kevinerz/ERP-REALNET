<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');
date_default_timezone_set('Asia/Jakarta');

$conn = new mysqli("localhost", "u272457353_kevinsamsung99", "Admionkevin99", "u272457353_umumdata");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak diizinkan. Gunakan POST.']);
    exit;
}

$required_fields = ['nama_pengaju', 'tanggal', 'tujuan', 'liter', 'total'];
foreach ($required_fields as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field wajib '$field' tidak boleh kosong."]);
        exit;
    }
}

if (isset($_FILES['nota']) && $_FILES['nota']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    $fileType = mime_content_type($_FILES['nota']['tmp_name']);

    if (!in_array($fileType, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung. Hanya JPG, JPEG, atau PNG.']);
        exit;
    }

    $uploadDir = '../keuangan/uploads/nota/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = uniqid('nota_') . '_' . basename($_FILES['nota']['name']);
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['nota']['tmp_name'], $filePath)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal mengupload file nota.']);
        exit;
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File nota wajib diunggah.']);
    exit;
}

// Ambil data dari form
$nama_pengaju = $_POST['nama_pengaju'];
$tgl          = $_POST['tanggal'];
$tujuan       = $_POST['tujuan'];
$liter        = (float) $_POST['liter'];
$total        = (float) $_POST['total'];
$catatan      = isset($_POST['catatan']) ? $_POST['catatan'] : '';

// =======================================================================
// ### PERUBAHAN DI SINI ###
// Menghapus 'divisi' dan 'status' dari query INSERT
$stmt = $conn->prepare("INSERT INTO reimburse_bbm (nama_pengaju, tanggal, tujuan, liter, total, catatan, foto_nota) VALUES (?, ?, ?, ?, ?, ?, ?)");

// Menyesuaikan bind_param dengan 7 parameter (s=string, d=double)
$stmt->bind_param("sssddss", $nama_pengaju, $tgl, $tujuan, $liter, $total, $catatan, $filePath);
// =======================================================================

if ($stmt->execute()) {
    http_response_code(201); // Created
    echo json_encode(['status' => 'success', 'message' => 'Reimburse berhasil diajukan.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan data: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>