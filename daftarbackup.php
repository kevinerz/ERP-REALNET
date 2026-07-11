<?php

// daftar.php

require_once __DIR__ . "/config.php"; // Include the configuration file
require_once __DIR__ . "/billing_helper.php"; // Include helper functions

// --- Database Connections ---
$connPemasangan = null;
$connUmumData = null;

try {
    // Koneksi database pemasangan
    $connPemasangan = new mysqli(DB_HOST, DB_USER_PEMASANGAN, DB_PASS_PEMASANGAN, DB_NAME_PEMASANGAN);
    if ($connPemasangan->connect_error) {
        throw new Exception("Koneksi ke database pemasangan gagal: " . $connPemasangan->connect_error);
    }

    // Koneksi database umumdata
    $connUmumData = new mysqli(DB_HOST, DB_USER_UMUMDATA, DB_PASS_UMUMDATA, DB_NAME_UMUMDATA);
    if ($connUmumData->connect_error) {
        throw new Exception("Koneksi ke database umumdata gagal: " . $connUmumData->connect_error);
    }

} catch (Exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    $alertType = 'danger';
    $alertMessage = "Terjadi kesalahan koneksi ke database. Beberapa fitur mungkin tidak berfungsi.";
}

// Ambil data POP
$pops = [];
if ($connPemasangan) {
    $queryPop = "SELECT id, name FROM jaringan_pop ORDER BY name ASC";
    $resultPop = $connPemasangan->query($queryPop);
    if ($resultPop) {
        while ($row = $resultPop->fetch_assoc()) {
            $pops[] = $row;
        }
    } else {
        error_log("Gagal mengambil data POP: " . $connPemasangan->error);
        $alertType = 'danger';
        $alertMessage = (!empty($alertMessage) ? $alertMessage . "<br>" : "") . "Gagal mengambil data POP. Mohon coba lagi nanti.";
    }
} else {
    $alertType = 'danger';
    $alertMessage = (!empty($alertMessage) ? $alertMessage . "<br>" : "") . "Tidak dapat mengambil data POP karena koneksi database gagal.";
}

// Ambil data paket
$pakets = [];
if ($connUmumData) {
    $queryPaket = "SELECT id_paket, nama_paket, harga, kecepatan FROM jaringan_paket ORDER BY nama_paket ASC";
    $resultPaket = $connUmumData->query($queryPaket);
    if ($resultPaket) {
        while ($row = $resultPaket->fetch_assoc()) {
            $pakets[] = $row;
        }
    } else {
        error_log("Gagal mengambil data paket: " . $connUmumData->error);
        $alertType = 'danger';
        $alertMessage = (!empty($alertMessage) ? $alertMessage . "<br>" : "") . "Gagal mengambil data paket. Mohon coba lagi nanti.";
    }
} else {
    $alertType = 'danger';
    $alertMessage = (!empty($alertMessage) ? $alertMessage . "<br>" : "") . "Tidak dapat mengambil data paket karena koneksi database gagal.";
}

// Mapping grup WhatsApp per POP
function getGroupIdForPop(string $popName): ?string {
    $groups = [
        "rajeg"     => "6281293958590-1587210420@g.us",
        "kemeri"    => "6287770366015-1628875457@g.us",
        "cianjur"   => "120363399972363054@g.us",
        "mauk"      => "120363419348224895@g.us",
        "brebes"    => "120363297070607107@g.us",
        "sengon"    => "120363366069803212@g.us",
        "grinting"  => "120363399972363054@g.us"
    ];
    return $groups[strtolower($popName)] ?? null;
}

$alertMessage = '';
$alertType = '';

