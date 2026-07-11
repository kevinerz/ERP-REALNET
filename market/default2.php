<?php
$submission_status = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'] ?? '';
    $wa = $_POST['whatsapp'] ?? '';
    $payment_type = $_POST['payment_type'] ?? '';
    $bank_nama = $_POST['bank_nama'] ?? '';
    $bank_rekening = $_POST['bank_rekening'] ?? '';
    $e_wallet_nama = $_POST['e_wallet_nama'] ?? '';
    $e_wallet_nomor = $_POST['e_wallet_nomor'] ?? '';

    require_once 'koneksi.php';

    $stmt = $conn->prepare("INSERT INTO mitra (nama, wa, payment_type, bank_nama, bank_rekening, e_wallet_nama, e_wallet_nomor) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nama, $wa, $payment_type, $bank_nama, $bank_rekening, $e_wallet_nama, $e_wallet_nomor);

    if ($stmt->execute()) {
        $submission_status = 'success';

        // Format pesan WA
        $pembayaran = $payment_type === 'bank' ?
            "🏦 Pembayaran melalui Transfer Bank\nBank: $bank_nama\nNo. Rekening: $bank_rekening" :
            "📱 Pembayaran melalui E-Wallet\nE-Wallet: $e_wallet_nama\nNo. Akun: $e_wallet_nomor";

        $pesan = "📥 Pendaftaran Baru Mitra RealNet\n\n" .
                 "👤 Nama: $nama\n" .
                 "📞 WA: $wa\n" .
                 "$pembayaran\n\n" .
                 "🟢 Bonus:\n" .
                 "- Marketing: Rp 50.000 / Pemasangan\n" .
                 "- PIC: Rp 5.000 / Bulan / Pemasangan";

        // Kirim ke grup
        kirim_wa("120363418654328024@g.us", $pesan);
        // Kirim ke pendaftar
        kirim_wa($wa, "Terima kasih $nama telah mendaftar sebagai Mitra RealNet.\n\n" .
                      "Berikut data Anda:\n" .
                      "Nama: $nama\n" .
                      "WA: $wa\n" .
                      "$pembayaran\n\n" .
                      "Kami akan segera memverifikasi data Anda. 🙏\n\nRealNet - PT Real Data Solusindo");

    } else {
        $submission_status = 'error';
    }

    $stmt->close();
    $conn->close();
}

function kirim_wa($to, $pesan) {
    $curl = curl_init();
    $body = [
        "messageType" => "text",
        "to" => $to,
        "body" => $pesan
    ];

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.starsender.online/api/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_HTTPHEADER => array(
            'Content-Type:application/json',
            'Authorization: 7106aa0b-0eb0-4673-aaf6-470ccc1f2390'
        ),
    ));

    curl_exec($curl);
    curl_close($curl);
}
?>



