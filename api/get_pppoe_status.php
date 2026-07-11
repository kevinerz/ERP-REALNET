<?php
require 'db_config.php'; // Hanya untuk CORS header

$username = $_GET['username'] ?? '';
$pop = strtolower($_GET['pop'] ?? '');

if (empty($username) || empty($pop)) {
    http_response_code(400);
    die(json_encode(["success" => false, "message" => "Parameter username dan pop wajib diisi."]));
}

$url = ($pop === 'rajeg')
    ? "https://datarealsolution.net/pppoe_status_rajeg.php?username=" . urlencode($username)
    : "https://datarealsolution.net/pppoe_status.php?username=" . urlencode($username);

// Gunakan file_get_contents atau cURL untuk mengambil data
$response = @file_get_contents($url);

if ($response === FALSE) {
    http_response_code(502); // Bad Gateway
    die(json_encode(["success" => false, "message" => "Gagal terhubung ke server status PPPoE."]));
}

// Langsung teruskan respons JSON dari server status
echo $response;
?>