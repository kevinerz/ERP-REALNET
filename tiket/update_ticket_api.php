<?php
// file: update_ticket_api.php
// DIPERBARUI: Sesuai dengan struktur API key terpisah untuk customer dan group

// Set header JSON dan CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// =====================================================
// 1. KONEKSI DATABASE
// =====================================================
$servername = "localhost";
$username   = "u272457353_kevinsamsung";
$password   = "Admionkevin99";
$dbname     = "u272457353_tiket_helpdesk";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

// =====================================================
// 2. AMBIL DATA DARI JSON BODY
// =====================================================
$data = json_decode(file_get_contents("php://input"), true);

$id       = (int) ($data['id'] ?? 0);
$vlan     = $conn->real_escape_string($data['vlan'] ?? ''); 
$sn       = $conn->real_escape_string($data['sn'] ?? '');
$teknisi  = $conn->real_escape_string($data['teknisi'] ?? '');
$action   = $conn->real_escape_string($data['action'] ?? '');
$maps_url = $conn->real_escape_string($data['maps_url'] ?? ''); 
$status   = $conn->real_escape_string($data['status'] ?? '');

// =====================================================
// 3. TENTUKAN TANGGAL SELESAI
// =====================================================
$tanggal_selesai = ($status === 'selesai') ? date('Y-m-d H:i:s') : null;

// =====================================================
// 4. UPDATE TIKET
// =====================================================
$sql = "UPDATE tiket 
        SET vlan=?, sn=?, teknisi=?, action=?, maps_url=?, status=?, tanggal_selesai=?
        WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssi", $vlan, $sn, $teknisi, $action, $maps_url, $status, $tanggal_selesai, $id);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Gagal update tiket']);
    exit;
}

// =====================================================
// 5. AMBIL DATA TIKET UNTUK NOTIFIKASI
// =====================================================
$q = $conn->prepare("SELECT nama_pelanggan, whatsapp, keluhan, pop, teknisi, maps_url FROM tiket WHERE id=?");
$q->bind_param("i", $id);
$q->execute();
$t = $q->get_result()->fetch_assoc();

// =====================================================
// 6. MAPPING POP → GROUP ID WHATSAPP
// =====================================================
function getGroupIdForPop($pop) {
    $groups = [
        "rajeg"     => "120363424064802149@g.us",
        "kemeri"    => "120363423460663827@g.us",
        "kelapa"    => "120363423157487069@g.us",
        "panggang"  => "120363422971129799@g.us",
        "brebes"    => "120363297070607107@g.us",
        "sengon"    => "120363366069803212@g.us",
        "badakanom"    => "120363409600702809@g.us",
        "mauk"      => "120363405820721170@g.us",
        "grinting"  => "120363399972363054@g.us"
    ];
    return $groups[$pop] ?? "default_group@g.us";
}

// =====================================================
// 7. FUNGSI NOTIFIKASI CUSTOMER (API KEY CUSTOMER)
// API Key: 7106aa0b-0eb0-4673-aaf6-470ccc1f2390
// =====================================================
function sendNotifCustomer($to, $msg) {
    $payload = json_encode([
        "messageType" => "text",
        "to"          => $to,
        "body"        => $msg,
        "delay"       => 3,
        "schedule"    => time() + 3
    ]);
    
    $ch = curl_init("https://api.starsender.online/api/send");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: 7106aa0b-0eb0-4673-aaf6-470ccc1f2390"
        ]
    ]);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    // Log untuk debugging
    file_put_contents("log_update_api.txt",
        "=== CUSTOMER NOTIF ===\n" .
        "To: $to\n" .
        "Message: $msg\n" .
        "Response: $response\n" .
        "Error: $err\n" .
        "API Key: 7106aa0b-0eb0-4673-aaf6-470ccc1f2390\n" .
        "Timestamp: " . date('Y-m-d H:i:s') . "\n\n",
        FILE_APPEND
    );
}

