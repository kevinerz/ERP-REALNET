<?php
session_start();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: index');
    exit;
}
require_once 'config/database.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = 'Username dan Password tidak boleh kosong.';
    } else {
        $stmt = $conn_bbm->prepare("SELECT id, nama, username, password, divisi FROM karyawan WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($password == $user['password']) { // Perbandingan Plaintext
                $_SESSION['loggedin'] = true;
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['divisi'] = $user['divisi'];
                header('Location: index');
                exit;
            } else {
                $error_message = 'Username atau Password salah.';
            }
        } else {
            $error_message = 'Username atau Password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - FMS PT. DATA REAL SOLUSINDO</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');
        :root {
            --gradient-start: #4e54c8;
            --gradient-end: #8f94fb;
            --gradient-main: linear-gradient(45deg, var(--gradient-start), var(--gradient-end));
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem;
        }
        .login-container {
            width: 100%;
            max-width: 900px;
            min-height: 600px;
            display: flex;
            background-color: #fff;
            border-radius: 1.5rem;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .login-branding {
            background-image: var(--gradient-main);
            color: #fff;
            width: 45%;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }
        .login-branding h1 {
            font-size: 4rem;
            font-weight: 800;
            margin: 0;
            text-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .login-branding h2 {
            font-size: 1.2rem;
            font-weight: 400;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        .login-branding p {
            font-weight: 500;
            letter-spacing: 1px;
            border-top: 1px solid rgba(255,255,255,0.5);
            padding-top: 1rem;
            margin-top: 1rem;
        }
        .login-form {
            width: 55%;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-form h3 {
            font-weight: 700;
            color: #333;
            margin-bottom: 1rem;
        }
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        .form-control {
            height: 50px;
            border: 2px solid #e9ecef;
            border-radius: 0.5rem;
            padding-left: 45px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: var(--gradient-start);
            box-shadow: 0 0 0 4px rgba(78, 84, 200, 0.2);
        }
        .form-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
        }
        .btn-login {
            background-image: var(--gradient-main);
            border: none;
            padding: 15px;
            font-weight: 600;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(78, 84, 200, 0.3);
        }
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(78, 84, 200, 0.4);
        }

        @media (max-width: 768px) {
            .login-branding { display: none; }
            .login-form { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-branding">
            <h1>FMS</h1>
            <h2>Finance Management System</h2>
            <p>PT. REAL DATA SOLUSINDO</p>
        </div>
        <div class="login-form">
            <h3>Selamat Datang Kembali!</h3>
            <p class="text-muted mb-4">Silakan masuk untuk melanjutkan.</p>

            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= $error_message ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <i class="bi bi-person-fill"></i>
                    <input type="text" name="username" id="username" class="form-control" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                </div>
                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-login">Login</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>