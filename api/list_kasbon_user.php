<?php
// DEBUG ON (bisa dimatikan kalau sudah stabil)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

include 'koneksi.php'; // SESUAIKAN: sama seperti API lain

function send_json($status, $message, $data = null) {
    echo json_encode([
        'status'  => $status,
        'message' => $message,
        'data'    => $data
    ]);
    exit;
}

// =====================
// BACA BODY JSON
// =====================
$raw = file_get_contents('php://input');
if ($raw === false) {
    send_json('error', 'Gagal membaca body request.', null);
}

$body = json_decode($raw, true);
if ($body === null && json_last_error() !== JSON_ERROR_NONE) {
    send_json('error', 'JSON tidak valid: ' . json_last_error_msg(), null);
}

// Dari Flutter kamu kirim "nama": widget.namaLogin
$namaLogin = trim($body['nama'] ?? '');
if ($namaLogin === '') {
    send_json('error', 'Field "nama" wajib diisi.', null);
}

// =====================
// QUERY KASBON DENGAN JOIN
// =====================
$sql = "
    SELECT 
        kb.tanggal,
        kb.jumlah,
        kb.keperluan,
        kb.status
    FROM kasbon kb
    INNER JOIN karyawan k ON kb.id_karyawan = k.id
    WHERE LOWER(k.nama) = LOWER(?) 
       OR LOWER(k.username) = LOWER(?)
    ORDER BY kb.tanggal DESC, kb.id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_json('error', 'Gagal prepare query kasbon: ' . $conn->error, null);
}

$stmt->bind_param('ss', $namaLogin, $namaLogin);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'tanggal'   => $row['tanggal'],
        'jumlah'    => (int)$row['jumlah'],
        'keperluan' => $row['keperluan'],
        'status'    => $row['status'],
    ];
}

$stmt->close();
$conn->close();

send_json('success', 'Data kasbon ditemukan.', $data);
