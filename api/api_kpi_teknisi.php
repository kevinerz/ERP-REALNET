<?php
require_once __DIR__ . '/../config/database.php';
// Set header untuk JSON dan izinkan akses lintas domain
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0); // Matikan pelaporan error di produksi

// --- KONFIGURASI LOKASI FOTO (SESUIKAN DENGAN SERVER ANDA) ---
$link_sumber_foto = "https://datarealsolution.net/";

// --- KONFIGURASI DAN KONEKSI DATABASE ---
$host = 'localhost';

// UMUM
$user_umum = 'u272457353_kevinsamsung99';
$pass_umum = 'Admionkevin99';
$db_umum   = 'u272457353_umumdata';

// PEMASANGAN
$user_pasang = 'u272457353_kevinsamsung9';
$pass_pasang = 'Admionkevin99';
$db_pasang   = 'u272457353_db_pemasangan';

// TIKET
$user_tiket = 'u272457353_kevinsamsung';
$pass_tiket = 'Admionkevin99';
$db_tiket   = 'u272457353_tiket_helpdesk';

// Koneksi
$conn_umum = @getErpDbConnection();
if ($conn_umum->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB_ERROR: Koneksi Umum Gagal.']);
    exit();
}

$conn_pasang = @getErpDbConnection();
if ($conn_pasang->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB_ERROR: Koneksi Pemasangan Gagal.']);
    exit();
}

$conn_tiket = @getErpDbConnection();
if ($conn_tiket->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB_ERROR: Koneksi Tiket Gagal.']);
    exit();
}

$conn_umum->set_charset('utf8mb4');
$conn_pasang->set_charset('utf8mb4');
$conn_tiket->set_charset('utf8mb4');

// --- RATE BONUS (Sama dengan dashboard) ---
define('RATE_PASANG', 50000);
define('RATE_TIKET', 15000);

// --- FUNGSI BANTUAN AVATAR ---
function get_avatar_url($db_val, $base_url) {
    if (empty($db_val)) return "";
    if (strpos($db_val, 'http') === 0) return $db_val;
    return rtrim($base_url, '/') . '/' . ltrim($db_val, '/');
}

// --- VARIABEL & INPUT ---
// Menggunakan $_REQUEST agar bisa menerima dari POST (Flutter) atau GET (Browser)
$username_input = $_REQUEST['username'] ?? null;
$bulan = $_REQUEST['bulan'] ?? date('m');
$tahun = $_REQUEST['tahun'] ?? date('Y');

if (!$username_input) {
    echo json_encode(['status' => 'error', 'message' => 'INPUT_ERROR: Username tidak terkirim.']);
    exit();
}

// Normalisasi bulan ke 2 digit (01,02,…)
$bulan = str_pad((int)$bulan, 2, '0', STR_PAD_LEFT);

// --- STEP 1: AMBIL PROFIL & ID DARI USERNAME ---
$username_safe = $conn_umum->real_escape_string(trim($username_input));

$sql_user = "SELECT id, nama, username, gaji, jabatan, avatar 
             FROM hr_karyawan 
             WHERE username = '$username_safe' 
             LIMIT 1";
$karyawan = $conn_umum->query($sql_user)->fetch_assoc();

if (!$karyawan) {
    echo json_encode([
        'status'  => 'error',
        'message' => "LOGIC_ERROR: Data karyawan tidak ditemukan dengan username '$username_input'."
    ]);
    exit();
}

$id_teknisi   = (int)$karyawan['id'];
$nama_asli    = trim($karyawan['nama']);
$gaji_pokok   = (float)$karyawan['gaji'];
$jabatan      = $karyawan['jabatan'] ?? '';
$avatar_db    = $karyawan['avatar'] ?? '';
$avatar_final = get_avatar_url($avatar_db, $link_sumber_foto);

// --- STEP 2: SIAPKAN KEYWORD PENCARIAN ---
// BBM & Tiket menggunakan Nama Lengkap
$safe_nama_umum  = $conn_umum->real_escape_string($nama_asli);
$safe_nama_tiket = $conn_tiket->real_escape_string($nama_asli);
// Pemasangan menggunakan Username
$safe_user_pasang = $conn_pasang->real_escape_string($username_safe);

