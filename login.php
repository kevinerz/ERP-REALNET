<?php
session_start();

// Matikan tampilan error untuk produksi. Error akan dicatat ke log server jika log_errors aktif di php.ini.
ini_set('display_errors', 0);
error_reporting(0); // Matikan semua pelaporan error ke output

// Konfigurasi Database - SANGAT DIREKOMENDASIKAN untuk memindahkan ini ke file di luar direktori web yang dapat diakses publik.
// Contoh: require_once __DIR__ . '/../config/database.php';
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'u272457353_kevinsamsung99');
define('DB_PASSWORD', 'Admionkevin99'); // PERINGATAN: Password ini disimpan dalam PLAIN TEXT! Sangat tidak aman.
define('DB_NAME', 'u272457353_umumdata');

// Membuat koneksi database
$conn = getErpDbConnection();
if ($conn->connect_error) {
    // Catat error koneksi ke log server, jangan tampilkan ke pengguna
    error_log("Koneksi database gagal: " . $conn->connect_error);
    die("Terjadi kesalahan sistem. Silakan coba lagi nanti."); // Pesan generik untuk pengguna
}

// *** DAFTAR DIVISI YANG MENGARAH KE DASHBOARD ***
// Satukan definisi ini agar konsisten
$dashboard_divisi = ['Admin', 'IT', 'Manager', 'SPV Teknis', 'Finance'];
// *** DAFTAR DIVISI YANG MENGARAH KE MENU TEKNISI ***
$teknisi_divisi = ['Leader Area', 'Teknisi'];

// Redirect jika sudah login
if (isset($_SESSION['username'])) {
    if (in_array($_SESSION['divisi'], $dashboard_divisi)) { // Gunakan variabel yang sama
        header("Location: dashboard.php");
    } else if (in_array($_SESSION['divisi'], $teknisi_divisi)) { // Tambahkan kondisi eksplisit untuk teknisi_divisi
        header("Location: menu_teknisi.php");
    } else {
        // Jika divisi tidak dikenali atau tidak ada di kedua daftar, arahkan ke login dengan error
        session_destroy(); // Hancurkan sesi yang tidak valid
        header("Location: login.php");
        exit;
    }
    exit;
}

