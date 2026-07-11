<?php
// Dimigrasikan ke koneksi terpusat (dulu PDO langsung ke u272457353_umumdata)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';

try {
    $pdo = getErpDbPdo();
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
