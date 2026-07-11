<?php
// ============================================================================
//  FILE: update_ticket_status.php
//  Versi FINAL + API KEY TERPISAH + JAM WIB + BAHASA MUDAH + KELUHAN LENGKAP
// ============================================================================

declare(strict_types=1);
ini_set('display_errors', '0');
error_reporting(E_ALL);

// ============================================================================
// SET TIMEZONE JAKARTA (WIB)
// ============================================================================
date_default_timezone_set('Asia/Jakarta');

// ============================================================================
// RESPONSE & CORS
// ============================================================================
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function json_response(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================================
// LOG REQUEST RAW BODY
// ============================================================================
$raw_body = file_get_contents('php://input');
file_put_contents(
    "log_update.txt",
    date("Y-m-d H:i:s") . " | RAW: $raw_body\n",
    FILE_APPEND
);

$input = json_decode($raw_body, true);
if (!is_array($input)) {
    file_put_contents(
        "last_error.txt",
        date("Y-m-d H:i:s") . " | BAD JSON: $raw_body\n",
        FILE_APPEND
    );
    json_response(400, [
        'success' => false,
        'message' => 'Body request harus JSON yang valid.'
    ]);
}

// ============================================================================
// AMBIL PARAMETER (FLEKSIBEL)
// ============================================================================
$id_raw = $input['id_gangguan']
    ?? $input['id']
    ?? $input['ticket_id']
    ?? $input['id_ticket']
    ?? null;

$id_gangguan  = (int)($id_raw ?? 0);
$status       = trim((string)($input['status'] ?? ''));
$nama_teknisi = trim((string)($input['nama_teknisi'] ?? $input['teknisi'] ?? ''));

// VALIDASI WAJIB
if ($id_gangguan <= 0 || $status === '' || $nama_teknisi === '') {
    file_put_contents(
        "last_error.txt",
        date("Y-m-d H:i:s") . " | PARAM KURANG: " . print_r($input, true) . "\n",
        FILE_APPEND
    );

    json_response(400, [
        'success' => false,
        'message' => 'Parameter tidak lengkap (id_gangguan, status, nama_teknisi).'
    ]);
}

$normalized_status = strtolower($status);
$tgl_update_sql    = ($normalized_status === 'selesai') ? ', tanggal_selesai = NOW()' : '';

// ============================================================================
// KONEKSI DATABASE
// ============================================================================
$servername = "localhost";
$username   = "u272457353_kevinsamsung";
$password   = "Admionkevin99";
$dbname     = "u272457353_tiket_helpdesk";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    file_put_contents(
        "last_error.txt",
        date("Y-m-d H:i:s") . " | DB ERROR: " . $conn->connect_error . "\n",
        FILE_APPEND
    );
    json_response(500, [
        'success' => false,
        'message' => 'Koneksi database gagal.'
    ]);
}
$conn->set_charset('utf8mb4');

// ============================================================================
// UPDATE STATUS TIKET
// ============================================================================
$sql_update = "UPDATE tiket 
               SET status = ?, teknisi = ? $tgl_update_sql
               WHERE id = ?";
$stmt = $conn->prepare($sql_update);

if (!$stmt) {
    file_put_contents(
        "last_error.txt",
        date("Y-m-d H:i:s") . " | UPDATE PREP ERROR: " . $conn->error . "\n",
        FILE_APPEND
    );
    json_response(500, [
        'success' => false,
        'message' => 'Gagal mempersiapkan query UPDATE.'
    ]);
}

$stmt->bind_param('ssi', $status, $nama_teknisi, $id_gangguan);

if (!$stmt->execute()) {
    file_put_contents(
        "last_error.txt",
        date("Y-m-d H:i:s") . " | UPDATE EXEC ERROR: " . $stmt->error . "\n",
        FILE_APPEND
    );
    $stmt->close();
    json_response(500, [
        'success' => false,
        'message' => 'Gagal memperbarui status tiket.'
    ]);
}
$stmt->close();

// ============================================================================
// AMBIL DETAIL TIKET UNTUK NOTIFIKASI
// ============================================================================
$sql_select = "SELECT nama_pelanggan, alamat, keluhan, whatsapp, pop
               FROM tiket
               WHERE id = ?
               LIMIT 1";
$stmt = $conn->prepare($sql_select);
$stmt->bind_param('i', $id_gangguan);
$stmt->execute();
$res    = $stmt->get_result();
$ticket = $res->fetch_assoc();
$stmt->close();

if (!$ticket) {
    json_response(200, [
        'success'         => true,
        'message'         => 'Status tiket berhasil diperbarui, tetapi data tiket tidak ditemukan.',
        'notif_pelanggan' => false,
        'notif_grup'      => false
    ]);
}

