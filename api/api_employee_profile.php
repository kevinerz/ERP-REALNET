<?php
require_once __DIR__ . '/../config/database.php';
// =================================================================
// API UNTUK MENGAMBIL PROFIL KARYAWAN (VERSI FIX USERNAME & NAMA)
// =================================================================

// Mengatur header agar output berupa JSON
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Koneksi Database
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u272457353_kevinsamsung99');
define('DB_PASSWORD', 'Admionkevin99');
define('DB_NAME', 'u272457353_umumdata');

$response = array();
$conn = getErpDbConnection();

if ($conn->connect_error) {
    $response['status'] = 'error';
    $response['message'] = "Koneksi database gagal: " . $conn->connect_error;
    echo json_encode($response);
    exit();
}

// -----------------------------------------------------------------
// LOGIKA UTAMA API
// -----------------------------------------------------------------

// Siapkan variabel untuk menampung input
$keyword_pencarian = null;

// Tangkap input (bisa 'username' atau 'nama')
if (isset($_REQUEST['username'])) {
    $keyword_pencarian = $_REQUEST['username'];
} 
else if (isset($_REQUEST['nama'])) {
    $keyword_pencarian = $_REQUEST['nama'];
}

// Lanjutkan proses jika input ada
if ($keyword_pencarian !== null) {
    
    // === PERBAIKAN PENTING DI SINI ===
    // Mencari data dimana 'username' COCOK -ATAU- 'nama' COCOK
    // Ini menangani kasus jika aplikasi mengirim "Gofur" (username) atau "MUHAMMAD GOFYR" (nama)
    
    $sql = "SELECT * FROM hr_karyawan WHERE username = ? OR nama = ?";
    
    $stmt = $conn->prepare($sql);
    
    // Kita bind parameter dua kali ("ss") karena ada dua tanda tanya (?) di query
    // Parameter pertama untuk kolom username, parameter kedua untuk kolom nama
    $stmt->bind_param("ss", $keyword_pencarian, $keyword_pencarian);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Hapus password agar aman
            if (isset($row['password'])) {
                unset($row['password']);
            }
            
            $response['status'] = 'success';
            $response['message'] = 'Data karyawan berhasil ditemukan.';
            $response['data'] = $row;
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Data tidak ditemukan untuk: ' . htmlspecialchars($keyword_pencarian);
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Eksekusi query gagal.';
    }
    $stmt->close();

} else {
    // Jika parameter tidak ada
    $response['status'] = 'error';
    $response['message'] = 'Parameter "username" atau "nama" dibutuhkan.';
}

$conn->close();
echo json_encode($response);
?>