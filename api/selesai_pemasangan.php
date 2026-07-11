<?php
// selesai_pemasangan.php - Backend untuk Flutter (Update Selesai + Notif WA Keren)
// UPDATED: Tambah notif ke Marketing Group + Honor Teknisi & Sales

session_start();
header('Content-Type: application/json');
require 'db_config.php';

// =====================================================
// HELPERS
// =====================================================

// --- Normalisasi WA untuk Link wa.me ---
function normalizeWa($raw) {
    $telp = preg_replace('/[^0-9]/', '', $raw);
    if (substr($telp, 0, 1) === '0') return '62' . substr($telp, 1);
    if (substr($telp, 0, 2) !== '62') return '62' . $telp;
    return $telp;
}

// --- Format Rupiah ---
function rupiah($angka) {
    return "Rp" . number_format((float)$angka, 0, ',', '.');
}

// =====================================================
// BACA INPUT JSON
// =====================================================
$input = json_decode(file_get_contents('php://input'), true);
$id    = filter_var($input['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID Tiket tidak valid.']);
    exit;
}

$conn_pemasangan = get_conn_pemasangan();
$conn_umum       = get_conn_umum();

try {
    $conn_pemasangan->begin_transaction();

    // =====================================================
    // 1. UPDATE STATUS FINAL
    // =====================================================
    $stmt_finish = $conn_pemasangan->prepare("UPDATE pelanggan_instalasi SET status='selesai' WHERE id=?");
    $stmt_finish->bind_param("i", $id);
    if (!$stmt_finish->execute()) throw new Exception("Gagal update status database.");
    $stmt_finish->close();

    // =====================================================
    // 2. AMBIL DATA LENGKAP TIKET
    // =====================================================
    $query_detail = "SELECT 
        pop AS nama_pop, nama, paket, alamat, userppp, passwordppp, vlan, 
        modem AS id_modem_pemasangan, teknisi, url_maps, ktp, telp, email, marketing 
        FROM pelanggan_instalasi WHERE id = ?";
        
    $stmt_get = $conn_pemasangan->prepare($query_detail);
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $result = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();

    if (!$result) throw new Exception("Data tiket tidak ditemukan.");
    extract($result); // Ekstrak variabel: $nama, $nama_pop, $userppp, dll.

    // =====================================================
    // 3. AMBIL DETAIL PAKET & MODEM
    // =====================================================
    $nama_paket_str = "Unknown"; $kecepatan = "-"; $harga_paket = 0;
    $modem_merk = "-"; $modem_model = "-"; $modem_sn = "-"; $lokasi_penyimpanan = "-";

    // Cek Paket
    if ($paket) {
        $stmt_paket = $conn_umum->prepare("SELECT nama_paket, kecepatan, harga FROM jaringan_paket WHERE id_paket=?");
        $stmt_paket->bind_param("i", $paket);
        $stmt_paket->execute();
        $res_paket = $stmt_paket->get_result()->fetch_assoc();
        if ($res_paket) {
            $nama_paket_str = $res_paket['nama_paket'];
            $kecepatan      = $res_paket['kecepatan'];
            $harga_paket    = $res_paket['harga'];
        }
        $stmt_paket->close();
    }

    // Cek Modem
    if ($id_modem_pemasangan) {
        $stmt_modem = $conn_umum->prepare("SELECT serial_number, model, merk, lokasi_penyimpanan FROM jaringan_modem WHERE id_modem=?");
        $stmt_modem->bind_param("i", $id_modem_pemasangan);
        $stmt_modem->execute();
        $res_modem = $stmt_modem->get_result()->fetch_assoc();
        if ($res_modem) {
            $modem_sn           = $res_modem['serial_number'];
            $modem_merk         = $res_modem['merk'];
            $modem_model        = $res_modem['model'];
            $lokasi_penyimpanan = $res_modem['lokasi_penyimpanan'];
        }
        $stmt_modem->close();
    }

    $conn_pemasangan->commit(); // Simpan perubahan DB sebelum kirim WA

    // =====================================================
    // 4. SETUP GROUP & HONOR
    // =====================================================
    $groups = [
        "rajeg"     => "6281293958590-1587210420@g.us",
        "muncung"   => "120363424548647899@g.us",
        "kemeri"    => "6287770366015-1628875457@g.us",
        "panggang"  => "120363405472722137@g.us",
        "kelapa"    => "120363423157487069@g.us",
        "mauk"      => "120363419348224895@g.us",
        "brebes"    => "120363297070607107@g.us",
        "sengon"    => "120363366069803212@g.us",
        "badakanom"    => "120363409600702809@g.us",
        "grinting"  => "120363399972363054@g.us"
    ];
    
    // Normalisasi nama pop agar cocok dengan key array
    $pop_key = strtolower(trim($nama_pop));
    $group_id = $groups[$pop_key] ?? null;

    // Group Marketing untuk notif honor
    $group_marketing_id = "120363418654328024@g.us";

    // Honor
    $honor_teknisi = 100000; // 100k
    $honor_sales   = 50000;  // 50k

    // Set timezone Jakarta untuk WIB
    date_default_timezone_set('Asia/Jakarta');
    $tanggalSekarang = date('d/m/Y');
    $jamSekarang = date('H:i');
    $hariSekarang = array('Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu')[date('w')];
    
    $waLink = normalizeWa($telp);

    // =====================================================
    // 5. KIRIM NOTIF KE GROUP TEKNISI
    // API KEY: e9c50247-3b8d-4cd8-924a-024a4d2b3124 (GROUP)
    // =====================================================
    if ($group_id) {
        $message_teknisi = "✅ *PEMASANGAN SELESAI (POP: {$nama_pop})*\n" .
                   "══════════════════\n" .
                   "📅 *Tanggal :* {$hariSekarang}, {$tanggalSekarang}\n" .
                   "🕐 *Jam :* {$jamSekarang} WIB\n" .
                   "🆔 *ID Tiket :* #{$id}\n\n" .

                   "👤 *DATA PELANGGAN*\n" .
                   "━━━━━━━━━━━━━━━━━━\n" .
                   "🏷️ *Nama :* {$nama}\n" .
                   "🏠 *Alamat :* {$alamat}\n" .
                   "📱 *Kontak :* wa.me/{$waLink}\n\n" .

                   "⚙️ *DATA TEKNIS & PAKET*\n" .
                   "━━━━━━━━━━━━━━━━━━\n" .
                   "🚀 *Paket :* {$nama_paket_str} ({$kecepatan})\n" .
                   "💰 *Harga :* " . rupiah($harga_paket) . "\n" .
                   "🔌 *PPPoE User :* `{$userppp}`\n" .
                   "🔑 *PPPoE Pass :* `{$passwordppp}`\n" .
                   "🔢 *VLAN :* {$vlan}\n\n" .

                   "📟 *PERANGKAT TERPASANG*\n" .
                   "━━━━━━━━━━━━━━━━━━\n" .
                   "📶 *Device :* {$modem_merk} {$modem_model}\n" .
                   "🆔 *S/N :* `{$modem_sn}`\n" .
                   "📦 *Sumber :* {$lokasi_penyimpanan}\n\n" .

                   "🛠️ *TIM & LOKASI*\n" .
                   "━━━━━━━━━━━━━━━━━━\n" .
                   "👷 *Teknisi :* {$teknisi}\n" .
                   "🤝 *Sales :* {$marketing}\n" .
                   "📍 *Maps :* {$url_maps}\n\n" .

                   "🚀 _Terima kasih, instalasi sukses & online!_";

        $curl = curl_init('https://api.starsender.online/api/send');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                "messageType" => "text",
                "to"          => $group_id,
                "body"        => $message_teknisi
            ]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124'
            ],
            CURLOPT_TIMEOUT        => 15
        ]);
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        
        if ($err) {
            error_log("WA Teknisi Error: $err");
        }
        
        // Log
        file_put_contents('log_selesai_teknisi.txt', 
            date('Y-m-d H:i:s') . " | ID#{$id} | {$nama_pop} | to {$group_id}\n",
            FILE_APPEND
        );
    }

    // =====================================================
    // 6. KIRIM NOTIF KE GROUP MARKETING
    // API KEY: e9c50247-3b8d-4cd8-924a-024a4d2b3124 (GROUP)
    // =====================================================
    $message_marketing = "✨ *PEMASANGAN BERHASIL - LAPORAN FINANCE* ✨\n" .
                        "════════════════════════════════════════\n\n" .
                        "📅 *Tanggal Selesai :* {$hariSekarang}, {$tanggalSekarang}\n" .
                        "🕐 *Jam :* {$jamSekarang} WIB\n" .
                        "🆔 *ID Tiket :* #{$id}\n" .
                        "🌐 *Area (POP) :* {$nama_pop}\n\n" .

                        "👥 *DATA PELANGGAN*\n" .
                        "─────────────────────────\n" .
                        "🏷️  *Nama Pelanggan :* {$nama}\n" .
                        "🏠 *Alamat :* {$alamat}\n" .
                        "📱 *WhatsApp :* wa.me/{$waLink}\n\n" .

                        "📦 *PAKET BERLANGGANAN*\n" .
                        "─────────────────────────\n" .
                        "🚀 *Paket :* {$nama_paket_str}\n" .
                        "⚡ *Kecepatan :* {$kecepatan}\n" .
                        "💰 *Nominal Paket :* " . rupiah($harga_paket) . "\n\n" .

                        "👥 *TEAM ASSIGNMENT*\n" .
                        "─────────────────────────\n" .
                        "👷 *Teknisi :* {$teknisi}\n" .
                        "🤝 *Sales/Marketing :* {$marketing}\n\n" .

                        "💸 *HONOR BREAKDOWN (Finance)*\n" .
                        "═════════════════════════════════════════\n" .
                        "👷 *Honor Teknisi :* " . rupiah($honor_teknisi) . "\n" .
                        "🤝 *Honor Sales :* " . rupiah($honor_sales) . "\n" .
                        "💰 *Total Honor :* " . rupiah($honor_teknisi + $honor_sales) . "\n\n" .

                        "─────────────────────────────────────────\n" .
                        "Hormat, Dedi sugianto\n" .
                        "*HRD - PT. Real Data Solusindo* 📊\n\n" .
                        "_Perhatian: Harap verifikasi data & proses pembayaran honor._";

    $curl_mkt = curl_init('https://api.starsender.online/api/send');
    curl_setopt_array($curl_mkt, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "messageType" => "text",
            "to"          => $group_marketing_id,
            "body"        => $message_marketing
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124'
        ],
        CURLOPT_TIMEOUT        => 15
    ]);
    $response_mkt = curl_exec($curl_mkt);
    $err_mkt = curl_error($curl_mkt);
    curl_close($curl_mkt);
    
    if ($err_mkt) {
        error_log("WA Marketing Error: $err_mkt");
    }
    
    // Log
    file_put_contents('log_selesai_marketing.txt',
        date('Y-m-d H:i:s') . " | ID#{$id} | {$nama} | Honor: Teknisi " . rupiah($honor_teknisi) . " + Sales " . rupiah($honor_sales) . "\n",
        FILE_APPEND
    );

    // =====================================================
    // 7. RESPONSE SUCCESS
    // =====================================================
    echo json_encode([
        'success' => true,
        'message' => 'Pemasangan selesai & laporan terkirim ke Teknisi & Marketing.',
        'id_tiket' => $id,
        'status' => 'selesai',
        'honor_teknisi' => $honor_teknisi,
        'honor_sales' => $honor_sales,
        'total_honor' => $honor_teknisi + $honor_sales
    ]);

} catch (Exception $e) {
    if (isset($conn_pemasangan)) $conn_pemasangan->rollback();
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    if (isset($conn_pemasangan)) $conn_pemasangan->close();
    if (isset($conn_umum)) $conn_umum->close();
}
?>