// Process Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    file_put_contents('log_prorata.txt', "\n==== FORM SUBMIT " . date('Y-m-d H:i:s') . " ====\n" . print_r($_POST, true), FILE_APPEND);

    // Validate input
    $requiredFields = ['nama', 'paket', 'pop', 'url_maps', 'alamat', 'ktp', 'telp', 'email', 'marketing'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $alertType = 'danger';
            $alertMessage = "Field **" . ucfirst(str_replace('_', ' ', $field)) . "** wajib diisi.";
            break;
        }
    }

    if (empty($alertMessage)) {
        // Sanitize and get input variables
        $nama       = htmlspecialchars($_POST['nama']);
        $idPaket    = (int)$_POST['paket'];
        $popId      = (int)$_POST['pop'];
        $urlMaps    = filter_var($_POST['url_maps'], FILTER_SANITIZE_URL);
        $alamat     = htmlspecialchars($_POST['alamat']);
        $ktp        = htmlspecialchars($_POST['ktp']);
        $telpRaw    = htmlspecialchars($_POST['telp']);
        $email      = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $marketing  = htmlspecialchars($_POST['marketing']);

        // Format WhatsApp number
        $telp = preg_replace('/[^0-9]/', '', $telpRaw);
        if (substr($telp, 0, 1) == '0') {
            $telp = '62' . substr($telp, 1);
        } elseif (substr($telp, 0, 3) == '+62') {
            $telp = '62' . substr($telp, 3);
        } elseif (substr($telp, 0, 2) != '62') {
            $telp = '62' . $telp;
        }

        if (strlen($telp) < 10) {
            $alertType = 'danger';
            $alertMessage = "Nomor WhatsApp tidak valid.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alertType = 'danger';
            $alertMessage = "Format email tidak valid.";
        }
    }

    $namaPop = null;
    if (empty($alertMessage)) {
        // Get POP name
        $stmtPop = $connPemasangan->prepare("SELECT name FROM jaringan_pop WHERE id = ?");
        $stmtPop->bind_param("i", $popId);
        $stmtPop->execute();
        $resPop = $stmtPop->get_result()->fetch_assoc();
        $namaPop = $resPop['name'] ?? null;
        $stmtPop->close();
        if (!$namaPop) {
            $alertType = 'danger';
            $alertMessage = "POP tidak ditemukan.";
        }
    }

    $pemasanganId = null; // Variable to store the new pemasangan ID
    if (empty($alertMessage)) {
        // Insert into pemasangan table
        $stmt = $connPemasangan->prepare("
            INSERT INTO pelanggan_instalasi
                (nama, paket, vlan, sn, pop, odp, url_maps, teknisi, alamat, ktp, telp, email, marketing)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $empty = ''; // Use an empty string for unused fields for now
        $stmt->bind_param(
            "sisssssssssss",
            $nama,
            $idPaket,
            $empty,
            $empty,
            $namaPop,
            $empty,
            $urlMaps,
            $empty,
            $alamat,
            $ktp,
            $telp,
            $email,
            $marketing
        );
        if ($stmt->execute()) {
            $pemasanganId = $connPemasangan->insert_id; // Get the ID of the newly inserted row
        } else {
            $alertType = 'danger';
            $alertMessage = "Gagal menyimpan data pemasangan: " . $stmt->error;
        }
        $stmt->close();
    }

    if (empty($alertMessage) && $pemasanganId) {
        // Query package details
        $stmtP = $connUmumData->prepare("SELECT nama_paket, harga, kecepatan FROM jaringan_paket WHERE id_paket = ?");
        $stmtP->bind_param("i", $idPaket);
        $stmtP->execute();
        $det = $stmtP->get_result()->fetch_assoc();
        $stmtP->close();

        $namaPaket      = $det['nama_paket']    ?? '-';
        $hargaPaket     = $det['harga']         ?? 0;
        $kecepatan      = $det['kecepatan']     ?? '-';
        $tanggalSekarang = date('d/m/Y H:i');

        // WA Group Internal
        $groupId = getGroupIdForPop($namaPop);
        $bodyInt = "
📢 *Pemasangan Baru (POP: {$namaPop})*

*Tanggal Pengajuan:* {$tanggalSekarang} WIB
*ID Pemasangan:* {$pemasanganId}

👤 *Data Pelanggan:*
    • *Nama:* {$nama}
    • *Telp:* {$telpRaw}
    • *Email:* {$email}
    • *Alamat:* {$alamat}
    • *KTP:* {$ktp}

📦 *Detail Paket:*
    • *Paket:* {$namaPaket} ({$kecepatan})
    • *Harga:* Rp" . number_format($hargaPaket, 0, ',', '.') . "

🗺️ *Lokasi Pemasangan:*
    • *Google Maps:* {$urlMaps}

🤝 *Data Marketing:*
    • *Marketing:* {$marketing}

Mohon segera diproses untuk survei dan pemasangan. Terima kasih!
";
        if ($groupId) {
            $curl = curl_init(WA_API_URL);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    "messageType" => "text",
                    "to"          => $groupId,
                    "body"        => $bodyInt
                ]),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: ' . WA_API_TOKEN
                ],
                CURLOPT_TIMEOUT        => 10,
            ]);
            $resp = curl_exec($curl);
            if (curl_errno($curl)) {
                error_log("cURL Error (Internal WA): " . curl_error($curl));
            }
            file_put_contents('log_internal_notification.txt', date('Y-m-d H:i:s') . " RESP: {$resp}\n", FILE_APPEND);
            curl_close($curl);
        } else {
            error_log("Group ID not found for POP: {$namaPop}");
        }

        // --- PRORATA & TRIPAY PAYMENT LINK ---
        if (function_exists('isPaketProrata') && isPaketProrata($idPaket)) {
            file_put_contents('log_prorata.txt', "PRORATA JALAN untuk id_paket: $idPaket\n", FILE_APPEND);

            $hargaProrata = hitungProrata($hargaPaket);
            $today = new DateTime();
            $month = (int)$today->format('m');
            $year = (int)$today->format('Y');
            $tgl5 = new DateTime("$year-$month-05");

            // Calculate prorata period
            if ($today < $tgl5) {
                $periodeAwal = $today->format('Y-m-d');
                $periodeAkhir = (clone $tgl5)->modify('-1 day')->format('Y-m-d');
            } else {
                $periodeAwal = $today->format('Y-m-d');
                $periodeAkhir = (clone $tgl5)->modify('+1 month')->modify('-1 day')->format('Y-m-d');
            }

            // SIAPKAN LINK KE pilih_pembayaran.php dengan semua data yang dibutuhkan
            // >>> FIX: PASTIKAN MENYERTakan PROTOKOL DAN DOMAIN LENGKAP <<<
            $linkKePilihPembayaran = BASE_APP_URL . "/pilih_pembayaran.php?" . http_build_query([
                'pemasangan_id' => $pemasanganId,
                'nama'          => $nama,
                'paket'         => $namaPaket,
                'telp'          => $telp,
                'email'         => $email,
                'harga'         => $hargaProrata,
                'periode_awal'  => $periodeAwal,
                'periode_akhir' => $periodeAkhir,
            ]);

            // --- Shorten the link here ---
            $shortenedLink = shortenUrl($linkKePilihPembayaran);
            // Use the shortened link for WhatsApp, but show original to admin if shortening fails
            $displayLink = $shortenedLink === $linkKePilihPembayaran ? $linkKePilihPembayaran : $shortenedLink;


            if (function_exists('waNotifProrata')) {
                // Send the shortened link to customer via WhatsApp
                waNotifProrata($telp, $nama, $namaPaket, $hargaProrata, $periodeAwal, $periodeAkhir, $shortenedLink);
            }

            $alertType = 'success';
            $alertMessage = "Permintaan pemasangan berhasil diajukan! Link pembayaran sudah dikirim ke WhatsApp pelanggan.";
            $alertMessage .= "<br><b>Link Pembayaran (Admin):</b> <a href='$displayLink' target='_blank'>$displayLink</a><br>";
            $alertMessage .= "<small>Jika pelanggan tidak menerima WA, bisa copy-paste link ini ke pelanggan.</small>";

        } else {
            // --- WA Notifikasi pelanggan normal (tanpa Tripay/prorata)
            $bodyCust = "
👋 Halo {$nama}!

Terima kasih telah mengajukan permintaan pemasangan internet dengan kami.

Permintaan Anda untuk paket *{$namaPaket}* ({$kecepatan}) seharga *Rp" . number_format($hargaPaket, 0, ',', '.') . "* sedang kami proses.

Tim kami akan segera menghubungi Anda untuk koordinasi lebih lanjut terkait survei lokasi dan jadwal pemasangan.

Mohon tunggu kabar dari kami, ya! 🙏
";
            $curl2 = curl_init(WA_API_URL);
            curl_setopt_array($curl2, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    "messageType" => "text",
                    "to"          => $telp,
                    "body"        => $bodyCust
                ]),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: ' . WA_API_TOKEN
                ],
                CURLOPT_TIMEOUT        => 10,
            ]);
            $resp2 = curl_exec($curl2);
            if (curl_errno($curl2)) {
                error_log("cURL Error (Customer WA): " . curl_error($curl2));
            }
            file_put_contents('log_customer_notification.txt', date('Y-m-d H:i:s') . " RESP: {$resp2}\n", FILE_APPEND);
            curl_close($curl2);

            $alertType = 'success';
            $alertMessage = "Permintaan pemasangan berhasil diajukan! Notifikasi telah dikirim ke WhatsApp pelanggan.";
        }
    }
}