// ============================================================================
// KONFIGURASI STARSENDER - API KEY TERPISAH
// ============================================================================
$starsender_api_key_customer = "7106aa0b-0eb0-4673-aaf6-470ccc1f2390";  // CUSTOMER
$starsender_api_key_group    = "e9c50247-3b8d-4cd8-924a-024a4d2b3124";  // GROUP TEKNISI

// ============================================================================
// FUNGSI HELPER
// ============================================================================

function normalizeWA(?string $num): ?string
{
    $num = preg_replace('/\D+/', '', $num ?? '');
    if ($num === '') {
        return null;
    }
    if (strpos($num, '0') === 0) {
        $num = '62' . substr($num, 1);
    }
    return $num;
}

function getGroupPOP(?string $pop): ?string
{
    $map = [
        "rajeg"    => "120363424064802149@g.us",
        "kemeri"   => "120363423460663827@g.us",
        "panggang" => "120363422971129799@g.us",
        "brebes"   => "120363297070607107@g.us",
        "kelapa"   => "120363423157487069@g.us",
        "sengon"   => "120363366069803212@g.us",
        "badakanom"    => "120363409600702809@g.us",
        "mauk"     => "120363405820721170@g.us",
        "grinting" => "120363399972363054@g.us"
    ];
    $key = strtolower(trim((string)$pop));
    return $map[$key] ?? null;
}

// ============================================================================
// FUNGSI NOTIFIKASI CUSTOMER - API KEY CUSTOMER
// ============================================================================
function sendNotifCustomer(string $to, string $msg): bool
{
    global $starsender_api_key_customer;

    $schedule_ms = (time() + 10) * 1000;

    $payload_arr = [
        "messageType" => "text",
        "to"          => $to,
        "body"        => $msg,
        "delay"       => 10,
        "schedule"    => $schedule_ms
    ];

    $payload = json_encode($payload_arr);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://api.starsender.online/api/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: ' . $starsender_api_key_customer
        ],
    ]);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    file_put_contents(
        "starsender_debug.txt",
        "=== CUSTOMER NOTIF ===\n" .
        "Timestamp: " . date("Y-m-d H:i:s") . "\n" .
        "TO: $to\n" .
        "RESPONSE: " . substr($response, 0, 100) . "\n" .
        "ERROR: $err\n" .
        "API Key: 7106aa0b-0eb0-4673-aaf6-470ccc1f2390\n" .
        "======================\n\n",
        FILE_APPEND
    );

    if ($err) {
        return false;
    }

    if (trim((string)$response) === '') {
        return false;
    }

    $decoded = json_decode($response, true);
    return (is_array($decoded) && ($decoded['success'] ?? false) === true);
}

// ============================================================================
// FUNGSI NOTIFIKASI GROUP - API KEY GROUP
// ============================================================================
function sendNotifGroup(string $to, string $msg): bool
{
    global $starsender_api_key_group;

    $schedule_ms = (time() + 10) * 1000;

    $payload_arr = [
        "messageType" => "text",
        "to"          => $to,
        "body"        => $msg,
        "delay"       => 10,
        "schedule"    => $schedule_ms
    ];

    $payload = json_encode($payload_arr);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => 'https://api.starsender.online/api/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => 'POST',
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: ' . $starsender_api_key_group
        ],
    ]);

    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    file_put_contents(
        "starsender_debug.txt",
        "=== GROUP NOTIF ===\n" .
        "Timestamp: " . date("Y-m-d H:i:s") . "\n" .
        "TO: $to\n" .
        "RESPONSE: " . substr($response, 0, 100) . "\n" .
        "ERROR: $err\n" .
        "API Key: e9c50247-3b8d-4cd8-924a-024a4d2b3124\n" .
        "===================\n\n",
        FILE_APPEND
    );

    if ($err) {
        return false;
    }

    if (trim((string)$response) === '') {
        return false;
    }

    $decoded = json_decode($response, true);
    return (is_array($decoded) && ($decoded['success'] ?? false) === true);
}

// ============================================================================
// FORMAT WAKTU DENGAN ZONA WIB
// ============================================================================
$tanggal_sekarang = date('d/m/Y');
$jam_sekarang = date('H:i');
$hari_sekarang = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')[date('w')];
$format_waktu = "{$hari_sekarang}, {$tanggal_sekarang} • {$jam_sekarang} WIB";

// ============================================================================
// SUSUN PESAN NOTIFIKASI
// ============================================================================
$nama    = $ticket['nama_pelanggan'] ?? '';
$alamat  = $ticket['alamat'] ?? '';
$keluhan = $ticket['keluhan'] ?? '';
$wa_raw  = $ticket['whatsapp'] ?? '';
$pop_raw = $ticket['pop'] ?? '';

