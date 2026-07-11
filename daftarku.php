<?php
// daftar.php - Form Pemasangan Realnet (UI: Bright & Happy | Google Maps + Coordinate Search)
// UPDATED: API Key Terpisah untuk Customer & Group

declare(strict_types=1);
session_start();

require_once __DIR__ . "/config.php";

date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', '0');
error_reporting(E_ALL);

// =======================
// UTIL
// =======================
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function db_connect(string $host, string $user, string $pass, string $db, string $label): mysqli {
    $conn = new mysqli($host, $user, $pass, $db);
    if ($conn->connect_error) {
        throw new Exception("Koneksi database {$label} gagal: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

function getGroupIdForPop(string $popName): ?string {
    // Sesuaikan ID Group WA Anda di sini
    $groups = [
        "rajeg"     => "6281293958590-1587210420@g.us",
        "muncung"   => "120363424548647899@g.us",
        "kelapa"    => "120363423157487069@g.us",
        "kemeri"    => "6287770366015-1628875457@g.us",
        "panggang"  => "120363405472722137@g.us",
        "mauk"      => "120363419348224895@g.us",
        "brebes"    => "120363297070607107@g.us",
        "sengon"    => "120363366069803212@g.us",
        "badakanom"    => "120363409600702809@g.us",
        "grinting"  => "120363399972363054@g.us"
    ];
    return $groups[strtolower(trim($popName))] ?? null;
}

// =====================================================
// FUNGSI NOTIFIKASI CUSTOMER - API KEY CUSTOMER
// API Key: 7106aa0b-0eb0-4673-aaf6-470ccc1f2390
// =====================================================
function sendWhatsAppCustomer(string $to, string $body, string $logFile = 'log_wa_customer.txt'): bool {
    if (!defined('WA_API_URL') || !defined('WA_API_TOKEN_CUSTOMER') || empty(WA_API_URL) || empty(WA_API_TOKEN_CUSTOMER)) {
        error_log("WA API URL/Token Customer belum diset di config.php.");
        return false;
    }

    $curl = curl_init(WA_API_URL);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "messageType" => "text",
            "to"          => $to,
            "body"        => $body
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: ' . WA_API_TOKEN_CUSTOMER
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $resp = curl_exec($curl);
    $err  = curl_error($curl);
    $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        error_log("cURL Error WA Customer to {$to}: {$err}");
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR to {$to}: {$err} (API: 7106aa0b...)\n", FILE_APPEND);
        return false;
    }

    file_put_contents($logFile, date('Y-m-d H:i:s') . " - HTTP {$code} to {$to}. RESP: {$resp} (API: 7106aa0b...)\n", FILE_APPEND);
    return ($code >= 200 && $code < 300);
}

// =====================================================
// FUNGSI NOTIFIKASI GROUP - API KEY GROUP
// API Key: e9c50247-3b8d-4cd8-924a-024a4d2b3124
// =====================================================
function sendWhatsAppGroup(string $to, string $body, string $logFile = 'log_wa_group.txt'): bool {
    if (!defined('WA_API_URL') || !defined('WA_API_TOKEN_GROUP') || empty(WA_API_URL) || empty(WA_API_TOKEN_GROUP)) {
        error_log("WA API URL/Token Group belum diset di config.php.");
        return false;
    }

    $curl = curl_init(WA_API_URL);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "messageType" => "text",
            "to"          => $to,
            "body"        => $body
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: ' . WA_API_TOKEN_GROUP
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $resp = curl_exec($curl);
    $err  = curl_error($curl);
    $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        error_log("cURL Error WA Group to {$to}: {$err}");
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR to {$to}: {$err} (API: e9c50247...)\n", FILE_APPEND);
        return false;
    }

    file_put_contents($logFile, date('Y-m-d H:i:s') . " - HTTP {$code} to {$to}. RESP: {$resp} (API: e9c50247...)\n", FILE_APPEND);
    return ($code >= 200 && $code < 300);
}

// =====================================================
// FUNGSI NOTIFIKASI MARKETING - API KEY CUSTOMER
// (Marketing adalah individual juga, bukan group)
// API Key: 7106aa0b-0eb0-4673-aaf6-470ccc1f2390
// =====================================================
function sendWhatsAppMarketing(string $to, string $body, string $logFile = 'log_wa_marketing.txt'): bool {
    if (!defined('WA_API_URL') || !defined('WA_API_TOKEN_CUSTOMER') || empty(WA_API_URL) || empty(WA_API_TOKEN_CUSTOMER)) {
        error_log("WA API URL/Token Customer belum diset di config.php.");
        return false;
    }

    $curl = curl_init(WA_API_URL);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "messageType" => "text",
            "to"          => $to,
            "body"        => $body
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: ' . WA_API_TOKEN_CUSTOMER
        ],
        CURLOPT_TIMEOUT        => 15,
    ]);

    $resp = curl_exec($curl);
    $err  = curl_error($curl);
    $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($err) {
        error_log("cURL Error WA Marketing to {$to}: {$err}");
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR to {$to}: {$err} (API: 7106aa0b...)\n", FILE_APPEND);
        return false;
    }

    file_put_contents($logFile, date('Y-m-d H:i:s') . " - HTTP {$code} to {$to}. RESP: {$resp} (API: 7106aa0b...)\n", FILE_APPEND);
    return ($code >= 200 && $code < 300);
}

