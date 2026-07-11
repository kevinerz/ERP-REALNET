<?php
// config/db.php
// Dimigrasikan ke koneksi terpusat (dulu PDO langsung ke u272457353_umumdata)
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

try {
    $pdo = getErpDbPdo();
} catch (Throwable $e) {
    http_response_code(500);
    exit("DB error.");
}
