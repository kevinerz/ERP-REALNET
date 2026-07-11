<?php
/**
 * slip_gaji_pdf.php (PRODUCTION - 1 PAGE FIXED) - INFORMATIVE + SEKAT POTONGAN
 * - 1 lembar (A4 portrait, layout rapat)
 * - Potongan dipisah: Kasbon & Lainnya (Lainnya di bawahnya)
 * - Komponen dibuat lebih komunikatif + ringkasan total
 *
 * URL publik:
 *  - slip_gaji_pdf.php?token=XXXXXXXX
 * fallback admin:
 *  - slip_gaji_pdf.php?id=123
 */

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(0);
@ini_set('memory_limit', '256M');

ob_start();

// =====================
// 1) LOAD DB CONNECTIONS
// =====================
require_once __DIR__ . '/config/database.php';

// =====================
// 2) LOAD DOMPDF (NON-COMPOSER)
// =====================
$dompdfAutoload = __DIR__ . '/dompdf/autoload.inc.php';
if (!file_exists($dompdfAutoload)) {
    http_response_code(500);
    error_log("[SLIP_GAJI_PDF] dompdf autoload not found: " . $dompdfAutoload);
    die("Sistem PDF belum siap. Hubungi admin.");
}
require_once $dompdfAutoload;

// =====================
// 3) HELPERS
// =====================
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function rupiah($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function fmtTanggal($ymd): string {
    if (!$ymd) return '-';
    $ts = strtotime((string)$ymd);
    return $ts ? date('d M Y', $ts) : (string)$ymd;
}
function imgToDataUri(string $path): string {
    if (!file_exists($path)) return '';
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mime = 'image/png';
    if ($ext === 'jpg' || $ext === 'jpeg') $mime = 'image/jpeg';
    if ($ext === 'webp') $mime = 'image/webp';
    $data = @file_get_contents($path);
    if ($data === false) return '';
    return "data:$mime;base64," . base64_encode($data);
}
function makePublicToken(): string { return bin2hex(random_bytes(32)); }

function getKaryawanByNik(mysqli $conn_bbm, string $nik): ?array {
    $sql = "SELECT
                id, nik, nama, no_telp, divisi, jabatan, bank, rekening,
                COALESCE(gaji_pokok,0) AS gaji_pokok,
                COALESCE(tunjangan_jabatan,0) AS tunjangan_jabatan,
                COALESCE(tunjangan_operasional,0) AS tunjangan_operasional
            FROM hr_karyawan
            WHERE nik = ? LIMIT 1";
    $stmt = @$conn_bbm->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param("s", $nik);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row;
}

// =====================
// 4) VALIDASI INPUT (TOKEN / ID)
// =====================
$id    = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

if ($token === '' && $id <= 0) { http_response_code(400); die("Parameter tidak valid."); }
if ($token !== '' && !preg_match('/^[a-f0-9]{32,64}$/i', $token)) { http_response_code(400); die("Token tidak valid."); }

// =====================
// 5) AMBIL DATA SLIP (DB FMS)
// =====================
if ($token !== '') {
    $sqlSlip = "SELECT id, public_token, karyawan_nik, nama_karyawan, tanggal_bayar, gaji_pokok, bonus, potongan, total_dibayar,
                       nama_bank, no_rekening, nama_rekening, keterangan
                FROM hr_slip_gaji
                WHERE public_token = ?
                LIMIT 1";
    $stmt = $conn->prepare($sqlSlip);
    if (!$stmt) { http_response_code(500); error_log("[SLIP_GAJI_PDF] Prepare by token failed: ".$conn->error); die("Sistem gangguan."); }
    $stmt->bind_param("s", $token);
} else {
    $sqlSlip = "SELECT id, public_token, karyawan_nik, nama_karyawan, tanggal_bayar, gaji_pokok, bonus, potongan, total_dibayar,
                       nama_bank, no_rekening, nama_rekening, keterangan
                FROM hr_slip_gaji
                WHERE id = ?
                LIMIT 1";
    $stmt = $conn->prepare($sqlSlip);
    if (!$stmt) { http_response_code(500); error_log("[SLIP_GAJI_PDF] Prepare by id failed: ".$conn->error); die("Sistem gangguan."); }
    $stmt->bind_param("i", $id);
}

$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) { http_response_code(404); die("Data slip gaji tidak ditemukan."); }
$slip = $res->fetch_assoc();
$stmt->close();

