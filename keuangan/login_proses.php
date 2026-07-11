<?php
require_once __DIR__ . '/../config/database.php';
session_start();
$conn = getErpDbConnection();
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM hr_karyawan WHERE username = ? AND password = ?");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $_SESSION['username'] = $row['username'];
    $_SESSION['divisi'] = $row['divisi'];
    $_SESSION['nama'] = $row['nama'];
    header("Location: list_reimburse.php");
} else {
    echo "Login gagal. <a href='login.php'>Coba lagi</a>";
}
?>
