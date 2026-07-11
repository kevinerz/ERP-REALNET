<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Matikan error display ke output (tetap log di server)
ini_set('display_errors', '0');
error_reporting(E_ALL);

// --- MODE DEBUG (true = kirim SQL di JSON saat error) ---
$debug = false;

// --- KONFIGURASI DATABASE ---
$servername = "localhost";
$username   = "u272457353_kevinsamsung";
$password   = "Admionkevin99";
$dbname     = "u272457353_tiket_helpdesk";

// Buat koneksi
$conn = getErpDbConnection();
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi database gagal: ' . $conn->connect_error
    ]);
    exit;
}
$conn->set_charset('utf8mb4');

// Respons default
$response = [
    'success'      => false,
    'message'      => '',
    'total_pages'  => 0,
    'current_page' => 1,
    'total_rows'   => 0,
    'data'         => []
];

try {
    // --- PARAMETER DARI FLUTTER ---
    $nama_login_raw = $_GET['nama_login'] ?? '';
    $nama_login     = trim($nama_login_raw);

    $keyword        = $_GET['cari']          ?? '';
    $status_filter  = $_GET['status_filter'] ?? '';
    $sort_by        = $_GET['sort_by']       ?? 'nama_pelanggan';
    $page_tiket     = isset($_GET['page_tiket']) ? max(1, (int)$_GET['page_tiket']) : 1;

    // Kalau mau dipaksa login (wajib ada nama_login), aktifkan blok ini:
    /*
    if ($nama_login === '') {
        http_response_code(400);
        $response['message'] = 'Parameter nama_login wajib diisi.';
        echo json_encode($response);
        exit;
    }
    */

    // --- LOGIKA FILTER POP BERDASARKAN TEKNISI ---
    // Normalisasi nama user ke uppercase agar pencocokan tidak peka huruf besar/kecil
    $user = strtoupper($nama_login);

    $akses_pop = [];

    // Mapping user -> daftar POP (semua key UPPERCASE)
    $map_user_pop = [
        // POP MAUK
        'ARIES'      => ['mauk'],
        'ALFARIZ'          => ['mauk'],

        // POP RAJEG
        'GOFUR'          => ['rajeg'],
        'LUCKYMAN'  => ['mauk'],
        
        // POP PANGGANG
        'ASEP'          => ['panggang'],

        // USER DUA POP (MAUK + RAJEG)
        'JIHAN'          => ['mauk', 'rajeg'],

        // POP KEMERI
        'RAMDANI'                => ['kemeri'],
        'BASIR'                  => ['kemeri'],
        'WAHYUNUR'      => ['kemeri'],
        'FAJAR SAPUTRO'          => ['kemeri'],
        'SOPI'                   => ['kemeri'],
        // POP muncung
        'MEKACIL'                => ['muncung'],
        'MUKHSIN'                  => ['muncung'],
        'ROHILI'      => ['muncung'],
    ];

    // Isi $akses_pop jika user ada di mapping
    if (isset($map_user_pop[$user])) {
        $akses_pop = $map_user_pop[$user]; // sudah berupa array POP
    } else {
        // Kalau ingin user yang tidak terdaftar TIDAK melihat tiket sama sekali, biarkan kosong.
        // Kalau ingin default lihat semua POP, bisa set manual:
        // $akses_pop = ['mauk', 'rajeg', 'kemeri'];
        $akses_pop = [];
    }

    // --- MEMBANGUN KLAUSA WHERE ---
    $where = [];

    // Filter keyword (nama_pelanggan / alamat)
    if ($keyword !== '') {
        $escaped_keyword = $conn->real_escape_string($keyword);
        $where[] = "(nama_pelanggan LIKE '%$escaped_keyword%' OR alamat LIKE '%$escaped_keyword%')";
    }

    // Filter status (kecuali "Semua"/"All")
    if ($status_filter !== '' &&
        strtolower($status_filter) !== 'semua' &&
        strtolower($status_filter) !== 'all'
    ) {
        $escaped_status = $conn->real_escape_string($status_filter);
        $where[] = "LOWER(status) = '" . strtolower($escaped_status) . "'";
    }

    // Filter POP berdasarkan hak akses teknisi
    if (!empty($akses_pop)) {
        // nilai POP di sini statis (mauk, rajeg, kemeri), tapi tetap diamankan
        $escaped_pop = array_map([$conn, 'real_escape_string'], $akses_pop);
        $in_clause   = "'" . implode("','", $escaped_pop) . "'";
        $where[]     = "pop IN ($in_clause)";
    }

    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

    // --- PAGINATION ---
    $rows_per_page = 10;

    $sqlCount = "SELECT COUNT(*) AS total FROM tiket_gangguan $whereClause";
    $resultCount = $conn->query($sqlCount);

    if (!$resultCount) {
        $response['success'] = false;
        $response['message'] = 'Query COUNT gagal: ' . $conn->error;
        if ($debug) {
            $response['sql_count'] = $sqlCount;
        }
        echo json_encode($response);
        exit;
    }

    $rowCount   = $resultCount->fetch_assoc();
    $total_rows = isset($rowCount['total']) ? (int)$rowCount['total'] : 0;

    $total_pages = $total_rows > 0 ? (int)ceil($total_rows / $rows_per_page) : 1;
    $page_tiket  = min($page_tiket, $total_pages);
    $start_row   = ($page_tiket - 1) * $rows_per_page;

    // Validasi sort_by (whitelist kolom yang boleh di-sort)
    $allowedSort = ['nama_pelanggan', 'status', 'tanggal_dibuat'];
    if (!in_array($sort_by, $allowedSort, true)) {
        $sort_by = 'nama_pelanggan';
    }

    // --- QUERY UTAMA ---
    // Struktur tabel:
    // id, nama_pelanggan, alamat, whatsapp, pop, vlan, sn,
    // keluhan, maps_url, teknisi, action, tanggal_dibuat,
    // tanggal_selesai, status
    $sql = "
        SELECT
            id                AS id_gangguan,
            nama_pelanggan,
            alamat,
            whatsapp,
            pop,
            vlan,
            sn,
            keluhan,
            maps_url,
            teknisi           AS nama_teknisi,
            action,
            tanggal_dibuat    AS tanggal,
            tanggal_selesai,
            status
        FROM tiket_gangguan
        $whereClause
        ORDER BY
            CASE
                WHEN LOWER(status) = 'belum dikerjakan' THEN 0
                WHEN LOWER(status) = 'di proses'        THEN 1
                WHEN LOWER(status) = 'selesai'          THEN 2
                ELSE 3
            END ASC,
            $sort_by ASC
        LIMIT $start_row, $rows_per_page
    ";

    $result = $conn->query($sql);

    if (!$result) {
        $response['success'] = false;
        $response['message'] = 'Query data gagal: ' . $conn->error;
        if ($debug) {
            $response['sql'] = $sql;
        }
        echo json_encode($response);
        exit;
    }

    $response['success']      = true;
    $response['total_pages']  = $total_pages;
    $response['current_page'] = $page_tiket;
    $response['total_rows']   = $total_rows;

    if ($result->num_rows > 0) {
        $response['message'] = 'Data berhasil diambil.';
        while ($row = $result->fetch_assoc()) {
            $response['data'][] = $row;
        }
    } else {
        $response['message'] = 'Tidak ada data tiket yang ditemukan.';
    }

} catch (Exception $e) {
    http_response_code(500);
    $response['success'] = false;
    $response['message'] = 'Terjadi kesalahan pada server: ' . $e->getMessage();
}

$conn->close();
echo json_encode($response);
