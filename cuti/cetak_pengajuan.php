<?php
declare(strict_types=1);
require_once __DIR__ . "/config/db.php";

// ── Cek ID ─────────────────────────────────────────────────────────
$id_cuti = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_cuti <= 0) {
    die("ID Pengajuan tidak valid.");
}

// ── Cek TCPDF ──────────────────────────────────────────────────────
$tcpdfPath = __DIR__ . "/TCPDF-main/tcpdf.php";
if (!file_exists($tcpdfPath)) {
    $tcpdfPath = __DIR__ . "/vendor/tecnickcom/tcpdf/tcpdf.php";
}
require_once $tcpdfPath;

// ── Ambil Data Spesifik ────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT c.*, k.nama, k.nik, k.divisi, k.jabatan
    FROM cuti c
    JOIN karyawan k ON k.id = c.id_karyawan
    WHERE c.id_cuti = ?
");
$stmt->execute([$id_cuti]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$data) {
    die("Data pengajuan tidak ditemukan.");
}

// ── Helper ─────────────────────────────────────────────────────────
function formatTglIndo($d) {
    if (!$d) return '-';
    $bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $dt = new DateTime($d);
    return $dt->format('d') . ' ' . $bulan[(int)$dt->format('m')] . ' ' . $dt->format('Y');
}

// ── Buat PDF ────────────────────────────────────────────────────────
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->SetTitle('Surat Pengajuan Cuti - ' . $data['nama']);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();

// ─── KOP SURAT ─────────────────────────────────────────────────────
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 7, 'PT REAL DATA SOLUSINDO', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Perum Puri rajeg blok B3 No 22,Tangerang', 0, 1, 'C');
$pdf->Cell(0, 5, 'Email: info@realdatasolusindo.com | Telp: (021) 1234567', 0, 1, 'C');
$pdf->Ln(2);
$pdf->SetLineWidth(0.5);
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->Ln(5);

// ─── JUDUL SURAT ───────────────────────────────────────────────────
$pdf->SetFont('helvetica', 'BU', 12);
$pdf->Cell(0, 10, 'PERMOHONAN CUTI KARYAWAN', 0, 1, 'C');
$pdf->Ln(5);

// ─── ISI SURAT ─────────────────────────────────────────────────────
$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 7, 'Yang bertanda tangan di bawah ini:', 0, 1, 'L');
$pdf->Ln(2);

// Data Karyawan
$pdf->SetX(30);
$pdf->Cell(40, 7, 'Nama', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->SetFont('helvetica', 'B', 11); $pdf->Cell(0, 7, $data['nama'], 0, 1);
$pdf->SetFont('helvetica', '', 11);
$pdf->SetX(30);
$pdf->Cell(40, 7, 'NIK', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, $data['nik'], 0, 1);
$pdf->SetX(30);
$pdf->Cell(40, 7, 'Jabatan / Divisi', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, $data['jabatan'] . ' / ' . $data['divisi'], 0, 1);

$pdf->Ln(5);
$pdf->MultiCell(0, 7, "Dengan ini mengajukan permohonan izin **" . $data['jenis_cuti'] . "** selama **" . $data['jumlah_hari'] . " hari**, terhitung mulai tanggal:", 0, 'L', false, 1, '', '', true, 0, true);

$pdf->Ln(2);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell(0, 10, formatTglIndo($data['tanggal_mulai']) . " s/d " . formatTglIndo($data['tanggal_selesai']), 0, 1, 'C');
$pdf->SetFont('helvetica', '', 11);

$pdf->Ln(2);
$pdf->Cell(0, 7, "Alasan Cuti:", 0, 1, 'L');
$pdf->SetFont('helvetica', 'I', 10);
$pdf->MultiCell(0, 7, $data['alasan'] ?: '-', 1, 'L');
$pdf->SetFont('helvetica', '', 11);

$pdf->Ln(5);
$pdf->MultiCell(0, 7, "Demikian permohonan ini saya sampaikan. Atas perhatian dan kebijaksanaannya saya ucapkan terima kasih.", 0, 'L');

$pdf->Ln(15);

// ─── TANDA TANGAN ──────────────────────────────────────────────────
$yPos = $pdf->GetY();
$boxW = 60;

// Kiri: Pemohon
$pdf->SetXY(20, $yPos);
$pdf->Cell($boxW, 5, 'Hormat Saya,', 0, 1, 'C');
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 11);
$pdf->Cell($boxW, 5, '( ' . $data['nama'] . ' )', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell($boxW, 5, 'Karyawan', 0, 1, 'C');

// Kanan: Persetujuan Atasan
$pdf->SetXY(130, $yPos);
$pdf->Cell($boxW, 5, 'Menyetujui,', 0, 1, 'C');
$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 11);

// Cek status untuk tampilan TTD atasan
$atasan = ($data['status'] === 'Disetujui') ? 'Bu Kusmariyani' : '....................';
$pdf->SetXY(130, $pdf->GetY());
$pdf->Cell($boxW, 5, '( ' . $atasan . ' )', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 10);
$pdf->SetX(130);
$pdf->Cell($boxW, 5, 'HR & GA / Atasan', 0, 1, 'C');

// Status Stamp (Watermark jika ditolak/disetujui)
$pdf->SetAlpha(0.1);
$pdf->SetFont('helvetica', 'B', 60);
$pdf->StartTransform();
$pdf->Rotate(30, 100, 150);
$pdf->Text(60, 140, strtoupper($data['status']));
$pdf->StopTransform();
$pdf->SetAlpha(1);

// ── Output ──────────────────────────────────────────────────────────
$filename = 'Permohonan_Cuti_' . str_replace(' ', '_', $data['nama']) . '.pdf';
$pdf->Output($filename, 'I'); // I = buka di browser