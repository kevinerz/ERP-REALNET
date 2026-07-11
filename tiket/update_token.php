<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// --- LOGGING SETTINGS ---
// Tentukan path untuk file log kustom (di direktori yang sama dengan skrip ini)
$log_file = __DIR__ . '/fcm_update_log.txt'; 

// Fungsi kustom untuk menulis log ke file
function custom_log($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    // Format log: [Timestamp] [Prefix] Message
    $log_message = "{$timestamp} [FCM_UPDATE] {$message}\n";
    // Tulis ke file dengan mode append
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// error_log() tetap berfungsi meskipun display_errors '0'
ini_set('display_errors', '0');
error_reporting(E_ALL);

custom_log("Request received.");

// Ambil data dari POST
$nama_karyawan = $_POST['nama_karyawan'] ?? ''; // Sekarang akan menerima USERNAME dari Flutter
$fcm_token = $_POST['fcm_token'] ?? ''; 

// LOG: Mencatat input yang diterima
custom_log("Input - Username: '{$nama_karyawan}', Token: '" . substr($fcm_token, 0, 10) . "...'");

if (empty($nama_karyawan) || empty($fcm_token)) {
    http_response_code(400);
    custom_log("Error: Nama/Username atau token kosong. Denied.");
    echo json_encode(['success' => false, 'message' => 'Nama dan token wajib diisi.']);
    exit;
}

// --- KONFIGURASI DATABASE ---
$db_host = "localhost";
$db_user = "u272457353_kevinsamsung99";
$db_pass = "Admionkevin99";
$db_name = "u272457353_umumdata";

// Koneksi database
$conn = getErpDbConnection();

if ($conn->connect_error) {
    http_response_code(500);
    $error_msg = "DB Connection Error: " . $conn->connect_error;
    custom_log($error_msg);
    echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
    exit;
}

$conn->set_charset('utf8mb4');

// =================================================================
// LANGKAH DEBUGGING: CHECK KEBERADAAN NAMA SEBELUM UPDATE
// FIX: MENGGUNAKAN COLUMN USERNAME UNTUK PENCARIAN
// =================================================================
$check_sql = "SELECT COUNT(*) FROM hr_karyawan WHERE LOWER(username)=LOWER(?)"; // <-- FIX: Menggunakan 'username'
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("s", $nama_karyawan); // $nama_karyawan sekarang adalah username
$check_stmt->execute();
$check_stmt->bind_result($count);
$check_stmt->fetch();
$check_stmt->close();

if ($count == 0) {
    http_response_code(404); 
    $error_msg = "Error: Username '{$nama_karyawan}' tidak ditemukan di tabel. Gagal Update.";
    custom_log($error_msg);
    echo json_encode(['success' => false, 'message' => $error_msg]);
    $conn->close();
    exit;
}
custom_log("Username ditemukan. Melanjutkan update...");
// =================================================================


// Update fcm_token di tabel karyawan menggunakan prepared statement
$sql = "UPDATE hr_karyawan SET fcm_token=? WHERE LOWER(username)=LOWER(?)"; // <-- FIX: Menggunakan 'username'
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    $error_msg = "Prepare statement gagal: " . $conn->error;
    custom_log($error_msg);
    echo json_encode(['success' => false, 'message' => 'Kesalahan internal server: Prepare statement gagal']);
    $conn->close();
    exit;
}

// LOG: Mencatat parameter yang di-bind
custom_log("Binding parameters: Token & Username");

$stmt->bind_param("ss", $fcm_token, $nama_karyawan); // $nama_karyawan sekarang adalah username

if ($stmt->execute()) {
    $rows_affected = $stmt->affected_rows;
    http_response_code(200);
    
    // LOG: Mencatat hasil eksekusi dan baris yang terpengaruh
    custom_log("Execute success. Rows affected: {$rows_affected}");
    
    if ($rows_affected > 0) {
        $message = "Token berhasil diperbarui untuk {$nama_karyawan}";
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        // Ini terjadi jika token sudah sama
        $message = "Token sudah terbaru atau username '{$nama_karyawan}' tidak ditemukan. Rows affected: 0";
        echo json_encode(['success' => true, 'message' => $message]);
    }
} else {
    http_response_code(500);
    $error_msg = "Execute statement gagal: " . $stmt->error;
    custom_log($error_msg);
    echo json_encode(['success' => false, 'message' => 'Gagal update token: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
custom_log("Request finished.");
?>