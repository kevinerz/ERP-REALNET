<?php
require_once __DIR__ . '/../config/database.php';
// submit_ai.php (Versi Hybrid - Bisa Proses & Tolak)
date_default_timezone_set("Asia/Jakarta");

// Koneksi Database
$db = getErpDbConnection();
if ($db->connect_error) die("Koneksi gagal: " . $db->connect_error);

// Tentukan Aksi: dari Tombol Kirim (POST) atau Tombol Tolak (GET)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// =============================================================
// AKSI 1: TOLAK TIKET
// =============================================================
if ($action === 'reject') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $db->query("UPDATE tiket_ai SET status = 'rejected' WHERE id = $id");
        header("Location: dashboard_ai.php?success=Tiket+berhasil+ditolak.");
    } else {
        header("Location: dashboard_ai.php?error=ID+tiket+tidak+valid.");
    }
    exit;
}

// =============================================================
// AKSI 2: PROSES DAN KIRIM TIKET (Logika lama Anda)
// =============================================================
if ($action === 'process') {
    // Ambil data POST dari form
    $id_ai      = intval($_POST['ai_id'] ?? 0);
    $nama       = $_POST['nama'] ?? '';
    $alamat     = $_POST['alamat'] ?? '';
    $whatsapp   = $_POST['whatsapp'] ?? '';
    $pop        = $_POST['pop'] ?? '';
    $keluhan    = $_POST['keluhan'] ?? '';
    $maps_url   = $_POST['maps_url'] ?? '';
    $now        = date("Y-m-d H:i:s");

    // Validasi sederhana
    if (!$nama || !$alamat || !$whatsapp || !$pop || !$keluhan) {
        header("Location: dashboard_ai.php?error=Data+tidak+lengkap.+Pastikan+POP+sudah+dipilih.");
        exit;
    }

    // Simpan ke tabel tiket utama
    $stmt = $db->prepare("INSERT INTO tiket_gangguan (nama_pelanggan, alamat, whatsapp, pop, keluhan, maps_url, tanggal_dibuat) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nama, $alamat, $whatsapp, $pop, $keluhan, $maps_url, $now);
    $stmt->execute();
    $stmt->close();

    // Tandai tiket_ai sebagai sent
    if ($id_ai > 0) {
        $db->query("UPDATE tiket_ai SET status='sent' WHERE id = $id_ai");
    }

    // Fungsi Kirim Notifikasi
    function sendNotification($to, $body) {
        $payload = ["messageType" => "text", "to" => $to, "body" => $body, "delay" => 1, "schedule" => time() * 1000];
        $ch = curl_init("https://api.starsender.online/api/send");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_exec($ch);
        curl_close($ch);
    }

    // Notifikasi ke pelanggan
    $body_cust = "👋 $nama,\nTiket gangguan Anda sudah kami terima:\n• Keluhan: $keluhan\nMohon ditunggu prosesnya 😊";
    sendNotification($whatsapp, $body_cust);

    // Notifikasi ke grup teknisi
    $grp = '';
    switch ($pop) {
        case 'rajeg':    $grp = '6281293958590-1587210420@g.us'; break;
        case 'kemeri':   $grp = '6287770366015-1628875457@g.us'; break;
        case 'mauk':     $grp = '120363419348224895@g.us'; break;
        // ... tambahkan case lainnya
    }

    if ($grp) {
        $wa_link = "wa.me/" . preg_replace('/[^0-9]/', '', $whatsapp);
        $body_grp = "🔧 *Gangguan Area $pop*\n\n" . "👤 *$nama*\n" . "💬 *$keluhan*\n" . "📍 $alamat\n" . ($maps_url ? "🗺️ $maps_url\n" : "") . "📞 $wa_link\n" . "⏰ $now";
        sendNotification($grp, $body_grp);
    }

    // Redirect kembali ke dashboard
    header("Location: dashboard_ai.php?success=Tiket+untuk+$nama+berhasil+dikirim+ke+tim+$pop.");
    exit;
}

// Jika tidak ada aksi yang cocok, redirect dengan error
header("Location: dashboard_ai.php?error=Aksi+tidak+dikenali.");
?>