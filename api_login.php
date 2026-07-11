<?php
require_once __DIR__ . '/config/database.php';
// Set the content type to JSON and allow cross-origin requests (CORS)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allows any domain to access this API
header('Access-Control-Allow-Methods: POST'); // Specifies the allowed HTTP method(s)
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With'); // Specifies allowed headers

// --- Database Connection ---
// It's a good practice to keep credentials in a separate, non-public file.
$host = 'localhost';
$user = 'u272457353_kevinsamsung99';
$pass = 'Admionkevin99'; // Note: Storing passwords in code is risky. Consider environment variables.
$dbname = 'u272457353_umumdata';

$conn = getErpDbConnection();

// Check for a connection error
if ($conn->connect_error) {
    // Stop execution and send a failure response
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

// --- Input Handling ---
// Get the raw JSON data from the request body
$raw_input = file_get_contents("php://input");
$input = json_decode($raw_input, true); // Decode the JSON into an associative array

// Check if JSON decoding was successful and if the required keys exist
if (!$input || !isset($input['username']) || !isset($input['password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid JSON format or missing parameters.']);
    exit;
}

// Trim whitespace from username and password
$username = trim($input['username']);
$password = $input['password']; // No need to trim the password, as spaces can be intentional

// Validate that username and password are not empty
if ($username === '' || $password === '') {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Username and password are required.']);
    exit;
}

// --- User Authentication ---
try {
    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT nama, password, divisi FROM hr_karyawan WHERE username = ?");
    if ($stmt === false) {
        throw new Exception('Failed to prepare statement: ' . $conn->error);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // User found, fetch the data
        $user_data = $result->fetch_assoc();
        $stored_hash = $user_data['password'];

        // --- IMPORTANT SECURITY STEP ---
        // Verify the provided password against the stored hash
        if (password_verify($password, $stored_hash)) {
            // Password is correct!
            http_response_code(200); // OK
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'nama' => $user_data['nama'],
                'divisi' => $user_data['divisi']
            ]);
        } else {
            // Password does not match
            http_response_code(401); // Unauthorized
            echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
        }
    } else {
        // Username not found
        http_response_code(401); // Unauthorized
        echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
    }
} catch (Exception $e) {
    // Catch any other server-side errors
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'An internal server error occurred.', 'error' => $e->getMessage()]);
} finally {
    // Always close the statement and connection
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}
?>