<?php
session_start();
require_once 'koneksi.php'; // Koneksi ke database MITRA (lokal)

// Fungsi Normalisasi Nomor HP (sama seperti saat daftar)
function normalizePhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }
    return $phone;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wa_input = normalizePhoneNumber($_POST['wa']);
    
    // Cek apakah nomor WA ada di database mitra
    $stmt = $conn->prepare("SELECT id, nama, wa FROM mitra WHERE wa = ?");
    $stmt->bind_param("s", $wa_input);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // Set Session
        $_SESSION['mitra_id'] = $row['id'];
        $_SESSION['mitra_nama'] = $row['nama'];
        $_SESSION['mitra_wa'] = $row['wa'];
        $_SESSION['login_mitra'] = true;

        header("Location: mitradashboard.php");
        exit();
    } else {
        $error = "Nomor WhatsApp tidak terdaftar sebagai Mitra.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Mitra RealNet</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .logo { max-width: 150px; margin-bottom: 20px; }
        h2 { color: #A00000; margin-bottom: 20px; }
        input { width: 100%; padding: 12px; margin: 10px 0 20px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #A00000; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        button:hover { background: #800000; }
        .error { color: red; font-size: 0.9em; margin-bottom: 15px; }
        .back-link { display: block; margin-top: 15px; color: #666; text-decoration: none; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class="login-card">
        <img src="logo.png" alt="RealNet" class="logo">
        <h2>Login Mitra</h2>
        <?php if($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
        
        <form method="POST">
            <label style="text-align:left; display:block; font-weight:600;">Nomor WhatsApp</label>
            <input type="text" name="wa" placeholder="Contoh: 08123456789" required inputmode="numeric">
            <button type="submit">Masuk Dashboard</button>
        </form>
        <a href="index.php" class="back-link">← Kembali ke Halaman Daftar</a>
    </div>
</body>
</html>