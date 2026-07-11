<?php
require_once __DIR__ . '/config/database.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ambil user updater dari session
$last_updated_by = $_SESSION['username'] ?? '';

// ──────────────────────────────────────
// 1. Konfigurasi StarSender
// ──────────────────────────────────────
define('SS_API_URL',   'https://api.starsender.online/api/send');
define('SS_API_KEY',   'e9c50247-3b8d-4cd8-924a-024a4d2b3124');

$popToGroup = [
    "rajeg"    => "6281293958590-1587210420@g.us",
    "kemeri"   => "6287770366015-1628875457@g.us",
    "cianjur"  => "120363399972363054@g.us",
    "mauk"     => "120363419348224895@g.us",
    "brebes"   => "120363297070607107@g.us",
    "badakanom" => "120363409600702809@g.us",
    "sengon"   => "120363366069803212@g.us",
    "grinting" => "120363399972363054@g.us"
];

function sendWaStarSender($to, $body) {
    $pesan = [
        "messageType" => "text",
        "to"          => $to,
        "body"        => $body
    ];
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => SS_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($pesan),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: ' . SS_API_KEY
        ],
    ]);
    $resp = curl_exec($curl);
    $err  = curl_error($curl);
    curl_close($curl);
    if ($err) {
        error_log("StarSender Error: $err");
        return false;
    }
    $data = json_decode($resp, true);
    return isset($data['success']) && $data['success'] === true;
}

// ──────────────────────────────────────
// 2. Koneksi Database
// ──────────────────────────────────────
$mysqlP = getErpDbConnection();
if ($mysqlP->connect_error) {
    die(json_encode(['success'=>false,'message'=>'DB pemasangan gagal: '.$mysqlP->connect_error]));
}
$mysqlU = getErpDbConnection();
if ($mysqlU->connect_error) {
    die(json_encode(['success'=>false,'message'=>'DB umum gagal: '.$mysqlU->connect_error]));
}

header('Content-Type: application/json');

if (!isset($_POST['id'], $_POST['action'])) {
    echo json_encode(['success'=>false,'message'=>'Parameter tidak lengkap']);
    exit;
}

$id     = (int)$_POST['id'];
$action = $_POST['action'];
$response = ['success'=>false,'message'=>'Aksi tidak valid'];

if ($action === 'diproses') {
    // Data input dari form
    $vlan        = $_POST['vlan']     ?? '';
    $sn          = $_POST['sn']       ?? '';
    $odp         = $_POST['odp']      ?? '';

    // Teknisi bisa multi
    $teknisi = '';
    if (isset($_POST['teknisi'])) {
        if (is_array($_POST['teknisi'])) {
            $teknisi = implode(',', array_map('trim', $_POST['teknisi']));
        } else {
            $teknisi = trim($_POST['teknisi']);
        }
    }

    // Ambil ID modem & dropcore (harus int atau null)
    $modem_id    = isset($_POST['modem']) && $_POST['modem'] !== '' ? (int)$_POST['modem'] : null;
    $dropcore_id = isset($_POST['dropcore']) && $_POST['dropcore'] !== '' ? (int)$_POST['dropcore'] : null;

    // Ambil data pelanggan
    $stmt = $mysqlP->prepare("SELECT nama, telp, pop FROM pelanggan_instalasi WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($nama, $telp, $pop);
    $stmt->fetch();
    $stmt->close();

    // Normalisasi pop & group
    $pop_key = strtolower(trim($pop));
    $groupId = $popToGroup[$pop_key] ?? null;
    $target  = $groupId ?: $telp;

    // Update pemasangan
    $u = $mysqlP->prepare(
        "UPDATE pelanggan_instalasi 
         SET vlan=?, sn=?, odp=?, teknisi=?, modem=?, dropcore=?, status='di proses', last_updated_by=? 
         WHERE id=?"
    );
    $u->bind_param("sssssssi", $vlan, $sn, $odp, $teknisi, $modem_id, $dropcore_id, $last_updated_by, $id);

    $ok1 = $u->execute();
    if (!$ok1) {
        $response = ['success'=>false,'message'=>'Gagal update data: ' . $u->error];
        echo json_encode($response);
        $u->close();
        $mysqlP->close();
        $mysqlU->close();
        exit;
    }
    $u->close();

    // Update status modem (jika ada)
    $ok2 = true; $ok3 = true;
    if ($modem_id) {
        $m = $mysqlU->prepare("UPDATE jaringan_modem SET status='dipasang',lokasi_penyimpanan=? WHERE id_modem=?");
        $m->bind_param("si", $nama, $modem_id);
        $ok2 = $m->execute();
        $m->close();
    }
    if ($dropcore_id) {
        $d = $mysqlU->prepare("UPDATE jaringan_kabel_dropcore SET status='digunakan',lokasi_penyimpanan=? WHERE id_kabel_dropcore=?");
        $d->bind_param("si", $nama, $dropcore_id);
        $ok3 = $d->execute();
        $d->close();
    }

    if ($ok1 && $ok2 && $ok3) {
        $body = "🚀 *Update Pemasangan*\nPOP: *".ucfirst($pop)."*\nPelanggan: *$nama*\nStatus: *Sedang Diproses*";
        $sent = sendWaStarSender($target, $body);
        $response = [
            'success'=>true,
            'message'=> 'Data diperbarui. ' . ($sent?'WA terkirim':'Gagal kirim WA'),
            'nama'=>$nama
        ];
    } else {
        $response = ['success'=>false,'message'=>'Gagal update data'];
    }

} elseif ($action === 'selesai') {
    $s = $mysqlP->prepare("UPDATE pelanggan_instalasi SET status='selesai' WHERE id=?");
    $s->bind_param("i", $id);
    $ok = $s->execute();
    $s->close();

    if ($ok) {
        $s2 = $mysqlP->prepare("SELECT nama, telp, pop FROM pelanggan_instalasi WHERE id=?");
        $s2->bind_param("i", $id);
        $s2->execute();
        $s2->bind_result($nama, $telp, $pop);
        $s2->fetch();
        $s2->close();

        $pop_key = strtolower(trim($pop));
        $groupId = $popToGroup[$pop_key] ?? null;
        $target  = $groupId ?: $telp;

        $body = "✅ *Pemasangan Selesai*\nPOP: *".ucfirst($pop)."*\nPelanggan: *$nama*";
        $sent = sendWaStarSender($target, $body);
        $response = [
            'success'=>true,
            'message'=> 'Status selesai. ' . ($sent?'WA terkirim':'Gagal kirim WA'),
            'nama'=>$nama
        ];
    } else {
        $response = ['success'=>false,'message'=>'Gagal ubah status'];
    }
}

echo json_encode($response);

$mysqlP->close();
$mysqlU->close();