// Auto-generate token untuk slip lama yang belum punya token
if (empty($slip['public_token'])) {
    $newToken = makePublicToken();
    $upd = $conn->prepare("UPDATE hr_slip_gaji SET public_token=? WHERE id=? LIMIT 1");
    if ($upd) {
        $sid = (int)$slip['id'];
        $upd->bind_param("si", $newToken, $sid);
        $upd->execute();
        $upd->close();
        $slip['public_token'] = $newToken;
    }
}

// =====================
// 6) DATA KARYAWAN (UMUMDATA)
// =====================
$karyawan = getKaryawanByNik($conn_bbm, (string)$slip['karyawan_nik']);

// =====================
// 7) NORMALISASI
// =====================
$nama_karyawan = $slip['nama_karyawan'] ?: ($karyawan['nama'] ?? '-');
$nik_karyawan  = $slip['karyawan_nik'] ?: ($karyawan['nik'] ?? '-');
$no_telp       = $karyawan['no_telp'] ?? '-';
$divisi        = $karyawan['divisi'] ?? '-';
$jabatan       = $karyawan['jabatan'] ?? '-';

$nama_bank     = $slip['nama_bank'] ?: ($karyawan['bank'] ?? '');
$no_rekening   = $slip['no_rekening'] ?: ($karyawan['rekening'] ?? '');
$nama_rekening = $slip['nama_rekening'] ?: $nama_karyawan;

// Data pembayaran slip (dari DB FMS)
$gaji_dasar_slip = (float)($slip['gaji_pokok'] ?? 0); // legacy (gabungan)
$bonus           = (float)($slip['bonus'] ?? 0);
$potongan_total  = (float)($slip['potongan'] ?? 0);

// Komponen gaji dari UMUMDATA (3 komponen)
$gp = (float)($karyawan['gaji_pokok'] ?? 0);
$tj = (float)($karyawan['tunjangan_jabatan'] ?? 0);
$to = (float)($karyawan['tunjangan_operasional'] ?? 0);
if (!$karyawan) { $gp=0; $tj=0; $to=0; }

$gaji_dasar_calc   = $gp + $tj + $to;
$gaji_dasar_tampil = ($gaji_dasar_calc > 0) ? $gaji_dasar_calc : $gaji_dasar_slip;

// Pecah potongan: kasbon & lainnya (lainnya di bawah) — default: semua dianggap kasbon
$pot_kasbon = $potongan_total;
$pot_lainnya = 0.0;

$pendapatan_total = $gaji_dasar_tampil + $bonus;
$total_dibayar = (float)($slip['total_dibayar'] ?? ($pendapatan_total - $potongan_total));
if ($total_dibayar < 0) $total_dibayar = 0;

$tanggal_bayar = $slip['tanggal_bayar'] ?? date('Y-m-d');
$keterangan    = trim((string)($slip['keterangan'] ?? ''));

$kode_slip = 'SLIP-' . date('Ym', strtotime($tanggal_bayar)) . '-' . str_pad((string)$slip['id'], 6, '0', STR_PAD_LEFT);

// =====================
// 8) IDENTITAS
// =====================
$companyName  = "PT REAL DATA SOLUSINDO";
$companyLine  = "Payroll & HRIS";
$companyAddr  = "Jalan Kartini Gang Cempaka, Desa Sengon, Kec. Tanjung, Kab. Brebes, Jawa Tengah 52254";
$companyEmail = "admin@datarealsolution.net";

