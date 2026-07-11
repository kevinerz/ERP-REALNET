<?php
require_once 'config/database.php';
require_once 'dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Ambil ID dari URL dan validasi
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    die("Error: ID Pengajuan Aset tidak valid.");
}

// 2. Ambil data pengajuan dari database FMS (via $conn)
$stmt = $conn->prepare("SELECT * FROM keu_pengajuan_pembelian_aset WHERE id = ? AND status_pembayaran = 'Sudah Bayar'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("Data Pembayaran Aset dengan ID $id tidak ditemukan atau belum dibayar.");
}
$conn->close();

// 4. Membuat Konten HTML untuk PDF
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pembayaran Aset - ID ' . $data['id'] . '</title>
    <style>
        body { font-family: sans-serif; margin: 25px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; }
        .info-table th, .info-table td { padding: 8px; text-align: left; border-bottom: 1px solid #eee;}
        .info-table th { width: 30%; background-color: #f2f2f2; }
        .footer { margin-top: 80px; }
        .signature { float: right; width: 250px; text-align: center; }
        .signature .name { margin-top: 60px; border-top: 1px solid #000; padding-top: 5px;}
    </style>
</head>
<body>
    <div class="header">
        <h1>BUKTI PEMBAYARAN ASET</h1>
        <p>No. Ref: ASET-' . str_pad($data['id'], 6, '0', STR_PAD_LEFT) . '</p>
    </div>

    <div class="content">
        <h3>Telah dibayarkan untuk pengadaan aset dengan rincian:</h3>
        <table class="info-table">
            <tr>
                <th>Tanggal Pembayaran</th>
                <td>' . date("d F Y", strtotime($data['tanggal_pembayaran'])) . '</td>
            </tr>
            <tr>
                <th>Diajukan Oleh</th>
                <td>' . htmlspecialchars($data['nama_pengaju']) . ' (' . htmlspecialchars($data['divisi_pengaju']) . ')</td>
            </tr>
             <tr>
                <th>Tanggal Pengajuan</th>
                <td>' . date("d F Y", strtotime($data['tanggal_pengajuan'])) . '</td>
            </tr>
            <tr><td colspan="2">&nbsp;</td></tr>
            <tr>
                <th>Nama Barang</th>
                <td><b>' . htmlspecialchars($data['nama_barang']) . '</b></td>
            </tr>
            <tr>
                <th>Jumlah</th>
                <td>' . htmlspecialchars($data['jumlah']) . ' unit</td>
            </tr>
            <tr>
                <th>Harga Satuan</th>
                <td>Rp ' . number_format($data['harga_satuan'], 0, ',', '.') . '</td>
            </tr>
            <tr style="font-weight: bold; background-color: #f2f2f2; font-size: 1.1em;">
                <th>TOTAL PEMBAYARAN</th>
                <td>Rp ' . number_format($data['total_harga'], 0, ',', '.') . '</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <div class="signature">
            <p>Disetujui dan Dibayarkan oleh,</p>
            <div class="name">( Bagian Keuangan )</div>
        </div>
    </div>
</body>
</html>
';

// 5. Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("bukti-bayar-aset-" . $data['id'] . ".pdf", ["Attachment" => false]);
exit();
?>