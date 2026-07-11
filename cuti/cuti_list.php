<?php
/**
 * cuti_list.php - PHP 7.3 Compatible
 * Halaman daftar pengajuan cuti dengan aksi approve/reject
 */
declare(strict_types=1);
require_once __DIR__ . "/config/db.php";

// Helper untuk keamanan output (PHP 7.3 compatible)
function h($s) { 
    return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); 
}

// ── Aksi approve/tolak ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'], $_POST['id_cuti'])) {
    $id      = (int)$_POST['id_cuti'];
    $aksi    = (string)$_POST['aksi'];
    $catatan = isset($_POST['catatan_atasan']) ? trim((string)$_POST['catatan_atasan']) : '';

    $newStatus = null;
    if ($aksi === 'setujui') $newStatus = 'Disetujui';
    if ($aksi === 'tolak')   $newStatus = 'Ditolak';

    if ($id > 0 && $newStatus) {
        $stmt = $pdo->prepare("UPDATE hr_cuti SET status = ?, catatan_atasan = ? WHERE id_cuti = ?");
        $stmt->execute([$newStatus, ($catatan !== '' ? $catatan : null), $id]);
    }
    header("Location: cuti_list.php");
    exit;
}

// ── Ambil data ──────────────────────────────────────────────────────
$rows = $pdo->query("
    SELECT c.*, k.nama, k.nik, k.divisi, k.jabatan
    FROM hr_cuti c
    JOIN hr_karyawan k ON k.id = c.id_karyawan
    ORDER BY c.id_cuti DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Statistik menggunakan Closure (Bukan Arrow Function agar jalan di PHP 7.3)
$total    = count($rows);
$diajukan = count(array_filter($rows, function($r) { return $r['status'] === 'Diajukan'; }));
$disetujui= count(array_filter($rows, function($r) { return $r['status'] === 'Disetujui'; }));
$ditolak  = count(array_filter($rows, function($r) { return $r['status'] === 'Ditolak'; }));
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
  <title>Data Cuti – PT Real Data Solusindo</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root {
      --brand:       #0D47A1;
      --brand-mid:   #1565C0;
      --brand-light: #1976D2;
      --accent:      #FF6F00;
      --bg:          #F0F4FF;
      --surface:     #FFFFFF;
      --surface2:    #EEF2FB;
      --border:      #C5D3F0;
      --text:        #0D1B3E;
      --muted:       #5A6A92;
      --success:     #1B8A5A;
      --success-bg:  #E6F6EF;
      --warning:     #E65100;
      --warning-bg:  #FFF3E0;
      --danger:      #C0392B;
      --danger-bg:   #FDECEA;
      --radius:      14px;
      --radius-sm:   8px;
      --shadow:      0 4px 24px rgba(13,71,161,.10);
    }

    *, *::before, *::after { box-sizing: border-box; margin:0; padding:0; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }

    /* ── HEADER ── */
    .app-header {
      background: linear-gradient(135deg, var(--brand), var(--brand-light));
      color: #fff;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 16px rgba(13,71,161,.30);
    }
    .header-inner {
      display: flex; align-items: center; gap: 12px;
      padding: 14px 16px; max-width: 800px; margin: 0 auto;
    }
    .header-logo {
      width: 40px; height: 40px; background: rgba(255,255,255,.18);
      border-radius: 10px; display: flex; align-items: center; justify-content: center;
    }
    .header-text { flex: 1; min-width: 0; }
    .header-company { font-size: 10px; font-weight: 600; letter-spacing:.12em; text-transform:uppercase; opacity:.80; margin-bottom:3px; }
    .header-title   { font-size: 17px; font-weight: 800; }
    .header-actions { display: flex; gap: 8px; }

    .hbtn {
      display: inline-flex; align-items: center; gap: 5px;
      background: rgba(255,255,255,.18); color: #fff;
      text-decoration: none; font-size: 11px; font-weight: 700;
      padding: 7px 12px; border-radius: 20px; transition: background .2s;
    }
    .hbtn:active { background: rgba(255,255,255,.30); }
    .hbtn.accent { background: var(--accent); }

    /* ── BODY ── */
    .page-body { padding: 14px 14px 40px; max-width: 800px; margin: 0 auto; }

    /* ── STATS ── */
    .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 14px; }
    .stat-card { background: var(--surface); border-radius: var(--radius-sm); padding: 12px 10px; text-align: center; box-shadow: var(--shadow); }
    .stat-num { font-family: 'DM Mono', monospace; font-size: 20px; font-weight: 600; }
    .stat-label { font-size: 9px; font-weight: 800; text-transform: uppercase; color: var(--muted); margin-top: 4px; }
    .c-all { color: var(--brand); } .c-pending { color: var(--warning); } .c-ok { color: var(--success); } .c-no { color: var(--danger); }

    /* ── LIST ── */
    .cuti-list { display: flex; flex-direction: column; gap: 12px; }
    .cuti-card { background: var(--surface); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; }

    .cuti-card-top { display: flex; align-items: flex-start; gap: 10px; padding: 14px; border-bottom: 1px solid var(--border); }
    .cuti-id-badge { font-family: 'DM Mono', monospace; font-size: 11px; background: var(--surface2); color: var(--muted); padding: 3px 8px; border-radius: 20px; }
    .cuti-info { flex: 1; min-width: 0; }
    .cuti-nama { font-size: 15px; font-weight: 700; color: var(--text); }
    .cuti-meta { font-size: 11px; color: var(--muted); margin-top: 2px; }

    .status-pill { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
    .s-diajukan  { background: var(--warning-bg); color: var(--warning); }
    .s-disetujui { background: var(--success-bg); color: var(--success); }
    .s-ditolak   { background: var(--danger-bg);  color: var(--danger); }

    .cuti-card-body { padding: 14px; }
    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .detail-item.full { grid-column: span 2; }
    .detail-label { font-size: 9px; font-weight: 800; color: var(--muted); text-transform: uppercase; margin-bottom: 3px; }
    .detail-value { font-size: 13px; font-weight: 600; }
    .jenis-tag { display: inline-block; padding: 2px 8px; background: var(--surface2); border: 1px solid var(--border); border-radius: 20px; font-size: 11px; color: var(--brand); }

    /* ── ACTION ── */
    .action-bar { padding: 12px 14px; border-top: 1px solid var(--border); background: #fcfdfe; display: flex; flex-direction: column; gap: 8px; }
    .action-input { width: 100%; height: 36px; padding: 0 12px; border: 1.5px solid var(--border); border-radius: 6px; font-size: 13px; }
    .action-btns { display: flex; gap: 8px; }
    .btn { flex: 1; height: 38px; border: none; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; color: #fff; }
    .btn-approve { background: var(--success); }
    .btn-reject  { background: var(--danger); }
    .btn-print   { background: var(--brand-mid); text-decoration: none; }
    .btn:disabled { opacity: 0.4; cursor: not-allowed; }

    .app-footer { text-align: center; font-size: 11px; color: var(--muted); padding: 20px; }
  </style>
</head>
<body>

<header class="app-header">
  <div class="header-inner">
    <div class="header-logo">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
    </div>
    <div class="header-text">
      <div class="header-company">PT Real Data Solusindo</div>
      <div class="header-title">Data Pengajuan Cuti</div>
    </div>
    <div class="header-actions">
      <a class="hbtn accent" href="export_cuti_pdf.php">Laporan</a>
      <a class="hbtn" href="cuti_create.php">+ Input</a>
    </div>
  </div>
</header>

<div class="page-body">
  <div class="stats-row">
    <div class="stat-card"><div class="stat-num c-all"><?= $total ?></div><div class="stat-label">Total</div></div>
    <div class="stat-card"><div class="stat-num c-pending"><?= $diajukan ?></div><div class="stat-label">Pending</div></div>
    <div class="stat-card"><div class="stat-num c-ok"><?= $disetujui ?></div><div class="stat-label">Setuju</div></div>
    <div class="stat-card"><div class="stat-num c-no"><?= $ditolak ?></div><div class="stat-label">Tolak</div></div>
  </div>

  <div class="cuti-list">
    <?php if (empty($rows)): ?>
      <div style="text-align:center; padding: 50px; color: var(--muted);">Belum ada data pengajuan.</div>
    <?php endif; ?>

    <?php foreach ($rows as $r): 
        // Logic Status Class untuk PHP 7.3
        $s = $r['status'];
        $statusClass = ($s === 'Disetujui') ? 's-disetujui' : (($s === 'Ditolak') ? 's-ditolak' : 's-diajukan');
        $isPending = ($s === 'Diajukan');
    ?>
    <div class="cuti-card">
      <div class="cuti-card-top">
        <span class="cuti-id-badge">#<?= (int)$r['id_cuti'] ?></span>
        <div class="cuti-info">
          <div class="cuti-nama"><?= h($r['nama']) ?></div>
          <div class="cuti-meta"><?= h($r['nik']) ?> &middot; <?= h($r['divisi']) ?></div>
        </div>
        <span class="status-pill <?= $statusClass ?>"><?= h($s) ?></span>
      </div>

      <div class="cuti-card-body">
        <div class="detail-grid">
          <div class="detail-item">
            <div class="detail-label">Jenis Cuti</div>
            <div class="detail-value"><span class="jenis-tag"><?= h($r['jenis_cuti']) ?></span></div>
          </div>
          <div class="detail-item">
            <div class="detail-label">Durasi</div>
            <div class="detail-value"><?= (int)$r['jumlah_hari'] ?> Hari</div>
          </div>
          <div class="detail-item full">
            <div class="detail-label">Periode Tanggal</div>
            <div class="detail-value"><?= h($r['tanggal_mulai']) ?> s/d <?= h($r['tanggal_selesai']) ?></div>
          </div>
        </div>
      </div>

      <div class="action-bar">
        <?php if ($isPending): ?>
          <form method="post">
            <input type="hidden" name="id_cuti" value="<?= (int)$r['id_cuti'] ?>">
            <input type="text" name="catatan_atasan" class="action-input" placeholder="Tulis catatan di sini...">
            <div class="action-btns" style="margin-top: 8px;">
              <button type="submit" name="aksi" value="setujui" class="btn btn-approve">✔ Setujui</button>
              <button type="submit" name="aksi" value="tolak" class="btn btn-reject">✖ Tolak</button>
            </div>
          </form>
        <?php else: ?>
          <div class="action-btns">
            <a href="cetak_pengajuan.php?id=<?= $r['id_cuti'] ?>" target="_blank" class="btn btn-print">
              🖨️ Cetak Surat Permohonan
            </a>
          </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="app-footer">© <?= date('Y') ?> PT Real Data Solusindo</div>

</body>
</html>