// =====================
// 9) ASSETS
// =====================
$logoDataUri  = imgToDataUri(__DIR__ . "/assets/logo.png");
$stampDataUri = imgToDataUri(__DIR__ . "/assets/stempel.png"); // optional

// =====================
// 10) HTML (1 PAGE TIGHT)
// =====================
$html = '
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
@page { margin: 12mm 12mm; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.4px; color:#0f172a; margin:0; }
.small { font-size: 9.6px; }
.mono { font-family: DejaVu Sans Mono, monospace; }

/* Header compact */
.header { border:1px solid #0b1220; border-radius:10px; padding:10px 12px; background:#0b1220; color:#fff; }
.hrow { width:100%; border-collapse:collapse; }
.hrow td { vertical-align:middle; }
.logoBox { width: 210px; padding-right:10px; }
.logoBox img { width:210px; height:auto; display:block; background:#fff; border-radius:8px; padding:6px; }
.coName { font-size: 13px; font-weight:900; margin:0; }
.coLine { font-size: 10px; font-weight:700; margin:2px 0 6px 0; opacity:.95; }
.coAddr { font-size: 9.4px; line-height: 1.3; opacity: 1; }
.meta { text-align:right; width: 200px; }
.badge { display:inline-block; padding:6px 10px; border-radius:999px; background:#111827; border:1px solid rgba(255,255,255,0.25); font-size:9.6px; font-weight:900; }
.metaSmall { font-size: 9.6px; margin-top: 6px; opacity: .95; }

/* Cards - tight */
.section { margin-top: 10px; }
.grid { width:100%; border-collapse:separate; border-spacing:8px 8px; }
.card { border:1px solid #e5e7eb; border-radius:10px; padding:9px 10px; background:#fff; }
.cardTitle { font-size: 9px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; color:#334155; margin:0 0 6px 0; }
.row { width:100%; border-collapse:collapse; }
.row td { padding: 1px 0; vertical-align: top; }
.lb { width: 40%; color:#64748b; font-size: 9.6px; }
.vl { width: 60%; font-weight: 800; font-size: 9.8px; }

/* Summary compact */
.summary { border:1px solid #cbd5e1; border-radius:10px; padding:10px 12px; background:#f8fafc; margin-top: 8px; }
.sumWrap { width:100%; border-collapse:collapse; }
.sumWrap td { vertical-align: middle; }
.sumLabel { font-size: 9.6px; color:#334155; font-weight:800; margin:0; }
.sumValue { font-size: 18px; font-weight: 900; margin: 3px 0 0 0; color:#0b1220; }
.sumMini { font-size: 9.4px; color:#475569; line-height: 1.35; }

/* TABLE: Informative + sekat potongan */
.tbl { width:100%; border-collapse:separate; border-spacing:0; margin-top:8px; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.tbl thead th { background:#0b1220; color:#fff; padding:8px 10px; font-size:9.2px; text-transform:uppercase; letter-spacing:.10em; text-align:left; }
.tbl tbody td { padding:8px 10px; border-top:1px solid #eef2f7; font-size:10px; line-height:1.25; }
.num { text-align:right; font-variant-numeric: tabular-nums; }

/* Sekat kolom POTONGAN */
.tbl thead th:nth-child(3),
.tbl tbody td:nth-child(3) { border-left: 2px solid #cbd5e1; }

/* Section rows */
.tr-section td {
  background:#f1f5f9;
  font-weight: 900;
  text-transform: uppercase;
  letter-spacing: .10em;
  font-size: 9px;
  color:#334155;
  border-top: 1px solid #e2e8f0;
}

/* Informative hint under component */
.subtxt { display:block; margin-top:2px; font-size:8.8px; color:#64748b; font-weight:700; letter-spacing:.02em; }

/* Strong totals */
.tr-strong td { font-weight: 900; background:#fafafa; }
.tr-pay td { font-weight: 900; background:#eef2ff; }

/* Signature compact (still 1 page) */
.signGrid { width:100%; border-collapse:separate; border-spacing:8px 8px; margin-top:8px; }
.signBox { border:1px solid #e5e7eb; border-radius:10px; padding:9px 10px; background:#fff; position:relative; }
.signTitle { font-size: 9px; font-weight:900; text-transform: uppercase; letter-spacing:.10em; color:#334155; margin:0 0 6px 0; }
.signName { font-size: 10px; font-weight:900; margin:0 0 6px 0; }
.signLine { height: 56px; border:1px dashed #cbd5e1; border-radius:10px; background:#f8fafc; }
.stamp { position:absolute; right:10px; bottom:10px; width:78px; height:78px; opacity:.9; }
.stamp img { width:78px; height:78px; object-fit:contain; }
.note { margin-top: 6px; font-size: 9px; color:#475569; line-height:1.35; }
.ket { margin-top: 8px; font-size: 9.6px; color:#334155; }
</style>
</head>
<body>

<div class="header">
  <table class="hrow">
    <tr>
      <td class="logoBox">
        '.($logoDataUri ? '<img src="'.$logoDataUri.'" alt="Logo">' : '<div style="font-size:10px;opacity:.9">logo.png tidak ditemukan</div>').'
      </td>
      <td>
        <div class="coName">'.e($companyName).'</div>
        <div class="coLine">'.e($companyLine).'</div>
        <div class="coAddr">'.e($companyAddr).'<br>Email: '.e($companyEmail).'</div>
      </td>
      <td class="meta">
        <div class="badge">'.e($kode_slip).'</div>
        <div class="metaSmall">Tanggal Bayar: <b>'.e(fmtTanggal($tanggal_bayar)).'</b></div>
      </td>
    </tr>
  </table>
</div>

<div class="section">
  <table class="grid">
    <tr>
      <td class="card" style="width:58%">
        <div class="cardTitle">Informasi Karyawan</div>
        <table class="row">
          <tr><td class="lb">Nama</td><td class="vl">'.e($nama_karyawan).'</td></tr>
          <tr><td class="lb">NIK</td><td class="vl">'.e($nik_karyawan).'</td></tr>
          <tr><td class="lb">Divisi / Jabatan</td><td class="vl">'.e($divisi).' • '.e($jabatan).'</td></tr>
          <tr><td class="lb">No. Telp</td><td class="vl">'.e($no_telp).'</td></tr>
        </table>
      </td>
      <td class="card" style="width:42%">
        <div class="cardTitle">Rekening Tujuan Transfer</div>
        <table class="row">
          <tr><td class="lb">Bank</td><td class="vl">'.e($nama_bank ?: '-').'</td></tr>
          <tr><td class="lb">No. Rekening</td><td class="vl mono">'.e($no_rekening ?: '-').'</td></tr>
          <tr><td class="lb">Atas Nama</td><td class="vl">'.e($nama_rekening ?: $nama_karyawan).'</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <div class="summary">
    <table class="sumWrap">
      <tr>
        <td style="width:58%">
          <div class="sumLabel">Ringkasan Pembayaran</div>
          <div class="sumMini">
            Pendapatan (Gaji Dasar + Bonus): <b>'.e(rupiah($pendapatan_total)).'</b><br>
            Potongan (Kasbon + Lainnya): <b>'.e(rupiah($potongan_total)).'</b><br>
            <span class="small">Dokumen ini merupakan slip gaji resmi dan berlaku sebagai bukti pembayaran.</span>
          </div>
        </td>
        <td style="width:42%; text-align:right;">
          <div class="sumLabel">Total Dibayarkan</div>
          <div class="sumValue">'.e(rupiah($total_dibayar)).'</div>
        </td>
      </tr>
    </table>
  </div>

  <table class="tbl">
    <thead>
      <tr>
        <th>Komponen</th>
        <th class="num">Pendapatan</th>
        <th class="num">Potongan</th>
      </tr>
    </thead>
    <tbody>
      <tr class="tr-section"><td colspan="3">PENDAPATAN (Diterima Karyawan)</td></tr>

      <tr>
        <td>Gaji Pokok <span class="subtxt">Gaji dasar sesuai jabatan/kontrak</span></td>
        <td class="num">'.e(rupiah($gp)).'</td>
        <td class="num">'.e(rupiah(0)).'</td>
      </tr>
      <tr>
        <td>Tunjangan Jabatan <span class="subtxt">Tunjangan sesuai posisi / tanggung jawab</span></td>
        <td class="num">'.e(rupiah($tj)).'</td>
        <td class="num">'.e(rupiah(0)).'</td>
      </tr>
      <tr>
        <td>Tunjangan Operasional <span class="subtxt">Dukungan operasional pekerjaan (transport/dll)</span></td>
        <td class="num">'.e(rupiah($to)).'</td>
        <td class="num">'.e(rupiah(0)).'</td>
      </tr>

      <tr class="tr-strong">
        <td>Subtotal Gaji Dasar</td>
        <td class="num">'.e(rupiah($gaji_dasar_tampil)).'</td>
        <td class="num">'.e(rupiah(0)).'</td>
      </tr>

      <tr>
        <td>Bonus / Insentif <span class="subtxt">Bonus kinerja / target / insentif</span></td>
        <td class="num">'.e(rupiah($bonus)).'</td>
        <td class="num">'.e(rupiah(0)).'</td>
      </tr>

      <tr class="tr-section"><td colspan="3">POTONGAN (Mengurangi Total)</td></tr>

      <tr>
        <td>Potongan Kasbon <span class="subtxt">Pengembalian pinjaman / kasbon periode berjalan</span></td>
        <td class="num">'.e(rupiah(0)).'</td>
        <td class="num">'.e(rupiah($pot_kasbon)).'</td>
      </tr>

      <tr>
        <td>Potongan Lainnya <span class="subtxt">Administrasi / koreksi / potongan lain (jika ada)</span></td>
        <td class="num">'.e(rupiah(0)).'</td>
        <td class="num">'.e(rupiah($pot_lainnya)).'</td>
      </tr>

      <tr class="tr-strong">
        <td>TOTAL</td>
        <td class="num">'.e(rupiah($pendapatan_total)).'</td>
        <td class="num">'.e(rupiah($potongan_total)).'</td>
      </tr>

      <tr class="tr-pay">
        <td>DIBAYARKAN</td>
        <td class="num">'.e(rupiah($total_dibayar)).'</td>
        <td class="num">'.e(rupiah(0)).'</td>
      </tr>
    </tbody>
  </table>

  <table class="signGrid">
    <tr>
      <td class="signBox" style="width:50%">
        <div class="signTitle">Disiapkan Oleh (Finance)</div>
        <div class="signName">'.e($companyName).'</div>
        <div class="signLine"></div>
        '.($stampDataUri ? '<div class="stamp"><img src="'.$stampDataUri.'" alt="Stempel"></div>' : '').'
        <div class="note">Tanda tangan dan stempel perusahaan.</div>
      </td>
      <td class="signBox" style="width:50%">
        <div class="signTitle">Diterima Oleh (Karyawan)</div>
        <div class="signName">'.e($nama_karyawan).'</div>
        <div class="signLine"></div>
        <div class="note">Dengan ini menyatakan menerima pembayaran sesuai slip gaji.</div>
      </td>
    </tr>
  </table>

  <div class="ket"><b>Keterangan:</b> '.e($keterangan ?: '-').'</div>
</div>

</body>
</html>
';

// =====================
// 11) RENDER PDF
// =====================
$options = new \Dompdf\Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new \Dompdf\Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$pdf = $dompdf->output();

if (ob_get_length()) { @ob_end_clean(); }

$filename = "Slip-Gaji-" . preg_replace('/[^A-Za-z0-9\-]/', '-', (string)$nama_karyawan) . "-" . $kode_slip . ".pdf";
header("Content-Type: application/pdf");
header('Content-Disposition: inline; filename="'.$filename.'"');
echo $pdf;
exit;
