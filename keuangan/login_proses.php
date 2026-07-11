<?php
session_start();
$conn = new mysqli("localhost", "u272457353_kevinsamsung99", "Admionkevin99", "u272457353_umumdata");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$username = $_POST['username'];
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT * FROM karyawan WHERE username = ? AND password = ?");
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
