<?php
require_once __DIR__ . '/../config/database.php';
// File: fetch_tickets.php
header('Content-Type: application/json');

$db = getErpDbConnection();
if ($db->connect_error) {
    echo json_encode([]);
    exit;
}

// Ambil ID tiket terakhir yang sudah ditampilkan di dasbor klien
$since_id = isset($_GET['since']) ? intval($_GET['since']) : 0;

// Ambil semua tiket yang LEBIH BARU dari tiket terakhir yang dilihat
$query = "SELECT * FROM tiket_ai WHERE (status = 'pending' OR status LIKE 'awaiting_%') AND id > ? ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->bind_param("i", $since_id);
$stmt->execute();
$result = $stmt->get_result();

$tickets = [];
while ($row = $result->fetch_assoc()) {
    $tickets[] = $row;
}

echo json_encode($tickets);

$stmt->close();
$db->close();
?>