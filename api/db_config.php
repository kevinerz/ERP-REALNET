<?php
// =======================================================================
// KONFIGURASI DATABASE & CORS
// =======================================================================

// 1. Set Header CORS (PENTING untuk Flutter)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// 2. Handle Preflight Request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 3. Define Credentials (Pemasangan)
define('DB_HOST_PEMASANGAN', 'localhost');
define('DB_USER_PEMASANGAN', 'u272457353_kevinsamsung9');
define('DB_PASS_PEMASANGAN', 'Admionkevin99');
define('DB_NAME_PEMASANGAN', 'u272457353_db_pemasangan');

// 4. Define Credentials (Umum Data / Inventory)
define('DB_HOST_UMUM', 'localhost');
define('DB_USER_UMUM', 'u272457353_kevinsamsung99');
define('DB_PASS_UMUM', 'Admionkevin99');
define('DB_NAME_UMUM', 'u272457353_umumdata');

// =======================================================================
// FUNGSI KONEKSI
// =======================================================================

function get_conn_pemasangan() {
    $conn = new mysqli(DB_HOST_PEMASANGAN, DB_USER_PEMASANGAN, DB_PASS_PEMASANGAN, DB_NAME_PEMASANGAN);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(["success" => false, "message" => "Koneksi database pemasangan gagal: " . $conn->connect_error]));
    }
    return $conn;
}

function get_conn_umum() {
    $conn = new mysqli(DB_HOST_UMUM, DB_USER_UMUM, DB_PASS_UMUM, DB_NAME_UMUM);
    if ($conn->connect_error) {
        http_response_code(500);
        die(json_encode(["success" => false, "message" => "Koneksi database umum gagal: " . $conn->connect_error]));
    }
    return $conn;
}

// =======================================================================
// HELPER FUNCTIONS
// =======================================================================

// Mapping username ke POP (Digunakan di get_pemasangan.php)
function get_pop_filter($username) {
    // Tips: Gunakan lowercase untuk key agar pencarian tidak case-sensitive jika input bervariasi
    $usernameToPop = [
        "Gofur"   => "rajeg",
        "jihan"   => "rajeg",
        "ALFARIZ" => "rajeg",
        "ARIES"   => "mauk",
        "SARANI"  => "mauk",
        "Fzr41"   => "kemeri",
        "Ramdani" => "kemeri",
        "sopi"    => "kemeri"
    ];
    
    // Cek direct match
    if (isset($usernameToPop[$username])) {
        return $usernameToPop[$username];
    }
    
    return null;
}
?>