function normalizeWa(string $raw): string {
    $telp = preg_replace('/[^0-9\+]/', '', trim($raw));
    $telp = str_replace('+', '', $telp);
    $telp = preg_replace('/[^0-9]/', '', $telp);

    if ($telp === '') return '';

    if (substr($telp, 0, 1) === '0') {
        return '62' . substr($telp, 1);
    }
    if (substr($telp, 0, 2) !== '62') {
        return '62' . $telp;
    }
    return $telp;
}

function parseLatLng(string $value): array {
    $v = trim($value);
    if (!preg_match('/^-?\d{1,2}(\.\d+)?\s*,\s*-?\d{1,3}(\.\d+)?$/', $v)) {
        return [false, 0.0, 0.0];
    }
    [$lat, $lng] = array_map('trim', explode(',', $v, 2));
    $latF = (float)$lat;
    $lngF = (float)$lng;

    if ($latF < -90 || $latF > 90 || $lngF < -180 || $lngF > 180) {
        return [false, 0.0, 0.0];
    }
    return [true, $latF, $lngF];
}

function rupiah($n): string {
    return "Rp" . number_format((float)$n, 0, ',', '.');
}

// =======================
// CSRF
// =======================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrfToken = $_SESSION['csrf_token'];

// =======================
// INIT
// =======================
$connPemasangan = null;
$connUmumData   = null;
$connMarketing  = null;

$alertType = '';
$alertMessage = '';

$pops = [];
$pakets = [];
$marketings = [];

$form = [
    'nama' => '',
    'paket' => '',
    'pop' => '',
    'url_maps' => '',
    'alamat' => '',
    'ktp' => '',
    'telp' => '',
    'email' => '',
    'marketing' => ''
];

// =======================
// CONNECT + LOAD MASTER
// =======================
try {
    $connPemasangan = db_connect(DB_HOST, DB_USER_PEMASANGAN, DB_PASS_PEMASANGAN, DB_NAME_PEMASANGAN, "pemasangan");
    $connUmumData   = db_connect(DB_HOST, DB_USER_UMUMDATA,   DB_PASS_UMUMDATA,   DB_NAME_UMUMDATA,   "umumdata");
    $connMarketing  = db_connect(DB_HOST, DB_USER_MARKETING,  DB_PASS_MARKETING,  DB_NAME_MARKETING,  "marketing");

    // POP
    $resPop = $connPemasangan->query("SELECT id, name FROM jaringan_pop ORDER BY name ASC");
    if ($resPop) {
        while ($row = $resPop->fetch_assoc()) $pops[] = $row;
    }

    // Paket
    $resPaket = $connUmumData->query("SELECT id_paket, nama_paket, harga, kecepatan FROM jaringan_paket ORDER BY nama_paket ASC");
    if ($resPaket) {
        while ($row = $resPaket->fetch_assoc()) $pakets[] = $row;
    }

    // Marketing (mitra)
    $resM = $connMarketing->query("SELECT id, nama, wa FROM mitra ORDER BY nama ASC");
    if ($resM) {
        while ($row = $resM->fetch_assoc()) $marketings[] = $row;
    }
} catch (Exception $e) {
    error_log("INIT ERROR: " . $e->getMessage());
    $alertType = 'danger';
    $alertMessage = "Terjadi kesalahan koneksi. Silakan coba lagi nanti.";
}

