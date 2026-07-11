<?php
// update_status_selesai.php
session_start();
if (!isset($_SESSION['username'])) { echo "noauth"; exit; }

if (empty($_POST['id'])) { echo "noid"; exit; }
$id = intval($_POST['id']);

$conn = new mysqli("localhost", "u272457353_kevinsamsung9", "Admionkevin99", "u272457353_db_pemasangan");
if ($conn->connect_error) { echo "dberr"; exit; }

$stmt = $conn->prepare("UPDATE pemasangan SET status='selesai' WHERE id=?");
$stmt->bind_param("i", $id);
if($stmt->execute()){
    echo "ok";
} else {
    echo "fail";
}
$stmt->close();
$conn->close();
?>
