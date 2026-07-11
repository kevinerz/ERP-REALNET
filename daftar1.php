<?php
require_once __DIR__ . '/config/database.php';
// Set the default timezone to Asia/Jakarta (WIB)
date_default_timezone_set('Asia/Jakarta');

// Konfigurasi database pemasangan
$servername = "localhost";
$username = "u272457353_kevinsamsung9";
$password = "Admionkevin99";
$database = "u272457353_db_pemasangan";

// Buat koneksi ke database pemasangan
$conn_pemasangan = getErpDbConnection();
if ($conn_pemasangan->connect_error) {
    die("Koneksi ke database pemasangan gagal: " . $conn_pemasangan->connect_error);
}

// Ambil data POP
$query_pop = "SELECT id, name FROM jaringan_pop ORDER BY name ASC";
$result_pop = $conn_pemasangan->query($query_pop);
if (!$result_pop) {
    die("Gagal mengambil data POP: " . $conn_pemasangan->error);
}

// Konfigurasi database umumdata
$servername_umumdata = "localhost";
$username_umumdata = "u272457353_kevinsamsung99";
$password_umumdata = "Admionkevin99";
$database_umumdata = "u272457353_umumdata";

$conn_umumdata = getErpDbConnection();
if ($conn_umumdata->connect_error) {
    die("Koneksi ke database umumdata gagal: " . $conn_umumdata->connect_error);
}

// Ambil data paket
$query_paket = "SELECT id_paket, nama_paket, harga, kecepatan FROM jaringan_paket ORDER BY nama_paket ASC";
$result_paket = $conn_umumdata->query($query_paket);
if (!$result_paket) {
    die("Gagal mengambil data paket: " . $conn_umumdata->error);
}

// Mapping grup WhatsApp per POP
function getGroupIdForPop($pop) {
    $groups = [
        "rajeg"     => "6281293958590-1587210420@g.us",
        "kemeri"    => "6287770366015-1628875457@g.us",
        "cianjur"   => "120363399972363054@g.us",
        "mauk"      => "120363419348224895@g.us",
        "brebes"    => "120363297070607107@g.us",
        "sengon"    => "120363366069803212@g.us",
        "grinting"  => "120363399972363054@g.us"
    ];
    // Pastikan nama POP dalam huruf kecil agar sesuai dengan keys array
    return $groups[strtolower($pop)] ?? null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi input
    $required = ['nama','paket','pop','url_maps','alamat','ktp','telp','email','marketing'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            echo "<div class='alert alert-danger'>Field “{$field}” wajib diisi.</div>";
            exit;
        }
    }

    $nama       = htmlspecialchars($_POST['nama']);
    $paket_id   = (int) $_POST['paket'];
    $pop_id     = (int) $_POST['pop'];
    $url_maps   = htmlspecialchars($_POST['url_maps']);
    $alamat     = htmlspecialchars($_POST['alamat']);
    $ktp        = htmlspecialchars($_POST['ktp']);
    $telp       = htmlspecialchars($_POST['telp']);
    $email      = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $marketing  = htmlspecialchars($_POST['marketing']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<div class='alert alert-danger'>Format email tidak valid.</div>";
        exit;
    }

    // Dapatkan nama POP
    $stmt_pop = $conn_pemasangan->prepare("SELECT name FROM jaringan_pop WHERE id = ?");
    $stmt_pop->bind_param("i", $pop_id);
    $stmt_pop->execute();
    $res_pop = $stmt_pop->get_result()->fetch_assoc();
    $nama_pop = $res_pop['name'] ?? null;
    $stmt_pop->close();

    if (!$nama_pop) {
        echo "<div class='alert alert-danger'>POP tidak ditemukan.</div>";
        exit;
    }

    // Insert ke tabel pemasangan (tanpa kolom user)
    $stmt = $conn_pemasangan->prepare("
        INSERT INTO pelanggan_instalasi
            (nama, paket, vlan, sn, pop, odp, url_maps, teknisi, alamat, ktp, telp, email, marketing)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    // semua kolom selain user -> vlan/sn/teknisi/odp diisi kosong
    $empty = '';
    $stmt->bind_param(
        "sisssssssssss",
        $nama,
        $paket_id,
        $empty,
        $empty,
        $nama_pop,
        $empty,
        $url_maps,
        $empty,
        $alamat,
        $ktp,
        $telp,
        $email,
        $marketing
    );

    if (!$stmt->execute()) {
        echo "<div class='alert alert-danger'>Gagal simpan: " . $stmt->error . "</div>";
        exit;
    }
    $stmt->close();

    // Ambil detail paket untuk notifikasi
    $stmt_p = $conn_umumdata->prepare("SELECT nama_paket, harga, kecepatan FROM jaringan_paket WHERE id_paket = ?");
    $stmt_p->bind_param("i", $paket_id);
    $stmt_p->execute();
    $det = $stmt_p->get_result()->fetch_assoc();
    $stmt_p->close();

    $nama_paket    = $det['nama_paket']    ?? '-';
    $harga_paket   = $det['harga']         ?? 0; // Set to 0 for formatting
    $kecepatan     = $det['kecepatan']     ?? '-';
    $tanggal_sekarang = date('d/m/Y H:i'); // Tambahkan tanggal dan waktu saat ini

    // --- Notifikasi Internal Tim ---
    $group_id = getGroupIdForPop($nama_pop);
    $body_int = "
📢 *Pemasangan Baru (POP: {$nama_pop})*

*Tanggal Pengajuan:* {$tanggal_sekarang} WIB

👤 *Data Pelanggan:*
  • *Nama:* {$nama}
  • *Telp:* {$telp}
  • *Email:* {$email}
  • *Alamat:* {$alamat}
  • *KTP:* {$ktp}

📦 *Detail Paket:*
  • *Paket:* {$nama_paket} ({$kecepatan})
  • *Harga:* Rp" . number_format($harga_paket, 0, ',', '.') . "

🗺️ *Lokasi Pemasangan:*
  • *Google Maps:* {$url_maps}

🤝 *Data Marketing:*
  • *Marketing:* {$marketing}

Mohon segera diproses untuk survei dan pemasangan. Terima kasih!
";
    if ($group_id) {
        $curl = curl_init('https://api.starsender.online/api/send');
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                "messageType"=>"text",
                "to"=>$group_id,
                "body"=>$body_int
            ]),
            CURLOPT_HTTPHEADER     => [
                'Content-Type:application/json',
                'Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124'
            ],
        ]);
        $resp = curl_exec($curl);
        file_put_contents('log_internal_notification.txt', date('Y-m-d H:i:s')." RESP: {$resp}\n", FILE_APPEND);
        curl_close($curl);
    }

    // --- Notifikasi Pelanggan ---
    $body_cust = "
