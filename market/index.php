<?php
// --- KONFIGURASI SISTEM ---
// Ganti dengan API Key StarSender Anda
define('STARSENDER_API_KEY', '7106aa0b-0eb0-4673-aaf6-470ccc1f2390'); 
// Ganti dengan ID Group Admin atau Nomor Admin (Format: 628xxx)
define('ADMIN_CONTACT', '120363418654328024@g.us'); 

$submission_status = null;
$error_message = '';

// Fungsi Normalisasi Nomor HP (08xx -> 628xx)
function normalizePhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') {
        $phone = '62' . substr($phone, 1);
    }
    return $phone;
}

// Fungsi Format Rupiah
function formatRupiah($angka){
    return "Rp " . number_format($angka,0,',','.');
}

// Fungsi Kirim WA
function kirim_wa($to, $pesan) {
    $curl = curl_init();
    $body = ["messageType" => "text", "to" => $to, "body" => $pesan];

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.starsender.online/api/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => array('Content-Type: application/json', 'Authorization: ' . STARSENDER_API_KEY),
    ));
    $response = curl_exec($curl);
    curl_close($curl);
}

// LOGIKA PEMROSESAN FORM
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = htmlspecialchars(trim($_POST['nama'] ?? ''));
    $wa_raw = htmlspecialchars(trim($_POST['whatsapp'] ?? ''));
    $payment_type = htmlspecialchars($_POST['payment_type'] ?? '');
    $bank_nama = htmlspecialchars($_POST['bank_nama'] ?? '');
    $bank_rekening = htmlspecialchars($_POST['bank_rekening'] ?? '');
    $e_wallet_nama = htmlspecialchars($_POST['e_wallet_nama'] ?? '');
    $e_wallet_nomor = htmlspecialchars($_POST['e_wallet_nomor'] ?? '');

    $wa_api = normalizePhoneNumber($wa_raw);
    require_once 'koneksi.php'; // Pastikan file koneksi.php ada

    if (empty($nama) || empty($wa_raw) || empty($payment_type)) {
        $submission_status = 'error';
        $error_message = 'Mohon lengkapi data wajib.';
    } else {
        $stmt = $conn->prepare("INSERT INTO mitra (nama, wa, payment_type, bank_nama, bank_rekening, e_wallet_nama, e_wallet_nomor) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("sssssss", $nama, $wa_api, $payment_type, $bank_nama, $bank_rekening, $e_wallet_nama, $e_wallet_nomor);
            if ($stmt->execute()) {
                $submission_status = 'success';

                // Pesan WhatsApp
                $metode_bayar = ($payment_type === 'bank') ? "🏦 Bank: $bank_nama\n💳 Rek: $bank_rekening" : "📱 Wallet: $e_wallet_nama\n🆔 Akun: $e_wallet_nomor";

                $pesan_admin = "📥 *PENDAFTARAN MITRA BARU*\n--------------------------------\n👤 Nama: $nama\n📞 WA: $wa_api\n$metode_bayar\n--------------------------------";
                
                $pesan_user = "Halo Kak *$nama*! 👋\n\nSelamat bergabung di Program Afiliasi RealNet!\nData Anda telah kami terima.\n\nSimpan link ini untuk mendaftarkan pelanggan:\n👉 https://datarealsolution.net/marketdaftar.php\n\n_PT Real Data Solusindo_";

                kirim_wa(ADMIN_CONTACT, $pesan_admin);
                kirim_wa($wa_api, $pesan_user);
            } else {
                $submission_status = 'error';
                $error_message = 'Gagal menyimpan data.';
            }
            $stmt->close();
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Program Afiliasi RealNet - PT Real Data Solusindo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #A00000;
            --primary-dark: #800000;
            --text-main: #333;
            --text-muted: #666;
            --bg-light: #f8f9fa;
            --card-bg: #ffffff;
            --border-radius: 12px;
            --shadow: 0 8px 25px rgba(0,0,0,0.08);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-light);
            color: var(--text-main);
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--card-bg);
            padding: 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .logo {
            display: block;
            max-width: 200px;
            margin: 0 auto 20px auto;
        }

        h1, h2, h3 {
            font-family: 'Merriweather', serif;
            color: var(--primary);
            margin-bottom: 15px;
        }

        h1 { text-align: center; font-size: 1.8rem; border-bottom: 2px solid #eee; padding-bottom: 15px; }
        h2 { font-size: 1.4rem; margin-top: 30px; border-left: 5px solid var(--primary); padding-left: 15px; }
        h3 { font-size: 1.2rem; color: #444; }

        p { text-align: justify; margin-bottom: 15px; font-size: 0.95rem; }

        /* Highlight Box */
        .info-box {
            background: #fff5f5;
            border: 1px solid #ffcccc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        .info-box ul { margin: 0; padding-left: 20px; }
        .info-box li { margin-bottom: 8px; }

        /* Form Styling */
        form {
            background: #fdfdfd;
            padding: 25px;
            border: 1px solid #eee;
            border-radius: 10px;
            margin-top: 20px;
        }

        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; }
        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            box-sizing: border-box;
        }
        input:focus, select:focus { border-color: var(--primary); outline: none; }

        button.btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: 0.3s;
        }
        button.btn-submit:hover { background: var(--primary-dark); transform: translateY(-2px); }

        /* Alerts */
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Career Path Styles */
        .career-list {
            list-style: none;
            padding: 0;
        }
        .career-list li {
            position: relative;
            padding-left: 20px;
            margin-bottom: 15px;
            border-left: 3px solid #ddd;
        }
        .career-list li:hover { border-left-color: var(--primary); }
        .career-list strong { color: var(--primary); font-size: 1.05rem; display: block; }

        /* Package Grid */
        .package-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
        }
        .package-card {
            background: #fff;
            border: 1px solid #eee;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: 0.3s;
        }
        .package-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: var(--primary); }
        .package-title { font-weight: 700; color: var(--primary); font-size: 1.1rem; margin-bottom: 5px; }
        .package-price { font-size: 0.9rem; color: #555; font-weight: 600; margin-bottom: 8px; }
        .package-desc { font-size: 0.85rem; color: #777; line-height: 1.4; }

        .hidden { display: none; }
        hr { border: 0; height: 1px; background: #eee; margin: 40px 0; }

        /* Mobile */
        @media (max-width: 600px) {
            .container { padding: 20px; }
            h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="container">
    <img src="logo.png" alt="Logo PT Real Data Solusindo" class="logo">

    <h1>Program Kemitraan Afiliasi RealNet</h1>

    <p>
        Dengan hormat, kami mengundang Anda untuk bergabung dalam Program Kemitraan Afiliasi RealNet yang diselenggarakan oleh <strong>PT Real Data Solusindo</strong>. Program ini didesain untuk memberikan peluang bagi individu yang berdedikasi untuk memperoleh penghasilan tambahan yang signifikan melalui skema komisi yang atraktif.
    </p>

    <div class="info-box">
        <h3>💰 Keuntungan Mitra:</h3>
        <ul>
            <li><strong>Mitra Marketing:</strong> Komisi <strong>Rp 50.000,-</strong> untuk setiap pemasangan sukses.</li>
            <li><strong>PIC (Koordinator):</strong> Komisi Passive <strong>Rp 5.000,-</strong> per bulan untuk setiap pelanggan aktif di bawah koordinasi Anda.</li>
        </ul>
    </div>

    <?php if ($submission_status === 'success'): ?>
        <div class="alert alert-success">✅ Pendaftaran Berhasil! Tim kami akan segera menghubungi Anda via WhatsApp.</div>
    <?php elseif ($submission_status === 'error'): ?>
        <div class="alert alert-error">⚠️ <?= $error_message ?: 'Gagal memproses data. Silakan coba lagi.' ?></div>
    <?php endif; ?>

    <h2>Formulir Pendaftaran</h2>
    <form method="post" action="" id="regForm">
        <label for="nama">Nama Lengkap (Sesuai KTP):</label>
        <input type="text" id="nama" name="nama" placeholder="Contoh: Budi Santoso" required>

        <label for="whatsapp">Nomor WhatsApp Aktif:</label>
        <input type="text" id="whatsapp" name="whatsapp" placeholder="Contoh: 081234567890" inputmode="numeric" required>

        <label for="payment_type">Metode Pencairan Komisi:</label>
        <select id="payment_type" name="payment_type" onchange="togglePaymentFields()" required>
            <option value="">-- Pilih Metode --</option>
            <option value="bank">Transfer Bank</option>
            <option value="e_wallet">E-Wallet (Dana/Ovo/Gopay)</option>
        </select>

        <div id="bank_fields" class="hidden">
            <label for="bank_nama">Nama Bank:</label>
            <select id="bank_nama" name="bank_nama">
                <option value="">-- Pilih Bank --</option>
                <option value="BCA">BCA</option>
                <option value="BRI">BRI</option>
                <option value="Mandiri">Mandiri</option>
                <option value="BNI">BNI</option>
                <option value="CIMB">CIMB Niaga</option>
                <option value="BSI">BSI</option>
                <option value="Lainnya">Lainnya</option>
            </select>
            <label for="bank_rekening">Nomor Rekening:</label>
            <input type="text" id="bank_rekening" name="bank_rekening" placeholder="Masukkan nomor rekening">
        </div>

        <div id="e_wallet_fields" class="hidden">
            <label for="e_wallet_nama">Pilih E-Wallet:</label>
            <select id="e_wallet_nama" name="e_wallet_nama">
                <option value="">-- Pilih E-Wallet --</option>
                <option value="DANA">DANA</option>
                <option value="Gopay">Gopay</option>
                <option value="OVO">OVO</option>
                <option value="ShopeePay">ShopeePay</option>
            </select>
            <label for="e_wallet_nomor">Nomor HP E-Wallet:</label>
            <input type="text" id="e_wallet_nomor" name="e_wallet_nomor" placeholder="08xxx">
        </div>

        <button type="submit" class="btn-submit" id="submitBtn">Daftar Sekarang</button>
    </form>

    <hr>

    <h2>📈 Jenjang Karir PIC RealNet</h2>
    <p>Kami menghargai dedikasi Anda. Berikut adalah jenjang karir progresif bagi para PIC berdasarkan jumlah pelanggan aktif:</p>
    
    <ul class="career-list">
        <li>
            <strong>10 Pelanggan Aktif: PIC Junior</strong>
            <span style="font-size:0.9rem; color:#666;">Anda resmi diakui dan mendapatkan dukungan pelatihan awal.</span>
        </li>
        <li>
            <strong>100 Pelanggan Aktif: PIC Senior</strong>
            <span style="font-size:0.9rem; color:#666;">Peran lebih strategis dalam pengembangan area dan memimpin tim kecil.</span>
        </li>
        <li>
            <strong>500 Pelanggan Aktif: Supervisor PIC</strong>
            <span style="font-size:0.9rem; color:#666;">Bertanggung jawab atas pengawasan beberapa PIC dan strategi wilayah luas.</span>
        </li>
        <li>
            <strong>1000 Pelanggan Aktif: Manajer Area PIC</strong>
            <span style="font-size:0.9rem; color:#666;">Posisi puncak dengan kendali penuh atas operasional area dan insentif maksimal.</span>
        </li>
    </ul>

    <hr>

    <h2>📦 Informasi Paket Layanan</h2>
    <p>Detail paket internet RealNet yang dapat Anda tawarkan:</p>

    <div class="package-grid">
        <div class="package-card">
            <div class="package-title">NEW-Silver</div>
            <div class="package-price">Rp 135.000,- / bulan</div>
            <div class="package-desc">
                ✅ Kecepatan 20 Mbps<br>
                ✅ Gratis biaya instalasi prorata
            </div>
        </div>

        <div class="package-card" style="border-color: var(--primary); background: #fffbfb;">
            <div class="package-title">NEW-Platinum</div>
            <div class="package-price">Rp 220.000,- / bulan</div>
            <div class="package-desc">
                ✅ Kecepatan 30 Mbps<br>
                🔥 <strong>Gratis biaya instalasi penuh</strong>
            </div>
        </div>

        <div class="package-card">
            <div class="package-title">NEW-Diamond</div>
            <div class="package-price">Rp 240.000,- / bulan</div>
            <div class="package-desc">
                ✅ Kecepatan 40 Mbps<br>
                🔥 <strong>Gratis biaya instalasi penuh</strong>
            </div>
        </div>
    </div>
    
    <p style="margin-top: 20px; text-align: center; font-size: 0.85rem; color: #888;">
        <em>PT Real Data Solusindo berkomitmen memberikan layanan terbaik bagi mitra dan pelanggan.</em>
    </p>

</div>

<script>
    function togglePaymentFields() {
        const type = document.getElementById('payment_type').value;
        const bank = document.getElementById('bank_fields');
        const wallet = document.getElementById('e_wallet_fields');
        const inputs = document.querySelectorAll('#bank_fields input, #bank_fields select, #e_wallet_fields input, #e_wallet_fields select');

        // Reset display & required attributes
        bank.classList.add('hidden');
        wallet.classList.add('hidden');
        inputs.forEach(el => el.required = false);

        if (type === 'bank') {
            bank.classList.remove('hidden');
            bank.querySelectorAll('input, select').forEach(el => el.required = true);
        } else if (type === 'e_wallet') {
            wallet.classList.remove('hidden');
            wallet.querySelectorAll('input, select').forEach(el => el.required = true);
        }
    }

    // Loading State saat submit
    document.getElementById('regForm').addEventListener('submit', function() {
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = 'Sedang Memproses...';
        btn.disabled = true;
        btn.style.opacity = '0.7';
    });

    // Auto scroll jika ada pesan sukses/error
    <?php if (isset($submission_status)): ?>
    window.onload = function() {
        document.querySelector('.container').scrollIntoView({behavior: 'smooth'});
    };
    <?php endif; ?>
</script>

</body>
</html>