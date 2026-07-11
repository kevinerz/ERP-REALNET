<?php
// get_pemasangan.php

// Include konfigurasi (Header CORS & Fungsi Koneksi ada di sini)
require 'db_config.php';

// Ambil parameter
$username = $_GET['username'] ?? '';
$cari = trim($_GET['cari'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$rows_per_page = 10;
$start_row = ($page - 1) * $rows_per_page;

if (empty($username)) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Parameter username wajib diisi."]);
    exit;
}

// ==========================================================
// 1. LOGIKA MAPPING USERNAME KE POP
// ==========================================================
$loginNameToBusinessName = [
    'muhamad gofur'     => 'Gofur',
    'fajar saputro'     => 'Fzr41',
    'wahyu hidayat'     => 'ALFARIZ',
    'arista dwi candra' => 'ARIES',
    'Muhammad Lukmam Hakim' => 'Luckyman',
    'Saepulloh' => 'Asep',
    'jihan riyadho'     => 'jihan'
];

// PERBAIKAN: Value diubah menjadi array agar satu nama bisa punya banyak POP
// (sebelumnya key "ASEP" duplikat, yang kedua menimpa yang pertama)
$businessNameToPop = [
    "Gofur"    => ["rajeg"],
    "ASEP"     => ["panggang", "kelapa"],  // <-- FIX: sekarang dapat keduanya
    "BASIR"    => ["kemeri"],
    "Luckyman" => ["mauk"],
    "ALFARIZ"  => ["mauk"],
    "ARIES"    => ["mauk"],
    "SARANI"   => ["mauk"],
    "JIHAN"    => ["rajeg"],
    "Fzr41"    => ["kemeri"],
    "Ramdani"  => ["kemeri"],
    "SOPI"     => ["kemeri"],
    "RAMDANI"  => ["kemeri"],
    "Wahyunur" => ["kemeri"],
    "MEKACIL"  => ["muncung"],
    "MUKHSIN"  => ["muncung"],
    "ROHILI"   => ["muncung"],
];

$username_lower = strtolower($username);
$businessName = $loginNameToBusinessName[$username_lower] ?? $username;

// Cari daftar POP (sekarang berupa array)
$pop_filters = [];
foreach ($businessNameToPop as $key => $pops) {
    if (strtolower($key) === strtolower($businessName)) {
        $pop_filters = $pops;
        break;
    }
}

// ==========================================================
// 2. KONEKSI & QUERY DATA PEMASANGAN
// ==========================================================
$conn_pemasangan = get_conn_pemasangan();

// Jika POP tidak ditemukan, return kosong
if (empty($pop_filters)) {
    echo json_encode([
        "success" => true,
        "data" => [],
        "pagination" => ["currentPage" => 1, "totalPages" => 1, "totalRows" => 0]
    ]);
    $conn_pemasangan->close();
    exit;
}

// --- Query Builder ---
$where = [];
$params = [];
$types = '';

// PERBAIKAN: Filter POP menggunakan IN (...) agar bisa multi-POP
$placeholders = implode(',', array_fill(0, count($pop_filters), '?'));
$where[] = "pop IN ($placeholders)";
array_push($params, ...$pop_filters);
$types .= str_repeat('s', count($pop_filters));

// Filter Pencarian
if ($cari) {
    $where[] = "(nama LIKE ? OR alamat LIKE ? OR telp LIKE ? OR ktp LIKE ?)";
    $like_param = "%$cari%";
    array_push($params, $like_param, $like_param, $like_param, $like_param);
    $types .= "ssss";
}

// Filter Status (Hanya Aktivasi & Di Proses)
$where[] = "(status = ? OR status = ?)";
array_push($params, "aktivasi", "di proses");
$types .= "ss";

$where_sql = "WHERE " . implode(" AND ", $where);

// --- Hitung Total Rows (Pagination) ---
$sql_count = "SELECT COUNT(*) AS total FROM pelanggan_instalasi $where_sql";
$stmt_count = $conn_pemasangan->prepare($sql_count);
if ($params) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_count->close();

$total_pages = max(1, ceil($total_rows / $rows_per_page));

// --- Ambil Data Utama ---
$sql_data = "SELECT id, nama, userppp, passwordppp, paket, vlan, sn, pop, odp, url_maps, teknisi, alamat, ktp, telp, email, marketing, tanggal, status, modem, dropcore
             FROM pelanggan_instalasi $where_sql ORDER BY tanggal DESC LIMIT ?, ?";
$params_data = $params;
$types_data = $types . "ii";
array_push($params_data, $start_row, $rows_per_page);

$stmt = $conn_pemasangan->prepare($sql_data);
$stmt->bind_param($types_data, ...$params_data);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn_pemasangan->close();

// ==========================================================
// 3. ENHANCEMENT: AMBIL NAMA PAKET
// ==========================================================
if (!empty($data)) {
    $conn_umum = get_conn_umum();

    $paket_map = [];
    $res_pk = $conn_umum->query("SELECT id_paket, nama_paket FROM jaringan_paket");
    if ($res_pk) {
        while ($row = $res_pk->fetch_assoc()) {
            $paket_map[$row['id_paket']] = $row['nama_paket'];
        }
    }
    $conn_umum->close();

    foreach ($data as &$row) {
        $id_paket = $row['paket'];
        if (isset($paket_map[$id_paket])) {
            $row['paket'] = $paket_map[$id_paket];
        }
    }
}

// ==========================================================
// 4. KIRIM RESPONSE
// ==========================================================
echo json_encode([
    "success" => true,
    "data" => $data,
    "pagination" => [
        "currentPage" => $page,
        "totalPages" => $total_pages,
        "totalRows" => $total_rows
    ]
]);
?>