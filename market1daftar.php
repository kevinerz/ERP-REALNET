<?php
// daftar.php - Form Pemasangan GRASINET

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/billing_helper.php";

// Koneksi Database
$connPemasangan = null;
$connUmumData = null;
$connMarketing = null;
$alertMessage = '';
$alertType = '';

try {
    $connPemasangan = new mysqli(DB_HOST, DB_USER_PEMASANGAN, DB_PASS_PEMASANGAN, DB_NAME_PEMASANGAN);
    if ($connPemasangan->connect_error) throw new Exception("Koneksi ke database pemasangan gagal: " . $connPemasangan->connect_error);

    $connUmumData = new mysqli(DB_HOST, DB_USER_UMUMDATA, DB_PASS_UMUMDATA, DB_NAME_UMUMDATA);
    if ($connUmumData->connect_error) throw new Exception("Koneksi ke database umumdata gagal: " . $connUmumData->connect_error);

    $connMarketing = new mysqli(DB_HOST, DB_USER_MARKETING, DB_PASS_MARKETING, DB_NAME_MARKETING);
    if ($connMarketing->connect_error) throw new Exception("Koneksi ke database marketing gagal: " . $connMarketing->connect_error);
} catch (Exception $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    $alertType = 'danger';
    $alertMessage = "Terjadi kesalahan koneksi ke database: " . $e->getMessage();
}

// Master Data
$pops = [];
if ($connPemasangan) {
    $resultPop = $connPemasangan->query("SELECT id, name FROM pop ORDER BY name ASC");
    if ($resultPop) while ($row = $resultPop->fetch_assoc()) $pops[] = $row;
}

$pakets = [];
if ($connUmumData) {
    $resultPaket = $connUmumData->query("SELECT id_paket, nama_paket, harga, kecepatan FROM paket WHERE id_paket IN (25,28,31,32) ORDER BY nama_paket ASC");
    if ($resultPaket) while ($row = $resultPaket->fetch_assoc()) $pakets[] = $row;
}

$marketings = [];
if ($connMarketing) {
    $resultMarketing = $connMarketing->query("SELECT id, nama, wa FROM mitra ORDER BY nama ASC");
    if ($resultMarketing) while ($row = $resultMarketing->fetch_assoc()) $marketings[] = $row;
}

function getGroupIdForPop(string $popName): ?string {
    $groups = [
        "rajeg" => "6281293958590-1587210420@g.us",
        "kemeri" => "6287770366015-1628875457@g.us",
        "cianjur" => "120363399972363054@g.us",
        "mauk" => "120363419348224895@g.us",
        "brebes" => "120363297070607107@g.us",
        "sengon" => "120363366069803212@g.us",
        "grinting" => "120363399972363054@g.us"
    ];
    return $groups[strtolower($popName)] ?? null;
}

// Reset setelah submit sukses
$isSuccess = isset($_GET['success']) && $_GET['success'] == '1';
if ($isSuccess) {
    $alertType = 'success';
    $alertMessage = "Permintaan pemasangan berhasil diajukan! Notifikasi telah dikirim ke WhatsApp pelanggan.";
}