// Proses login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $pass = trim($_POST['password']);

    // Gunakan prepared statement untuk mencegah SQL Injection
    $stmt = $conn->prepare("SELECT password, divisi, nama FROM hr_karyawan WHERE username = ?");

    if ($stmt === false) {
        // Catat error jika prepared statement gagal
        error_log("Gagal menyiapkan statement: " . $conn->error);
        $_SESSION['error'] = 'Terjadi kesalahan internal. Silakan coba lagi.';
    } else {
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $db_password = $row['password']; // Password plain-text dari database
            $divisi = $row['divisi'];
            $nama = $row['nama'];

            // Bandingkan password plain-text
            if ($pass == $db_password) {
                // Cek hak akses secara keseluruhan (semua divisi yang diizinkan masuk sistem)
                $all_allowed_divisi = array_merge($dashboard_divisi, $teknisi_divisi);
                if (in_array($divisi, $all_allowed_divisi)) {
                    $_SESSION['username'] = $user;
                    $_SESSION['divisi'] = $divisi;
                    $_SESSION['nama'] = $nama;

                    // Redirect berdasarkan peran (menggunakan variabel yang sama)
                    if (in_array($divisi, $dashboard_divisi)) {
                        header("Location: dashboard.php");
                    } else { // Ini berarti $divisi ada di $teknisi_divisi
                        header("Location: menu_teknisi.php");
                    }
                    exit;
                } else {
                    $_SESSION['error'] = 'Anda tidak memiliki akses ke sistem ini.';
                }
            } else {
                $_SESSION['error'] = 'Username atau password salah.'; // Pesan generik untuk keamanan
            }
        } else {
            $_SESSION['error'] = 'Username atau password salah.'; // Pesan generik untuk keamanan
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Sistem Profesional</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        /* Palet Warna */
        :root {
            --color-white: #ffffff;
            --color-light-grey: #f0f2f5;
            --color-medium-grey: #adb5bd; /* Silver */
            --color-dark-grey: #343a40;
            --color-orange: #fd7e14; /* Oranye utama */
            --color-orange-dark: #e66a00; /* Oranye gelap untuk hover */
            --color-blue-accent: #007bff; /* Bisa dipakai untuk tautan atau aksen minor */
            --color-red-alert: #dc3545;
        }

        /* Desain Latar Belakang - Bentuk abstrak bergerak */
        .background-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            overflow: hidden;
            background: var(--color-light-grey); /* Latar belakang putih/abu-abu terang */
            z-index: -1;
        }

        .background-shapes .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.5); /* Bentuk putih transparan */
            opacity: 0.8;
            border-radius: 50%;
            animation: moveShapes 20s infinite ease-in-out alternate;
            box-shadow: 0 0 15px rgba(0,0,0,0.05);
        }

        .background-shapes .shape:nth-child(1) {
            width: 250px; height: 250px; top: 10%; left: -5%; animation-delay: 0s;
        }
        .background-shapes .shape:nth-child(2) {
            width: 180px; height: 180px; top: 60%; left: 85%; animation-delay: 4s; border-radius: 30%; /* Bentuk kotak membulat */
        }
        .background-shapes .shape:nth-child(3) {
            width: 120px; height: 120px; top: 30%; left: 40%; animation-delay: 8s;
        }
        .background-shapes .shape:nth-child(4) {
            width: 300px; height: 300px; top: 75%; left: 10%; animation-delay: 12s;
        }
        .background-shapes .shape:nth-child(5) {
            width: 100px; height: 100px; top: -10%; left: 70%; animation-delay: 16s; border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%; /* Bentuk tidak beraturan */
        }

        @keyframes moveShapes {
            0% { transform: translate(0, 0) scale(1) rotate(0deg); }
            25% { transform: translate(-20px, 30px) scale(1.1) rotate(15deg); }
            50% { transform: translate(20px, -40px) scale(0.9) rotate(30deg); }
            75% { transform: translate(-30px, 10px) scale(1.2) rotate(45deg); }
            100% { transform: translate(0, 0) scale(1) rotate(0deg); }
        }

        /* Umum Body */
        body {
            font-family: 'Roboto', sans-serif; /* Font yang bersih dan profesional */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
            color: var(--color-dark-grey); /* Warna teks default gelap */
            position: relative;
            z-index: 1;
        }

        /* Container Login */
        .login-container {
            max-width: 450px; /* Lebih lebar sedikit */
            width: 100%;
            animation: fadeInTop 1s ease-out forwards; /* Animasi muncul dari atas */
            z-index: 2;
        }

        /* Logo */
        .logo {
            max-width: 200px; /* Logo lebih besar */
            margin-bottom: 35px; /* Spasi lebih */
            display: block;
            margin-left: auto;
            margin-right: auto;
            filter: drop-shadow(0px 3px 5px rgba(0,0,0,0.1)); /* Bayangan lembut pada logo */
        }

        /* Card - Desain yang lebih minimalis & bersih */
        .card {
            border: none;
            border-radius: 1.25rem; /* Sudut sedikit membulat */
            background-color: var(--color-white); /* Latar belakang putih solid */
            box-shadow: 0 1.5rem 3rem rgba(0,0,0,0.15); /* Bayangan yang lebih profesional */
            overflow: hidden; /* Penting untuk rounded corners pada inner elements */
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-8px); /* Sedikit naik saat hover */
            box-shadow: 0 2rem 4rem rgba(0,0,0,0.2);
        }

        .card-body {
            padding: 3.5rem; /* Padding internal yang lebih besar */
            color: var(--color-dark-grey); /* Teks di dalam card gelap */
        }

        /* Judul Kartu */
        .card-title {
            font-family: 'Lato', sans-serif; /* Font yang berbeda untuk judul */
            font-weight: 700; /* Lebih tebal */
            color: var(--color-dark-grey); /* Warna teks judul gelap */
            margin-bottom: 2.5rem; /* Spasi lebih banyak */
            letter-spacing: 0.5px;
            position: relative;
            text-transform: uppercase; /* Huruf kapital */
        }

        .card-title::after { /* Garis bawah elegan */
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background-color: var(--color-orange);
            border-radius: 5px;
        }

        /* Input Form */
        .form-floating .form-control {
            border-radius: 0.5rem; /* Sudut input yang lebih halus */
            border: 1px solid var(--color-medium-grey); /* Border silver */
            background-color: var(--color-white);
            color: var(--color-dark-grey);
            padding: 1.2rem 1.25rem; /* Padding lebih besar */
            transition: all 0.3s ease;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.05); /* Bayangan dalam halus */
        }
        /* Hapus placeholder agar tidak dobel */
        .form-floating .form-control::placeholder {
            /* Pastikan tidak ada placeholder yang muncul */
            color: transparent; 
        }
        .form-floating .form-control:focus {
            border-color: var(--color-orange); /* Border oranye saat fokus */
            box-shadow: 0 0 0 0.25rem rgba(253, 126, 20, 0.25); /* Efek bayangan oranye */
            background-color: var(--color-white);
        }
        .form-floating label {
            color: var(--color-medium-grey); /* Warna label silver */
            font-weight: 400;
            padding-left: 1.25rem; /* Sesuaikan padding label */
        }
        .form-floating .form-control:focus ~ label,
        .form-floating .form-control:not(:placeholder-shown) ~ label {
            color: var(--color-orange); /* Label oranye saat fokus/ada isi */
        }

        /* Tombol Login */
        .btn-login {
            font-size: 1.15rem; /* Ukuran font lebih besar */
            padding: 1.1rem 2.5rem; /* Padding tombol lebih besar */
            background: linear-gradient(45deg, var(--color-orange), var(--color-orange-dark)); /* Gradien oranye */
            border: none;
            transition: all 0.4s ease;
            border-radius: 0.75rem; /* Sudut tombol lebih halus */
            letter-spacing: 1px; /* Spasi huruf lebih besar */
            font-weight: 600;
            color: var(--color-white);
            box-shadow: 0 0.6rem 1.2rem rgba(253, 126, 20, 0.3); /* Bayangan oranye */
            text-transform: uppercase; /* Huruf kapital */
        }
        .btn-login:hover {
            background: linear-gradient(45deg, var(--color-orange-dark), var(--color-orange)); /* Membalik gradien saat hover */
            transform: translateY(-5px) scale(1.02); /* Efek angkat dan sedikit membesar */
            box-shadow: 0 0.8rem 1.5rem rgba(253, 126, 20, 0.4); /* Bayangan oranye lebih dalam */
        }

        /* Animasi Fade In dari atas */
        @keyframes fadeInTop {
            from { opacity: 0; transform: translateY(-80px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Alert (Pesan Error) */
        .alert {
            border-radius: 0.75rem; /* Sudut yang membulat */
            font-size: 0.95rem;
            padding: 1rem;
            margin-bottom: 2rem;
            background-color: rgba(var(--color-red-alert), 0.1); /* Merah sangat transparan */
            color: var(--color-red-alert); /* Warna teks merah */
            border: 1px solid var(--color-red-alert);
            font-weight: 500;
        }

        /* Media Queries untuk Responsivitas */
        @media (max-width: 768px) {
            .card-body {
                padding: 2.5rem;
            }
            .card-title {
                font-size: 1.6rem;
                margin-bottom: 2rem;
            }
            .btn-login {
                font-size: 1.05rem;
                padding: 0.9rem 2rem;
            }
            .logo {
                max-width: 180px;
                margin-bottom: 30px;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            .card-body {
                padding: 2rem;
            }
            .logo {
                max-width: 150px;
                margin-bottom: 25px;
            }
            .card-title {
                font-size: 1.4rem;
                margin-bottom: 1.5rem;
            }
            .card-title::after {
                bottom: -8px;
            }
            .btn-login {
                font-size: 0.95rem;
                padding: 0.8rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-sm-10 col-md-8 col-lg-6 mx-auto">
                <div class="login-container">
                    <div class="text-center">
                        <img src="logo.png" alt="Logo Perusahaan" class="logo img-fluid">
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title text-center">LOGIN SISTEM</h5>
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger text-center" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
                                </div>
                            <?php endif; ?>
                            <form action="login.php" method="POST">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="username" name="username" required autocomplete="username">
                                    <label for="username"><i class="fas fa-user me-2"></i>Username</label>
                                </div>
                                <div class="form-floating mb-4">
                                    <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                                    <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary btn-login text-uppercase fw-bold" type="submit">
                                        <i class="fas fa-sign-in-alt me-2"></i>Masuk
                                    </button>
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