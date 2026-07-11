<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ob_clean();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin', '*');
header('Access-Control-Allow-Methods', 'POST, OPTIONS');
header('Access-Control-Allow-Headers', 'Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

include 'koneksi.php';

/* =======================
 * KONFIGURASI STARSENDER
 * ======================= */
define('STARSENDER_API_KEY', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124');
define('STARSENDER_URL', 'https://api.starsender.online/api/send');

/* =========================
 * FUNGSI HELPER RESPONSE
 * ========================= */
function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

/* =========================
 * FUNGI KIRIM WHATSAPP (FORMAT BARU)
 * ========================= */
function sendWaStarsender($nomor, $pesan) {
    if (empty($nomor) || empty($pesan)) {
        return false;
    }

    $payload = [
        'messageType' => 'text',
        'to'          => $nomor,   // 6287770...
        'body'        => $pesan,
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => STARSENDER_URL,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . STARSENDER_API_KEY,
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 10
    ]);

    $result = curl_exec($ch);
    $err    = curl_error($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($err) {
        error_log("Starsender error: $err");
        return false;
    }
    if ($code < 200 || $code >= 300) {
        error_log("Starsender HTTP code: $code, response: $result");
        return false;
    }
    return true;
}

/* =========================
 * VALIDASI METODE
 * ========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response('error', 'Metode request tidak valid. Gunakan POST.');
}

/* =========================
 * AMBIL DATA INPUT
 * ========================= */
$json_data = file_get_contents('php://input');
$data = json_decode($json_data);

if (
    !$data ||
    !isset($data->nama) ||
    !isset($data->tanggal) ||
    !isset($data->jumlah) ||
    !isset($data->keperluan)
) {
    send_json_response('error', 'Data input tidak lengkap.');
}

$nama      = trim($data->nama);
$tanggal   = $data->tanggal;
$jumlah    = $data->jumlah;
$keperluan = $data->keperluan;

if (empty($nama) || empty($tanggal) || empty($jumlah) || empty($keperluan)) {
    send_json_response('error', 'Semua field wajib diisi!');
}

/* =========================
 * CARI DATA KARYAWAN
 * ========================= */
$get_karyawan = $conn->prepare("SELECT id, divisi, no_telp FROM hr_karyawan WHERE LOWER(nama) = LOWER(?)");
if (!$get_karyawan) {
    send_json_response('error', 'Gagal mempersiapkan query: ' . $conn->error);
}
$get_karyawan->bind_param("s", $nama);
$get_karyawan->execute();
$result = $get_karyawan->get_result();

if ($result->num_rows === 0) {
    send_json_response('error', 'Nama tidak ditemukan di database.', $nama);
}
$user             = $result->fetch_assoc();
$id_karyawan      = (int)$user['id'];
$divisi_pengaju   = $user['divisi'];
$no_telp_pengaju  = $user['no_telp']; // jika mau dipakai nanti

/* =========================
 * LOGIKA STATUS APPROVAL
 * ========================= */
$status = 'manager';
switch (strtolower($divisi_pengaju)) {
    case 'teknisi':
    case 'leader area':
        $status = 'spv_teknis';
        break;
    case 'spv teknis':
    case 'finance':
        $status = 'manager';
        break;
    case 'manager':
        $status = 'spv_administrasi';
        break;
    case 'spv administrasi':
        $status = 'manager';
        break;
}

/* =========================
 * SIMPAN DATA KASBON
 * ========================= */
$sql = "INSERT INTO keu_kasbon (id_karyawan, tanggal, jumlah, keperluan, status)
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    send_json_response('error', 'Gagal mempersiapkan statement insert: ' . $conn->error);
}
$stmt->bind_param("isdss", $id_karyawan, $tanggal, $jumlah, $keperluan, $status);

if ($stmt->execute()) {
    // ==============================
    // KIRIM WHATSAPP NOTIFIKASI
    // ==============================
    $rupiah = 'Rp ' . number_format((float)$jumlah, 0, ',', '.');

    $pesan_wa =
        "*PENGAJUAN KASBON BARU*\n" .
        "Nama      : {$nama}\n" .
        "Tanggal   : {$tanggal}\n" .
        "Jumlah    : {$rupiah}\n" .
        "Keperluan : {$keperluan}\n" .
        "Status setuju    : *" . strtoupper($status) . "*\n\n" .
        "Mohon untuk dicek dan diproses di sistem.";

    // Nomor tujuan WA (SPV/Manager)
    $nomor_tujuan = '6287770366015';

    // Kirim WA, tapi hasilnya tidak mengubah response API
    sendWaStarsender($nomor_tujuan, $pesan_wa);

    send_json_response('success', 'Pengajuan kasbon berhasil dikirim.');
} else {
    send_json_response('error', 'Gagal menyimpan data kasbon: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>