$pesan_pelanggan = '';
$pesan_grup      = '';

if ($normalized_status === 'di proses') {
    $pesan_pelanggan =
        "Yth. Bpk/Ibu *$nama*,\n\n" .
        "🔧 Laporan **gangguan** Anda sedang kami **proses** oleh teknisi *$nama_teknisi*.\n\n" .
        "📋 Jenis gangguan: $keluhan\n\n" .
        "⏳ Mohon **ditunggu**, kami segera menangani masalah Anda.\n\n" .
        "🕐 Update: $format_waktu\n\n" .
        "-- *Customer Service REALNET* --";

    $pesan_grup =
        "❗ **TIKET SEDANG DIPROSES** ❗\n\n" .
        "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
        "🆔 **ID Tiket:** *$id_gangguan*\n" .
        "👷 **Teknisi:** *$nama_teknisi*\n\n" .
        "**DATA PELANGGAN:**\n" .
        "👤 *Nama:* $nama\n" .
        "📍 *Alamat:* $alamat\n\n" .
        "**JENIS GANGGUAN:**\n" .
        "⚠️ *Keluhan:* $keluhan\n\n" .
        "🕐 **Waktu Update:** $format_waktu\n" .
        "━━━━━━━━━━━━━━━━━━━━━━━━━━━━";

} elseif ($normalized_status === 'selesai') {
    $pesan_pelanggan =
        "Yth. Bpk/Ibu *$nama*,\n\n" .
        "✅ Gangguan Anda telah **SELESAI** ditangani oleh teknisi kami.\n\n" .
        "📋 Jenis gangguan yang diperbaiki: $keluhan\n\n" .
        "🎉 **Layanan sudah kembali normal** dan siap digunakan.\n\n" .
        "Jika masih ada **kendala**, silakan hubungi kami kembali.\n\n" .
        "🕐 Waktu selesai: $format_waktu\n\n" .
        "Terima kasih telah mempercayai **REALNET**.\n\n" .
        "-- *Customer Service REALNET* --";

    $pesan_grup =
        "✅ **TIKET BERHASIL DISELESAIKAN** ✅\n\n" .
        "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n" .
        "🆔 **ID Tiket:** *$id_gangguan*\n" .
        "👷 **Teknisi:** *$nama_teknisi*\n\n" .
        "**DATA PELANGGAN:**\n" .
        "👤 *Nama:* $nama\n" .
        "📍 *Alamat:* $alamat\n\n" .
        "**JENIS GANGGUAN YANG DISELESAIKAN:**\n" .
        "✅ *Keluhan:* $keluhan\n\n" .
        "🕐 **Waktu Selesai:** $format_waktu\n" .
        "━━━━━━━━━━━━━━━━━━━━━━━━━━━━";
}

// ============================================================================
// LOG DASAR NOTIF DAN KIRIM
// ============================================================================
file_put_contents(
    "notif_log.txt",
    date("Y-m-d H:i:s") .
    " | ID=$id_gangguan | POP=$pop_raw | WA=$wa_raw | STATUS=$status | KELUHAN=$keluhan\n",
    FILE_APPEND
);

$notif_pelanggan = false;
$notif_grup      = false;

// ========== KIRIM KE PELANGGAN ==========
$wa_norm = normalizeWA($wa_raw);
if ($wa_norm !== null && $pesan_pelanggan !== '') {
    file_put_contents(
        "notif_log.txt",
        "  → CUSTOMER: $wa_norm (API: 7106aa0b...)\n",
        FILE_APPEND
    );
    $notif_pelanggan = sendNotifCustomer($wa_norm, $pesan_pelanggan);
}

// ========== KIRIM KE GRUP POP ==========
$group_id = getGroupPOP($pop_raw);
if ($group_id !== null && $pesan_grup !== '') {
    file_put_contents(
        "notif_log.txt",
        "  → GROUP: $group_id (API: e9c50247...)\n",
        FILE_APPEND
    );
    $notif_grup = sendNotifGroup($group_id, $pesan_grup);
}

// ============================================================================
// RESPONSE AKHIR UNTUK FLUTTER
// ============================================================================
json_response(200, [
    'success'         => true,
    'message'         => 'Status tiket berhasil diperbarui dan notif terkirim.',
    'id_gangguan'     => $id_gangguan,
    'status'          => $status,
    'teknisi'         => $nama_teknisi,
    'keluhan'         => $keluhan,
    'waktu'           => $format_waktu,
    'notif_pelanggan' => $notif_pelanggan,
    'notif_grup'      => $notif_grup
]);

$conn->close();
?>