// =======================
// HANDLE POST
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $connPemasangan && $connUmumData && $connMarketing) {
    foreach ($form as $k => $v) {
        $form[$k] = (string)($_POST[$k] ?? '');
    }

    file_put_contents('log_form_submit.txt', "\n==== FORM SUBMIT " . date('Y-m-d H:i:s') . " ====\n" . print_r($_POST, true), FILE_APPEND);

    $csrf = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        $alertType = 'danger';
        $alertMessage = "Sesi form tidak valid. Silakan refresh halaman dan coba lagi.";
    }

    $required = ['nama','paket','pop','url_maps','alamat','ktp','telp','email','marketing'];
    if (empty($alertMessage)) {
        foreach ($required as $f) {
            if (!isset($_POST[$f])) {
                $alertType = 'danger';
                $alertMessage = "Field wajib tidak lengkap.";
                break;
            }
            if ($f === 'marketing') {
                if ($_POST[$f] === '') {
                    $alertType = 'danger';
                    $alertMessage = "Field Marketing wajib dipilih.";
                    break;
                }
            } else {
                if (trim((string)$_POST[$f]) === '') {
                    $alertType = 'danger';
                    $alertMessage = "Field <strong>" . h(ucfirst(str_replace('_',' ',$f))) . "</strong> wajib diisi.";
                    break;
                }
            }
        }
    }

    $nama         = trim((string)($_POST['nama'] ?? ''));
    $idPaket      = (int)($_POST['paket'] ?? 0);
    $popId        = (int)($_POST['pop'] ?? 0);
    $koordinat    = trim((string)($_POST['url_maps'] ?? '')); 
    $alamat       = trim((string)($_POST['alamat'] ?? ''));
    $ktp          = preg_replace('/[^0-9]/', '', (string)($_POST['ktp'] ?? ''));
    $telpRaw      = trim((string)($_POST['telp'] ?? ''));
    $email        = trim((string)($_POST['email'] ?? ''));
    $marketingId = (int)($_POST['marketing'] ?? 0);

    if (empty($alertMessage)) {
        if ($idPaket <= 0) {
            $alertType = 'danger'; $alertMessage = "Paket wajib dipilih.";
        } elseif ($popId <= 0) {
            $alertType = 'danger'; $alertMessage = "POP wajib dipilih.";
        }
    }

    $lat = 0.0; $lng = 0.0;
    if (empty($alertMessage)) {
        [$okLL, $lat, $lng] = parseLatLng($koordinat);
        if (!$okLL) {
            $alertType = 'danger';
            $alertMessage = "Koordinat tidak valid. Gunakan tombol <strong>Pilih Titik</strong>.";
        }
    }

    $telp = '';
    if (empty($alertMessage)) {
        $telp = normalizeWa($telpRaw);
        if (strlen($telp) < 10) {
            $alertType = 'danger';
            $alertMessage = "Nomor WhatsApp tidak valid.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alertType = 'danger';
            $alertMessage = "Format email tidak valid.";
        } elseif (strlen($ktp) < 10) {
            $alertType = 'danger';
            $alertMessage = "Nomor KTP terlalu pendek / tidak valid.";
        }
    }

    $namaMarketing = "Tidak Ada";
    $waMarketing   = null;
    if (empty($alertMessage) && $marketingId > 0) {
        $stmt = $connMarketing->prepare("SELECT nama, wa FROM mitra WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $marketingId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $namaMarketing = $row['nama'] ?? '';
            $waMarketing   = $row['wa'] ?? null;
            if ($namaMarketing === '') {
                $alertType = 'danger'; $alertMessage = "Marketing tidak ditemukan.";
            } else {
                if (!empty($waMarketing)) {
                    $waMarketing = normalizeWa((string)$waMarketing);
                    if (strlen((string)$waMarketing) < 10) $waMarketing = null;
                }
            }
        }
    }

    $namaPop = '';
    if (empty($alertMessage)) {
        $stmt = $connPemasangan->prepare("SELECT name FROM jaringan_pop WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $popId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $namaPop = $row['name'] ?? '';
            if ($namaPop === '') {
                $alertType = 'danger'; $alertMessage = "POP tidak ditemukan.";
            }
        }
    }

    $pemasanganId = null;
    if (empty($alertMessage)) {
        $connPemasangan->begin_transaction();
        try {
            $tanggalDB = date('Y-m-d');
            $statusDB  = 'belum diproses'; 

            $vlan = ''; $sn = ''; $odp = ''; $teknisi = '';

            $stmt = $connPemasangan->prepare("
                INSERT INTO pelanggan_instalasi
                    (nama, paket, vlan, sn, pop, odp, url_maps, teknisi, alamat, ktp, telp, email, marketing, tanggal, status)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) throw new Exception("Prepare insert gagal: " . $connPemasangan->error);

            $stmt->bind_param("sisssssssssssss", $nama, $idPaket, $vlan, $sn, $namaPop, $odp, $koordinat, $teknisi, $alamat, $ktp, $telp, $email, $namaMarketing, $tanggalDB, $statusDB);

            if (!$stmt->execute()) throw new Exception("Execute insert gagal: " . $stmt->error);

            $pemasanganId = $connPemasangan->insert_id;
            $stmt->close();
            $connPemasangan->commit();
        } catch (Exception $e) {
            $connPemasangan->rollback();
            error_log("INSERT ERROR: " . $e->getMessage());
            $alertType = 'danger';
            $alertMessage = "Gagal menyimpan data. Silakan coba lagi.";
            $pemasanganId = null;
        }
    }

    if (empty($alertMessage) && $pemasanganId) {
        $namaPaket = '-'; $hargaPaket = 0; $kecepatan = '-';
        $stmtP = $connUmumData->prepare("SELECT nama_paket, harga, kecepatan FROM jaringan_paket WHERE id_paket = ?");
        if ($stmtP) {
            $stmtP->bind_param("i", $idPaket);
            $stmtP->execute();
            $det = $stmtP->get_result()->fetch_assoc();
            $stmtP->close();
            $namaPaket  = $det['nama_paket'] ?? '-';
            $hargaPaket = $det['harga'] ?? 0;
            $kecepatan  = $det['kecepatan'] ?? '-';
        }

        $tanggalSekarang = date('d/m/Y H:i');
        $mapsLink = "https://www.google.com/maps?q=" . number_format($lat, 6, '.', '') . "," . number_format($lng, 6, '.', '');
        $groupId = getGroupIdForPop($namaPop);
         
        $waPelangganDirect = normalizeWa($telpRaw);

        // =====================================================
        // PESAN INTERNAL GRUP - API KEY GROUP
        // =====================================================
        $bodyInt = "🚀 *NEW INSTALL REQUEST* 🚀\n"
                 . "══════════════════\n"
                 . "📅 *Tanggal :* {$tanggalSekarang}\n"
                 . "🆔 *ID Tiket :* #{$pemasanganId}\n"
                 . "🏢 *Area POP:* {$namaPop}\n\n"

                 . "👤 *DATA PELANGGAN*\n"
                 . "━━━━━━━━━━━━━━━━━━\n"
                 . "🏷️ *Nama :* {$nama}\n"
                 . "💳 *NIK/KTP :* {$ktp}\n"
                 . "📧 *Email :* {$email}\n"
                 . "📱 *WhatsApp :* wa.me/{$waPelangganDirect}\n"
                 . "🏠 *Alamat :* {$alamat}\n\n"

                 . "📦 *PAKET DIPILIH*\n"
                 . "━━━━━━━━━━━━━━━━━━\n"
                 . "🚀 *Paket :* {$namaPaket}\n"
                 . "⚡ *Speed :* {$kecepatan}\n"
                 . "💰 *Harga :* " . rupiah($hargaPaket) . "\n\n"

                 . "📍 *LOKASI PASANG*\n"
                 . "━━━━━━━━━━━━━━━━━━\n"
                 . "🌐 *Maps :* {$mapsLink}\n"
                 . "📌 *Coord :* {$koordinat}\n\n"

                 . "🤝 *MARKETING / SALES*\n"
                 . "━━━━━━━━━━━━━━━━━━\n"
                 . "🧑‍💼 *Nama :* {$namaMarketing}\n";

        if (!empty($waMarketing)) {
            $bodyInt .= "📱 *Kontak :* wa.me/{$waMarketing}\n";
        }

        $bodyInt .= "\n⚡ _Mohon segera diproses & jadwalkan survei._";

        // =====================================================
        // KIRIM NOTIF GROUP - API KEY GROUP (e9c50247...)
        // =====================================================
        if ($groupId) {
            sendWhatsAppGroup($groupId, $bodyInt, 'log_internal_notification.txt');
        }

        // =====================================================
        // PESAN CUSTOMER - API KEY CUSTOMER
        // =====================================================
        $bodyCust = "👋 Halo {$nama}!\n\n" .
                    "Terima kasih telah mendaftar di Realnet.\n\n" .
                    "✅ Permintaan paket *{$namaPaket}* ({$kecepatan}) sedang kami proses.\n" .
                    "📍 Lokasi: {$mapsLink}\n\n" .
                    "Tim kami akan segera menghubungi Anda.\n\n" .
                    "Selamat bergabung! 🎉";

        // =====================================================
        // KIRIM NOTIF CUSTOMER - API KEY CUSTOMER (7106aa0b...)
        // =====================================================
        sendWhatsAppCustomer($telp, $bodyCust, 'log_customer_notification.txt');

        // =====================================================
        // PESAN MARKETING - API KEY CUSTOMER
        // =====================================================
        if (!empty($waMarketing)) {
            $bodyMarketing = "📢 Halo {$namaMarketing}!\n\n" .
                            "👏 Pelanggan referensi Anda telah mendaftar!\n\n" .
                            "👤 *Nama :* {$nama}\n" .
                            "📦 *Paket :* {$namaPaket}\n" .
                            "🌐 *Lokasi :* {$mapsLink}\n\n" .
                            "Terima kasih atas rekomendasi Anda! 🤝";

            // =====================================================
            // KIRIM NOTIF MARKETING - API KEY CUSTOMER (7106aa0b...)
            // =====================================================
            sendWhatsAppMarketing($waMarketing, $bodyMarketing, 'log_marketing_notification.txt');
        }

        $alertType = 'success';
        $alertMessage = "Pendaftaran berhasil! Terima kasih telah memilih Realnet.";
        foreach ($form as $k => $v) $form[$k] = '';
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
        $csrfToken = $_SESSION['csrf_token'];
    }
}

