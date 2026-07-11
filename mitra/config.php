<?php
// config.php
// Dimigrasikan ke koneksi terpusat (dulu PDO langsung ke u272457353_mitra)
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getErpDbPdo();
} catch (Throwable $e) {
    http_response_code(500);
    exit("DB connection failed.");
}

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
