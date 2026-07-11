<?php
require_once __DIR__ . '/../config/database.php';
// submit_tiket.php - menerima input POST dari AI/webhook dan simpan ke database tiket

date_default_timezone_set("Asia/Jakarta");

$servername = "localhost";
$username   = "u272457353_kevinsamsung";
$password   = "Admionkevin99";
$dbname     = "u272457353_tiket_helpdesk";

$db = getErpDbConnection();
if ($db->connect_error) die("Koneksi gagal: " . $db->connect_error);

$nama      = $_POST['nama'] ?? '';
$alamat    = $_POST['alamat'] ?? '';
$telepon   = $_POST['telepon'] ?? '';
$pop       = $_POST['pop'] ?? '';
$keluhan   = $_POST['keluhan'] ?? '';
$maps_url  = $_POST['maps_url'] ?? '';
$waktu     = date("Y-m-d H:i:s");

if (!$nama || !$alamat || !$telepon || !$pop || !$keluhan) {
    http_response_code(400);
    exit(json_encode(["success" => false, "message" => "Data tidak lengkap"]));
}

$stmt = $db->prepare("INSERT INTO tiket_gangguan (nama, alamat, telepon, pop, keluhan, maps_url, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $nama, $alamat, $telepon, $pop, $keluhan, $maps_url, $waktu);
$stmt->execute();

echo json_encode(["success" => true, "message" => "Tiket berhasil dibuat"]);