// --- STEP 3: EKSEKUSI QUERY KPI RINGKAS ---

// 1. DATA KASBON (Filter by ID)
$q1 = "SELECT SUM(jumlah) as val 
       FROM keu_kasbon 
       WHERE id_karyawan = '$id_teknisi' 
         AND MONTH(tanggal) = '$bulan' 
         AND YEAR(tanggal) = '$tahun' 
         AND status != 'Lunas'";
$d_kasbon = $conn_umum->query($q1)->fetch_assoc();

// 2. DATA BBM (Filter by NAMA LENGKAP)
$q2 = "SELECT SUM(liter) as vol, SUM(total) as uang 
       FROM keu_reimburse_bbm 
       WHERE nama_pengaju LIKE '%$safe_nama_umum%' 
         AND MONTH(tanggal) = '$bulan' 
         AND YEAR(tanggal) = '$tahun'";
$d_bbm = $conn_umum->query($q2)->fetch_assoc();

// 3. DATA PEMASANGAN (Filter by USERNAME)
$q3 = "SELECT COUNT(*) as total, 
              SUM(CASE WHEN status IN ('Done','Selesai','Finished','Sukses') THEN 1 ELSE 0 END) as done 
       FROM pelanggan_instalasi 
       WHERE teknisi LIKE '%$safe_user_pasang%' 
         AND MONTH(tanggal) = '$bulan' 
         AND YEAR(tanggal) = '$tahun'";
$d_pasang = $conn_pasang->query($q3)->fetch_assoc();

// 4. DATA TIKET (Filter by NAMA LENGKAP)
$q4 = "SELECT COUNT(*) as total, 
              SUM(CASE WHEN status IN ('Closed','Selesai','Close','Done') THEN 1 ELSE 0 END) as done 
       FROM tiket_gangguan 
       WHERE teknisi LIKE '%$safe_nama_tiket%' 
         AND MONTH(tanggal_dibuat) = '$bulan' 
         AND YEAR(tanggal_dibuat) = '$tahun'";
$d_tiket = $conn_tiket->query($q4)->fetch_assoc();

// --- STEP 4: QUERY RINCIAN AKTIVITAS (UNTUK LIST DI FLUTTER) ---

// A. Rincian Pemasangan Selesai
$q_detail_pasang = "SELECT nama, alamat, tanggal, status 
                    FROM pelanggan_instalasi 
                    WHERE teknisi LIKE '%$safe_user_pasang%' 
                      AND MONTH(tanggal) = '$bulan' 
                      AND YEAR(tanggal) = '$tahun'
                      AND status IN ('Done','Selesai','Finished','Sukses')
                    ORDER BY tanggal DESC";
$r_pasang = $conn_pasang->query($q_detail_pasang);
$detail_pasang = [];
if ($r_pasang && $r_pasang->num_rows > 0) {
    while ($row = $r_pasang->fetch_assoc()) {
        $detail_pasang[] = [
            'tanggal' => $row['tanggal'],
            'nama'    => $row['nama'],
            'alamat'  => $row['alamat'],
            'status'  => $row['status'],
        ];
    }
}

// B. Rincian Tiket Selesai
$q_detail_tiket = "SELECT nama_pelanggan, alamat, tanggal_selesai, status 
                   FROM tiket_gangguan 
                   WHERE teknisi LIKE '%$safe_nama_tiket%' 
                     AND MONTH(tanggal_dibuat) = '$bulan' 
                     AND YEAR(tanggal_dibuat) = '$tahun'
                     AND status IN ('Closed','Selesai','Close','Done')
                   ORDER BY tanggal_selesai DESC";
$r_tiket = $conn_tiket->query($q_detail_tiket);
$detail_tiket = [];
if ($r_tiket && $r_tiket->num_rows > 0) {
    while ($row = $r_tiket->fetch_assoc()) {
        $detail_tiket[] = [
            'tanggal_selesai' => $row['tanggal_selesai'],
            'nama_pelanggan'  => $row['nama_pelanggan'],
            'alamat'          => $row['alamat'],
            'status'          => $row['status'],
        ];
    }
}

