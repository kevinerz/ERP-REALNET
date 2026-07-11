<?php
require_once __DIR__ . '/config/database.php';
// jadwal_libur_today.php
header('Content-Type: application/json; charset=utf-8');

date_default_timezone_set('Asia/Jakarta');

define('DB_HOST_UMUMDATA', 'localhost');
define('DB_USER_UMUMDATA', 'u272457353_kevinsamsung99');
define('DB_PASS_UMUMDATA', 'Admionkevin99');
define('DB_NAME_UMUMDATA', 'u272457353_umumdata');

$mysqli = getErpDbConnection();
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Koneksi gagal: ' . $mysqli->connect_error
    ]);
    exit;
}
$mysqli->set_charset('utf8mb4');

// Tentukan hari (bisa override pakai GET ?hari=Senin)
$mapHari = [
    'Monday'    => 'Senin',
    'Tuesday'   => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday'  => 'Kamis',
    'Friday'    => 'Jumat',
    'Saturday'  => 'Sabtu',
    'Sunday'    => 'Minggu',
];

if (isset($_GET['hari']) && in_array($_GET['hari'], $mapHari, true)) {
    $hariLibur = $_GET['hari'];
} else {
    $hariEnglish = date('l'); // Monday, Tuesday, ...
    $hariLibur   = $mapHari[$hariEnglish] ?? 'Senin';
}

$tanggal = date('Y-m-d');

// Ambil daftar karyawan libur hari ini
$sql = "
    SELECT k.nama, k.divisi, k.jabatan
    FROM hr_jadwal_libur jl
    JOIN hr_karyawan k ON k.id = jl.id_karyawan
    WHERE jl.hari = ?
    ORDER BY k.nama ASC
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('s', $hariLibur);
$stmt->execute();
$result = $stmt->get_result();

$libur = [];
while ($row = $result->fetch_assoc()) {
    $libur[] = $row;
}
$stmt->close();

// Format teks seperti untuk grup WA
$lines = [];
$lines[] = "Jadwal libur hari {$hariLibur}, {$tanggal}:";

if (count($libur) === 0) {
    $lines[] = "- (Tidak ada jadwal libur yang terdaftar)";
} else {
    foreach ($libur as $k) {
        $nama = $k['nama'];
        $infoTambahan = trim($k['divisi'] . ' - ' . $k['jabatan']);
        if ($infoTambahan !== '-') {
            $lines[] = "- {$nama} ({$infoTambahan})";
        } else {
            $lines[] = "- {$nama}";
        }
    }
}

$textMessage = implode("\n", $lines);

// TODO: kalau mau langsung kirim WA, panggil fungsi WA Anda di sini.
// Contoh pseudo (sesuaikan dengan sistem Anda):
// require_once 'whatsapp_helper.php';
// sendWhatsappGroup($idGroupRealnet, $textMessage);

echo json_encode([
    'success' => true,
    'hari'    => $hariLibur,
    'tanggal' => $tanggal,
    'count'   => count($libur),
    'data'    => $libur,
    'text'    => $textMessage
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
