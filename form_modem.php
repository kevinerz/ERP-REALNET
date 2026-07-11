<?php
require_once __DIR__ . '/config/database.php';
// modem_input.php

// --- KONFIGURASI DATABASE ---
$servername = "localhost";
$username   = "u272457353_kevinsamsung99";
$password   = "Admionkevin99";
$database   = "u272457353_umumdata";

// Buat koneksi
$conn = getErpDbConnection();
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Koneksi DB gagal']));
}

// Jika ada parameter barcode, tangani sebagai AJAX JSON
if (isset($_GET['barcode'])) {
    header('Content-Type: application/json');
    $barcode = $conn->real_escape_string(trim($_GET['barcode']));
    if ($barcode === '') {
        echo json_encode(['error' => 'Barcode kosong']);
        exit;
    }
    $sql = "
      SELECT id_modem, serial_number, mac_address, model, merk,
             status, tanggal_masuk, keterangan, lokasi_penyimpanan
      FROM jaringan_modem
      WHERE serial_number = '$barcode'
         OR mac_address   = '$barcode'
         OR id_modem      = '$barcode'
      LIMIT 1
    ";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        echo json_encode($res->fetch_assoc());
    } else {
        echo json_encode(['error' => 'Data tidak ditemukan']);
    }
    exit;
}

// Jika bukan AJAX, tampilkan form HTML di bawah
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Input Modem via Barcode</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container">
    <h2 class="mb-4">Input Data Modem (Scan Barcode)</h2>
    <form id="modemForm">
      <div class="mb-3">
        <label for="barcode" class="form-label">Scan Barcode</label>
        <input type="text" id="barcode" class="form-control" placeholder="Scan di sini lalu tekan Enter" autofocus>
      </div>
      <div class="row">
        <?php
        $fields = [
          'id_modem'=>'ID Modem','serial_number'=>'Serial Number','mac_address'=>'MAC Address',
          'model'=>'Model','merk'=>'Merk','status'=>'Status',
          'tanggal_masuk'=>'Tanggal Masuk','keterangan'=>'Keterangan','lokasi_penyimpanan'=>'Lokasi Penyimpanan'
        ];
        foreach ($fields as $name => $label) {
          $type = $name==='keterangan' ? 'textarea' : ($name==='tanggal_masuk' ? 'date' : 'text');
          echo '<div class="col-md-6 mb-3">'
             .  "<label for=\"$name\" class=\"form-label\">$label</label>";
          if ($type==='textarea') {
            echo "<textarea id=\"$name\" name=\"$name\" class=\"form-control\" rows=\"2\" readonly></textarea>";
          } else {
            echo "<input type=\"$type\" id=\"$name\" name=\"$name\" class=\"form-control\" readonly>";
          }
          echo '</div>';
        }
        ?>
      </div>
    </form>
  </div>

  <script>
  document.getElementById('barcode').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      const code = this.value.trim();
      if (!code) return;
      fetch(`?barcode=${encodeURIComponent(code)}`)
        .then(r => r.json())
        .then(data => {
          if (data.error) {
            alert(data.error);
            return;
          }
          // isi semua field
          for (let key in data) {
            const el = document.getElementById(key);
            if (el) el.value = data[key];
          }
        })
        .catch(console.error);
    }
  });
  </script>
</body>
</html>
