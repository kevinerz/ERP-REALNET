<?php
require_once 'libraries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

$conn = new mysqli("localhost", "u272457353_kevinsamsung99", "Admionkevin99", "u272457353_umumdata");
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

// Ambil filter periode
$tanggal_awal = $_GET['tanggal_awal'] ?? '';
$tanggal_akhir = $_GET['tanggal_akhir'] ?? '';

$where_conditions = [];
$judul_periode = "Semua Data";

if ($tanggal_awal && $tanggal_akhir) {
    $awal = $conn->real_escape_string($tanggal_awal);
    $akhir = $conn->real_escape_string($tanggal_akhir);
    $where_conditions[] = "tanggal BETWEEN '$awal' AND '$akhir'";
    $judul_periode = "Periode " . date('d-m-Y', strtotime($awal)) . " s/d " . date('d-m-Y', strtotime($akhir));
}

$where = !empty($where_conditions) ? implode(' AND ', $where_conditions) : '1';

$query = "SELECT id, nama_pengaju, tanggal, tujuan, liter, total, catatan 
          FROM reimburse_bbm 
          WHERE $where 
          ORDER BY nama_pengaju ASC, tanggal DESC";
$data = $conn->query($query);

// Simpan data dan hitung total
$rows = [];
$total_keseluruhan = 0;
$total_per_nama = [];

while ($row = $data->fetch_assoc()) {
    $rows[] = $row;
    $total_keseluruhan += (float)$row['total'];
    
    // Hitung total per nama
    $nama = $row['nama_pengaju'];
    if (!isset($total_per_nama[$nama])) {
        $total_per_nama[$nama] = 0;
    }
    $total_per_nama[$nama] += (float)$row['total'];
}

// Buat HTML untuk DomPDF
$html = '
<style>
    body { font-family: Arial; font-size:11px; }
    .table { border-collapse: collapse; width: 100%; margin-bottom: 15px; }
    .table, .table th, .table td { border: 1px solid #000; }
    .table th, .table td { padding: 5px 4px; }
    .table th { background-color: #16a085; color: white; font-weight: bold; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    h2 { color: #16a085; margin-bottom: 5px; }
    .summary-box { 
        border: 2px solid #16a085; 
        padding: 10px; 
        margin-top: 20px; 
        background-color: #f0f9f7;
    }
    .summary-box h3 { 
        color: #16a085; 
        margin: 0 0 10px 0; 
        font-size: 14px; 
    }
    .summary-table {
        width: 100%;
        border-collapse: collapse;
    }
    .summary-table td {
        padding: 4px 8px;
        border-bottom: 1px solid #ddd;
    }
    .summary-table tr:last-child td {
        border-bottom: none;
    }
    .grand-total {
        background-color: #16a085;
        color: white;
        font-weight: bold;
        font-size: 13px;
    }
</style>
<h2 style="text-align:center;">Laporan Reimburse BBM</h2>
<p style="text-align:center; font-size:13px; margin-bottom:15px;">'.$judul_periode.'</p>

<table class="table">
    <thead>
        <tr>
            <th style="width: 4%;">No</th>
            <th style="width: 16%;">Nama</th>
            <th style="width: 10%;">Tanggal</th>
            <th style="width: 18%;">Tujuan</th>
            <th style="width: 8%;">Liter</th>
            <th style="width: 13%;">Total</th>
            <th style="width: 31%;">Catatan</th>
        </tr>
    </thead>
    <tbody>
';

$no = 1;
foreach ($rows as $row) {
    $html .= '<tr>
        <td class="text-center">'.($no++).'</td>
        <td>'.htmlspecialchars($row['nama_pengaju']).'</td>
        <td class="text-center">'.date('d-m-Y', strtotime($row['tanggal'])).'</td>
        <td>'.htmlspecialchars($row['tujuan']).'</td>
        <td class="text-center">'.htmlspecialchars($row['liter']).' L</td>
        <td class="text-right">Rp '.number_format($row['total'], 0, ',', '.').'</td>
        <td>'.nl2br(htmlspecialchars($row['catatan'])).'</td>
    </tr>';
}

if (empty($rows)) {
    $html .= '<tr><td colspan="7" class="text-center">Data kosong.</td></tr>';
}

$html .= '
    </tbody>
    <tfoot>
        <tr class="grand-total">
            <th colspan="5" class="text-center">TOTAL KESELURUHAN</th>
            <th class="text-right">Rp '.number_format($total_keseluruhan, 0, ',', '.').'</th>
            <th></th>
        </tr>
    </tfoot>
</table>
';

// Tambahkan ringkasan per nama
if (!empty($total_per_nama)) {
    $html .= '
    <div class="summary-box">
        <h3>Ringkasan Total Per Nama</h3>
        <table class="summary-table">
    ';
    
    // Urutkan berdasarkan nama
    ksort($total_per_nama);
    
    foreach ($total_per_nama as $nama => $total) {
        $html .= '
            <tr>
                <td style="width: 60%;"><strong>'.htmlspecialchars($nama).'</strong></td>
                <td style="width: 40%; text-align: right;">Rp '.number_format($total, 0, ',', '.').'</td>
            </tr>
        ';
    }
    
    $html .= '
            <tr style="border-top: 2px solid #16a085;">
                <td style="padding-top: 8px;"><strong>TOTAL SEMUA</strong></td>
                <td style="text-align: right; padding-top: 8px;"><strong>Rp '.number_format($total_keseluruhan, 0, ',', '.').'</strong></td>
            </tr>
        </table>
    </div>
    ';
}

$html .= '
<p style="margin-top: 15px; font-size: 10px; color: #666;">
    Total data: '.count($rows).' pengajuan reimburse BBM
</p>
';

// Proses DomPDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

$filename = 'reimburse_bbm';
if ($tanggal_awal && $tanggal_akhir) {
    $filename .= '_'.$tanggal_awal.'_sd_'.$tanggal_akhir;
}
$filename .= '.pdf';

$dompdf->stream($filename, ["Attachment"=>0]);
exit;
?>