👋 Halo {$nama}!

Terima kasih telah mengajukan permintaan pemasangan internet dengan kami.

Permintaan Anda untuk paket *{$nama_paket}* ({$kecepatan}) seharga *Rp" . number_format($harga_paket, 0, ',', '.') . "* sedang kami proses.

Tim kami akan segera menghubungi Anda untuk koordinasi lebih lanjut terkait survei lokasi dan jadwal pemasangan.

Mohon tunggu kabar dari kami, ya! 🙏
";
    $curl2 = curl_init('https://api.starsender.online/api/send');
    curl_setopt_array($curl2, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            "messageType"=>"text",
            "to"=>$telp,
            "body"=>$body_cust
        ]),
        CURLOPT_HTTPHEADER     => [
            'Content-Type:application/json',
            'Authorization: e9c50247-3b8d-4cd8-924a-024a4d2b3124'
        ],
    ]);
    curl_exec($curl2);
    file_put_contents('log_customer_notification.txt', date('Y-m-d H:i:s')."\n", FILE_APPEND);
    curl_close($curl2);

    echo "<div class='alert alert-success'>Data berhasil disimpan dan notifikasi terkirim.</div>";
}

$conn_pemasangan->close();
$conn_umumdata->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Form Pemasangan Internet</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
        --primary-light: #86e3ce; /* Greenish Cyan */
        --primary-dark: #38b6ff;  /* Bright Blue */
        --accent-color: #47a78a;  /* Medium Sea Green */
        --text-dark: #34495e;     /* Darker Grey Blue */
        --text-light: #ecf0f1;    /* Lighter Grey */
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
      display: flex; /* Menggunakan flexbox untuk centering */
      justify-content: center; /* Horisontal centering */
      align-items: center; /* Vertikal centering */
      padding: 20px; /* Memberi sedikit padding agar tidak terlalu mepet tepi */
      box-sizing: border-box; /* Pastikan padding tidak menambah ukuran */
    }

    .form-container {
      max-width: 480px; /* Sedikit lebih lebar untuk kenyamanan */
      width: 100%; /* Pastikan mengisi lebar yang tersedia */
      background: var(--bg-light);
      border-radius: 1.8em; /* Sedikit lebih membulat */
      box-shadow: var(--shadow-soft);
      padding: 2.5em 1.8em; /* Padding lebih proporsional */
      border: 3px solid var(--primary-light);
      animation: popin 0.75s cubic-bezier(.33,1.61,.74,.91);
    }

    @keyframes popin {
      0% {transform: scale(0.95) translateY(30px); opacity: 0;}
      100% {transform: none; opacity: 1;}
    }

    .form-title {
      font-size: 2rem; /* Ukuran font judul lebih besar */
      font-weight: 700; /* Bobot lebih tebal */
      color: var(--primary-dark);
      text-align: center;
      margin-bottom: 2.2em; /* Jarak bawah lebih lega */
      letter-spacing: 0.02em;
    }

    .form-label {
      font-weight: 600;
      color: var(--accent-color); /* Warna label yang menenangkan */
      margin-bottom: 0.6em; /* Jarak antara label dan input */
      display: block; /* Pastikan label di baris baru */
    }

    .form-control {
      border-radius: 1.2em; /* Lebih membulat */
      border: 1.8px solid var(--border-light);
      font-size: 1.05em; /* Ukuran font sedikit lebih besar */
      background: #fcfdfe; /* Latar belakang input sangat terang */
      padding: 0.9em 1.2em; /* Padding yang nyaman */
      margin-bottom: 1.3em; /* Jarak bawah antar input */
      box-shadow: none;
      transition: all 0.3s ease-in-out; /* Transisi untuk semua properti */
      color: var(--text-dark);
    }

    .form-control:focus {
      border-color: var(--primary-dark);
      box-shadow: 0 0 0 3px rgba(56,182,255,0.2); /* Efek shadow saat focus */
      background: #eaf8ff; /* Warna background sedikit berubah saat focus */
      outline: none; /* Hilangkan outline default browser */
    }

    .btn-custom {
      background: linear-gradient(90deg, var(--primary-dark) 0%, var(--primary-light) 100%);
      color: var(--text-light);
      border: none;
      border-radius: 1.5em; /* Lebih membulat */
      padding: 1.1em 0; /* Padding lebih tinggi */
      font-size: 1.25rem; /* Ukuran font tombol lebih besar */
      font-weight: 700; /* Bobot tebal */
      width: 100%;
      margin-top: 1.8em; /* Jarak atas tombol */
      box-shadow: 0 5px 15px rgba(56,182,255,0.25); /* Shadow lebih menonjol */
      transition: all 0.3s ease-in-out; /* Transisi lebih halus */
      letter-spacing: 0.03em;
      text-transform: uppercase; /* Huruf kapital */
    }

    .btn-custom:hover, .btn-custom:focus {
      background: linear-gradient(90deg, var(--accent-color) 0%, var(--primary-dark) 100%);
      color: var(--text-light);
      transform: translateY(-3px) scale(1.01); /* Efek sedikit mengangkat */
      box-shadow: var(--shadow-hover);
      outline: none;
    }

    textarea.form-control {
      min-height: 5.5em; /* Tinggi minimum textarea */
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
        background-color: #fcebeb; /* Merah muda lembut */
        color: #c0392b; /* Merah gelap */
        border-color: #e74c3c;
    }
    .alert-success {
        background-color: #eaf7f2; /* Hijau muda lembut */
        color: #27ae60; /* Hijau gelap */
        border-color: #2ecc71;
    }

    /* Responsiveness */
    @media (max-width: 768px) {
      .form-container {
        margin: 15px; /* Margin samping untuk layar kecil */
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
    <h2 class="form-title">Form Pemasangan Internet</h2>
    <form method="POST">
      <div class="mb-3"> <label for="nama" class="form-label">Nama Lengkap:</label>
        <input type="text" name="nama" id="nama" class="form-control" placeholder="Masukkan nama lengkap Anda" required>
      </div>
      <div class="mb-3">
        <label for="paket" class="form-label">Pilih Paket:</label>
        <select name="paket" id="paket" class="form-control" required>
          <option value="" disabled selected>-- Pilih Paket Internet --</option>
          <?php while($r=$result_paket->fetch_assoc()): ?>
            <option value="<?= $r['id_paket'] ?>">
              <?= htmlspecialchars($r['nama_paket'])." ({$r['kecepatan']}) (Rp ".number_format($r['harga'],0,',','.').")" ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="mb-3">
        <label for="pop" class="form-label">Area POP (Point of Presence):</label>
        <select name="pop" id="pop" class="form-control" required>
          <option value="" disabled selected>-- Pilih Area POP --</option>
          <?php while($p=$result_pop->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="mb-3">
        <label for="url_maps" class="form-label">URL Lokasi Google Maps:</label>
        <input type="url" name="url_maps" id="url_maps" class="form-control" placeholder="Contoh: https://maps.app.goo.gl/abcdefg" required>
      </div>
      <div class="mb-3">
        <label for="alamat" class="form-label">Alamat Pemasangan Lengkap:</label>
        <textarea name="alamat" id="alamat" class="form-control" placeholder="Contoh: Jl. Merdeka No. 123, RT/RW, Kelurahan, Kecamatan, Kota" required></textarea>
      </div>
      <div class="mb-3">
        <label for="ktp" class="form-label">Nomor KTP:</label>
        <input type="text" name="ktp" id="ktp" class="form-control" placeholder="Masukkan nomor identitas KTP Anda" required>
      </div>
      <div class="mb-3">
        <label for="telp" class="form-label">Nomor Telepon (WhatsApp):</label>
        <input type="text" name="telp" id="telp" class="form-control" placeholder="Contoh: 6281234567890" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Alamat Email:</label>
        <input type="email" name="email" id="email" class="form-control" placeholder="Contoh: namaanda@gmail.com" required>
      </div>
      <div class="mb-3">
        <label for="marketing" class="form-label">Nama Marketing (Jika Ada):</label>
        <input type="text" name="marketing" id="marketing" class="form-control" placeholder="Nama marketing yang merekomendasikan" required>
      </div>
      <button type="submit" class="btn btn-custom">Kirim Permintaan Pemasangan</button>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>