// =====================================================
// 8. FUNGSI NOTIFIKASI GROUP TEKNISI (API KEY GROUP)
// API Key: e9c50247-3b8d-4cd8-924a-024a4d2b3124
// =====================================================
function sendNotifGroup($to, $msg) {
    $payload = json_encode([
        "messageType" => "text",
        "to"          => $to,
        "body"        => $msg,
        "delay"       => 3,
        "schedule"    => time() + 3
    ]);
    
    $ch = curl_init("https://api.starsender.online/api/send");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124"
        ]
    ]);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    
    // Log untuk debugging
    file_put_contents("log_update_api.txt",
        "=== GROUP NOTIF ===\n" .
        "To: $to\n" .
        "Message: $msg\n" .
        "Response: $response\n" .
        "Error: $err\n" .
        "API Key: e9c50247-3b8d-4cd8-924a-024a4d2b3124\n" .
        "Timestamp: " . date('Y-m-d H:i:s') . "\n\n",
        FILE_APPEND
    );
}

// =====================================================
// 9. KIRIM NOTIFIKASI BERDASARKAN STATUS
// =====================================================
if ($t) {
    
    // CASE 1: STATUS DI PROSES
    if ($status === 'di proses') {
        
        // Pesan untuk customer (lebih ramah dan singkat)
        $msgCust = "👋 Halo {$t['nama_pelanggan']},\n\n" .
                   "🔧 Laporan Anda sedang diproses oleh teknisi {$t['teknisi']}.\n" .
                   "⏳ Kami akan menyelesaikannya dalam waktu singkat.\n\n" .
                   "Terima kasih atas kesabaran Anda.";
        
        // Pesan untuk group teknisi (detail dan terstruktur)
        $msgGroup = "🎫 *TIKET #$id - STATUS: DIPROSES*\n\n" .
                    "👤 Nama: {$t['nama_pelanggan']}\n" .
                    "🌐 POP: {$t['pop']}\n" .
                    "📱 WA: {$t['whatsapp']}\n\n" .
                    "🚨 Keluhan:\n{$t['keluhan']}\n\n" .
                    "👨‍🔧 Teknisi: {$t['teknisi']}\n" .
                    "⏱️ Update: " . date('Y-m-d H:i:s') . "\n" .
                    "📌 Status: SEDANG DIPROSES";
        
        // Kirim notif ke customer (API KEY CUSTOMER)
        sendNotifCustomer($t['whatsapp'], $msgCust);
        
        // Kirim notif ke group teknisi (API KEY GROUP)
        sendNotifGroup(getGroupIdForPop($t['pop']), $msgGroup);
    }
    
    // CASE 2: STATUS SELESAI
    elseif ($status === 'selesai') {
        
        // Pesan untuk customer (konfirmasi penyelesaian)
        $msgCust = "👋 Halo {$t['nama_pelanggan']},\n\n" .
                   "✅ Laporan gangguan Anda telah SELESAI diperbaiki.\n" .
                   "🎉 Layanan sudah kembali normal.\n\n" .
                   "Jika masih ada kendala, segera hubungi kami kembali.\n" .
                   "Terima kasih telah menggunakan layanan RealNet.";
        
        // Pesan untuk group teknisi (konfirmasi penyelesaian)
        $msgGroup = "🎫 *TIKET #$id - STATUS: SELESAI* ✅\n\n" .
                    "👤 Nama: {$t['nama_pelanggan']}\n" .
                    "🌐 POP: {$t['pop']}\n" .
                    "📱 WA: {$t['whatsapp']}\n\n" .
                    "🚨 Keluhan:\n{$t['keluhan']}\n\n" .
                    "👨‍🔧 Teknisi: {$t['teknisi']}\n" .
                    "🗺️ Maps: {$t['maps_url']}\n" .
                    "✅ Selesai: " . $tanggal_selesai . "\n" .
                    "📌 Status: SELESAI - GOOD JOB!";
        
        // Kirim notif ke customer (API KEY CUSTOMER)
        sendNotifCustomer($t['whatsapp'], $msgCust);
        
        // Kirim notif ke group teknisi (API KEY GROUP)
        sendNotifGroup(getGroupIdForPop($t['pop']), $msgGroup);
    }
}

// =====================================================
// 10. RESPONSE JSON
// =====================================================
echo json_encode([
    'success' => true, 
    'message' => 'Tiket berhasil diupdate',
    'ticket_id' => $id,
    'status' => $status,
    'timestamp' => date('Y-m-d H:i:s')
]);

// Close koneksi
$stmt->close();
$q->close();
$conn->close();

?>