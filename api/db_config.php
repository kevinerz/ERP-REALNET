<?php
// =======================================================================
// KONFIGURASI DATABASE & CORS
// (Dimigrasikan: get_conn_pemasangan() & get_conn_umum() dulu masing-masing
//  konek ke database berbeda (db_pemasangan & umumdata) -- sekarang
//  keduanya sudah digabung jadi satu database `erprealnet`, jadi keduanya
//  mengembalikan koneksi yang sama. Nama fungsi dipertahankan supaya kode
//  pemanggil di file lain tidak perlu diubah.)
// =======================================================================

require_once __DIR__ . '/../config/database.php';

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

// =======================================================================
// FUNGSI KONEKSI (sekarang keduanya ke database erprealnet yang sama)
// =======================================================================

function get_conn_pemasangan() {
    return getErpDbConnection();
}

function get_conn_umum() {
    return getErpDbConnection();
}

// =======================================================================
// HELPER FUNCTIONS
// =======================================================================

// Mapping username ke POP (Digunakan di get_pemasangan.php)
function get_pop_filter($username) {
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

    if (isset($usernameToPop[$username])) {
        return $usernameToPop[$username];
    }

    return null;
}
