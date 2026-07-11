<?php
require_once __DIR__ . '/config/database.php';
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);


// Database config
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u272457353_kevinsamsung99');
define('DB_PASSWORD', 'Admionkevin99');
define('DB_NAME', 'u272457353_umumdata');

$conn = getErpDbConnection();
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Redirect jika sudah login
if (isset($_SESSION['username'])) {
    if (in_array($_SESSION['divisi'], ['Admin', 'IT', 'Manager','Leader Area','SPV Teknis'])) {
        header("Location: dashboard.php");
    } else {
        header("Location: menu_teknisi.php");
    }
    exit;
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM hr_karyawan WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $db_password = $row['password']; // plain-text
        $divisi = $row['divisi'];
        $nama = $row['nama'];

        if ($pass == $db_password) {
            // Cek hak akses (tambahkan Manager)
            if (in_array($divisi, ['IT', 'Teknisi', 'Admin', 'Manager','Leader Area','SPV Teknis','Finance'])) {
                $_SESSION['username'] = $user;
                $_SESSION['divisi'] = $divisi;
                $_SESSION['nama'] = $nama;

                // Redirect berdasarkan peran
                if (in_array($divisi, ['Admin', 'IT', 'Manager','SPV Teknis','Finance'])) {
                    header("Location: dashboard.php");
                } else {
                    header("Location: menu_teknisi.php");
                }
                exit;
            } else {
                $_SESSION['error'] = 'Anda tidak memiliki akses ke sistem ini.';
            }
        } else {
            $_SESSION['error'] = 'Username atau password salah.';
        }
    } else {
        $_SESSION['error'] = 'Username atau password salah.';
    }

    $stmt->close();
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(120deg, #84fab0 0%, #8fd3f4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 15px;
            animation: fadeIn 1s ease-in-out;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
        }
        .card-body {
            padding: 2rem;
        }
        .btn-login {
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
            background-color: #4CAF50;
            border-color: #4CAF50;
            transition: all 0.3s ease;
        }
        .btn-login:hover {
            background-color: #45a049;
            border-color: #45a049;
            transform: translateY(-2px);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">
        <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
            <div class="login-container">
                <div class="text-center mb-4">
                    <img src="logo.png" alt="Logo" class="logo">
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-center mb-4">Login Sistem</h5>
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                        <?php endif; ?>
                        <form action="login.php" method="POST">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" placeholder="Username" required>
                                <label for="username">Username</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                                <label for="password">Password</label>
                            </div>
                            <div class="d-grid">
                                <button class="btn btn-primary btn-login text-uppercase fw-bold" type="submit">Sign in</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
