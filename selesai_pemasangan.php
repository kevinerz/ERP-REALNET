<?php
require_once __DIR__ . '/config/database.php';
// Ensure session is started at the very beginning
session_start();

// Set content type for JSON response
header('Content-Type: application/json');

// --- Input Validation ---
if (!isset($_POST['id'])) {
    // Log error for server-side debugging
    error_log("ERROR: ID tidak valid. POST['id'] tidak ditemukan.");
    echo json_encode(['success' => false, 'message' => 'ID tidak valid.']);
    exit;
}

$id = intval($_POST['id']);
if ($id <= 0) {
    // Log error for server-side debugging
    error_log("ERROR: ID harus positif. ID yang diterima: " . $_POST['id']);
    echo json_encode(['success' => false, 'message' => 'ID harus positif.']);
    exit;
}

error_log("DEBUG: Processing request for ID: " . $id);

// ------------------------------------------------------------------
// CONNECTION 1: u272457353_db_pemasangan (for installation data)
// ------------------------------------------------------------------
$conn_pemasangan = getErpDbConnection();
if ($conn_pemasangan->connect_error) {
    error_log("FATAL ERROR: Koneksi database pemasangan gagal: " . $conn_pemasangan->connect_error);
    echo json_encode(['success'=>false,'message'=>'Koneksi database pemasangan gagal']);
    exit;
}
error_log("DEBUG: Connected to pemasangan database successfully.");


// Start a transaction for atomicity to ensure DB update consistency
$conn_pemasangan->begin_transaction();
error_log("DEBUG: Transaction started for pemasangan database.");

// 1. Update the installation status in 'pemasangan' database
$stmt_update = $conn_pemasangan->prepare("UPDATE pelanggan_instalasi SET status='selesai' WHERE id=?");
if (!$stmt_update) {
    $conn_pemasangan->rollback(); // Rollback in case of prepare failure
    error_log("ERROR: Prepare statement update pemasangan gagal: " . $conn_pemasangan->error);
    echo json_encode(['success'=>false, 'message'=>'Prepare statement update gagal']);
    $conn_pemasangan->close();
    exit;
}

$stmt_update->bind_param("i", $id);
$exec_update = $stmt_update->execute();

if (!$exec_update) {
    $conn_pemasangan->rollback(); // Rollback if update fails
    error_log("ERROR: Eksekusi update status gagal untuk ID {$id}: " . $stmt_update->error);
    echo json_encode(['success' => false, 'message' => 'Update status gagal']);
    $stmt_update->close();
    $conn_pemasangan->close();
    exit;
}
$stmt_update->close();
error_log("DEBUG: Installation status updated to 'selesai' for ID: " . $id);

