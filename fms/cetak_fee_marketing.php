<?php
// Memuat semua koneksi database dari config
require_once 'config/database.php';
// Memuat autoloader dari Composer untuk library Dompdf
require_once 'dompdf/autoload.inc.php';

// Menggunakan namespace Dompdf
use Dompdf\Dompdf;
use Dompdf\Options;

// 1. Ambil ID dari URL dan validasi
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    die("Error: ID Pemasangan tidak valid.");
}

// 2. Ambil data pemasangan dari database 'pemasangan' (via $conn_pasang)
$stmt_pemasangan = $conn_pasang->prepare("SELECT * FROM pelanggan_instalasi WHERE id = ?");
$stmt_pemasangan->bind_param("i", $id);
$stmt_pemasangan->execute();
$result_pemasangan = $stmt_pemasangan->get_result();
$data = $result_pemasangan->fetch_assoc();

if (!$data) {
    die("Data Pemasangan dengan ID $id tidak ditemukan.");
}

// 3. Ambil nama paket dari database 'umumdata' (via $conn_bbm)
$nama_paket = 'N/A';
if (!empty($data['paket'])) {
    $stmt_paket = $conn_bbm->prepare("SELECT nama_paket FROM jaringan_paket WHERE id_paket = ?");
    $stmt_paket->bind_param("i", $data['paket']);
    $stmt_paket->execute();
    $result_paket = $stmt_paket->get_result();
    if ($result_paket->num_rows > 0) {
        $nama_paket = $result_paket->fetch_assoc()['nama_paket'];
    }
}

// Menutup koneksi database karena data sudah didapat
$conn_pasang->close();
$conn_bbm->close();

// Menentukan besaran fee marketing (contoh: Rp 50.000, sesuaikan jika perlu)
$fee_marketing = 50000;

// 4. Membuat Konten HTML untuk PDF
$html = '
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Fee Marketing - ' . htmlspecialchars($data['nama']) . '</title>
    <style>
        body { font-family: sans-serif; margin: 25px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; }
        .content { margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; }
        .info-table th { width: 30%; background-color: #f2f2f2; }
        .info-table td { width: 70%; }
        .footer { margin-top: 50px; text-align: center; font-size: 12px; color: #777; }
        .total-section { margin-top: 30px; float: right; width: 50%; }
        .total-table th, .total-table td { border-top: 1px solid #ccc; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BUKTI PEMBERIAN FEE MARKETING</h1>
        <p>PT. Real Data Solusindo</p>
    </div>

    <div class="content">
        <h3>Detail Pemasangan</h3>
        <table class="info-table" border="1">
            <tr>
                <th>Nama Pelanggan</th>
                <td>' . htmlspecialchars($data['nama']) . '</td>
            </tr>
            <tr>
                <th>Alamat</th>
                <td>' . htmlspecialchars($data['alamat']) . '</td>
            </tr>
            <tr>
                <th>POP</th>
                <td>' . htmlspecialchars($data['pop']) . '</td>
            </tr>
             <tr>
                <th>Paket</th>
                <td>' . htmlspecialchars($nama_paket) . '</td>
            </tr>
            <tr>
                <th>Telepon</th>
                <td>' . htmlspecialchars($data['telp']) . '</td>
            </tr>
        </table>

        <h3>Detail Fee</h3>
         <table class="info-table" border="1">
            <tr>
                <th>Nama Marketing</th>
                <td>' . htmlspecialchars($data['marketing']) . '</td>
            </tr>
             <tr>
                <th>Tanggal Pemasangan Selesai</th>
                <td>' . date("d F Y", strtotime($data['tanggal'])) . '</td>
            </tr>
        </table>
        
        <div class="total-section">
            <table class="total-table">
                 <tr>
                    <td>FEE DITERIMA:</td>
                    <td style="text-align:right;">Rp ' . number_format($fee_marketing, 0, ',', '.') . '</td>
                </tr>
            </table>
        </div>
    </div>
    
    <div style="clear:both;"></div>

    <div class="footer">
        Dokumen ini dicetak secara otomatis oleh sistem FMS pada tanggal ' . date("d F Y, H:i:s") . '
    </div>
</body>
</html>
';

// 5. Generate PDF menggunakan Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF ke browser (tanpa paksa download)
$dompdf->stream("fee-marketing-" . $data['nama'] . ".pdf", ["Attachment" => false]);
exit();
?>