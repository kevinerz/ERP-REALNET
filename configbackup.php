<?php

// config.php

// Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Timezone Setting
date_default_timezone_set('Asia/Jakarta'); // Indonesia (Western Indonesian Time)

// config.php
define('BASE_APP_URL', 'https://datarealsolution.net'); // Make sure this is correct

// --- Database Configuration ---
define('DB_HOST', 'localhost');

// For u272457353_db_pemasangan
define('DB_USER_PEMASANGAN', 'u272457353_kevinsamsung9');
define('DB_PASS_PEMASANGAN', 'Admionkevin99');
define('DB_NAME_PEMASANGAN', 'u272457353_db_pemasangan');

// For u272457353_umumdata
define('DB_USER_UMUMDATA', 'u272457353_kevinsamsung99');
define('DB_PASS_UMUMDATA', 'Admionkevin99');
define('DB_NAME_UMUMDATA', 'u272457353_umumdata');

// --- Tripay Configuration ---
define('TRIPAY_API_KEY', '20Tc8Y8CUXT8BWNCnv8RYDnfQa98cC2mE9DfOgwo'); // <--- UPDATE THIS LINE
define('TRIPAY_PRIVATE_KEY', 'AG53I-w5uBC-Ijmak-RAeXV-Kuvpn'); // This one seems consistent
define('TRIPAY_MERCHANT_CODE', 'T41755'); // Make sure this is also correct for your account
define('TRIPAY_BASE_URL', 'https://tripay.co.id/api'); // Make sure this matches your key's environment (production for this URL)

// --- URL Shortener Configuration (TinyURL) ---
define('SHORTENER_BASE_URL', 'https://api.tinyurl.com'); // Base URL for the modern TinyURL API
define('SHORTENER_API_KEY', 'QTUkIeoehJ53UXcI6YFtzyaL8iq8Cn9CNB7XlmYCP1PnnVxryb24uiKE25CQ'); // <--- YOUR NEW TINYURL API KEY


// --- WhatsApp API Configuration (Starsender) ---
define('WA_API_TOKEN', 'e9c50247-3b8d-4cd8-924a-024a4d2b3124'); // Your Starsender API Token
define('WA_API_URL', 'https://api.starsender.online/api/send'); // Starsender API Send Message Endpoint

// --- Prorata Package IDs (Example) ---
// These are the package IDs from your 'paket' table that require prorated billing.
define('PRORATA_PACKAGE_IDS', [25, 28, 31, 32]);


$servername = "localhost";
$username = "u272457353_kevinsamsung9";
$password = "Admionkevin99";
$database = "u272457353_db_pemasangan";

// Koneksi ke database
$conn = new mysqli($servername, $username, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
