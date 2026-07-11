<?php
session_start();

// Jika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Koneksi database
    $conn = new mysqli("localhost", "u272457353_kevinsamsung99", "Admionkevin99", "u272457353_umumdata");
    if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

    // Ambil dan rapikan input
    $username = strtolower(trim($_POST['username']));
    $password = trim($_POST['password']);

    // Cek login
    $stmt = $conn->prepare("SELECT * FROM karyawan WHERE LOWER(username) = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $_SESSION['username'] = $row['username'];
        $_SESSION['divisi']   = $row['divisi'];
        $_SESSION['nama']     = $row['nama'];

        // Redirect berdasarkan divisi
        if ($row['divisi'] === 'Leader Area') {
            header("Location: form_reimburse.php");
        } else {
            header("Location: https://datarealsolution.net/list_reimburse.php");
        }
        exit;
    } else {
        $error = "Login gagal. Username atau password salah.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Karyawan</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f0f0f0; }
        form { background: #fff; padding: 20px; width: 300px; margin: auto; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 8px; margin-top: 10px; }
        button { width: 100%; padding: 8px; margin-top: 15px; background: green; color: white; border: none; border-radius: 3px; }
        .error { color: red; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>
    <form method="post">
        <h3>Login Karyawan</h3>
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>
</body>
</html>
