<?php
// Nyalakan error & log ke file lokal
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/kasbon_error.log'); // log akan ada di file ini

session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    echo "Akses ditolak.";
    exit;
}

/* =========================
 * KONFIGURASI STARSENDER
 * ========================= */
define('STARSENDER_API_KEY', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124');
define('STARSENDER_URL', 'https://api.starsender.online/api/send');

/**
 * Kirim WhatsApp via Starsender V3
 * Wajib: messageType, to, body (JSON)
 */
function sendWaStarsender($nomor, $pesan) {
    error_log("DEBUG Starsender: mulai kirim ke {$nomor}");

    if (!function_exists('curl_init')) {
        error_log("Starsender: cURL tidak aktif di server.");
        return false;
    }

    if (empty($nomor) || empty($pesan)) {
        error_log("Starsender: nomor atau pesan kosong.");
        return false;
    }

    // Payload sesuai dokumentasi Message API:
    // messageType (text/media), to, body
    $payload = [
        'messageType' => 'text',     // untuk pesan teks biasa
        'to'          => $nomor,     // bisa 08xxx atau 628xxx (docs: boleh 0 atau kode negara)
        'body'        => $pesan,
        // Optional: 'delay' => 0, 'schedule' => null, dll.
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => STARSENDER_URL,
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: ' . STARSENDER_API_KEY,    // API key di header
            'Content-Type: application/json',
        ],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 20,
    ]);

    $result = curl_exec($ch);
    $err    = curl_error($ch);
    $code   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("Starsender HTTP code: {$code}");
    error_log("Starsender response body: {$result}");

    if ($err) {
        error_log("Starsender cURL error: {$err}");
        return false;
    }

    return ($code >= 200 && $code < 300);
}

/* =========================
 * AMBIL DATA USER LOGIN
 * ========================= */
$username = $_SESSION['username'];

$get_id = $conn->prepare("SELECT id, divisi, no_telp FROM hr_karyawan WHERE username = ?");
$get_id->bind_param("s", $username);
$get_id->execute();
$result = $get_id->get_result();
if ($result->num_rows === 0) {
    echo "User tidak ditemukan.";
    exit;
}
$user            = $result->fetch_assoc();
$id_karyawan     = $user['id'];
$divisi_pengaju  = $user['divisi'];
$no_telp_pengaju = $user['no_telp'] ?? '';

/* =========================
 * AMBIL DATA DARI FORM
 * ========================= */
$tanggal   = $_POST['tanggal']   ?? '';
$jumlah    = $_POST['jumlah']    ?? '';
$keperluan = $_POST['keperluan'] ?? '';

if (empty($tanggal) || empty($jumlah) || empty($keperluan)) {
    echo "<div class='alert alert-danger text-center'>Semua field wajib diisi!</div>";
    exit;
}

/* =========================
 * LOGIKA STATUS APPROVAL
 * ========================= */
switch ($divisi_pengaju) {
    case 'Teknisi':
    case 'Leader Area':
        $status = 'spv_teknis';
        break;
    case 'SPV Teknis':
    case 'Finance':
        $status = 'manager';
        break;
    case 'Manager':
        $status = 'spv_administrasi';
        break;
    case 'SPV Administrasi':
    case 'spv_administrasi':
        $status = 'manager';
        break;
    default:
        $status = 'Manager';
        break;
}

/* =========================
 * INSERT KE TABEL KASBON
 * ========================= */
$sql = "INSERT INTO keu_kasbon (id_karyawan, tanggal, jumlah, keperluan, status)
        VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("issss", $id_karyawan, $tanggal, $jumlah, $keperluan, $status);
$success = $stmt->execute();

/* =========================
 * KIRIM WHATSAPP JIKA SUKSES
 * ========================= */
$wa_status_msg = '';

if ($success) {
    $rupiah = 'Rp ' . number_format((float)$jumlah, 0, ',', '.');

    $pesan_wa =
        "*PENGAJUAN KASBON BARU*\n" .
        "Nama      : {$username}\n" .
        "Divisi    : {$divisi_pengaju}\n" .
        "Tanggal   : {$tanggal}\n" .
        "Jumlah    : {$rupiah}\n" .
        "Keperluan : {$keperluan}\n" .
        "Status Aprove    : *" . strtoupper($status) . "*\n\n" .
        "Mohon dicek dan diproses di sistem.";

    // Nomor WA tujuan (atasan / grup)
    $nomor_tujuan = '6287770366015';

    $ok = sendWaStarsender($nomor_tujuan, $pesan_wa);

    if ($ok) {
        $wa_status_msg = 'Notifikasi WhatsApp berhasil dikirim.';
    } else {
        $wa_status_msg = 'Notifikasi WhatsApp GAGAL dikirim. Cek file kasbon_error.log.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengajuan Kasbon</title>
    <meta http-equiv="refresh" content="5;url=<?= in_array($divisi_pengaju, ['Teknisi', 'Leader Area']) ? 'https://datarealsolution.net/menu_teknisi.php' : 'https://datarealsolution.net/list_kasbon.php' ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <?php if ($success): ?>
        <div class="alert alert-success text-center">
            Pengajuan kasbon berhasil dikirim.<br>
            <?= $wa_status_msg ? '<small>' . htmlspecialchars($wa_status_msg, ENT_QUOTES, 'UTF-8') . '</small><br>' : '' ?>
            Anda akan dialihkan kembali dalam 5 detik...
        </div>
    <?php else: ?>
        <div class="alert alert-danger text-center">
            Gagal menyimpan data: <?= htmlspecialchars($stmt->error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
