<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect ke halaman login jika belum login
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Menu Teknisi - PT. DATA REAL SOLUSINDO</title>

  <!-- Manifest & Theme Color PWA -->
  <link rel="manifest" href="/manifest.json" />
  <meta name="theme-color" content="#0d6efd" />

  <!-- Bootstrap 5 CSS -->
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />

  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f8f9fa;
      color: #212529;
    }

    .content-container {
      max-width: 480px;
      margin: 30px auto;
      padding: 0 15px 40px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }

    .welcome-message {
      text-align: center;
      margin-bottom: 40px;
      padding: 20px 15px;
      background: #ffffff;
      box-shadow: 0 4px 15px rgb(0 0 0 / 0.07);
      border-radius: 12px;
      width: 100%;
    }

    .welcome-message h2 {
      font-weight: 700;
      font-size: 1.8rem;
      color: #0d6efd;
      margin-bottom: 5px;
    }

    .welcome-message p {
      font-size: 1rem;
      color: #495057;
      margin: 0;
    }

    /* Kotak menu dengan grid 2 kolom */
    .menu-box {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 20px;
      width: 100%;
      margin-bottom: 40px;
    }

    .menu-item {
      position: relative;
      background-color: #0d6efd;
      color: white;
      border-radius: 15px;
      padding: 18px 10px;
      font-weight: 600;
      font-size: 1.1rem;
      text-align: center;
      text-decoration: none;
      box-shadow: 0 6px 15px rgb(13 110 253 / 0.3);
      transition: background-color 0.3s ease, transform 0.15s ease;
      user-select: none;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .menu-item:hover,
    .menu-item:focus-visible {
      background-color: #084298;
      transform: translateY(-4px);
      text-decoration: none;
      outline: none;
    }

    .menu-item .badge {
      position: absolute;
      top: 8px;
      right: 12px;
      background-color: #dc3545;
      font-weight: 700;
      font-size: 0.75rem;
      padding: 0.3em 0.55em;
      border-radius: 10rem;
      box-shadow: 0 1px 5px rgb(220 53 69 / 0.7);
      user-select: none;
    }

    .logout-btn {
      background-color: #dc3545;
      color: white;
      border-radius: 25px;
      padding: 12px 30px;
      font-size: 1.1rem;
      font-weight: 600;
      width: 100%;
      max-width: 320px;
      text-align: center;
      text-decoration: none;
      box-shadow: 0 5px 14px rgb(220 53 69 / 0.5);
      transition: background-color 0.3s ease, box-shadow 0.2s ease;
      user-select: none;
      cursor: pointer;
    }

    .logout-btn:hover,
    .logout-btn:focus-visible {
      background-color: #b02a37;
      box-shadow: 0 8px 18px rgb(176 42 55 / 0.7);
      outline: none;
      text-decoration: none;
      color: white;
    }

    /* Tombol Install PWA */
    #btnInstall {
      position: fixed;
      bottom: 25px;
      right: 25px;
      display: none;
      background-color: #198754;
      color: #fff;
      border: none;
      border-radius: 50%;
      width: 60px;
      height: 60px;
      font-size: 28px;
      box-shadow: 0 3px 12px rgb(25 135 84 / 0.6);
      cursor: pointer;
      user-select: none;
      transition: background-color 0.3s ease;
      z-index: 1000;
    }

    #btnInstall:hover,
    #btnInstall:focus-visible {
      background-color: #146c43;
      outline: none;
    }

    /* Responsive: 1 kolom di layar kecil */
    @media (max-width: 600px) {
      .menu-box {
        grid-template-columns: 1fr;
      }
      .logout-btn {
        max-width: 100%;
      }
    }
  </style>
</head>
<body>
  <div class="content-container">
    <section class="welcome-message" role="region" aria-label="Selamat datang">
      <h2>Selamat datang, <?= htmlspecialchars($_SESSION['username']); ?>!</h2>
      <p>Divisi Anda: <?= htmlspecialchars($_SESSION['divisi'] ?? '-'); ?></p>
    </section>

    <nav class="menu-box" role="navigation" aria-label="Menu utama teknisi">
      <a href="tiket/gangguan_teknisi.php" class="menu-item" tabindex="0"
        >GANGGUAN <span class="badge rounded-pill" aria-label="99 lebih notifikasi">99+</span></a
      >
      <a href="pemasangan_teknisi.php" class="menu-item" tabindex="0"
        >PASANG <span class="badge rounded-pill" aria-label="99 lebih notifikasi">99+</span></a
      >
      <a href="employee_profile.php" class="menu-item" tabindex="0"
        >PROFILE <span class="badge rounded-pill" aria-label="99 lebih notifikasi">99+</span></a
      >
      <a href="keuangan/form_reimburse.php" class="menu-item" tabindex="0"
        >BBM <span class="badge rounded-pill" aria-label="99 lebih notifikasi">99+</span></a
      >
      <a href="aset/dashboard.php" class="menu-item" tabindex="0"
        >ASSET <span class="badge rounded-pill" aria-label="99 lebih notifikasi">99+</span></a
      >
      <a href="kasbon/form_kasbon.php" class="menu-item" tabindex="0"
        >KASBON <span class="badge rounded-pill" aria-label="99 lebih notifikasi">99+</span></a
      >
    </nav>

    <a href="logout.php" class="logout-btn" role="button" aria-label="Logout dari aplikasi">Logout</a>
  </div>

  <button id="btnInstall" aria-label="Pasang aplikasi ke perangkat">⇩</button>

  <script>
    let deferredPrompt;

    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredPrompt = e;
      document.getElementById('btnInstall').style.display = 'block';
      console.log('beforeinstallprompt fired');
    });

    document.getElementById('btnInstall').addEventListener('click', async () => {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      const choiceResult = await deferredPrompt.userChoice;
      console.log('User choice:', choiceResult.outcome);
      deferredPrompt = null;
      document.getElementById('btnInstall').style.display = 'none';
    });

    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker
          .register('/sw.js')
          .then((reg) => console.log('Service Worker terdaftar:', reg.scope))
          .catch((err) => console.error('Gagal daftar Service Worker:', err));
      });
    }
  </script>

  <!-- Bootstrap 5 Bundle JS (Popper included) -->
  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"
    crossorigin="anonymous"
  ></script>
</body>
</html>
