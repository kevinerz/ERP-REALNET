<?php
require __DIR__ . '/config.php';

$success = '';
$error = '';

/**
 * Cek kolom opsional ada/tidak (biar insert tidak error walau DB belum ALTER).
 */
function tableHasColumns(PDO $pdo, string $table, array $cols): array {
  $db = (string)$pdo->query("SELECT DATABASE()")->fetchColumn();
  if ($db === '') return [];
  $in = implode(',', array_fill(0, count($cols), '?'));
  $sql = "SELECT column_name
          FROM information_schema.columns
          WHERE table_schema = ?
            AND table_name = ?
            AND column_name IN ($in)";
  $stmt = $pdo->prepare($sql);
  $stmt->execute(array_merge([$db, $table], $cols));
  return array_map(fn($r) => $r['column_name'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}

$optionalCols = ['provinsi_code','kota_kab_code','kecamatan','kecamatan_code','kelurahan_code'];
$existingOptionalCols = tableHasColumns($pdo, 'mitra_resmi', $optionalCols);
$has = array_flip($existingOptionalCols);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_pemilik     = trim((string)($_POST['nama_pemilik'] ?? ''));
  $nama_brand       = trim((string)($_POST['nama_brand'] ?? ''));
  $alamat_lengkap   = trim((string)($_POST['alamat_lengkap'] ?? ''));

  $provinsi         = trim((string)($_POST['provinsi'] ?? ''));
  $kota_kab         = trim((string)($_POST['kota_kab'] ?? ''));
  $kecamatan        = trim((string)($_POST['kecamatan'] ?? ''));
  $kelurahan_dusun  = trim((string)($_POST['kelurahan_dusun'] ?? ''));

  $provinsi_code    = trim((string)($_POST['provinsi_code'] ?? ''));
  $kota_kab_code    = trim((string)($_POST['kota_kab_code'] ?? ''));
  $kecamatan_code   = trim((string)($_POST['kecamatan_code'] ?? ''));
  $kelurahan_code   = trim((string)($_POST['kelurahan_code'] ?? ''));

  $latitude_raw     = trim((string)($_POST['latitude'] ?? ''));
  $longitude_raw    = trim((string)($_POST['longitude'] ?? ''));

  $kapasitas_nilai  = trim((string)($_POST['kapasitas_nilai'] ?? ''));
  $kapasitas_satuan = (string)($_POST['kapasitas_satuan'] ?? 'Mbps');

  // Validasi wajib
  $required = [
    'Nama pemilik' => $nama_pemilik,
    'Nama brand' => $nama_brand,
    'Alamat lengkap' => $alamat_lengkap,
    'Provinsi' => $provinsi,
    'Kota/Kabupaten' => $kota_kab,
    'Kecamatan' => $kecamatan,
    'Kelurahan/Desa (Dusun)' => $kelurahan_dusun,
    'Kapasitas' => $kapasitas_nilai,
  ];
  foreach ($required as $label => $val) {
    if ($val === '') { $error = "Field wajib belum diisi: {$label}."; break; }
  }

  if ($error === '') {
    if (!in_array($kapasitas_satuan, ['Mbps','Gbps'], true)) {
      $error = "Satuan kapasitas tidak valid.";
    } elseif (!is_numeric($kapasitas_nilai) || (float)$kapasitas_nilai <= 0) {
      $error = "Kapasitas harus angka > 0.";
    }
  }

  // Validasi koordinat (opsional)
  $latitude = null;
  $longitude = null;
  if ($error === '') {
    if ($latitude_raw !== '' || $longitude_raw !== '') {
      if (!is_numeric($latitude_raw) || !is_numeric($longitude_raw)) {
        $error = "Koordinat harus angka (latitude/longitude).";
      } else {
        $lat = (float)$latitude_raw;
        $lng = (float)$longitude_raw;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
          $error = "Koordinat di luar rentang valid.";
        } else {
          $latitude = $lat;
          $longitude = $lng;
        }
      }
    }
  }

  if ($error === '') {
    $cols = [
      'nama_pemilik',
      'nama_brand',
      'alamat_lengkap',
      'kelurahan_dusun',
      'kota_kab',
      'provinsi',
      'latitude',
      'longitude',
      'kapasitas_nilai',
      'kapasitas_satuan',
    ];

    $params = [
      ':nama_pemilik' => $nama_pemilik,
      ':nama_brand' => $nama_brand,
      ':alamat_lengkap' => $alamat_lengkap,
      ':kelurahan_dusun' => $kelurahan_dusun,
      ':kota_kab' => $kota_kab,
      ':provinsi' => $provinsi,
      ':latitude' => $latitude,
      ':longitude' => $longitude,
      ':kapasitas_nilai' => (float)$kapasitas_nilai,
      ':kapasitas_satuan' => $kapasitas_satuan,
    ];

    if (isset($has['kecamatan'])) {
      $cols[] = 'kecamatan';
      $params[':kecamatan'] = $kecamatan;
    }

    if (isset($has['provinsi_code']))   { $cols[] = 'provinsi_code';   $params[':provinsi_code'] = ($provinsi_code !== '' ? $provinsi_code : null); }
    if (isset($has['kota_kab_code']))   { $cols[] = 'kota_kab_code';   $params[':kota_kab_code'] = ($kota_kab_code !== '' ? $kota_kab_code : null); }
    if (isset($has['kecamatan_code']))  { $cols[] = 'kecamatan_code';  $params[':kecamatan_code'] = ($kecamatan_code !== '' ? $kecamatan_code : null); }
    if (isset($has['kelurahan_code']))  { $cols[] = 'kelurahan_code';  $params[':kelurahan_code'] = ($kelurahan_code !== '' ? $kelurahan_code : null); }

    $colSql = implode(',', $cols);
    $phSql  = implode(',', array_map(fn($c) => ':' . $c, $cols));

    $sql = "INSERT INTO mitra_resmi ($colSql) VALUES ($phSql)";
    $stmt = $pdo->prepare($sql);

    try {
      $stmt->execute($params);
      $success = "Pendaftaran berhasil dikirim. Status: pending.";
      $_POST = [];
    } catch (Throwable $e) {
      $error = "Gagal simpan data. Cek struktur tabel/kolom (atau jalankan ALTER TABLE).";
    }
  }
}

