<?php
require 'db_config.php';

// ==========================================================
// DEBUG SEMENTARA (boleh dimatikan kalau sudah yakin)
// ==========================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==========================================================
// HEADER RESPONSE JSON + CORS SEDERHANA
// ==========================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// ==========================================================
// AMBIL PARAMETER username
// ==========================================================
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if ($username === '') {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Parameter username wajib diisi."
    ]);
    exit;
}

// ==========================================================
// KONEKSI DATABASE
// ==========================================================
$conn_umum = get_conn_umum();
if (!$conn_umum || $conn_umum->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Koneksi database gagal."
    ]);
    exit;
}

$conn_umum->set_charset('utf8mb4');

$data = [
    'odp'      => [],
    'modem'    => [],
    'dropcore' => [],
    'teknisi'  => []
];

// ==========================================================
// MAPPING: username -> nama karyawan
// Dipakai untuk filter modem:
//   status             = 'dibawa'
//   lokasi_penyimpanan = username
//       ATAU
//   lokasi_penyimpanan = nama_karyawan
// Contoh: username 'alvariz' -> nama 'Wahyu Hidayat'
// ==========================================================
$lokasiUser = $username;
$lokasiNama = null;

$sql_map = "SELECT nama FROM karyawan WHERE username = ? LIMIT 1";
$stmt_map = $conn_umum->prepare($sql_map);
if ($stmt_map) {
    $stmt_map->bind_param("s", $username);
    $stmt_map->execute();
    $res_map = $stmt_map->get_result();
    if ($res_map && $row_map = $res_map->fetch_assoc()) {
        $lokasiNama = $row_map['nama']; // misal: "Wahyu Hidayat"
    }
    if ($res_map) {
        $res_map->free();
    }
    $stmt_map->close();
}

// ==========================
// LIST ODP
// ==========================
$res_odp = $conn_umum->query("SELECT DISTINCT nama_odp FROM ODP ORDER BY nama_odp ASC");
if ($res_odp) {
    $data['odp'] = $res_odp->fetch_all(MYSQLI_ASSOC);
    $res_odp->free();
}

// ==========================
// LIST MODEM: status = 'dibawa',
// lokasi = username ATAU nama teknisi (jika mapping ketemu & beda)
// ==========================
if ($lokasiNama !== null && $lokasiNama !== '' && $lokasiNama !== $lokasiUser) {
    // PAKAI username ATAU nama karyawan
    $sql_modem = "
        SELECT 
            id_modem AS id, 
            serial_number, 
            model, 
            merk
        FROM modem 
        WHERE status = 'dibawa'
          AND (lokasi_penyimpanan = ? OR lokasi_penyimpanan = ?)
    ";

    $stmt_modem = $conn_umum->prepare($sql_modem);
    if ($stmt_modem) {
        $stmt_modem->bind_param("ss", $lokasiUser, $lokasiNama);
        $stmt_modem->execute();
        $res_modem = $stmt_modem->get_result();
        if ($res_modem) {
            $data['modem'] = $res_modem->fetch_all(MYSQLI_ASSOC);
            $res_modem->free();
        }
        $stmt_modem->close();
    }
} else {
    // FALLBACK: hanya lokasi_penyimpanan = username (behaviour lama)
    $sql_modem = "
        SELECT 
            id_modem AS id, 
            serial_number, 
            model, 
            merk
        FROM modem 
        WHERE status = 'dibawa'
          AND lokasi_penyimpanan = ?
    ";

    $stmt_modem = $conn_umum->prepare($sql_modem);
    if ($stmt_modem) {
        $stmt_modem->bind_param("s", $lokasiUser);
        $stmt_modem->execute();
        $res_modem = $stmt_modem->get_result();
        if ($res_modem) {
            $data['modem'] = $res_modem->fetch_all(MYSQLI_ASSOC);
            $res_modem->free();
        }
        $stmt_modem->close();
    }
}

// ==========================
// KABEL DROPCORE (status 'tersedia')
// ==========================
$res_dropcore = $conn_umum->query("
    SELECT 
        id_kabel_dropcore AS id, 
        kode_kabel, 
        panjang_meter 
    FROM kabel_dropcore 
    WHERE status = 'tersedia'
");
if ($res_dropcore) {
    $data['dropcore'] = $res_dropcore->fetch_all(MYSQLI_ASSOC);
    $res_dropcore->free();
}

// ==========================
// TEKNISI
// ==========================
$res_teknisi = $conn_umum->query("
    SELECT username, divisi 
    FROM karyawan 
    WHERE divisi IN ('Teknisi','Leader Area') 
    ORDER BY divisi, username ASC
");
if ($res_teknisi) {
    $data['teknisi'] = $res_teknisi->fetch_all(MYSQLI_ASSOC);
    $res_teknisi->free();
}

// ==========================
// OUTPUT JSON
// ==========================
echo json_encode([
    "success" => true,
    "data"    => $data
]);

$conn_umum->close();
exit;