// C. Rincian BBM
$q_detail_bbm = "SELECT tanggal, liter, total, status_keuangan 
                 FROM keu_reimburse_bbm 
                 WHERE nama_pengaju LIKE '%$safe_nama_umum%' 
                   AND MONTH(tanggal) = '$bulan' 
                   AND YEAR(tanggal) = '$tahun'
                 ORDER BY tanggal DESC";
$r_bbm = $conn_umum->query($q_detail_bbm);
$detail_bbm = [];
if ($r_bbm && $r_bbm->num_rows > 0) {
    while ($row = $r_bbm->fetch_assoc()) {
        $detail_bbm[] = [
            'tanggal'        => $row['tanggal'],
            'liter'          => (float)$row['liter'],
            'total'          => (float)$row['total'],
            'status_keuangan'=> $row['status_keuangan'],
        ];
    }
}

// D. Rincian Kasbon Outstanding
$q_detail_kasbon = "SELECT tanggal, jumlah, status 
                    FROM keu_kasbon 
                    WHERE id_karyawan = '$id_teknisi' 
                      AND MONTH(tanggal) = '$bulan' 
                      AND YEAR(tanggal) = '$tahun'
                      AND status != 'Lunas'
                    ORDER BY tanggal DESC";
$r_kasbon = $conn_umum->query($q_detail_kasbon);
$detail_kasbon = [];
if ($r_kasbon && $r_kasbon->num_rows > 0) {
    while ($row = $r_kasbon->fetch_assoc()) {
        $detail_kasbon[] = [
            'tanggal' => $row['tanggal'],
            'jumlah'  => (float)$row['jumlah'],
            'status'  => $row['status'],
        ];
    }
}

// --- STEP 5: PERHITUNGAN RINGKAS ---

$bonus_pasang      = (float)($d_pasang['done'] ?? 0) * RATE_PASANG;
$bonus_tiket       = (float)($d_tiket['done'] ?? 0) * RATE_TIKET;
$potongan_kasbon   = (float)($d_kasbon['val'] ?? 0);
$total_bbm         = (float)($d_bbm['uang'] ?? 0);
$total_bbm_liter   = (float)($d_bbm['vol'] ?? 0);
$jumlah_pemasangan = (int)($d_pasang['done'] ?? 0);
$jumlah_tiket      = (int)($d_tiket['done'] ?? 0);

$thp = $gaji_pokok + $bonus_pasang + $bonus_tiket - $potongan_kasbon;

// --- OUTPUT JSON ---

$output = [
    'status'  => 'success',
    'message' => 'Data KPI berhasil diambil.',
    'debug_info' => [
        'username_searched'    => $username_input,
        'nama_karyawan_fetched'=> $nama_asli,
        'sql_pemasangan_query' => $q3,
        'sql_tiket_query'      => $q4
    ],
    'kpi_data' => [
        'bulan'                 => $bulan,
        'tahun'                 => $tahun,
        'gaji_pokok'            => $gaji_pokok,
        'thp_estimasi'          => $thp,
        'reimburse_bbm_total'   => $total_bbm,
        // blok profil karyawan (untuk header Flutter)
        'karyawan' => [
            'id'         => $id_teknisi,
            'nama'       => $nama_asli,
            'username'   => $karyawan['username'],
            'jabatan'    => $jabatan,
            'gaji'       => $gaji_pokok,
            'avatar'     => $avatar_db,
            'avatar_url' => $avatar_final,
        ],
        // ringkasan metrik
        'metrics' => [
            'pemasangan'       => $jumlah_pemasangan,
            'bonus_pemasangan' => $bonus_pasang,
            'tiket_selesai'    => $jumlah_tiket,
            'bonus_tiket'      => $bonus_tiket,
            'kasbon_potongan'  => $potongan_kasbon,
            'bbm_liter'        => $total_bbm_liter,
        ],
        // rincian aktivitas untuk list/detail di aplikasi
        'detail_pemasangan' => $detail_pasang,
        'detail_tiket'      => $detail_tiket,
        'detail_bbm'        => $detail_bbm,
        'detail_kasbon'     => $detail_kasbon,
    ],
];

echo json_encode($output, JSON_PRETTY_PRINT);

// Tutup koneksi
$conn_umum->close();
$conn_pasang->close();
$conn_tiket->close();