function old($k): string {
  return htmlspecialchars((string)($_POST[$k] ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar Mitra Resmi - PT Real Data Solusindo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --primary: #667eea;
      --secondary: #764ba2;
    }
    body {
      background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    }
    .header-brand {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      padding: 2rem;
      border-radius: 0.5rem;
      margin-bottom: 2rem;
    }
    .header-brand h2 {
      margin: 0;
      font-weight: 700;
      font-size: 2rem;
    }
    .header-brand .tagline {
      font-size: 0.95rem;
      margin-top: 0.5rem;
      opacity: 0.95;
    }
    .info-card {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      color: white;
      border-radius: 0.5rem;
      padding: 2rem;
      margin-top: 2rem;
    }
    .info-card h5 {
      font-weight: 700;
      margin-bottom: 1.5rem;
      font-size: 1.3rem;
    }
    .info-row {
      margin-bottom: 1.2rem;
      font-size: 0.95rem;
    }
    .info-row strong {
      display: block;
      margin-bottom: 0.3rem;
      opacity: 0.9;
    }
    .info-row span {
      display: block;
      font-size: 1rem;
    }
    .badge-feature {
      background: rgba(255,255,255,0.2);
      border: 1px solid rgba(255,255,255,0.3);
      padding: 0.5rem 1rem;
      border-radius: 0.25rem;
      margin: 0.5rem 0.5rem 0.5rem 0;
      display: inline-block;
      font-size: 0.85rem;
    }
    .form-control:focus,
    .form-select:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
    .btn-primary {
      background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
      border: none;
      padding: 0.75rem 2rem;
      font-weight: 600;
    }
    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        
        <!-- HEADER BRAND -->
        <div class="header-brand text-center">
          <h2>🌐 PT REAL DATA SOLUSINDO</h2>
          <p class="tagline">ISP Resmi | Solusi Internet Terpercaya untuk WiFi RT/RW Net Anda</p>
        </div>

        <div class="card shadow-sm border-0">
          <div class="card-body p-4">
            <h5 class="mb-1">Pendaftaran Mitra Resmi</h5>
            <p class="text-muted mb-3">Bergabunglah dengan jaringan mitra kami dan nikmati layanan internet terbaik dengan dukungan 24/7.</p>

            <?php if ($error): ?>
              <div class="alert alert-danger mb-3"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
              <div class="alert alert-success mb-3"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <div id="wilayah_error" class="alert alert-warning d-none mb-3"></div>

            <form method="post" autocomplete="off">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Nama Pemilik *</label>
                  <input class="form-control" name="nama_pemilik" value="<?= old('nama_pemilik') ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Nama Brand *</label>
                  <input class="form-control" name="nama_brand" value="<?= old('nama_brand') ?>" required>
                </div>

                <div class="col-12">
                  <label class="form-label">Alamat Lengkap *</label>
                  <textarea class="form-control" name="alamat_lengkap" rows="3" required><?= old('alamat_lengkap') ?></textarea>
                </div>

                <!-- DROPDOWN WILAYAH -->
                <div class="col-md-3">
                  <label class="form-label">Provinsi *</label>
                  <select id="provinsi_select" class="form-select" required></select>
                  <input type="hidden" name="provinsi" id="provinsi_name" value="<?= old('provinsi') ?>">
                  <input type="hidden" name="provinsi_code" id="provinsi_code" value="<?= old('provinsi_code') ?>">
                </div>

                <div class="col-md-3">
                  <label class="form-label">Kota/Kabupaten *</label>
                  <select id="kota_select" class="form-select" required disabled></select>
                  <input type="hidden" name="kota_kab" id="kota_name" value="<?= old('kota_kab') ?>">
                  <input type="hidden" name="kota_kab_code" id="kota_code" value="<?= old('kota_kab_code') ?>">
                </div>

                <div class="col-md-3">
                  <label class="form-label">Kecamatan *</label>
                  <select id="kecamatan_select" class="form-select" required disabled></select>
                  <input type="hidden" name="kecamatan" id="kecamatan_name" value="<?= old('kecamatan') ?>">
                  <input type="hidden" name="kecamatan_code" id="kecamatan_code" value="<?= old('kecamatan_code') ?>">
                </div>

                <div class="col-md-3">
                  <label class="form-label">Kelurahan/Desa (Dusun) *</label>
                  <select id="kelurahan_select" class="form-select" required disabled></select>
                  <input type="hidden" name="kelurahan_dusun" id="kelurahan_name" value="<?= old('kelurahan_dusun') ?>">
                  <input type="hidden" name="kelurahan_code" id="kelurahan_code" value="<?= old('kelurahan_code') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label">Latitude (opsional)</label>
                  <input class="form-control" name="latitude" placeholder="-6.1234567" value="<?= old('latitude') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label">Longitude (opsional)</label>
                  <input class="form-control" name="longitude" placeholder="106.1234567" value="<?= old('longitude') ?>">
                </div>

                <div class="col-md-6">
                  <label class="form-label">Kapasitas *</label>
                  <input type="number" step="0.001" min="0.001" class="form-control"
                         name="kapasitas_nilai" placeholder="Contoh: 200" value="<?= old('kapasitas_nilai') ?>" required>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Satuan *</label>
                  <?php $unit = (string)($_POST['kapasitas_satuan'] ?? 'Mbps'); ?>
                  <select class="form-select" name="kapasitas_satuan" required>
                    <option value="Mbps" <?= $unit==='Mbps'?'selected':'' ?>>Mbps</option>
                    <option value="Gbps" <?= $unit==='Gbps'?'selected':'' ?>>Gbps</option>
                  </select>
                </div>

                <div class="col-12 d-grid mt-3">
                  <button class="btn btn-primary btn-lg" type="submit">📝 Kirim Pendaftaran</button>
                </div>
              </div>
            </form>

          </div>
        </div>

        <!-- INFO CARD PERUSAHAAN -->
        <div class="info-card">
          <h5>⭐ Mengapa Memilih PT Real Data Solusindo?</h5>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <div class="info-row">
                <strong>✓ Layanan 24/7 Tanpa Henti</strong>
                <span>Tim support profesional siap membantu Anda kapan saja, siang atau malam.</span>
              </div>
              <div class="info-row">
                <strong>✓ Kecepatan Internet Stabil</strong>
                <span>Koneksi internet yang handal dengan kecepatan konsisten untuk WiFi RT/RW Net Anda.</span>
              </div>
              <div class="info-row">
                <strong>✓ Teknologi Terdepan</strong>
                <span>Menggunakan infrastruktur modern dan teknologi terkini untuk performa maksimal.</span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-row">
                <strong>✓ Harga Kompetitif</strong>
                <span>Paket harga yang terjangkau dengan kualitas premium sesuai kebutuhan Anda.</span>
              </div>
              <div class="info-row">
                <strong>✓ Solusi Fleksibel</strong>
                <span>Paket yang dapat disesuaikan untuk memenuhi kebutuhan spesifik bisnis Anda.</span>
              </div>
              <div class="info-row">
                <strong>✓ ISP Resmi Terpercaya</strong>
                <span>Provider internet bersertifikat dengan track record memuaskan di seluruh Indonesia.</span>
              </div>
            </div>
          </div>

          <div class="row g-3 mt-3 pt-3 border-top border-white border-opacity-25">
            <div class="col-md-6">
              <div class="info-row">
                <strong>📧 Email</strong>
                <span>admin@datarealsolution.net</span>
              </div>
              <div class="info-row">
                <strong>📱 Telepon / WhatsApp</strong>
                <span>+62 855-4517-6427</span>
              </div>
            </div>
            <div class="col-md-6">
              <div class="info-row">
                <strong>📍 Alamat Kantor</strong>
                <span>Jalan Kartini Gang Cempaka<br>Desa Sengon, Tanjung, Brebes</span>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center text-muted mt-4 pb-3">
          <small>© <?= date('Y') ?> PT REAL DATA SOLUSINDO | ISP Resmi Penyedia Layanan WiFi RT/RW Net | Komitmen Kami adalah Kepuasan Anda</small>
        </div>
      </div>
    </div>
  </div>

<script>
/**
 * EMSIFA (endpoint yang benar)
 * Base: https://www.emsifa.com/api-wilayah-indonesia/api
 * - provinces.json
 * - regencies/{provinceId}.json
 * - districts/{regencyId}.json
 * - villages/{districtId}.json
 */
const EMSIFA_BASE = "https://www.emsifa.com/api-wilayah-indonesia/api";

const provSel = document.getElementById("provinsi_select");
const kotaSel = document.getElementById("kota_select");
const kecSel  = document.getElementById("kecamatan_select");
const kelSel  = document.getElementById("kelurahan_select");

const provName = document.getElementById("provinsi_name");
const provCode = document.getElementById("provinsi_code");
const kotaName = document.getElementById("kota_name");
const kotaCode = document.getElementById("kota_code");
const kecName  = document.getElementById("kecamatan_name");
const kecCode  = document.getElementById("kecamatan_code");
const kelName  = document.getElementById("kelurahan_name");
const kelCode  = document.getElementById("kelurahan_code");

const errEl = document.getElementById("wilayah_error");

function showError(msg){
  if (!errEl) return;
  errEl.classList.remove("d-none");
  errEl.textContent = msg;
}

function setOptions(selectEl, items, placeholder) {
  selectEl.innerHTML = "";
  const opt0 = document.createElement("option");
  opt0.value = "";
  opt0.textContent = placeholder;
  selectEl.appendChild(opt0);

  for (const it of items) {
    const opt = document.createElement("option");
    opt.value = it.id;
    opt.textContent = it.name;
    selectEl.appendChild(opt);
  }
}

function resetDownstream(level) {
  if (level <= 2) {
    setOptions(kotaSel, [], "Pilih Kota/Kab");
    kotaSel.disabled = true;
    kotaName.value = ""; kotaCode.value = "";
  }
  if (level <= 3) {
    setOptions(kecSel, [], "Pilih Kecamatan");
    kecSel.disabled = true;
    kecName.value = ""; kecCode.value = "";
  }
  if (level <= 4) {
    setOptions(kelSel, [], "Pilih Kelurahan/Desa");
    kelSel.disabled = true;
    kelName.value = ""; kelCode.value = "";
  }
}

async function fetchJSON(url) {
  const res = await fetch(url, { cache: "no-store" });
  if (!res.ok) {
    let body = "";
    try { body = await res.text(); } catch(e){}
    throw new Error(`HTTP ${res.status} ${res.statusText} | ${String(body).slice(0, 160)}`);
  }
  return res.json();
}

function selectById(selectEl, id) {
  if (!id) return false;
  for (let i = 0; i < selectEl.options.length; i++) {
    if (selectEl.options[i].value === id) {
      selectEl.selectedIndex = i;
      return true;
    }
  }
  return false;
}

async function loadProvinces() {
  const arr = await fetchJSON(`${EMSIFA_BASE}/provinces.json`);
  setOptions(provSel, arr || [], "Pilih Provinsi");
}

provSel.addEventListener("change", async () => {
  resetDownstream(2);

  const id = provSel.value;
  const name = provSel.options[provSel.selectedIndex]?.text || "";
  provCode.value = id;
  provName.value = name;

  if (!id) return;

  const arr = await fetchJSON(`${EMSIFA_BASE}/regencies/${encodeURIComponent(id)}.json`);
  setOptions(kotaSel, arr || [], "Pilih Kota/Kab");
  kotaSel.disabled = false;
});

kotaSel.addEventListener("change", async () => {
  resetDownstream(3);

  const id = kotaSel.value;
  const name = kotaSel.options[kotaSel.selectedIndex]?.text || "";
  kotaCode.value = id;
  kotaName.value = name;

  if (!id) return;

  const arr = await fetchJSON(`${EMSIFA_BASE}/districts/${encodeURIComponent(id)}.json`);
  setOptions(kecSel, arr || [], "Pilih Kecamatan");
  kecSel.disabled = false;
});

kecSel.addEventListener("change", async () => {
  resetDownstream(4);

  const id = kecSel.value;
  const name = kecSel.options[kecSel.selectedIndex]?.text || "";
  kecCode.value = id;
  kecName.value = name;

  if (!id) return;

  const arr = await fetchJSON(`${EMSIFA_BASE}/villages/${encodeURIComponent(id)}.json`);
  setOptions(kelSel, arr || [], "Pilih Kelurahan/Desa");
  kelSel.disabled = false;
});

kelSel.addEventListener("change", () => {
  const id = kelSel.value;
  const name = kelSel.options[kelSel.selectedIndex]?.text || "";
  kelCode.value = id;
  kelName.value = name;
});

(async function initWilayah(){
  try {
    resetDownstream(2);
    await loadProvinces();

    // restore dari POST jika ada
    const pId = provCode.value;
    if (selectById(provSel, pId) && provSel.value) {
      provSel.dispatchEvent(new Event("change"));
    }
  } catch (e) {
    showError("Dropdown wilayah gagal load (EMSIFA). Detail: " + (e?.message || e));
  }
})();
</script>
</body>
</html>