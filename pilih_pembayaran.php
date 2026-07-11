<?php

// pilih_pembayaran.php

require_once __DIR__ . "/config.php"; // Include the configuration file
require_once __DIR__ . "/billing_helper.php"; // Include helper functions

// Fungsi untuk mendapatkan nilai dari $_GET dengan sanitasi dan fallback
function getSanitizedGetParam(string $paramName, string $defaultValue = ''): string {
    return htmlspecialchars($_GET[$paramName] ?? $defaultValue, ENT_QUOTES, 'UTF-8');
}

// Mengambil dan membersihkan data dari URL
$pemasanganId = isset($_GET['pemasangan_id']) ? (int)$_GET['pemasangan_id'] : 0;
$nama         = getSanitizedGetParam('nama', 'Pelanggan');
$paket        = getSanitizedGetParam('paket', 'N/A');
$telp         = getSanitizedGetParam('telp', 'N/A');
$email        = getSanitizedGetParam('email', 'N/A');
$harga        = isset($_GET['harga']) ? (int)$_GET['harga'] : 0;
$periodeAwal  = getSanitizedGetParam('periode_awal', 'N/A');
$periodeAkhir = getSanitizedGetParam('periode_akhir', 'N/A');

// Validasi dasar: Pastikan parameter penting tidak kosong
if ($harga <= 0 || $pemasanganId <= 0) {
    header("HTTP/1.1 400 Bad Request");
    echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>Error Data</title><style>body{display:flex;justify-content:center;align-items:center;height:100vh;font-family:sans-serif;background:#f8f9fa;text-align:center;}.box{padding:2rem;border:1px solid #dee2e6;background:#fff;border-radius:0.5rem;}h3{color:#dc3545;}</style></head><body><div class='box'><h3>⚠️ Informasi Pembayaran Tidak Valid</h3><p>Data harga atau ID pemasangan tidak benar. Mohon hubungi admin.</p></div></body></html>";
    exit();
}

$alertMessage = '';
$alertType = '';
$paymentChannels = [];

// 1. Ambil daftar metode pembayaran dari Tripay
if (defined('TRIPAY_BASE_URL') && defined('TRIPAY_API_KEY')) {
    $tripayChannelUrl = TRIPAY_BASE_URL . "/merchant/payment-channel";
    $curl = curl_init($tripayChannelUrl);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer " . TRIPAY_API_KEY],
        CURLOPT_TIMEOUT        => 15,
    ]);
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        $alertType = 'danger';
        $alertMessage = "Gagal terhubung ke gateway pembayaran. Coba beberapa saat lagi.";
        error_log("Tripay Channel cURL Error: " . $err);
    } else {
        $channelsResult = json_decode($response, true);
        if (isset($channelsResult['success']) && $channelsResult['success']) {
            $paymentChannels = array_filter($channelsResult['data'], fn($channel) => $channel['active']);
        } else {
            $alertType = 'danger';
            $alertMessage = "Metode pembayaran tidak dapat dimuat saat ini.";
            error_log("Tripay Channel API Error: " . ($channelsResult['message'] ?? 'Unknown Error'));
        }
    }
} else {
    $alertType = 'danger';
    $alertMessage = "Konfigurasi payment gateway tidak ditemukan.";
    error_log("Tripay constants are not defined in config.php");
}


