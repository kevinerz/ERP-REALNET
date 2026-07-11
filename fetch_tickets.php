<?php
// Ganti include 'config.php' menjadi 'konfig.php'
include('konfig.php'); // Sertakan file konfigurasi database Anda

header('Content-Type: application/json'); // Beri tahu browser bahwa respons adalah JSON

// Pagination config
$limit = 10; // Tiket per halaman

// Ambil parameter filter & sort dari GET request
$cari = isset($_GET['cari']) ? $conn->real_escape_string($_GET['cari']) : '';
$status_filter = isset($_GET['status_filter']) ? $conn->real_escape_string($_GET['status_filter']) : '';
$pop_filter = isset($_GET['pop_filter']) ? $conn->real_escape_string($_GET['pop_filter']) : '';
$allowedSort = ['nama_pelanggan', 'status', 'tanggal_dibuat'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort) ? $_GET['sort'] : 'tanggal_dibuat';
$order = (isset($_GET['order']) && strtoupper($_GET['order']) === 'ASC') ? 'ASC' : 'DESC';

// Halaman dan offset untuk keseluruhan hasil (bukan per POP lagi)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Bangun WHERE clause
$where_clauses = [];
if ($cari !== '') {
    $where_clauses[] = "nama_pelanggan LIKE '%{$cari}%'";
}
if ($status_filter !== '') {
    $where_clauses[] = "status = '{$status_filter}'";
}
if ($pop_filter !== '') {
    $where_clauses[] = "pop = '{$pop_filter}'";
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Kueri untuk mendapatkan total tiket (untuk paginasi)
$qTotal = "SELECT COUNT(*) AS total FROM tiket_gangguan $where_sql";
$total_result = $conn->query($qTotal);
$total_tiket = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_tiket / $limit);

// Kueri untuk mendapatkan data tiket
$qTiket = "
    SELECT *
    FROM tiket_gangguan
    $where_sql
    ORDER BY
        CASE
            WHEN status = 'belum dikerjakan' THEN 1
            WHEN status = 'di proses' THEN 2
            WHEN status = 'selesai' THEN 3
            ELSE 4
        END,
    $sort $order
    LIMIT $limit OFFSET $offset
";
$result_tiket = $conn->query($qTiket);

$tickets = [];
while ($row = $result_tiket->fetch_assoc()) {
    $tickets[] = $row;
}

// Ambil daftar POP unik untuk filter
$query_pop = "SELECT DISTINCT pop FROM tiket_gangguan ORDER BY pop ASC";
$result_pop = $conn->query($query_pop);
$daftar_pop = [];
while ($row = $result_pop->fetch_assoc()) {
    $daftar_pop[] = $row['pop'];
}

echo json_encode([
    'tickets' => $tickets,
    'total_pages' => $total_pages,
    'current_page' => $page,
    'total_tickets' => $total_tiket,
    'pop_list' => $daftar_pop, // Kirim daftar POP juga
    'filters' => [ // Kirim filter yang sedang aktif
        'cari' => $cari,
        'status_filter' => $status_filter,
        'pop_filter' => $pop_filter,
        'sort' => $sort,
        'order' => $order
    ]
]);

$conn->close();
?>