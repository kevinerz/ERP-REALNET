<?php
/**
 * TEST LOGIN ROS7 REST API (port 2023)
 * - Cek koneksi + autentikasi basic auth
 * - Endpoint: /rest/system/resource
 *
 * Pakai:
 *   test_login_rest.php?run=1
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL);

// ====== CONFIG ======
define('MT_SCHEME', 'http');              // http untuk port 2023 (sesuai screenshot)
define('MT_HOST',   '103.68.214.135');
define('MT_PORT',   2023);
define('MT_USER',   'NOC@kevin');
define('MT_PASS',   '2025kevin');

define('REST_BASE', MT_SCHEME.'://'.MT_HOST.':'.MT_PORT.'/rest');

$run = ($_GET['run'] ?? '') === '1'
    || (php_sapi_name() === 'cli' && in_array('run=1', $argv ?? [], true));

if (!$run) {
    echo json_encode([
        "success" => false,
        "message" => "Tambahkan ?run=1 untuk test login",
        "rest_base" => REST_BASE,
        "example" => basename(__FILE__) . "?run=1"
    ], JSON_PRETTY_PRINT);
    exit;
}

function rest_get(string $path): array {
    $url = REST_BASE . $path;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        CURLOPT_USERPWD        => MT_USER . ':' . MT_PASS,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => 40,
        CURLOPT_HTTPHEADER     => ['Accept: application/json'],
    ]);

    if (MT_SCHEME === 'https') {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
        throw new Exception("cURL error: $err");
    }

    $json = json_decode($resp, true);
    return [$code, $json, $resp, $url];
}

try {
    $t0 = microtime(true);

    // Test endpoint yang paling umum & ringan
    [$code, $json, $raw, $url] = rest_get('/system/resource');

    $ms = (int)round((microtime(true) - $t0) * 1000);

    // Kategori hasil
    if ($code === 401 || $code === 403) {
        echo json_encode([
            "success" => false,
            "status" => "AUTH_FAILED",
            "http_code" => $code,
            "url" => $url,
            "elapsed_ms" => $ms,
            "raw" => $raw
        ], JSON_PRETTY_PRINT);
        exit;
    }

    if ($code < 200 || $code >= 300) {
        echo json_encode([
            "success" => false,
            "status" => "HTTP_ERROR",
            "http_code" => $code,
            "url" => $url,
            "elapsed_ms" => $ms,
            "raw" => $raw
        ], JSON_PRETTY_PRINT);
        exit;
    }

    echo json_encode([
        "success" => true,
        "status" => "LOGIN_OK",
        "http_code" => $code,
        "url" => $url,
        "elapsed_ms" => $ms,
        "data_preview" => [
            "version" => $json["version"] ?? null,
            "uptime" => $json["uptime"] ?? null,
            "cpu-load" => $json["cpu-load"] ?? null,
            "free-memory" => $json["free-memory"] ?? null,
            "total-memory" => $json["total-memory"] ?? null,
        ],
        "raw_length" => strlen($raw)
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "status" => "CONNECT_ERROR",
        "rest_base" => REST_BASE,
        "error" => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