// 2. Tangani pemilihan metode pembayaran oleh pelanggan
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['method_code'])) {
    $selectedMethodCode = htmlspecialchars($_POST['method_code']);
    $orderId = 'INSTALL-' . $pemasanganId . '-' . time();

    // Panggil fungsi buatInvoiceTripay dari billing_helper.php
    $urlPembayaran = buatInvoiceTripay(
        $orderId, $nama, $email, $telp, $harga, $paket, $selectedMethodCode
    );

    if ($urlPembayaran) {
        header("Location: " . $urlPembayaran);
        exit();
    } else {
        $alertType = 'danger';
        $alertMessage = "Gagal membuat link pembayaran. Silakan coba metode lain atau hubungi support.";
        error_log("Failed to generate Tripay URL for method: " . $selectedMethodCode . " for ID: " . $pemasanganId);
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran REALNET</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            --primary-color: #005DFF;
            --success-color: #198754;
            --danger-color: #dc3545;
            --text-dark: #1e293b;
            --text-secondary: #64748b;
            --bg-light: #f1f5f9;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --radius: 1rem;
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: var(--text-dark);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 1.5rem;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }

        .payment-container { width: 100%; max-width: 520px; }
        .payment-box {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            padding: 2rem 2.5rem;
            box-shadow: 0 8px 40px rgba(100, 116, 139, 0.12);
            border: 1px solid var(--border-color);
        }
        
        /* --- Penyempurnaan Tipografi --- */
        .header { text-align: center; margin-bottom: 2rem; }
        .brand-title {
            font-weight: 800; font-size: 2.25rem; color: var(--primary-color);
            margin: 0; letter-spacing: -1.5px; /* Huruf lebih rapat untuk kesan premium */
        }
        .page-title {
            font-weight: 700; font-size: 1.5rem; color: var(--text-dark);
            margin-top: 0.5rem; margin-bottom: 0.25rem;
        }
        .page-subtitle {
            font-size: 1rem; font-weight: 400; color: var(--text-secondary);
            margin: 0; line-height: 1.5;
        }

        .details-list { list-style: none; padding: 0; margin: 0; }
        .details-list li {
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.9rem 0; border-bottom: 1px solid var(--border-color);
            font-size: 0.95rem;
        }
        .details-list li:last-child { border-bottom: none; }
        .details-list li span { /* Label: "Nama Pelanggan" */
            color: var(--text-secondary);
            font-weight: 500;
        }
        .details-list li strong { /* Value: "Siti Aisyah" */
            font-weight: 600; color: var(--text-dark); text-align: right;
            word-break: break-all; /* Mencegah email panjang merusak layout */
        }
        .details-list li.total { padding-top: 1rem; font-size: 1.1rem; }
        .total-amount {
            font-size: 1.8rem !important; /* Total dibuat lebih menonjol */
            font-weight: 800 !important; color: var(--success-color) !important;
        }

        .separator { border: 0; height: 1px; background-color: var(--border-color); margin: 1rem 0 2rem 0; }
        
        .selection-title {
            font-weight: 600; font-size: 1.2rem; /* Sedikit lebih besar */
            text-align: center; margin-bottom: 1.5rem; color: var(--text-dark);
        }
        
        .payment-methods-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
            gap: 1rem;
        }

        .btn-method {
            background: none; border: 1px solid var(--border-color); border-radius: 0.75rem; padding: 1rem 0.5rem; width: 100%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            font-family: inherit; color: var(--text-dark); text-align: center; cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .btn-method:hover {
            transform: translateY(-4px) scale(1.03); border-color: var(--primary-color);
            box-shadow: 0 8px 20px rgba(0, 93, 255, 0.15); background-color: var(--card-bg);
        }
        .btn-method:focus-visible { outline: 2px solid var(--primary-color); outline-offset: 2px; }
        .btn-method img { height: 28px; max-width: 60px; margin-bottom: 0.75rem; object-fit: contain; }
        .method-name {
            font-size: 0.875rem; font-weight: 600; line-height: 1.2;
        }

        .footer-note {
            margin-top: 2.5rem; text-align: center; font-size: 0.85rem; color: var(--text-secondary);
            display: flex; align-items: center; justify-content: center; gap: 0.5rem;
        }
        .footer-note .bi { color: var(--success-color); font-size: 1.2rem; }
        .footer-note p { margin: 0; line-height: 1.6; } /* Jarak baris lebih lega */
        
        .alert { padding: 1rem; margin-bottom: 1.5rem; border-radius: 0.5rem; text-align: center; font-weight: 500; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c2c7; }

        @media (max-width: 576px) {
            body { padding: 1rem; }
            .payment-box { padding: 1.5rem; }
            .brand-title { font-size: 2rem; }
            .page-title { font-size: 1.3rem; }
            .total-amount { font-size: 1.6rem !important; }
            .payment-methods-grid { grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
            .btn-method { padding: 0.75rem 0.25rem; border-radius: 0.6rem; }
            .btn-method img { height: 24px; margin-bottom: 0.5rem; }
            .method-name { font-size: 0.8rem; }
        }
    </style>
</head>
<body>

<div class="payment-container">
    <div class="payment-box">
        
        <div class="header">
            <h1 class="brand-title">REALNET</h1>
            <h2 class="page-title">Detail Tagihan Anda</h2>
            <p class="page-subtitle">Pembayaran untuk layanan Pemasangan Baru.</p>
        </div>
        
        <?php if (!empty($alertMessage)): ?>
            <div class="alert alert-danger" role="alert">
                <?= htmlspecialchars($alertMessage); ?>
            </div>
        <?php endif; ?>

        <div class="invoice-details">
            <ul class="details-list">
                <li>
                    <span>Nama Pelanggan</span>
                    <strong><?= htmlspecialchars($nama) ?></strong>
                </li>
                <li>
                    <span>Paket Internet</span>
                    <strong><?= htmlspecialchars($paket) ?></strong>
                </li>
                 <li>
                    <span>Nomor WhatsApp</span>
                    <strong><?= htmlspecialchars($telp) ?></strong>
                </li>
                <li>
                    <span>Periode Aktif</span>
                    <strong><?= htmlspecialchars($periodeAwal) ?> s/d <?= htmlspecialchars($periodeAkhir) ?></strong>
                </li>
                <li class="total">
                    <span>Total Tagihan</span>
                    <strong class="total-amount">Rp<?= number_format($harga, 0, ',', '.') ?></strong>
                </li>
            </ul>
        </div>
        
        <hr class="separator">

        <div class="payment-selection">
            <h3 class="selection-title">Pilih Metode Pembayaran</h3>
            
            <?php if (!empty($paymentChannels)): ?>
                <form method="POST" action="">
                    <div class="payment-methods-grid">
                        <?php foreach ($paymentChannels as $channel): ?>
                            <button type="submit" name="method_code" value="<?= htmlspecialchars($channel['code']) ?>" class="btn-method" title="Bayar dengan <?= htmlspecialchars($channel['name']) ?>">
                                <img src="<?= htmlspecialchars($channel['icon_url']) ?>" alt="<?= htmlspecialchars($channel['name']) ?>">
                                <span class="method-name"><?= htmlspecialchars($channel['name']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </form>
            <?php else: ?>
                 <?php if (empty($alertMessage)): // Tampilkan hanya jika belum ada alert lain ?>
                    <div class="alert alert-danger">
                        Gagal memuat metode pembayaran. Silakan muat ulang halaman atau hubungi support.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="footer-note">
            <i class="bi bi-shield-check"></i>
            <p>Transaksi aman dan terenkripsi. Layanan aktif otomatis setelah pembayaran berhasil.</p>
        </div>
    </div>
</div>

</body>
</html>