// 2. Fetch details of the finished installation from 'pemasangan' database
// CRITICAL CHANGE: Now fetching 'modem' column instead of 'sn' for modem ID.
$stmt_select_pemasangan = $conn_pemasangan->prepare("SELECT
    pop AS nama_pop,          
    nama,
    paket,                    
    alamat,
    userppp AS userPpp,
    passwordppp AS passwordPpp,
    vlan,
    modem AS id_modem_pemasangan, -- Get the modem ID from 'pemasangan.modem'
    sn,                       -- Keep 'sn' just in case it's displayed, but it's not for modem lookup anymore
    teknisi,
    url_maps,
    ktp,
    telp,
    email,
    marketing
FROM
    pelanggan_instalasi
WHERE
    id = ?");

if (!$stmt_select_pemasangan) {
    $conn_pemasangan->rollback();
    error_log("ERROR: Prepare statement select pemasangan gagal: " . $conn_pemasangan->error);
    echo json_encode(['success'=>false, 'message'=>'Prepare statement select pemasangan gagal']);
    $conn_pemasangan->close();
    exit;
}

$stmt_select_pemasangan->bind_param("i", $id);
$stmt_select_pemasangan->execute();
$result_pemasangan = $stmt_select_pemasangan->get_result();
$installation_details = $result_pemasangan->fetch_assoc();
$stmt_select_pemasangan->close();

if (!$installation_details) {
    $conn_pemasangan->rollback();
    error_log("ERROR: Detail pemasangan tidak ditemukan untuk ID: " . $id . " setelah update.");
    echo json_encode(['success' => false, 'message' => 'Detail pemasangan tidak ditemukan.']);
    $conn_pemasangan->close();
    exit;
}

// Assign fetched details to variables using extract().
// id_modem_pemasangan will contain the modem ID (e.g., 512).
extract($installation_details);
error_log("DEBUG: Fetched installation details from pemasangan DB. id_paket: " . (isset($paket) ? $paket : 'N/A') . ", id_modem_pemasangan: " . (isset($id_modem_pemasangan) ? $id_modem_pemasangan : 'N/A') . ", SN (from pemasangan.sn): " . (isset($sn) ? $sn : 'N/A') . ", Nama Pop: " . (isset($nama_pop) ? $nama_pop : 'N/A'));


// Initialize package and modem details with default values
$nama_paket_str = "Tidak diketahui"; 
$kecepatan = "N/A";
$harga_paket = 0;
$modem_model = "Tidak diketahui";
$modem_merk = "Tidak diketahui";
$serial_number_modem = "N/A"; // Variable to hold the actual serial number from umumdata.modem
$lokasi_penyimpanan = "Tidak diketahui"; 

// ------------------------------------------------------------------
// CONNECTION 2: u272457353_umumdata (for package and modem details)
// ------------------------------------------------------------------
$conn_umumdata = getErpDbConnection();
if ($conn_umumdata->connect_error) {
    error_log("ERROR: Koneksi database umumdata gagal: " . $conn_umumdata->connect_error . ". Package and modem details will be default.");
    // If this connection fails, package and modem details will remain default values.
} else {
    error_log("DEBUG: Connected to umumdata database successfully.");

    // --- Fetch package details (nama_paket, kecepatan, harga) using id_paket ---
    if (isset($paket) && is_numeric($paket)) { 
        $stmt_select_paket_details = $conn_umumdata->prepare("SELECT nama_paket, kecepatan, harga FROM jaringan_paket WHERE id_paket = ?");
        if ($stmt_select_paket_details) {
            $stmt_select_paket_details->bind_param("i", $paket); 
            $stmt_select_paket_details->execute();
            $result_paket_details = $stmt_select_paket_details->get_result();
            $package_details = $result_paket_details->fetch_assoc();
            $stmt_select_paket_details->close();

            if ($package_details) {
                $nama_paket_str = $package_details['nama_paket'];
                $kecepatan = $package_details['kecepatan'];
                $harga_paket = $package_details['harga'];
                error_log("DEBUG: Fetched package details: Nama: '{$nama_paket_str}', Kecepatan: '{$kecepatan}', Harga: '{$harga_paket}'");
            } else {
                error_log("WARNING: Detail paket untuk id_paket '{$paket}' TIDAK DITEMUKAN di database umumdata.paket. Pastikan id_paket ini ada.");
            }
        } else {
            error_log("ERROR: Prepare statement select paket details gagal: " . $conn_umumdata->error);
        }
    } else {
        error_log("WARNING: \$paket (id_paket) dari pemasangan DB tidak valid atau kosong: " . (isset($paket) ? $paket : 'N/A') . ". Tidak dapat mencari detail paket.");
    }


    // --- Fetch modem details (model, merk, serial_number, lokasi_penyimpanan) using id_modem ---
    // CRITICAL CHANGE: Using id_modem_pemasangan (from pemasangan.modem) for lookup
    if (isset($id_modem_pemasangan) && is_numeric($id_modem_pemasangan) && $id_modem_pemasangan > 0) { 
        // Select serial_number here as well to display it
        $stmt_select_modem = $conn_umumdata->prepare("SELECT serial_number, model, merk, lokasi_penyimpanan FROM jaringan_modem WHERE id_modem = ?");
        if ($stmt_select_modem) {
            $stmt_select_modem->bind_param("i", $id_modem_pemasangan); 
            $stmt_select_modem->execute();
            $result_modem = $stmt_select_modem->get_result();
            $modem_details = $result_modem->fetch_assoc();
            $stmt_select_modem->close();

            if ($modem_details) {
                $serial_number_modem = $modem_details['serial_number']; // Get actual SN
                $modem_model = $modem_details['model'];
                $modem_merk = $modem_details['merk'];
                $lokasi_penyimpanan = $modem_details['lokasi_penyimpanan']; 
                error_log("DEBUG: Fetched modem details: Merk: '{$modem_merk}', Model: '{$modem_model}', SN: '{$serial_number_modem}', Lokasi: '{$lokasi_penyimpanan}' (via id_modem: {$id_modem_pemasangan})");
            } else {
                error_log("WARNING: Detail modem untuk id_modem '{$id_modem_pemasangan}' TIDAK DITEMUKAN di database umumdata.modem. Pastikan id_modem ini ada.");
            }
        } else {
            error_log("ERROR: Prepare statement select modem gagal: " . $conn_umumdata->error);
        }
    } else {
        error_log("WARNING: \$modem (id_modem) dari pemasangan DB tidak valid, kosong, atau <= 0: " . (isset($id_modem_pemasangan) ? $id_modem_pemasangan : 'N/A') . ". Tidak dapat mencari detail modem.");
    }

    $conn_umumdata->close(); 
    error_log("DEBUG: Closed umumdata database connection.");
}

// ------------------------------------------------------------------
// Prepare and Send WhatsApp Notification
// ------------------------------------------------------------------

// Define WhatsApp group IDs
$groups = [
    "rajeg"     => "6281293958590-1587210420@g.us",
    "kemeri"    => "6287770366015-1628875457@g.us",
    "cianjur"   => "120363399972363054@g.us",
    "mauk"      => "120363419348224895@g.us",
    "brebes"    => "120363297070607107@g.us",
    "sengon"    => "120363366069803212@g.us",
    "badakanom" => "120363409600702809@g.us",
    "grinting"  => "120363399972363054@g.us"
];

// Determine the correct group ID based on 'nama_pop'
$tanggalSekarang = date('d/m/Y H:i');
$group_id = $groups[strtolower($nama_pop)] ?? null;
error_log("DEBUG: Resolved group ID for POP '{$nama_pop}': " . ($group_id ? $group_id : 'N/A'));

$notification_sent = false;
if ($group_id) {
    // Construct the WhatsApp message with all retrieved details
    $message = "📢 *PELANGGAN BARU SELESAI DI PASANG ({$nama_pop})*\n\n" .
               "Tanggal Pengajuan: *{$tanggalSekarang}* WIB" . "\n" .
               "Nama Pelanggan: *{$nama}*\n" .
               "Paket: *{$nama_paket_str}* ({$kecepatan}) Rp" . number_format($harga_paket, 0, ',', '.') . "\n" .
               "Alamat: {$alamat}\n" .
               "Username PPPoE: *{$userPpp}*\n" .
               "Password PPPoE: *{$passwordPpp}*\n" .
               "VLAN: *{$vlan}*\n" .
               "MODEM : {$modem_merk} {$modem_model} (SN: {$serial_number_modem})\n" . // Using serial_number_modem from umumdata.modem
               "Lokasi Penyimpanan Modem: {$lokasi_penyimpanan}\n" . 
               "TEKNISI : {$teknisi}\n" .
               "--------------------------\n" .
               "*DETAIL LAINYA.*\n" .
               "--------------------------\n" .
               "Detail Pemasangan Awal:\n" .
               "URL Maps: {$url_maps}\n" .
               "KTP: {$ktp}\n" .
               "Telp: {$telp}\n" .
               "Email: {$email}\n" .
               "Marketing: {$marketing}\n";

    error_log("DEBUG: WhatsApp Message Body Prepared."); 
    // During active debugging, you can uncomment this to see the full message
    // error_log("DEBUG: WhatsApp Message Body:\n" . $message); 

    $curl = curl_init('https://api.starsender.online/api/send');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "messageType" => "text",
            "to"          => $group_id,
            "body"        => $message
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124'
        ],
    ]);

    $resp = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);

    file_put_contents('log_whatsapp_notification.txt', date('Y-m-d H:i:s') . " Sending to {$group_id} - HTTP Code: {$httpCode} - CURL Error: {$curlError} - RESP: {$resp}\n", FILE_APPEND);
    curl_close($curl);

    if ($httpCode === 200) {
        $notification_sent = true;
        error_log("DEBUG: WhatsApp notification sent successfully to {$group_id}.");
    } else {
        error_log("ERROR: WhatsApp notification failed for POP {$nama_pop}: HTTP Code {$httpCode}, Response: {$resp}, Error: {$curlError}");
    }
} else {
    error_log("WARNING: No WhatsApp group found for POP: {$nama_pop}. Notification not sent.");
}

// ------------------------------------------------------------------
// Final Response and Transaction Management for pemasangan DB
// ------------------------------------------------------------------

if ($exec_update && $notification_sent) {
    $conn_pemasangan->commit();
    echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate dan notifikasi dikirim.']);
    error_log("DEBUG: Transaction committed. Success: Status updated and notification sent.");
} elseif ($exec_update) {
    $conn_pemasangan->commit();
    echo json_encode(['success' => true, 'message' => 'Status berhasil diupdate, tetapi notifikasi WhatsApp gagal dikirim.']);
    error_log("DEBUG: Transaction committed. Partial Success: Status updated, but notification failed.");
} else {
    $conn_pemasangan->rollback();
    echo json_encode(['success' => false, 'message' => 'Update gagal total.']);
    error_log("DEBUG: Transaction rolled back. Failure: Update failed or other critical error.");
}

$conn_pemasangan->close(); 
error_log("DEBUG: Closed pemasangan database connection.");
?>