// Form Handler
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $requiredFields = ['nama', 'paket', 'pop', 'url_maps', 'alamat', 'ktp', 'telp', 'email', 'marketing'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field]) && $_POST[$field] !== '0') {
            $alertType = 'danger';
            $alertMessage = "Field <strong>" . ucfirst(str_replace('_', ' ', $field)) . "</strong> wajib diisi.";
            break;
        }
    }

    if (empty($alertMessage)) {
        $nama        = htmlspecialchars($_POST['nama']);
        $idPaket     = (int)$_POST['paket'];
        $popId       = (int)$_POST['pop'];
        $urlMaps     = filter_var($_POST['url_maps'], FILTER_SANITIZE_URL);
        $alamat      = htmlspecialchars($_POST['alamat']);
        $ktp         = preg_replace('/[^0-9]/', '', $_POST['ktp']);
        $telpRaw     = htmlspecialchars($_POST['telp']);
        $email       = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $marketingId = (int)$_POST['marketing'];

        // Format WA
        $telp = preg_replace('/[^0-9]/', '', $telpRaw);
        if (substr($telp, 0, 1) == '0') $telp = '62' . substr($telp, 1);
        elseif (substr($telp, 0, 3) == '+62') $telp = '62' . substr($telp, 3);
        elseif (substr($telp, 0, 2) != '62') $telp = '62' . $telp;

        // Validasi dasar
        if (strlen($telp) < 10) {
            $alertType = 'danger'; $alertMessage = "Nomor WhatsApp tidak valid. Minimal 10 digit angka.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $alertType = 'danger'; $alertMessage = "Format email tidak valid.";
        } elseif (!preg_match('/^[0-9]{16}$/', $ktp)) {
            $alertType = 'danger'; $alertMessage = "Nomor KTP harus 16 digit angka.";
        }
    }

    // --- Cek KTP duplikat ---
    if (empty($alertMessage)) {
        $stmtCheck = $connPemasangan->prepare("SELECT COUNT(*) AS total FROM pemasangan WHERE ktp = ?");
        $stmtCheck->bind_param("s", $ktp);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();

        if ($resCheck && $resCheck['total'] > 0) {
            $alertType = 'danger';
            $alertMessage = "Nomor KTP <b>$ktp</b> sudah terdaftar. Tidak dapat melakukan pendaftaran ganda.";
        }
    }

    $namaMarketing = null; $waMarketing = null;
    if (empty($alertMessage)) {
        if ($marketingId > 0) {
            $stmtMarketingDetail = $connMarketing->prepare("SELECT nama, wa FROM mitra WHERE id = ?");
            $stmtMarketingDetail->bind_param("i", $marketingId);
            $stmtMarketingDetail->execute();
            $resMarketingDetail = $stmtMarketingDetail->get_result()->fetch_assoc();
            $namaMarketing = $resMarketingDetail['nama'] ?? null;
            $waMarketing = $resMarketingDetail['wa'] ?? null;
            $stmtMarketingDetail->close();

            if (!$namaMarketing) {
                $alertType = 'danger';
                $alertMessage = "Nama Marketing tidak ditemukan untuk ID yang dipilih.";
            }
        } else {
            $namaMarketing = "Tidak Ada";
            $waMarketing = null;
        }
    }

    $namaPop = null;
    if (empty($alertMessage)) {
        $stmtPop = $connPemasangan->prepare("SELECT name FROM pop WHERE id = ?");
        $stmtPop->bind_param("i", $popId);
        $stmtPop->execute();
        $resPop = $stmtPop->get_result()->fetch_assoc();
        $namaPop = $resPop['name'] ?? null;
        $stmtPop->close();
        if (!$namaPop) {
            $alertType = 'danger';
            $alertMessage = "Area POP tidak ditemukan.";
        }
    }

    $pemasanganId = null;
    if (empty($alertMessage)) {
        $stmt = $connPemasangan->prepare("
            INSERT INTO pemasangan
                (nama, paket, vlan, sn, pop, odp, url_maps, teknisi, alamat, ktp, telp, email, marketing)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $empty = '';
        $stmt->bind_param("sisssssssssss", $nama, $idPaket, $empty, $empty, $namaPop, $empty, $urlMaps, $empty, $alamat, $ktp, $telp, $email, $namaMarketing);
        if ($stmt->execute()) $pemasanganId = $connPemasangan->insert_id;
        else { $alertType = 'danger'; $alertMessage = "Gagal menyimpan data pemasangan: " . $stmt->error; }
        $stmt->close();
    }

    if (empty($alertMessage) && $pemasanganId) {
        header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
        exit;
    }
}

