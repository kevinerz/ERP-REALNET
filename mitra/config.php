<?php
// config.php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
date_default_timezone_set('Asia/Jakarta');

$db_host = "localhost";
$db_name = "u272457353_mitra";
$db_user = "u272457353_kevinsamsungk";
$db_pass = "Admionkevin99";

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";

try {
  $pdo = new PDO($dsn, $db_user, $db_pass, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  exit("DB connection failed.");
}

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
