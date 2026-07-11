<?php
require_once __DIR__ . '/../config/database.php';
// ===== HEADER & CORS =====
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Handle preflight (OPTIONS) untuk browser
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ===== KONEKSI DATABASE =====
$host   = 'localhost';
$user   = 'u272457353_kevinsamsung99';
$pass   = 'Admionkevin99';
$dbname = 'u272457353_umumdata';

$conn = getErpDbConnection();

// Cek koneksi
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

$conn->set_charset('utf8mb4');

// ===== INPUT HANDLING =====
$raw_input = file_get_contents("php://input");
$input     = json_decode($raw_input, true);

if (!is_array($input) || !isset($input['username']) || !isset($input['password'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON format or missing parameters.'
    ]);
    exit;
}

$username = trim((string)$input['username']);
$password = (string)$input['password'];

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username and password are required.'
    ]);
    exit;
}

// ===== AUTHENTICATION (PLAINTEXT) =====
try {
    // Ambil data user
    $stmt = $conn->prepare("SELECT nama, username, password, divisi FROM hr_karyawan WHERE username = ?");
    if ($stmt === false) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user_data = $result->fetch_assoc();
        $stored_plaintext_password = $user_data['password'];

        // Cek password tanpa hash (plaintext)
        if ($password === $stored_plaintext_password) {
            http_response_code(200);
            echo json_encode([
                'success'  => true,
                'message'  => 'Login successful',
                'nama'     => $user_data['nama'],
                'divisi'   => $user_data['divisi'],
                'username' => $user_data['username']
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid username or password.'
            ]);
        }
    } else {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid username or password.'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An internal server error occurred.',
        'error'   => $e->getMessage() // bisa dihapus di production
    ]);
} finally {
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    $conn->close();
}