// Close database connections at the end
if ($connPemasangan) {
    $connPemasangan->close();
}
if ($connUmumData) {
    $connUmumData->close();
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Form GRASINET</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-light: #86e3ce;
            --primary-dark: #38b6ff;
            --accent-color: #47a78a;
            --text-dark: #34495e;
            --text-light: #ecf0f1;
            --bg-light: #ffffff;
            --border-light: #d0f3ef;
            --shadow-soft: 0 8px 24px rgba(80,180,180,0.09), 0 1.5px 4px rgba(80,180,120,0.06);
            --shadow-hover: 0 10px 30px rgba(56,182,255,0.15);
        }
        body {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-dark) 100%);
            min-height: 100vh;
            font-family: 'Poppins', sans-serif;
            margin: 0;
            color: var(--text-dark);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .form-container {
            max-width: 480px;
            width: 100%;
            background: var(--bg-light);
            border-radius: 1.8em;
            box-shadow: var(--shadow-soft);
            padding: 2.5em 1.8em;
            border: 3px solid var(--primary-light);
            animation: popin 0.75s cubic-bezier(.33,1.61,.74,.91);
        }
        @keyframes popin {
            0% {transform: scale(0.95) translateY(30px); opacity: 0;}
            100% {transform: none; opacity: 1;}
        }
        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            text-align: center;
            margin-bottom: 2.2em;
            letter-spacing: 0.02em;
        }
        .form-label {
            font-weight: 600;
            color: var(--accent-color);
            margin-bottom: 0.6em;
            display: block;
        }
        .form-control {
            border-radius: 1.2em;
            border: 1.8px solid var(--border-light);
            font-size: 1.05em;
            background: #fcfdfe;
            padding: 0.9em 1.2em;
            margin-bottom: 1.3em;
            box-shadow: none;
            transition: all 0.3s ease-in-out;
            color: var(--text-dark);
        }
        .form-control:focus {
            border-color: var(--primary-dark);
            box-shadow: 0 0 0 3px rgba(56,182,255,0.2);
            background: #eaf8ff;
            outline: none;
        }
        .btn-custom {
            background: linear-gradient(90deg, var(--primary-dark) 0%, var(--primary-light) 100%);
            color: var(--text-light);
            border: none;
            border-radius: 1.5em;
            padding: 1.1em 0;
            font-size: 1.25rem;
            font-weight: 700;
            width: 100%;
            margin-top: 1.8em;
            box-shadow: 0 5px 15px rgba(56,182,255,0.25);
            transition: all 0.3s ease-in-out;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        .btn-custom:hover, .btn-custom:focus {
            background: linear-gradient(90deg, var(--accent-color) 0%, var(--primary-dark) 100%);
            color: var(--text-light);
            transform: translateY(-3px) scale(1.01);
            box-shadow: var(--shadow-hover);
            outline: none;
        }
        textarea.form-control {
            min-height: 5.5em;
            resize: vertical;
        }
        .alert {
            font-size: 0.95em;
            border-radius: 0.9em;
            margin-bottom: 1.5em;
            box-shadow: 0 3px 14px rgba(80,180,180,0.08);
            padding: 1em 1.5em;
            text-align: center;
        }
        .alert-danger {
            background-color: #fcebeb;
            color: #c0392b;
            border-color: #e74c3c;
        }
        .alert-success {
            background-color: #eaf7f2;
            color: #27ae60;
            border-color: #2ecc71;
        }
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border-color: #ffeeba;
        }
        @media (max-width: 768px) {
            .form-container {
                margin: 15px;
                padding: 2em 1.5em;
                border-radius: 1.5em;
            }
            .form-title {
                font-size: 1.8rem;
                margin-bottom: 2em;
            }
            .btn-custom {
                font-size: 1.1rem;
                padding: 1em 0;
            }
        }
        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            .form-container {
                margin: 10px;
                padding: 1.8em 1em;
                border-radius: 1.2em;
            }
            .form-title {
                font-size: 1.5rem;
                margin-bottom: 1.5em;
            }
            .form-label {
                font-size: 0.9em;
            }
            .form-control {
                font-size: 0.95em;
                padding: 0.8em 1em;
                margin-bottom: 1em;
            }
            .btn-custom {
                font-size: 1rem;
                padding: 0.9em 0;
                margin-top: 1.5em;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2 class="form-title">Form Pemasangan GRASINET</h2>

        <?php if (!empty($alertMessage)): ?>
            <div id="alertBox" class="alert alert-<?= $alertType ?> fade show" role="alert">
                <?= $alertMessage ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="pemasanganForm">
            <div class="mb-3">
                <label for="nama" class="form-label">Nama Lengkap:</label>
                <input type="text" name="nama" id="nama" class="form-control" placeholder="Masukkan nama lengkap Anda" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="paket" class="form-label">Pilih Paket:</label>
                <select name="paket" id="paket" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Paket Internet --</option>
                    <?php foreach ($pakets as $p): ?>
                        <option value="<?= $p['id_paket'] ?>" <?= (isset($_POST['paket']) && $_POST['paket'] == $p['id_paket']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nama_paket']) . " ({$p['kecepatan']}) (Rp " . number_format($p['harga'], 0, ',', '.') . ")" ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="pop" class="form-label">Area POP (Point of Presence):</label>
                <select name="pop" id="pop" class="form-control" required>
                    <option value="" disabled selected>-- Pilih Area POP --</option>
                    <?php foreach ($pops as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= (isset($_POST['pop']) && $_POST['pop'] == $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="url_maps" class="form-label">URL Lokasi Google Maps:</label>
                <input type="url" name="url_maps" id="url_maps" class="form-control" placeholder="Contoh: http://maps.google.com/link-lokasi-anda" required value="<?= htmlspecialchars($_POST['url_maps'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="alamat" class="form-label">Alamat Pemasangan Lengkap:</label>
                <textarea name="alamat" id="alamat" class="form-control" placeholder="Contoh: Jl. Merdeka No. 123, RT/RW, Kelurahan, Kecamatan, Kota" required><?= htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>
            </div>
            <div class="mb-3">
                <label for="ktp" class="form-label">Nomor KTP:</label>
                <input type="text" name="ktp" id="ktp" class="form-control" placeholder="Masukkan nomor identitas KTP Anda" required value="<?= htmlspecialchars($_POST['ktp'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="telp" class="form-label">Nomor Telepon (WhatsApp):</label>
                <input type="text" name="telp" id="telp" class="form-control" placeholder="Contoh: 081234567890 atau 6281234567890" required value="<?= htmlspecialchars($_POST['telp'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Alamat Email:</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="Contoh: namaanda@gmail.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="marketing" class="form-label">Nama Marketing (Jika Ada):</label>
                <input type="text" name="marketing" id="marketing" class="form-control" placeholder="Nama marketing yang merekomendasikan" required value="<?= htmlspecialchars($_POST['marketing'] ?? '') ?>">
            </div>
            <button type="submit" class="btn btn-custom">Kirim Permintaan Pemasangan</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('pemasanganForm');
            const alertBox = document.getElementById('alertBox');

            function clearForm() {
                form.reset();
                document.getElementById('paket').selectedIndex = 0;
                document.getElementById('pop').selectedIndex = 0;
            }

            if (alertBox && alertBox.classList.contains('alert-success')) {
                setTimeout(function() {
                    alertBox.classList.add('fade');
                    alertBox.style.opacity = '0';
                    setTimeout(function() {
                        alertBox.style.display = 'none';
                        alertBox.remove();
                    }, 500);
                    clearForm();
                }, 5000);
            }
        });
    </script>
</body>
</html>