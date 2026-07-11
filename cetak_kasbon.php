<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'libraries/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

include 'kasbon/koneksi.php';

// Ambil data kasbon
$query = "SELECT k.*, u.nama, u.divisi FROM kasbon k JOIN karyawan u ON k.id_karyawan = u.id ORDER BY k.tanggal_dibuat DESC";
$result = $conn->query($query);

// Bangun HTML
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        h2 { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px; text-align: left; font-size: 12px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
<h2>Laporan Kasbon</h2>
<table>
    <thead>
        <tr>
            <th>No</th>
            <th>Nama</th>
            <th>Divisi</th>
            <th>Tanggal</th>
            <th>Jumlah</th>
            <th>Keperluan</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>';

$no = 1;
while ($row = $result->fetch_assoc()) {
    $html .= '<tr>
        <td>' . $no++ . '</td>
        <td>' . htmlspecialchars($row['nama']) . '</td>
        <td>' . htmlspecialchars($row['divisi']) . '</td>
        <td>' . $row['tanggal'] . '</td>
        <td>Rp ' . number_format($row['jumlah'], 0, ',', '.') . '</td>
        <td>' . htmlspecialchars($row['keperluan']) . '</td>
        <td>' . strtoupper($row['status']) . '</td>
    </tr>';
}

$html .= '</tbody></table></body></html>';

// Inisialisasi Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Tampilkan PDF langsung
$dompdf->stream("laporan_kasbon.pdf", ["Attachment" => false]); // false = preview di browser
?>
