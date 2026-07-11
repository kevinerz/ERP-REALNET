<?php
declare(strict_types=1);
/**
 * export_cuti_pdf.php - PHP 7.3 Compatible
 * Export Laporan Cuti ke PDF menggunakan TCPDF
 */

require_once __DIR__ . "/config/db.php";

// ── Cek TCPDF ──────────────────────────────────────────────────────
// Sesuaikan path ini dengan struktur folder Anda
$tcpdfAutoload = __DIR__ . "/TCPDF-main/tcpdf.php"; 

if (!file_exists($tcpdfAutoload)) {
    // Coba path vendor jika menggunakan composer
    $tcpdfAutoload = __DIR__ . "/vendor/tecnickcom/tcpdf/tcpdf.php";
}

if (!file_exists($tcpdfAutoload)) {
    die("TCPDF tidak ditemukan di: " . $tcpdfAutoload);
}
require_once $tcpdfAutoload;

// ── Ambil Data ─────────────────────────────────────────────────────
$rows = $pdo->query("
    SELECT c.id_cuti, c.jenis_cuti, c.tanggal_mulai, c.tanggal_selesai,
           c.jumlah_hari, c.alasan, c.status, c.catatan_atasan,
           k.nama, k.nik, k.divisi, k.jabatan
    FROM cuti c
    JOIN karyawan k ON k.id = c.id_karyawan
    ORDER BY c.id_cuti DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ── Helper (PHP 7.3 Compatible) ────────────────────────────────────
function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function statusColor($s) {
    // Ganti match() menjadi switch karena PHP 7.3 belum mendukung match()
    switch ($s) {
        case 'Disetujui': return '#1B8A5A';
        case 'Ditolak':   return '#C0392B';
        default:          return '#E65100';
    }
}

function formatTgl($d) {
    if (!$d || $d === '') return '-';
    try {
        $dt = new DateTime($d);
        $bulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
        return $dt->format('d') . ' ' . $bulan[(int)$dt->format('m')] . ' ' . $dt->format('Y');
    } catch(Exception $e) { return $d; }
}

// ── Buat PDF ────────────────────────────────────────────────────────
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8');
$pdf->SetCreator('PT Real Data Solusindo');
$pdf->SetAuthor('PT Real Data Solusindo');
$pdf->SetTitle('Laporan Data Cuti Karyawan');
$pdf->SetSubject('Data Cuti');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 35);
$pdf->AddPage();

$pw = $pdf->getPageWidth();   // 297mm landscape
$ph = $pdf->getPageHeight();  // 210mm

// ─── HEADER ────────────────────────────────────────────────────────
$pdf->SetFillColor(13, 71, 161);
$pdf->Rect(0, 0, $pw, 22, 'F');

$pdf->SetFillColor(255, 111, 0);
$pdf->Rect(0, 22, $pw, 1.5, 'F');

$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetXY(15, 5);
$pdf->Cell(0, 6, 'PT REAL DATA SOLUSINDO', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 8);
$pdf->SetX(15);
$pdf->Cell(0, 5, 'Laporan Data Cuti Karyawan', 0, 0, 'L');

$today = date('d F Y');
$pdf->SetXY(15, 8);
$pdf->Cell($pw - 30, 5, 'Dicetak: ' . $today, 0, 0, 'R');

$pdf->SetTextColor(13, 27, 62);
$pdf->SetY(29);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 7, 'LAPORAN DATA CUTI KARYAWAN', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 8.5);
$pdf->SetTextColor(90, 106, 146);
$bulanIndo = ['','Januari','Februari','Maret','April','Mei','Juni',
               'Juli','Agustus','September','Oktober','November','Desember'];
$pdf->Cell(0, 5, 'Periode: ' . $bulanIndo[(int)date('m')] . ' ' . date('Y'), 0, 1, 'C');
$pdf->SetTextColor(13, 27, 62);
$pdf->Ln(2);

// ─── TABEL ─────────────────────────────────────────────────────────
$colW = [10, 58, 25, 22, 36, 36, 12, 22]; 
$headers = ['No', 'Nama / NIK', 'Divisi', 'Jabatan', 'Tgl Mulai', 'Tgl Selesai', 'Hari', 'Status'];

$pdf->SetFillColor(13, 71, 161);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('helvetica', 'B', 8);

foreach ($headers as $i => $h) {
    $align = ($i === 0 || $i === 6) ? 'C' : 'L';
    $pdf->Cell($colW[$i], 7, $h, 0, 0, $align, true);
}
$pdf->Ln();

