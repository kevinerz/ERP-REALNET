<?php
// update_ticket.php

// 1. Konfigurasi Database
$servername = "localhost";
$username   = "u272457353_kevinsamsung";
$password   = "Admionkevin99";
$dbname     = "u272457353_tiket_helpdesk";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// 2. Tangkap data POST
if (!isset($_POST['id'])) {
    error_log("Error: No ticket ID found in POST request.");
    header('Location: index.php#accordionTiket');
    exit;
}

$id       = (int) $_POST['id'];
$vlan     = $conn->real_escape_string($_POST['vlan'] ?? '');
$sn       = $conn->real_escape_string($_POST['sn'] ?? '');
$teknisi  = $conn->real_escape_string($_POST['teknisi'] ?? '');
$action   = $conn->real_escape_string($_POST['action'] ?? '');
$maps_url = $conn->real_escape_string($_POST['maps_url'] ?? '');
$status   = $conn->real_escape_string($_POST['status'] ?? '');

// 3. Tentukan tanggal_selesai jika status 'selesai'
$tanggal_selesai = ($status === 'selesai')
    ? date('Y-m-d H:i:s')
    : null;

// 4. Prepare & execute UPDATE
$sql = "UPDATE tiket 
        SET vlan=?, sn=?, teknisi=?, action=?, maps_url=?, status=?, tanggal_selesai=?
        WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param(
    "sssssssi",
    $vlan,
    $sn,
    $teknisi,
    $action,
    $maps_url,
    $status,
    $tanggal_selesai,
    $id
);

if (!$stmt->execute()) {
    error_log("Error updating record: " . $stmt->error);
    // tetap redirect meski gagal
    $stmt->close();
    $conn->close();
    header('Location: index.php#accordionTiket');
    exit;
}

// 5. Kirim notifikasi jika perlu
function getGroupIdForPop($pop) {
    $groups = [
        "rajeg"   => "120363424064802149@g.us",
        "kemeri"  => "6287770366015-1628875457@g.us",
        "panggang" => "120363422971129799@g.us",
        "brebes"  => "120363297070607107@g.us",
        "sengon"  => "120363366069803212@g.us",
        "badakanom"    => "120363409600702809@g.us",
        "mauk"  => "120363405820721170@g.us",
        "grinting"=> "120363399972363054@g.us"
    ];
    return $groups[$pop] ?? "default_group_id@g.us";
}

function sendNotification($recipient, $body) {
    $payload = [
        "messageType" => "text",
        "to"          => $recipient,
        "body"        => $body,
        "delay"       => 10,
        "schedule"    => time() + 10
    ];
    $ch = curl_init('https://api.starsender.online/api/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124'
        ]
    ]);
    curl_exec($ch);
    curl_close($ch);
}

$ticketQ = $conn->prepare(
    "SELECT nama_pelanggan, whatsapp, keluhan, pop, teknisi, maps_url 
     FROM tiket WHERE id=?"
);
$ticketQ->bind_param("i", $id);
$ticketQ->execute();
$ticket   = $ticketQ->get_result()->fetch_assoc();
$ticketQ->close();

// Jika di proses
if ($status === 'di proses' && $ticket) {
    $groupId = getGroupIdForPop($ticket['pop']);
    $msgCust = "👋 Halo {$ticket['nama_pelanggan']},\n\nLaporan Anda sedang diproses oleh teknisi {$ticket['teknisi']}.";
    $msgGrp  = "🎫 Tiket #{$id} sedang DIPROSES:\nNama: {$ticket['nama_pelanggan']}\nPOP: {$ticket['pop']}\nKeluhan: {$ticket['keluhan']}\nTeknisi: {$ticket['teknisi']}";
    sendNotification($ticket['whatsapp'], $msgCust);
    sendNotification($groupId,                $msgGrp);
}

// Jika selesai
if ($status === 'selesai' && $ticket) {
    $groupId = getGroupIdForPop($ticket['pop']);
    $msgCust = "👋 Halo {$ticket['nama_pelanggan']},\n\nLaporan Anda telah SELESAI. Terima kasih.";
    $msgGrp  = "🎫 Tiket #{$id} SELESAI:\nNama: {$ticket['nama_pelanggan']}\nMaps: {$ticket['maps_url']}\nTeknisi: {$ticket['teknisi']}";
    sendNotification($ticket['whatsapp'], $msgCust);
    sendNotification($groupId,                $msgGrp);
}

$stmt->close();
$conn->close();

// 6. Redirect kembali ke accordion
header('Location: /tiket/gangguan_teknisi.php#accordionTiket');
exit;