<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Program Afiliasi RealNet - PT Real Data Solusindo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #A00000; /* Darker red for a more luxurious feel */
            --secondary-color: #333;
            --accent-color: #E0E0E0; /* Light gray for subtle accents */
            --text-color: #555;
            --background-color: #F8F8F8;
            --container-bg: #FFFFFF;
            --border-radius: 8px;
            --box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            --form-bg: #fdfdfd; /* Added for consistency */
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background: var(--background-color);
            margin: 0;
            padding: 0;
            line-height: 1.7;
            color: var(--text-color);
            -webkit-font-smoothing: antialiased; /* Better font rendering */
            -moz-osx-font-smoothing: grayscale; /* Better font rendering */
        }

        .container {
            max-width: 800px;
            margin: 20px auto; /* Reduced margin for mobile */
            padding: 20px; /* Reduced padding for mobile */
            background: var(--container-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }

        h1, h2, h3 {
            font-family: 'Merriweather', serif;
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 25px;
            line-height: 1.3; /* Improved readability for long titles */
        }
        h1 {
            font-size: 2em; /* Adjusted for mobile readability */
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-color);
        }
        h2 {
            font-size: 1.6em; /* Adjusted for mobile readability */
            margin-top: 35px;
            color: var(--secondary-color);
        }
        h3 {
            font-size: 1.3em; /* Adjusted for mobile readability */
            margin-top: 30px;
            color: var(--secondary-color);
        }
        p {
            margin-bottom: 15px;
            text-align: justify;
        }
        ul {
            list-style: disc;
            margin: 0 0 20px 20px; /* Adjusted margin for mobile */
            padding: 0;
        }
        ul li {
            margin-bottom: 10px;
        }
        strong {
            color: var(--secondary-color);
        }
        .logo {
            display: block;
            max-width: 200px; /* Slightly smaller logo for mobile */
            margin: 0 auto 25px auto; /* Adjusted margin */
        }

        /* Form Styling */
        form {
            background: var(--form-bg);
            padding: 25px; /* Reduced padding for mobile */
            border-radius: var(--border-radius);
            border: 1px solid var(--accent-color);
            margin-top: 25px;
        }
        form label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary-color);
        }
        form input[type="text"],
        form select {
            width: 100%; /* Full width for better mobile experience */
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
            box-sizing: border-box; /* Include padding in width */
            -webkit-appearance: none; /* Remove default styling for select on iOS */
            -moz-appearance: none; /* Remove default styling for select on Firefox */
            appearance: none; /* Remove default styling */
            background-image: linear-gradient(45deg, transparent 50%, #ccc 50%), linear-gradient(135deg, #ccc 50%, transparent 50%), linear-gradient(to right, #eee, #eee); /* Custom arrow for select */
            background-position: calc(100% - 15px) calc(1em + 2px), calc(100% - 10px) calc(1em + 2px), 100% 0;
            background-size: 5px 5px, 5px 5px, 2.5em 2.5em;
            background-repeat: no-repeat;
        }
        form input[type="text"]:focus,
        form select:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 5px rgba(160, 0, 0, 0.3);
        }
        form button {
            width: 100%;
            padding: 15px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1em;
            font-weight: 700;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background: #800000; /* Slightly darker on hover */
        }
        form button:active { /* Added active state for better feedback on touch devices */
            background: #600000;
        }

        /* Messages */
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-left: 5px solid;
            border-radius: 5px;
            font-weight: 600;
            word-break: break-word; /* Prevent overflow on long messages */
        }
        .message.success {
            background: #e6ffe6; /* Light green */
            color: #1a7a3a; /* Dark green */
            border-color: #28a745;
        }
        .message.error {
            background: #ffe6e6; /* Light red */
            color: #cc0000; /* Dark red */
            border-color: #dc3545;
        }

        .hidden {
            display: none;
        }

        hr {
            border: 0;
            height: 1px;
            background: var(--accent-color);
            margin: 30px 0; /* Adjusted margin */
        }
        .career-path {
            background-color: var(--form-bg); /* Use form-bg for consistency */
            padding: 25px;
            border-radius: var(--border-radius);
            border: 1px solid var(--accent-color);
            margin-top: 30px;
        }
        .career-path h3 {
            text-align: left;
            margin-top: 0;
            margin-bottom: 15px;
        }
        .career-path ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .career-path ul li {
            background-color: #fff;
            border: 1px solid #eee;
            margin-bottom: 10px;
            padding: 12px 15px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            flex-wrap: wrap; /* Allow content to wrap on small screens */
            text-align: left;
        }
        .career-path ul li strong {
            color: var(--primary-color);
            margin-right: 10px;
            font-size: 1.1em;
            flex-shrink: 0; /* Prevent strong tag from shrinking */
        }
        .career-path ul li span { /* Added span for the description to ensure proper wrapping */
            flex-grow: 1;
        }

        /* Paket Layanan Styling */
        .package-list {
            list-style: none;
            padding: 0;
            margin: 0 0 20px 0;
        }
        .package-list li {
            background-color: #fff;
            border: 1px solid #eee;
            margin-bottom: 10px;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .package-list li strong {
            color: var(--primary-color);
            font-size: 1.1em;
            margin-bottom: 5px;
        }
        .package-list li span {
            font-size: 0.95em;
            color: var(--text-color);
        }

        /* Media Queries for better responsiveness */
        @media (max-width: 768px) {
            .container {
                margin: 15px; /* Slightly less margin on smaller tablets */
                padding: 15px; /* Slightly less padding on smaller tablets */
            }
            h1 {
                font-size: 1.8em;
            }
            h2 {
                font-size: 1.4em;
            }
            h3 {
                font-size: 1.2em;
            }
            p, ul li {
                font-size: 0.95em; /* Slightly smaller font for better fit */
            }
            .logo {
                max-width: 180px;
            }
        }

        @media (max-width: 480px) {
            .container {
                margin: 10px; /* Even less margin on small phones */
                padding: 10px; /* Even less padding on small phones */
            }
            h1 {
                font-size: 1.6em; /* Smaller on very small screens */
                margin-bottom: 20px;
            }
            h2 {
                font-size: 1.3em;
                margin-top: 25px;
            }
            h3 {
                font-size: 1.1em;
                margin-top: 20px;
            }
            form {
                padding: 15px;
            }
            form label {
                font-size: 0.9em;
            }
            form input[type="text"],
            form select {
                padding: 10px;
                font-size: 0.9em;
                margin-bottom: 15px;
            }
            form button {
                padding: 12px;
                font-size: 1em;
            }
            .career-path ul li, .package-list li {
                padding: 10px 12px;
                font-size: 0.9em;
            }
            .career-path ul li strong, .package-list li strong {
                font-size: 1em;
            }
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
    <p>
        Kami menawarkan dua kategori kemitraan utama:
    </p>
    <ul>
        <li><strong>Mitra Marketing:</strong> Dapatkan komisi sebesar Rp 50.000,- (lima puluh ribu Rupiah) untuk setiap pemasangan layanan RealNet yang berhasil Anda referensikan.</li>
        <li><strong>PIC (Person in Charge) / Koordinator:</strong> Peroleh komisi berulang sebesar Rp 5.000,- (lima ribu Rupiah) per bulan untuk setiap pemasangan aktif yang berada di bawah koordinasi Anda.</li>
    </ul>

    ---

    <h2>Formulir Pendaftaran Mitra Marketing</h2>

    <?php if (isset($submission_status) && $submission_status === 'success'): ?>
        <div class="message success">
            Pengajuan Anda telah berhasil kami terima. Tim kami akan segera menghubungi Anda untuk proses verifikasi lebih lanjut. Terima kasih atas partisipasi Anda.
        </div>
    <?php elseif (isset($submission_status) && $submission_status === 'error'): ?>
        <div class="message error">
            Mohon maaf, terjadi kendala saat memproses pendaftaran Anda. Mohon coba kembali beberapa saat lagi atau hubungi dukungan teknis kami.
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <label for="nama">Nama Lengkap:</label>
        <input type="text" id="nama" name="nama" placeholder="Cantumkan nama lengkap sesuai identitas" required>

        <label for="whatsapp">Nomor WhatsApp Aktif:</label>
        <input type="text" id="whatsapp" name="whatsapp" placeholder="Contoh: 081234567890" required>

        <label for="payment_type">Metode Pembayaran Komisi:</label>
        <select id="payment_type" name="payment_type" onchange="togglePaymentFields()" required>
            <option value="">-- Pilih Metode --</option>
            <option value="bank">Transfer Bank</option>
            <option value="e_wallet">E-Wallet</option>
        </select>

        <div id="bank_fields" class="hidden">
            <label for="bank_nama">Pilih Bank:</label>
            <select id="bank_nama" name="bank_nama">
                <option value="">-- Pilih Bank --</option>
                <option value="BCA">BCA (Bank Central Asia)</option>
                <option value="Mandiri">Mandiri</option>
                <option value="BRI">BRI (Bank Rakyat Indonesia)</option>
                <option value="BNI">BNI (Bank Negara Indonesia)</option>
                <option value="CIMB Niaga">CIMB Niaga</option>
                <option value="Permata Bank">Permata Bank</option>
                <option value="Danamon">Danamon</option>
                <option value="BTN">BTN (Bank Tabungan Negara)</option>
                <option value="OCBC NISP">OCBC NISP</option>
                <option value="Panin Bank">Panin Bank</option>
                <option value="Maybank">Maybank</option>
                <option value="BTN Syariah">BTN Syariah</option>
                <option value="BSI">BSI (Bank Syariah Indonesia)</option>
                <option value="Lainnya">Bank Lainnya</option>
            </select>

            <label for="bank_rekening">Nomor Rekening:</label>
            <input type="text" id="bank_rekening" name="bank_rekening" placeholder="Masukkan nomor rekening Anda">
        </div>

        <div id="e_wallet_fields" class="hidden">
            <label for="e_wallet_nama">Pilih E-Wallet:</label>
            <select id="e_wallet_nama" name="e_wallet_nama">
                <option value="">-- Pilih E-Wallet --</option>
                <option value="DANA">DANA</option>
                <option value="Gopay">Gopay</option>
                <option value="OVO">OVO</option>
                <option value="ShopeePay">ShopeePay</option>
                <option value="LinkAja">LinkAja</option>
                <option value="Lainnya">E-Wallet Lainnya</option>
            </select>

            <label for="e_wallet_nomor">Nomor Telepon/ID E-Wallet:</label>
            <input type="text" id="e_wallet_nomor" name="e_wallet_nomor" placeholder="Masukkan nomor telepon/ID E-Wallet Anda">
        </div>

        <button type="submit">Daftar sebagai Mitra Sekarang</button>
    </form>

    <hr>

    <div class="career-path">
        <h3>Jenjang Karir Program PIC RealNet</h3>
        <p>
            Kami sangat menghargai dedikasi dan kontribusi Anda dalam mengembangkan jaringan pelanggan RealNet. Untuk memberikan apresiasi dan peluang pertumbuhan karir, kami telah merancang jenjang karir progresif bagi para PIC berdasarkan jumlah pelanggan aktif yang berhasil Anda bawa:
        </p>
        <ul>
            <li><strong>10 Pelanggan Aktif:</strong> <span>Anda akan secara resmi diakui sebagai **PIC Junior**. Pada tahap ini, Anda akan mendapatkan dukungan dan pelatihan lebih lanjut untuk mengoptimalkan potensi Anda.</span></li>
            <li><strong>100 Pelanggan Aktif:</strong> <span>Anda akan naik tingkat menjadi **PIC Senior**. Sebagai PIC Senior, Anda akan memiliki peran yang lebih strategis dalam pengembangan area dan berkesempatan memimpin tim kecil.</span></li>
            <li><strong>500 Pelanggan Aktif:</strong> <span>Anda akan dipromosikan menjadi **Supervisor PIC**. Pada posisi ini, Anda bertanggung jawab atas pengawasan beberapa PIC dan pengembangan strategi pertumbuhan di wilayah yang lebih luas.</span></li>
            <li><strong>1000 Pelanggan Aktif:</strong> <span>Anda akan mencapai posisi puncak sebagai **Manajer Area PIC**. Sebagai Manajer Area, Anda akan memegang kendali penuh atas operasional PIC di area yang ditunjuk, dengan tanggung jawab dan insentif yang lebih besar.</span></li>
        </ul>
        <p>
            Kami berkomitmen untuk mendukung perjalanan karir Anda dan menyediakan lingkungan yang kondusif untuk mencapai kesuksesan bersama.
        </p>
    </div>

    <hr>

    <h3>Informasi Paket Layanan Internet RealNet</h3>
    <p>
        Berikut adalah detail paket layanan internet RealNet yang dapat Anda tawarkan kepada calon pelanggan:
    </p>
    <ul class="package-list"> <li>
            <strong>NEW-Silver:</strong>
            <span>Kecepatan 20 Mbps – Biaya bulanan Rp 135.000,- – Gratis biaya instalasi prorata.</span>
        </li>
        <li>
            <strong>NEW-Diamond:</strong>
            <span>Kecepatan 40 Mbps – Biaya bulanan Rp 240.000,- – Gratis biaya instalasi penuh.</span>
        </li>
        <li>
            <strong>NEW-Platinum:</strong>
            <span>Kecepatan 30 Mbps – Biaya bulanan Rp 220.000,- – Gratis biaya instalasi penuh.</span>
        </li>
    </ul>
    <p>
        Kami berkomitmen untuk menyediakan layanan internet berkualitas tinggi dan dukungan penuh kepada seluruh mitra kami.
    </p>
</div>

<script>
    function togglePaymentFields() {
        const paymentType = document.getElementById('payment_type').value;
        const bankFields = document.getElementById('bank_fields');
        const eWalletFields = document.getElementById('e_wallet_fields');

        // Reset required attributes
        document.getElementById('bank_nama').removeAttribute('required');
        document.getElementById('bank_rekening').removeAttribute('required');
        document.getElementById('e_wallet_nama').removeAttribute('required');
        document.getElementById('e_wallet_nomor').removeAttribute('required');

        // Clear input values when hidden
        document.getElementById('bank_nama').value = '';
        document.getElementById('bank_rekening').value = '';
        document.getElementById('e_wallet_nama').value = '';
        document.getElementById('e_wallet_nomor').value = '';


        if (paymentType === 'bank') {
            bankFields.classList.remove('hidden');
            eWalletFields.classList.add('hidden');
            document.getElementById('bank_nama').setAttribute('required', 'required');
            document.getElementById('bank_rekening').setAttribute('required', 'required');
        } else if (paymentType === 'e_wallet') {
            bankFields.classList.add('hidden');
            eWalletFields.classList.remove('hidden');
            document.getElementById('e_wallet_nama').setAttribute('required', 'required');
            document.getElementById('e_wallet_nomor').setAttribute('required', 'required');
        } else {
            bankFields.classList.add('hidden');
            eWalletFields.classList.add('hidden');
        }
    }

    // Call on page load to ensure correct initial state
    document.addEventListener('DOMContentLoaded', togglePaymentFields);
</script>

</body>
</html>