$pdf->SetFont('helvetica', '', 8);
$rowNum = 0;
foreach ($rows as $r) {
    $rowNum++;
    $isEven = ($rowNum % 2 === 0);
    $rowH = 7;

    $pdf->SetFillColor($isEven ? 240 : 255, $isEven ? 244 : 255, $isEven ? 255 : 255);
    $fill = true;
    $pdf->SetTextColor(13, 27, 62);

    $pdf->Cell($colW[0], $rowH, (string)$rowNum, 0, 0, 'C', $fill);

    // Nama / NIK (Manual Multi-line)
    $xNama = $pdf->GetX();
    $yNama = $pdf->GetY();
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell($colW[1], 4, mb_strimwidth((string)$r['nama'], 0, 28, '...'), 0, 0, 'L', $fill);
    $pdf->SetXY($xNama, $yNama + 4);
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->SetTextColor(90, 106, 146);
    $pdf->Cell($colW[1], 3, 'NIK: ' . ($r['nik'] ?? '-'), 0, 0, 'L', $fill);
    $pdf->SetXY($xNama + $colW[1], $yNama);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(13, 27, 62);
    $pdf->Cell($colW[2], $rowH, mb_strimwidth((string)$r['divisi'], 0, 16, '..'), 0, 0, 'L', $fill);
    $pdf->Cell($colW[3], $rowH, mb_strimwidth((string)$r['jabatan'], 0, 14, '..'), 0, 0, 'L', $fill);
    $pdf->Cell($colW[4], $rowH, formatTgl((string)$r['tanggal_mulai']), 0, 0, 'L', $fill);
    $pdf->Cell($colW[5], $rowH, formatTgl((string)$r['tanggal_selesai']), 0, 0, 'L', $fill);
    $pdf->Cell($colW[6], $rowH, (string)$r['jumlah_hari'], 0, 0, 'C', $fill);

    $sc = statusColor((string)$r['status']);
    list($sr, $sg, $sb) = sscanf($sc, '#%02x%02x%02x');
    $pdf->SetTextColor($sr, $sg, $sb);
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->Cell($colW[7], $rowH, (string)$r['status'], 0, 1, 'C', $fill);

    $pdf->SetTextColor(13, 27, 62);
    $pdf->SetFont('helvetica', '', 8);
}

// ─── RINGKASAN ─────────────────────────────────────────────────────
$pdf->Ln(4);
$total = count($rows);

// Ganti fn() => ... menjadi function() use()
$diajukan = count(array_filter($rows, function($r) { return $r['status'] === 'Diajukan'; }));
$disetujui = count(array_filter($rows, function($r) { return $r['status'] === 'Disetujui'; }));
$ditolak = count(array_filter($rows, function($r) { return $r['status'] === 'Ditolak'; }));

$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(30, 5, 'Ringkasan:', 0, 0, 'L');
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell(0, 5, "Total: {$total} | Diajukan: {$diajukan} | Disetujui: {$disetujui} | Ditolak: {$ditolak}", 0, 1, 'L');

// ─── TANDA TANGAN ──────────────────────────────────────────────────
$curY = $pdf->GetY();
if ($curY + 50 > $ph - 20) { $pdf->AddPage(); }
$pdf->Ln(6);

$pdf->SetFont('helvetica', '', 8.5);
$pdf->SetTextColor(90, 106, 146);
$pdf->Cell(0, 5, 'Jakarta, ' . date('d F Y'), 0, 1, 'R');
$pdf->Ln(1);

$sigY = $pdf->GetY();
$boxW = 60;
$rightX = $pw - 15 - $boxW;

// Kiri: HRD
$pdf->SetTextColor(13, 27, 62);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->SetXY(15, $sigY);
$pdf->Cell($boxW, 5, 'Mengetahui,', 0, 1, 'C');
$pdf->SetX(15);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($boxW, 5, 'Manajer HRD', 0, 1, 'C');
$pdf->SetXY(15, $sigY + 25);
$pdf->Cell($boxW, 5, '( _____________________ )', 0, 1, 'C');

// Kanan: Direktur
$pdf->SetXY($rightX, $sigY);
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->Cell($boxW, 5, 'Menyetujui,', 0, 1, 'C');
$pdf->SetX($rightX);
$pdf->SetFont('helvetica', '', 8);
$pdf->Cell($boxW, 5, 'Direktur', 0, 1, 'C');
$pdf->SetXY($rightX, $sigY + 25);
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetTextColor(13, 71, 161);
$pdf->Cell($boxW, 5, '( Kudhori )', 0, 1, 'C');

// ─── FOOTER OTOMATIS ───────────────────────────────────────────────
$pageCount = $pdf->getNumPages();
for ($p = 1; $p <= $pageCount; $p++) {
    $pdf->setPage($p);
    $pdf->SetFillColor(240, 244, 255);
    $pdf->Rect(0, $ph - 13, $pw, 13, 'F');
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetTextColor(90, 106, 146);
    $pdf->SetXY(15, $ph - 10);
    $pdf->Cell(0, 5, 'PT Real Data Solusindo | Dokumen Otomatis Sistem', 0, 0, 'L');
    $pdf->Cell($pw - 30, 5, 'Hal. ' . $p . ' / ' . $pageCount, 0, 0, 'R');
}

$filename = 'laporan_cuti_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');