if ($connPemasangan) $connPemasangan->close();
if ($connUmumData)   $connUmumData->close();
if ($connMarketing)  $connMarketing->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Registrasi Realnet</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
   
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  
  <style>
    :root {
      /* Palette: Bright & Happy */
      --primary: #4f46e5;
      --secondary: #0ea5e9;
      --accent: #ec4899;
      --success: #10b981;
      
      --gradient-main: linear-gradient(135deg, #0ea5e9 0%, #a855f7 50%, #ec4899 100%);
      
      --bg-body: #f8fafc;
      --card-bg: #ffffff;
      --text-main: #1e293b;
      --text-muted: #64748b;
      --border-input: #e2e8f0;
      --input-focus-ring: rgba(14, 165, 233, 0.25);
      
      --radius-l: 24px;
      --radius-m: 16px;
      --shadow-soft: 0 20px 40px -12px rgba(0, 0, 0, 0.08);
      --shadow-hover: 0 25px 50px -12px rgba(14, 165, 233, 0.15);
    }

    body {
      font-family: 'Poppins', sans-serif;
      background-color: var(--bg-body);
      background-image: radial-gradient(at 0% 0%, hsla(253,16%,7%,0) 0, hsla(253,16%,7%,0) 50%), 
                        radial-gradient(at 50% 0%, hsla(225,39%,30%,0) 0, hsla(225,39%,30%,0) 50%), 
                        radial-gradient(at 100% 0%, hsla(339,49%,30%,0) 0, hsla(339,49%,30%,0) 50%);
      background-size: cover;
      color: var(--text-main);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 15px;
      background: radial-gradient(circle at top left, #e0f2fe, transparent 40%),
                  radial-gradient(circle at bottom right, #fce7f3, transparent 40%),
                  #f8fafc;
    }

    .container-custom {
      max-width: 900px;
      width: 100%;
      position: relative;
    }

    /* Card Styling */
    .card-main {
      background: var(--card-bg);
      border: 1px solid white;
      border-radius: var(--radius-l);
      box-shadow: var(--shadow-soft);
      padding: 0;
      overflow: hidden;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .card-main:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-hover);
    }

    /* Header Decorative */
    .header-deco {
      background: var(--gradient-main);
      padding: 40px 40px 50px;
      color: white;
      text-align: center;
      position: relative;
      border-bottom-left-radius: 50% 20px;
      border-bottom-right-radius: 50% 20px;
    }
    .header-deco h1 {
      font-weight: 800;
      letter-spacing: -0.5px;
      margin-bottom: 8px;
      text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .header-deco p {
      opacity: 0.9;
      font-size: 1.05rem;
      font-weight: 400;
    }

    .form-wrapper {
      padding: 40px;
      margin-top: -30px; /* Pull up to overlap header */
    }

    /* Form Elements */
    .form-label {
      font-weight: 600;
      color: #334155;
      font-size: 0.9rem;
      margin-bottom: 8px;
    }
    .form-label i {
      color: var(--secondary);
      margin-right: 6px;
      font-size: 1.1em;
    }

    .form-control, .form-select {
      background-color: #f1f5f9;
      border: 2px solid transparent;
      border-radius: var(--radius-m);
      padding: 12px 16px;
      color: var(--text-main);
      font-weight: 500;
      transition: all 0.2s ease;
    }
    .form-control::placeholder {
      color: #94a3b8;
      font-weight: 400;
    }
    .form-control:focus, .form-select:focus {
      background-color: #ffffff;
      border-color: var(--secondary);
      box-shadow: 0 0 0 4px var(--input-focus-ring);
    }

    /* Buttons */
    .btn-submit {
      background: var(--gradient-main);
      border: none;
      color: white;
      font-weight: 700;
      padding: 14px 24px;
      border-radius: var(--radius-m);
      width: 100%;
      font-size: 1.1rem;
      box-shadow: 0 10px 20px -5px rgba(236, 72, 153, 0.4);
      transition: all 0.3s ease;
    }
    .btn-submit:hover {
      filter: brightness(110%);
      transform: translateY(-2px);
      box-shadow: 0 15px 30px -5px rgba(236, 72, 153, 0.5);
    }
    
    .btn-reset {
      background: #f1f5f9;
      color: var(--text-muted);
      font-weight: 600;
      border: none;
      padding: 12px 20px;
      border-radius: var(--radius-m);
      transition: all 0.2s;
    }
    .btn-reset:hover {
      background: #e2e8f0;
      color: var(--text-main);
    }

    .btn-map {
      background: #ffffff;
      border: 2px solid #e2e8f0;
      color: var(--secondary);
      font-weight: 600;
      border-top-right-radius: var(--radius-m) !important;
      border-bottom-right-radius: var(--radius-m) !important;
    }
    .btn-map:hover {
      background: #f8fafc;
      border-color: var(--secondary);
    }

    /* Grid Layout */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }
    @media(max-width: 768px) {
      .form-grid { grid-template-columns: 1fr; gap: 16px; }
      .form-wrapper { padding: 24px; }
      .header-deco { padding: 30px 20px 40px; }
    }

    /* Alerts */
    .alert-custom {
      border-radius: var(--radius-m);
      border: none;
      font-weight: 500;
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }
    .alert-danger { background-color: #fef2f2; color: #dc2626; border-left: 5px solid #dc2626; }
    .alert-success { background-color: #f0fdf4; color: #16a34a; border-left: 5px solid #16a34a; }
    
    .section-divider {
      height: 1px;
      background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
      margin: 30px 0;
    }

    /* Modal Map */
    .modal-content {
      border-radius: var(--radius-l);
      border: none;
      box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
    }
    .modal-header {
      background: #f8fafc;
      border-bottom: 1px solid #e2e8f0;
      border-top-left-radius: var(--radius-l);
      border-top-right-radius: var(--radius-l);
    }
    .modal-title { font-weight: 700; color: var(--text-main); }
    #mapPick { height: 400px; width: 100%; border-radius: var(--radius-m); }
    
    .mini-help { font-size: 0.8rem; color: #94a3b8; margin-top: 4px; display: block; }

    /* Google Maps Autocomplete override */
    .pac-container {
        z-index: 9999 !important; /* Agar muncul di atas modal */
        border-radius: 8px;
        margin-top: 5px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>
<body>

<div class="container-custom">
  <?php if (!empty($alertMessage)): ?>
    <div class="alert alert-custom alert-<?= h($alertType) ?> mb-4 shadow-sm">
      <i class="bi <?= ($alertType==='success') ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> fs-4"></i>
      <div><?= $alertMessage ?></div>
    </div>
  <?php endif; ?>

  <div class="card-main">
    <div class="header-deco">
      <h1>🚀 Pasang Realnet Sekarang</h1>
      <p>Internet Cepat, Keluarga Bahagia. Isi data di bawah untuk berlangganan.</p>
    </div>

    <div class="form-wrapper">
      <form method="POST" id="pemasanganForm" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">

        <div class="form-grid">
          <div>
            <div class="mb-3">
              <label class="form-label" for="nama"><i class="bi bi-person-fill"></i> Nama Lengkap</label>
              <input type="text" class="form-control" id="nama" name="nama" required placeholder="Contoh: Budi Santoso" value="<?= h($form['nama']) ?>">
            </div>

            <div class="mb-3">
              <label class="form-label" for="telp"><i class="bi bi-whatsapp"></i> WhatsApp Aktif</label>
              <input type="tel" class="form-control" id="telp" name="telp" required placeholder="0812xxxxxxxx" value="<?= h($form['telp']) ?>">
              <span class="mini-help">Nomor ini akan menerima notifikasi pendaftaran.</span>
            </div>

            <div class="mb-3">
              <label class="form-label" for="email"><i class="bi bi-envelope-at-fill"></i> Alamat Email</label>
              <input type="email" class="form-control" id="email" name="email" required placeholder="email@contoh.com" value="<?= h($form['email']) ?>">
            </div>

            <div class="mb-3">
              <label class="form-label" for="ktp"><i class="bi bi-card-heading"></i> Nomor KTP / NIK</label>
              <input type="number" class="form-control" id="ktp" name="ktp" required placeholder="16 digit NIK" value="<?= h($form['ktp']) ?>">
            </div>
          </div>

          <div>
            <div class="mb-3">
              <label class="form-label" for="paket"><i class="bi bi-rocket-takeoff-fill"></i> Pilihan Paket</label>
              <select class="form-select" id="paket" name="paket" required>
                <option value="" disabled <?= ($form['paket']==='')?'selected':''; ?>>-- Pilih Kecepatan --</option>
                <?php foreach ($pakets as $p): ?>
                  <option value="<?= (int)$p['id_paket'] ?>" <?= ($form['paket'] !== '' && (int)$form['paket'] === (int)$p['id_paket']) ? 'selected' : '' ?>>
                    <?= h($p['nama_paket']) ?> (<?= h($p['kecepatan']) ?>) — <?= h(rupiah($p['harga'])) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label" for="pop"><i class="bi bi-hdd-network-fill"></i> Area Terdekat (POP)</label>
              <select class="form-select" id="pop" name="pop" required>
                <option value="" disabled <?= ($form['pop']==='')?'selected':''; ?>>-- Pilih Wilayah --</option>
                <?php foreach ($pops as $p): ?>
                  <option value="<?= (int)$p['id'] ?>" <?= ($form['pop'] !== '' && (int)$form['pop'] === (int)$p['id']) ? 'selected' : '' ?>>
                    <?= h($p['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label" for="marketing"><i class="bi bi-person-heart"></i> Sales / Marketing</label>
              <select class="form-select" id="marketing" name="marketing" required>
                <option value="" disabled <?= ($form['marketing']==='')?'selected':''; ?>>-- Pilih Sales --</option>
                <option value="0" <?= ($form['marketing']==='0')?'selected':''; ?>>Daftar Sendiri (Tanpa Sales)</option>
                <?php foreach ($marketings as $m): ?>
                  <option value="<?= (int)$m['id'] ?>" <?= ($form['marketing'] !== '' && (int)$form['marketing'] === (int)$m['id']) ? 'selected' : '' ?>>
                    <?= h($m['nama']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label" for="url_maps"><i class="bi bi-geo-alt-fill"></i> Titik Lokasi Pemasangan</label>
              <div class="input-group">
                <input type="text" class="form-control" id="url_maps" name="url_maps" required readonly placeholder="Klik tombol di samping 👉" value="<?= h($form['url_maps']) ?>" style="background: #f8fafc;">
                <button class="btn btn-map" type="button" data-bs-toggle="modal" data-bs-target="#mapModal">
                  <i class="bi bi-map-fill"></i> Buka Peta
                </button>
              </div>
              <span class="mini-help">Wajib isi agar teknisi mudah menemukan rumah Anda.</span>
            </div>
          </div>
        </div>

        <div class="section-divider"></div>

        <div class="mb-4">
          <label class="form-label" for="alamat"><i class="bi bi-house-door-fill"></i> Alamat Lengkap</label>
          <textarea class="form-control" id="alamat" name="alamat" rows="3" required placeholder="Jalan, RT/RW, No. Rumah, Kelurahan, Kecamatan, Patokan..."><?= h($form['alamat']) ?></textarea>
        </div>

        <div class="d-flex gap-3 align-items-center">
          <button type="submit" class="btn btn-submit">
            Kirim Pendaftaran <i class="bi bi-send-fill ms-2"></i>
          </button>
          <button type="button" class="btn btn-reset" id="btnReset" title="Bersihkan Form">
            <i class="bi bi-arrow-counterclockwise"></i>
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <div class="text-center mt-4 text-muted small">
    © <?= date('Y') ?> PT. Real Data Solusindo. All rights reserved.<br>
    <span style="opacity:0.6">Serving with ❤️ and High Speed Internet.</span>
  </div>
</div>

<div class="modal fade" id="mapModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-geo-alt-fill text-danger me-2"></i>Tentukan Titik Rumah</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <input type="text" id="mapSearch" class="form-control" placeholder="Cari alamat atau koordinat (cth: -6.200, 106.816)...">
        </div>
        
        <div id="mapPick"></div>
        
        <div class="mt-3 d-flex justify-content-between align-items-center bg-light p-2 rounded">
          <small class="text-muted">Koordinat: <span id="coordPreview" class="fw-bold text-dark">-</span></small>
          <button type="button" class="btn btn-success fw-bold px-4" id="btnUseLocation" data-bs-dismiss="modal" disabled>
            <i class="bi bi-check-lg"></i> Gunakan Lokasi Ini
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDH4s_S0mOhLisPV_3e3SRXai11dZwA7dY&libraries=places&loading=async"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('pemasanganForm');
  const btnReset = document.getElementById('btnReset');

  // Reset logic
  btnReset.addEventListener('click', function(){
    form.reset();
    document.getElementById('paket').selectedIndex = 0;
    document.getElementById('pop').selectedIndex = 0;
    document.getElementById('marketing').selectedIndex = 0;
    document.getElementById('url_maps').value = '';
  });

  // GOOGLE MAPS LOGIC
  let map, marker, picked = null;
  const urlMapsInput = document.getElementById('url_maps');
  const coordPreview = document.getElementById('coordPreview');
  const btnUse = document.getElementById('btnUseLocation');
  const modalEl = document.getElementById('mapModal');
  const mapDiv = document.getElementById('mapPick');
  const inputSearch = document.getElementById('mapSearch');

  // Fungsi untuk set marker saat user klik atau search
  function setLocation(lat, lng) {
    const latFixed = parseFloat(lat).toFixed(6);
    const lngFixed = parseFloat(lng).toFixed(6);
    picked = { lat: latFixed, lng: lngFixed };
    
    coordPreview.textContent = latFixed + ', ' + lngFixed;
    btnUse.disabled = false;
    
    const pos = { lat: parseFloat(lat), lng: parseFloat(lng) };
    
    if (marker) {
      marker.setPosition(pos);
    } else {
      marker = new google.maps.Marker({
        position: pos,
        map: map,
        animation: google.maps.Animation.DROP
      });
    }
    map.panTo(pos);
  }

  function initMap() {
    // Default location: Jakarta (Silakan ganti jika perlu)
    const defaultCenter = { lat: -6.200000, lng: 106.816666 }; 
    
    map = new google.maps.Map(mapDiv, {
      center: defaultCenter,
      zoom: 13,
      mapTypeId: 'roadmap',
      streetViewControl: false,
      mapTypeControl: false,
      fullscreenControl: true
    });

    // Event: Klik Peta
    map.addListener('click', function(e) {
      setLocation(e.latLng.lat(), e.latLng.lng());
    });

    // Fitur Search (Autocomplete)
    const autocomplete = new google.maps.places.Autocomplete(inputSearch);
    autocomplete.bindTo('bounds', map);
    
    // Listener saat user memilih tempat ATAU menekan enter
    autocomplete.addListener('place_changed', function() {
      const place = autocomplete.getPlace();
      
      // 1. Jika user memilih tempat valid dari sugesti Google
      if (place.geometry && place.geometry.location) {
        if (place.geometry.viewport) {
          map.fitBounds(place.geometry.viewport);
        } else {
          map.setCenter(place.geometry.location);
          map.setZoom(17);
        }
        setLocation(place.geometry.location.lat(), place.geometry.location.lng());
        return;
      }

      // 2. Jika tidak ada geometry, cek apakah input adalah koordinat manual
      // Format: Latitude, Longitude (contoh: -6.200, 106.800)
      const rawInput = inputSearch.value.trim();
      const parts = rawInput.split(',');

      if (parts.length === 2) {
        const lat = parseFloat(parts[0].trim());
        const lng = parseFloat(parts[1].trim());

        // Validasi angka
        if (!isNaN(lat) && !isNaN(lng)) {
             // Validasi range sederhana (Lat -90 s/d 90, Lng -180 s/d 180)
             if (lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
                 const pos = { lat: lat, lng: lng };
                 map.setCenter(pos);
                 map.setZoom(17);
                 setLocation(lat, lng);
                 // Tambahan: bersihkan input biar rapi atau biarkan
                 return; 
             }
        }
      }

      // 3. Jika gagal semua
      alert("Lokasi tidak ditemukan. Jika mencari koordinat, gunakan format: Latitude, Longitude (contoh: -6.2146, 106.8451)");
    });

    // Cek nilai existing di input (jika edit/error submit)
    const current = (urlMapsInput.value || '').trim();
    if (current.includes(',')) {
      const parts = current.split(',');
      const lat = parseFloat(parts[0]);
      const lng = parseFloat(parts[1]);
      if (!isNaN(lat) && !isNaN(lng)) {
        map.setZoom(16);
        setLocation(lat, lng);
      }
    } else if (navigator.geolocation) {
       // Coba ambil lokasi user
       navigator.geolocation.getCurrentPosition(
         (pos) => {
           const myPos = { lat: pos.coords.latitude, lng: pos.coords.longitude };
           map.setCenter(myPos);
           map.setZoom(15);
         },
         () => { /* Error handling location: ignore */ }
       );
    }
  }

  // Saat Modal Dibuka
  modalEl.addEventListener('shown.bs.modal', function(){
    if (!map) {
        initMap();
    } else {
        // Trigger resize agar peta tidak abu-abu
        const currentCenter = map.getCenter();
        google.maps.event.trigger(map, 'resize');
        map.setCenter(currentCenter);
    }
  });

  // Tombol "Gunakan Lokasi Ini"
  btnUse.addEventListener('click', function(){
    if (!picked) return;
    urlMapsInput.value = picked.lat + ',' + picked.lng;
  });
  
  // Enter di search box tidak submit form
  inputSearch.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') e.preventDefault();
  });
});
</script>
</body>
</html>