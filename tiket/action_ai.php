<?php
// action_ai.php
header('Content-Type: application/json');

// ===================================================================
// FUNGSI NOTIFIKASI DARI submit_ai.php LAMA ANDA
// ===================================================================
function sendNotification($to, $body) {
    $payload = [
        "messageType" => "text",
        "to"          => $to,
        "body"        => $body,
        "delay"       => 1,
        "schedule"    => time() * 1000
    ];
    $ch = curl_init("https://api.starsender.online/api/send");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124" // API Key Anda
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    // Sebaiknya nonaktifkan eksekusi saat development jika tidak ingin mengirim notif sungguhan
    curl_exec($ch);
    curl_close($ch);
}


// ===================================================================
// KONEKSI & LOGIKA UTAMA
// ===================================================================

// Validasi dasar, pastikan ada 'action' yang dikirim
if (!isset($_POST['action']) || !isset($_POST['ai_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Permintaan tidak valid.']);
    exit;
}

$db = new mysqli("localhost", "u272457353_kevinsamsung", "Admionkevin99", "u272457353_tiket_helpdesk");
if ($db->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Koneksi database gagal.']);
    exit;
}
date_default_timezone_set("Asia/Jakarta");

$action = $_POST['action'];
$id_ai  = intval($_POST['ai_id']);

if ($action === 'reject') {
    // Aksi untuk menolak tiket (mengubah status menjadi 'rejected')
    $stmt = $db->prepare("UPDATE tiket_ai SET status = 'rejected' WHERE id = ?");
    $stmt->bind_param("i", $id_ai);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Tiket berhasil ditolak dan dihapus dari daftar.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menolak tiket.']);
    }
    $stmt->close();

} 
elseif ($action === 'process') {
    // Aksi untuk memproses tiket (menggabungkan logika dari submit_ai.php lama)
    $nama     = $_POST['nama']     ?? '';
    $alamat   = $_POST['alamat']   ?? '';
    $whatsapp = $_POST['whatsapp'] ?? '';
    $pop      = $_POST['pop']      ?? '';
    $keluhan  = $_POST['keluhan']  ?? '';
    $maps_url = $_POST['maps_url'] ?? '';
    $now      = date("Y-m-d H:i:s");

    if (!$nama || !$alamat || !$whatsapp || !$pop || !$keluhan) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap. Pastikan semua field terisi.']);
        exit;
    }

    // 1. Simpan ke tabel tiket utama
    $stmt = $db->prepare("INSERT INTO tiket (nama_pelanggan, alamat, whatsapp, pop, keluhan, maps_url, tanggal_dibuat) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nama, $alamat, $whatsapp, $pop, $keluhan, $maps_url, $now);
    $stmt->execute();
    $insert_success = $stmt->affected_rows > 0;
    $stmt->close();

    if ($insert_success) {
        // 2. Tandai tiket_ai sebagai 'sent'
        $db->query("UPDATE tiket_ai SET status='sent' WHERE id = $id_ai");

        // == LOGIKA NOTIFIKASI (DIAMBIL DARI submit_ai.php LAMA) ==
        
        // 3. Notifikasi ke pelanggan
        $body_cust = "👋 $nama,\nTiket gangguan Anda sudah kami terima:\n• Keluhan: $keluhan\nMohon ditunggu prosesnya 😊";
        sendNotification($whatsapp, $body_cust);

        // 4. Notifikasi ke grup teknisi
        $grp = '';
        switch ($pop) {
            case 'rajeg':    $grp = '6281293958590-1587210420@g.us'; break;
            case 'kemeri':   $grp = '6287770366015-1628875457@g.us'; break;
            case 'mauk':     $grp = '120363419348224895@g.us'; break;
            case 'cianjur':  $grp = '120363399972363054@g.us'; break;
            case 'brebes':   $grp = '120363297070607107@g.us'; break;
            case 'sengon':   $grp = '120363366069803212@g.us'; break;
            case 'grinting': $grp = '120363399972363054@g.us'; break;
        }

        if ($grp) {
            $wa_link = "wa.me/" . preg_replace('/[^0-9]/', '', $whatsapp);
            
            // Menggunakan format notifikasi grup yang sudah Anda sempurnakan
            $body_grp = "🔧 *Gangguan Area $pop*\n\n" .
                        "👤 *$nama*\n" .
                        "💬 *$keluhan*\n" .
                        "📍 $alamat\n" .
                        ($maps_url ? "🗺️ $maps_url\n" : "") .
                        "📞 $wa_link\n" .
                        "⏰ $now";

            sendNotification($grp, $body_grp);
        }
        
        // == AKHIR DARI LOGIKA NOTIFIKASI ==

        // Kirim status sukses kembali ke dasbor
        echo json_encode(['status' => 'success', 'message' => "Tiket untuk $nama berhasil dikirim ke tim $pop!"]);
    } else {
        // Kirim status error jika gagal insert
        echo json_encode(['status' => 'error', 'message' => "Gagal menyimpan tiket utama ke database."]);
    }
}

$db->close();
?>