<?php
declare(strict_types=1);
require_once __DIR__ . "/config/db.php";

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$errors = [];
$success = null;

// Ambil SEMUA data karyawan
$karyawan = $pdo->query("SELECT id, nama, nik, divisi, jabatan 
                         FROM karyawan 
                         ORDER BY nama ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_karyawan = (int)($_POST['id_karyawan'] ?? 0);
    $jenis_cuti  = trim((string)($_POST['jenis_cuti'] ?? 'Tahunan'));
    $mulai       = trim((string)($_POST['tanggal_mulai'] ?? ''));
    $selesai     = trim((string)($_POST['tanggal_selesai'] ?? ''));
    $alasan      = trim((string)($_POST['alasan'] ?? ''));
    $alamat      = trim((string)($_POST['alamat_selama_cuti'] ?? ''));
    $kontak      = trim((string)($_POST['kontak_darurat'] ?? ''));

    $allowedJenis = ['Tahunan','Sakit','Izin','Melahirkan','Dinas','Lainnya'];
    if ($id_karyawan <= 0) $errors[] = "Karyawan wajib dipilih.";
    if (!in_array($jenis_cuti, $allowedJenis, true)) $errors[] = "Jenis cuti tidak valid.";
    if ($mulai === '' || $selesai === '') $errors[] = "Tanggal mulai & selesai wajib diisi.";

    $jumlah_hari = 1;
    $dtMulai = null; $dtSelesai = null;
    if (!$errors) {
        try {
            $dtMulai = new DateTime($mulai);
            $dtSelesai = new DateTime($selesai);
            if ($dtSelesai < $dtMulai) {
                $errors[] = "Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.";
            } else {
                $interval = $dtMulai->diff($dtSelesai);
                $jumlah_hari = (int)$interval->format('%a') + 1;
            }
        } catch (Throwable $e) {
            $errors[] = "Format tanggal tidak valid.";
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id FROM karyawan WHERE id = ? LIMIT 1");
        $stmt->execute([$id_karyawan]);
        if (!$stmt->fetch()) $errors[] = "Karyawan tidak ditemukan.";
    }

    $lampiranPath = null;
    if (!$errors && isset($_FILES['lampiran']) && $_FILES['lampiran']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['lampiran']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Upload lampiran gagal.";
        } else {
            $maxBytes = 2 * 1024 * 1024;
            if ((int)$_FILES['lampiran']['size'] > $maxBytes) {
                $errors[] = "Ukuran lampiran maksimal 2MB.";
            } else {
                $ext = strtolower(pathinfo($_FILES['lampiran']['name'], PATHINFO_EXTENSION));
                $allowedExt = ['pdf','jpg','jpeg','png'];
                if (!in_array($ext, $allowedExt, true)) {
                    $errors[] = "Lampiran hanya boleh: pdf/jpg/png.";
                } else {
                    $uploadDir = __DIR__ . "/uploads/cuti";
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $safeName = "cuti_" . $id_karyawan . "_" . date('Ymd_His') . "_" . bin2hex(random_bytes(4)) . "." . $ext;
                    $dest = $uploadDir . "/" . $safeName;
                    if (!move_uploaded_file($_FILES['lampiran']['tmp_name'], $dest)) {
                        $errors[] = "Gagal menyimpan file lampiran.";
                    } else {
                        $lampiranPath = "uploads/cuti/" . $safeName;
                    }
                }
            }
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare("
            INSERT INTO cuti
            (id_karyawan, jenis_cuti, tanggal_mulai, tanggal_selesai, jumlah_hari,
             alasan, alamat_selama_cuti, kontak_darurat, lampiran, status)
            VALUES
            (:id_karyawan, :jenis_cuti, :tanggal_mulai, :tanggal_selesai, :jumlah_hari,
             :alasan, :alamat_selama_cuti, :kontak_darurat, :lampiran, 'Diajukan')
        ");
        $stmt->execute([
            ':id_karyawan'       => $id_karyawan,
            ':jenis_cuti'        => $jenis_cuti,
            ':tanggal_mulai'     => $mulai,
            ':tanggal_selesai'   => $selesai,
            ':jumlah_hari'       => $jumlah_hari,
            ':alasan'            => ($alasan !== '' ? $alasan : null),
            ':alamat_selama_cuti'=> ($alamat !== '' ? $alamat : null),
            ':kontak_darurat'    => ($kontak !== '' ? $kontak : null),
            ':lampiran'          => $lampiranPath,
        ]);
        $success = "Cuti berhasil diajukan. (Jumlah hari: {$jumlah_hari})";
        $_POST = [];
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
  <title>Input Cuti – PT Real Data Solusindo</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand:        #0D47A1;
      --brand-mid:    #1565C0;
      --brand-light:  #1976D2;
      --accent:       #FF6F00;
      --accent-light: #FFA000;
      --bg:           #F0F4FF;
      --surface:      #FFFFFF;
      --surface2:     #EEF2FB;
      --border:       #C5D3F0;
      --text:         #0D1B3E;
      --text-muted:   #5A6A92;
      --success:      #1B8A5A;
      --success-bg:   #E6F6EF;
      --error:        #C0392B;
      --error-bg:     #FDECEA;
      --radius:       14px;
      --radius-sm:    8px;
      --shadow:       0 4px 24px rgba(13,71,161,.10);
      --shadow-lg:    0 8px 40px rgba(13,71,161,.16);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }

    /* ── HEADER ── */
    .app-header {
      background: linear-gradient(135deg, var(--brand) 0%, var(--brand-light) 100%);
      color: #fff;
      padding: 0;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 16px rgba(13,71,161,.30);
    }

    .header-inner {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
    }

    .header-logo {
      width: 40px;
      height: 40px;
      background: rgba(255,255,255,.18);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      backdrop-filter: blur(4px);
    }

    .header-logo svg { width: 22px; height: 22px; fill: #fff; }

    .header-text { flex: 1; min-width: 0; }

    .header-company {
      font-size: 10px;
      font-weight: 600;
      letter-spacing: .12em;
      text-transform: uppercase;
      opacity: .80;
      line-height: 1;
      margin-bottom: 3px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .header-title {
      font-size: 17px;
      font-weight: 800;
      line-height: 1.1;
      letter-spacing: -.01em;
    }

    .header-action a {
      display: flex;
      align-items: center;
      gap: 5px;
      background: rgba(255,255,255,.18);
      color: #fff;
      text-decoration: none;
      font-size: 12px;
      font-weight: 600;
      padding: 7px 12px;
      border-radius: 20px;
      white-space: nowrap;
      backdrop-filter: blur(4px);
      transition: background .2s;
    }
    .header-action a:active { background: rgba(255,255,255,.30); }

    /* ── MAIN CONTENT ── */
    .page-body {
      padding: 16px 14px 40px;
      max-width: 600px;
      margin: 0 auto;
    }

    /* ── ALERTS ── */
    .alert {
      border-radius: var(--radius-sm);
      padding: 13px 15px;
      font-size: 14px;
      line-height: 1.5;
      margin-bottom: 16px;
      display: flex;
      gap: 10px;
      align-items: flex-start;
      animation: slideDown .3s ease;
    }
    @keyframes slideDown {
      from { opacity:0; transform: translateY(-8px); }
      to   { opacity:1; transform: translateY(0); }
    }

    .alert-success {
      background: var(--success-bg);
      border-left: 4px solid var(--success);
      color: var(--success);
    }

    .alert-error {
      background: var(--error-bg);
      border-left: 4px solid var(--error);
      color: var(--error);
    }

    .alert-icon { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .alert-body { flex: 1; }
    .alert-title { font-weight: 700; margin-bottom: 4px; }
    .alert-body ul { padding-left: 16px; }
    .alert-body ul li { margin-bottom: 2px; }

    /* ── CARD ── */
    .card {
      background: var(--surface);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      overflow: hidden;
      margin-bottom: 14px;
    }

    .card-header {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 14px 16px 13px;
      border-bottom: 1px solid var(--border);
      background: var(--surface2);
    }

    .card-header-icon {
      width: 32px;
      height: 32px;
      background: var(--brand);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .card-header-icon svg { width: 16px; height: 16px; fill: #fff; }

    .card-header-title {
      font-size: 13px;
      font-weight: 700;
      color: var(--brand);
      letter-spacing: .04em;
      text-transform: uppercase;
    }

    .card-body { padding: 16px; }

    /* ── FORM FIELDS ── */
    .field { margin-bottom: 14px; }
    .field:last-child { margin-bottom: 0; }

    .field-label {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 12.5px;
      font-weight: 700;
      color: var(--text-muted);
      letter-spacing: .04em;
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .field-label .req {
      color: var(--accent);
      font-size: 14px;
      line-height: 1;
    }

    .field-label .badge-opt {
      background: var(--surface2);
      color: var(--text-muted);
      font-size: 9px;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 20px;
      letter-spacing: .06em;
      text-transform: uppercase;
    }

    .form-control,
    .form-select {
      width: 100%;
      height: 46px;
      padding: 0 14px;
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      font-family: inherit;
      font-size: 15px;
      color: var(--text);
      background: #fff;
      transition: border-color .2s, box-shadow .2s;
      -webkit-appearance: none;
      appearance: none;
    }

    .form-control:focus,
    .form-select:focus {
      outline: none;
      border-color: var(--brand-light);
      box-shadow: 0 0 0 3px rgba(25,118,210,.15);
    }

    .form-select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%235A6A92'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      background-size: 20px;
      padding-right: 36px;
    }

    .form-text {
      font-size: 11.5px;
      color: var(--text-muted);
      margin-top: 5px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    /* File input */
    .file-input-wrapper {
      position: relative;
    }

    .file-input-display {
      width: 100%;
      height: 46px;
      border: 1.5px dashed var(--border);
      border-radius: var(--radius-sm);
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0 14px;
      cursor: pointer;
      background: var(--surface2);
      transition: border-color .2s, background .2s;
      font-size: 14px;
      color: var(--text-muted);
    }
    .file-input-display:active {
      border-color: var(--brand-light);
      background: #e8effc;
    }

    .file-input-display .file-icon { font-size: 18px; }
    .file-input-display .file-text { flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      width: 100%;
      height: 100%;
      cursor: pointer;
    }

    /* Two-column row */
    .field-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }

    /* ── JENIS CUTI PILLS ── */
    .jenis-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
    }

    .pill-input { display: none; }

    .pill-label {
      padding: 8px 14px;
      border-radius: 20px;
      border: 1.5px solid var(--border);
      font-size: 13px;
      font-weight: 600;
      color: var(--text-muted);
      cursor: pointer;
      transition: all .18s;
      white-space: nowrap;
      user-select: none;
    }

    .pill-input:checked + .pill-label {
      background: var(--brand);
      border-color: var(--brand);
      color: #fff;
      box-shadow: 0 2px 8px rgba(13,71,161,.25);
    }

    /* Hidden select for POST */
    #jenis_cuti_hidden { display: none; }

    /* ── DAY COUNTER BADGE ── */
    .day-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--brand);
      color: #fff;
      font-family: 'DM Mono', monospace;
      font-size: 13px;
      font-weight: 500;
      padding: 5px 12px;
      border-radius: 20px;
      margin-top: 10px;
      transition: transform .2s;
    }

    .day-badge.hidden { display: none; }
    .day-badge.pop { animation: pop .25s ease; }
    @keyframes pop {
      0%   { transform: scale(1); }
      50%  { transform: scale(1.12); }
      100% { transform: scale(1); }
    }

    /* ── BUTTONS ── */
    .btn-row {
      display: flex;
      gap: 10px;
      margin-top: 4px;
    }

    .btn-submit {
      flex: 1;
      height: 50px;
      background: linear-gradient(135deg, var(--brand) 0%, var(--brand-light) 100%);
      color: #fff;
      border: none;
      border-radius: var(--radius-sm);
      font-family: inherit;
      font-size: 15px;
      font-weight: 700;
      letter-spacing: .02em;
      cursor: pointer;
      box-shadow: 0 4px 14px rgba(13,71,161,.30);
      transition: opacity .2s, transform .15s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .btn-submit:active { opacity: .88; transform: scale(.98); }

    .btn-reset {
      height: 50px;
      width: 90px;
      background: var(--surface2);
      color: var(--text-muted);
      border: 1.5px solid var(--border);
      border-radius: var(--radius-sm);
      font-family: inherit;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: background .2s;
    }
    .btn-reset:active { background: #dde5f8; }

    /* ── FOOTER ── */
    .app-footer {
      text-align: center;
      font-size: 11px;
      color: var(--text-muted);
      padding: 0 16px 20px;
    }

    /* ── SAFE AREA (iOS) ── */
    @supports (padding-bottom: env(safe-area-inset-bottom)) {
      .page-body { padding-bottom: calc(40px + env(safe-area-inset-bottom)); }
    }
  </style>
</head>
<body>

<!-- ═══════════ HEADER ═══════════ -->
<header class="app-header">
  <div class="header-inner">
    <div class="header-logo">
      <!-- briefcase icon -->
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM9 7V5a1 1 0 011-1h4a1 1 0 011 1v2"/>
      </svg>
    </div>
    <div class="header-text">
      <div class="header-company">PT Real Data Solusindo</div>
      <div class="header-title">Pengajuan Cuti</div>
    </div>
    <div class="header-action">
      <a href="cuti_list.php">
        <!-- list icon -->
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
        Riwayat
      </a>
    </div>
  </div>
</header>

<!-- ═══════════ BODY ═══════════ -->
<div class="page-body">

  <?php if ($success): ?>
  <div class="alert alert-success">
    <div class="alert-icon">✅</div>
    <div class="alert-body">
      <div class="alert-title">Berhasil Diajukan!</div>
      <?= h($success) ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($errors): ?>
  <div class="alert alert-error">
    <div class="alert-icon">⚠️</div>
    <div class="alert-body">
      <div class="alert-title">Terjadi Kesalahan</div>
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= h($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="cutiForm">

    <!-- ── CARD 1: Data Karyawan ── -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-icon">
          <svg viewBox="0 0 24 24"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
        </div>
        <div class="card-header-title">Data Karyawan</div>
      </div>
      <div class="card-body">
        <div class="field">
          <label class="field-label">Pilih Karyawan <span class="req">*</span></label>
          <select name="id_karyawan" class="form-select" required>
            <option value="">— pilih karyawan —</option>
            <?php foreach ($karyawan as $k):
              $selected = ((int)($_POST['id_karyawan'] ?? 0) === (int)$k['id']) ? 'selected' : '';
              $label = $k['nama'] . " | " . ($k['nik'] ?? '-') . " | " . ($k['divisi'] ?? '-');
            ?>
              <option value="<?= (int)$k['id'] ?>" <?= $selected ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- ── CARD 2: Detail Cuti ── -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-icon">
          <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5C3.9 4 3 4.9 3 6v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zm0-13H5V6h14v1zm-7 5h5v5h-5z"/></svg>
        </div>
        <div class="card-header-title">Detail Cuti</div>
      </div>
      <div class="card-body">

        <!-- Jenis Cuti -->
        <div class="field">
          <div class="field-label">Jenis Cuti <span class="req">*</span></div>
          <?php
            $curJenis = (string)($_POST['jenis_cuti'] ?? 'Tahunan');
            $optJenis = ['Tahunan','Sakit','Izin','Melahirkan','Dinas','Lainnya'];
          ?>
          <div class="jenis-pills" id="jenisPills">
            <?php foreach ($optJenis as $o):
              $chk = ($curJenis === $o) ? 'checked' : '';
            ?>
              <input type="radio" class="pill-input" name="_jenis_pill" id="pill_<?= h($o) ?>" value="<?= h($o) ?>" <?= $chk ?>>
              <label class="pill-label" for="pill_<?= h($o) ?>"><?= h($o) ?></label>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="jenis_cuti" id="jenis_cuti_hidden" value="<?= h($curJenis) ?>">
        </div>

        <!-- Tanggal -->
        <div class="field-row">
          <div class="field">
            <label class="field-label" for="tanggal_mulai">Mulai <span class="req">*</span></label>
            <input type="date" id="tanggal_mulai" name="tanggal_mulai" class="form-control"
                   value="<?= h((string)($_POST['tanggal_mulai'] ?? '')) ?>" required>
          </div>
          <div class="field">
            <label class="field-label" for="tanggal_selesai">Selesai <span class="req">*</span></label>
            <input type="date" id="tanggal_selesai" name="tanggal_selesai" class="form-control"
                   value="<?= h((string)($_POST['tanggal_selesai'] ?? '')) ?>" required>
          </div>
        </div>

        <!-- Day counter -->
        <div class="day-badge hidden" id="dayBadge">
          📅 <span id="dayCount">0</span> hari
        </div>

        <!-- Alasan -->
        <div class="field" style="margin-top:14px">
          <label class="field-label" for="alasan">Alasan <span class="badge-opt">opsional</span></label>
          <input type="text" id="alasan" name="alasan" class="form-control"
                 placeholder="Contoh: keperluan keluarga"
                 value="<?= h((string)($_POST['alasan'] ?? '')) ?>">
        </div>

      </div>
    </div>

    <!-- ── CARD 3: Info Tambahan ── -->
    <div class="card">
      <div class="card-header">
        <div class="card-header-icon">
          <svg viewBox="0 0 24 24"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5c-1.4 0-2.5-1.1-2.5-2.5S10.6 6.5 12 6.5s2.5 1.1 2.5 2.5S13.4 11.5 12 11.5z"/></svg>
        </div>
        <div class="card-header-title">Info Tambahan</div>
      </div>
      <div class="card-body">

        <div class="field">
          <label class="field-label" for="alamat_selama_cuti">Alamat Selama Cuti <span class="badge-opt">opsional</span></label>
          <input type="text" id="alamat_selama_cuti" name="alamat_selama_cuti" class="form-control"
                 placeholder="Jl. Contoh No. 1, Kota"
                 value="<?= h((string)($_POST['alamat_selama_cuti'] ?? '')) ?>">
        </div>

        <div class="field">
          <label class="field-label" for="kontak_darurat">Kontak Darurat <span class="badge-opt">opsional</span></label>
          <input type="tel" id="kontak_darurat" name="kontak_darurat" class="form-control"
                 placeholder="08xxxxxxxxxx"
                 value="<?= h((string)($_POST['kontak_darurat'] ?? '')) ?>">
        </div>

        <div class="field">
          <div class="field-label">Lampiran <span class="badge-opt">opsional</span></div>
          <div class="file-input-wrapper">
            <div class="file-input-display" id="fileDisplay">
              <span class="file-icon">📎</span>
              <span class="file-text" id="fileText">Pilih file (pdf / jpg / png)</span>
            </div>
            <input type="file" name="lampiran" id="lampiran" accept=".pdf,.jpg,.jpeg,.png"
                   onchange="updateFileName(this)">
          </div>
          <div class="form-text">📌 Maks. 2 MB</div>
        </div>

      </div>
    </div>

    <!-- ── BUTTONS ── -->
    <div class="btn-row">
      <button type="submit" class="btn-submit">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        Ajukan Cuti
      </button>
      <button type="reset" class="btn-reset" onclick="resetForm()">Reset</button>
    </div>

  </form>
</div>

<div class="app-footer">
  © <?= date('Y') ?> PT Real Data Solusindo. All rights reserved.
</div>

<script>
  // Sync jenis cuti pills → hidden input
  document.querySelectorAll('.pill-input').forEach(function(radio) {
    radio.addEventListener('change', function() {
      document.getElementById('jenis_cuti_hidden').value = this.value;
    });
  });

  // Day counter
  function updateDayCount() {
    var mulai   = document.getElementById('tanggal_mulai').value;
    var selesai = document.getElementById('tanggal_selesai').value;
    var badge   = document.getElementById('dayBadge');

    if (!mulai || !selesai) { badge.classList.add('hidden'); return; }

    var d1 = new Date(mulai);
    var d2 = new Date(selesai);

    if (d2 < d1) { badge.classList.add('hidden'); return; }

    var diff = Math.round((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
    document.getElementById('dayCount').textContent = diff;
    badge.classList.remove('hidden');
    badge.classList.remove('pop');
    void badge.offsetWidth; // reflow
    badge.classList.add('pop');
  }

  document.getElementById('tanggal_mulai').addEventListener('change', updateDayCount);
  document.getElementById('tanggal_selesai').addEventListener('change', updateDayCount);

  // Init on load (after POST error)
  updateDayCount();

  // File name display
  function updateFileName(input) {
    var txt = document.getElementById('fileText');
    if (input.files && input.files.length > 0) {
      txt.textContent = input.files[0].name;
      document.getElementById('fileDisplay').style.borderColor = 'var(--brand-light)';
      document.getElementById('fileDisplay').style.background  = '#e8effc';
    }
  }

  // Reset helper
  function resetForm() {
    document.getElementById('fileText').textContent = 'Pilih file (pdf / jpg / png)';
    document.getElementById('fileDisplay').style.borderColor = '';
    document.getElementById('fileDisplay').style.background  = '';
    document.getElementById('dayBadge').classList.add('hidden');
    document.getElementById('jenis_cuti_hidden').value = 'Tahunan';
  }
</script>

</body>
</html>