// Tutup Koneksi
if ($connPemasangan) $connPemasangan->close();
if ($connUmumData) $connUmumData->close();
if ($connMarketing) $connMarketing->close();
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
body {
  background: linear-gradient(135deg,#86e3ce 0%,#38b6ff 100%);
  font-family:'Poppins',sans-serif;display:flex;align-items:center;justify-content:center;
  min-height:100vh;margin:0;padding:20px;box-sizing:border-box;
}
.form-container {
  background:#fff;max-width:480px;width:100%;border-radius:20px;padding:30px 20px;
  box-shadow:0 8px 24px rgba(0,0,0,0.1);
}
.form-title {
  text-align:center;font-weight:700;color:#38b6ff;font-size:1.8rem;margin-bottom:25px;
}
.form-label{font-weight:600;color:#47a78a;}
.form-control{border-radius:12px;padding:10px 14px;margin-bottom:14px;}
.btn-custom {
  background:linear-gradient(90deg,#38b6ff,#86e3ce);color:#fff;font-weight:700;
  border:none;border-radius:12px;padding:12px;width:100%;font-size:1.1rem;
}
.btn-custom:hover{opacity:.9;}
.alert{text-align:center;border-radius:12px;}
@media (max-width:480px){
  .form-title{font-size:1.5rem;}
}
</style>
</head>
<body>
<div class="form-container">
<h2 class="form-title">Form Pemasangan GRASINET</h2>

<?php if (!empty($alertMessage)): ?>
<div id="alertBox" class="alert alert-<?= $alertType ?> fade show"><?= $alertMessage ?></div>
<?php endif; ?>

<form method="POST" autocomplete="off">
<label class="form-label">Nama Lengkap</label>
<input type="text" name="nama" class="form-control" required value="<?= $isSuccess ? '' : htmlspecialchars($_POST['nama'] ?? '') ?>">

<label class="form-label">Pilih Paket</label>
<select name="paket" class="form-control" required>
  <option value="">-- Pilih Paket --</option>
  <?php foreach ($pakets as $p): ?>
  <option value="<?= $p['id_paket'] ?>" <?= (isset($_POST['paket']) && $_POST['paket']==$p['id_paket'] && !$isSuccess)?'selected':''; ?>>
    <?= $p['nama_paket']." ({$p['kecepatan']}) - Rp".number_format($p['harga'],0,',','.'); ?>
  </option>
  <?php endforeach; ?>
</select>

<label class="form-label">Area POP</label>
<select name="pop" class="form-control" required>
  <option value="">-- Pilih Area POP --</option>
  <?php foreach ($pops as $p): ?>
  <option value="<?= $p['id'] ?>" <?= (isset($_POST['pop']) && $_POST['pop']==$p['id'] && !$isSuccess)?'selected':''; ?>>
    <?= $p['name'] ?>
  </option>
  <?php endforeach; ?>
</select>

<label class="form-label">URL Lokasi Google Maps</label>
<input type="url" name="url_maps" class="form-control" placeholder="https://maps.google.com/..." required value="<?= $isSuccess ? '' : htmlspecialchars($_POST['url_maps'] ?? '') ?>">

<label class="form-label">Alamat Lengkap</label>
<textarea name="alamat" class="form-control" required><?= $isSuccess ? '' : htmlspecialchars($_POST['alamat'] ?? '') ?></textarea>

<label class="form-label">Nomor KTP</label>
<input type="text" name="ktp" class="form-control" placeholder="16 digit angka" required value="<?= $isSuccess ? '' : htmlspecialchars($_POST['ktp'] ?? '') ?>">

<label class="form-label">Nomor WhatsApp</label>
<input type="text" name="telp" class="form-control" placeholder="081234567890" required value="<?= $isSuccess ? '' : htmlspecialchars($_POST['telp'] ?? '') ?>">

<label class="form-label">Email</label>
<input type="email" name="email" class="form-control" placeholder="nama@gmail.com" required value="<?= $isSuccess ? '' : htmlspecialchars($_POST['email'] ?? '') ?>">

<label class="form-label">Nama Marketing</label>
<select name="marketing" class="form-control" required>
  <option value="">-- Pilih Marketing --</option>
  <option value="0" <?= (isset($_POST['marketing']) && $_POST['marketing']=='0' && !$isSuccess)?'selected':''; ?>>Tidak Ada</option>
  <?php foreach ($marketings as $m): ?>
  <option value="<?= $m['id'] ?>" <?= (isset($_POST['marketing']) && $_POST['marketing']==$m['id'] && !$isSuccess)?'selected':''; ?>><?= $m['nama'] ?></option>
  <?php endforeach; ?>
</select>

<button type="submit" class="btn-custom mt-3">Kirim Pendaftaran</button>
</form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
  const alertBox=document.getElementById('alertBox');
  if(alertBox && alertBox.classList.contains('alert-success')){
    setTimeout(()=>{alertBox.style.opacity='0';setTimeout(()=>alertBox.remove(),500)},4000);
  }
});
</script>
</body>
</html>
