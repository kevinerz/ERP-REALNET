<?php
require_once __DIR__ . '/config/database.php';
// update_status_selesai.php
session_start();
if (!isset($_SESSION['username'])) { echo "noauth"; exit; }

if (empty($_POST['id'])) { echo "noid"; exit; }
$id = intval($_POST['id']);

$conn = getErpDbConnection();
if ($conn->connect_error) { echo "dberr"; exit; }

$stmt = $conn->prepare("UPDATE pelanggan_instalasi SET status='selesai' WHERE id=?");
$stmt->bind_param("i", $id);
if($stmt->execute()){
    echo "ok";
} else {
    echo "fail";
}
$stmt->close();
$conn->close();
?>
